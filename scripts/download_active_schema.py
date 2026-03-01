#!/usr/bin/env python3
"""
Download active JSON-LD schemas from a site and store them in SQLite.

Usage:
  python scripts/download_active_schema.py --site https://chromaela.com
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sqlite3
import time
from collections import deque
from dataclasses import dataclass
from datetime import datetime, timezone
from html import unescape
from typing import Iterable, List, Optional, Set, Tuple
from urllib.parse import urlparse, urljoin
from urllib.request import Request, urlopen
from urllib.error import HTTPError, URLError
import xml.etree.ElementTree as ET


SCRIPT_RE = re.compile(
    r"<script[^>]*type=[\"']application/ld\+json[\"'][^>]*>([\s\S]*?)</script>",
    re.IGNORECASE,
)
XML_LOC_RE = re.compile(r"<loc>(.*?)</loc>", re.IGNORECASE | re.DOTALL)


@dataclass
class FetchResult:
    url: str
    status: int
    content_type: str
    text: str
    elapsed_ms: int
    error: Optional[str] = None


def now_utc() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def normalize_site(site: str) -> str:
    parsed = urlparse(site)
    if not parsed.scheme:
        site = "https://" + site
        parsed = urlparse(site)
    if not parsed.netloc:
        raise ValueError(f"Invalid site URL: {site}")
    return f"{parsed.scheme}://{parsed.netloc}"


def fetch_text(url: str, timeout: int = 20) -> FetchResult:
    start = time.time()
    req = Request(
        url,
        headers={
            "User-Agent": "ChromaSchemaDownloader/1.0 (+https://chromaela.com)",
            "Accept": "text/html,application/xml,text/xml;q=0.9,*/*;q=0.8",
        },
    )
    try:
        with urlopen(req, timeout=timeout) as res:
            raw = res.read()
            content_type = res.headers.get("Content-Type", "")
            charset = "utf-8"
            if "charset=" in content_type:
                charset = content_type.split("charset=")[-1].split(";")[0].strip()
            text = raw.decode(charset, errors="replace")
            return FetchResult(
                url=url,
                status=getattr(res, "status", 200),
                content_type=content_type,
                text=text,
                elapsed_ms=int((time.time() - start) * 1000),
            )
    except HTTPError as exc:
        body = ""
        try:
            body = exc.read().decode("utf-8", errors="replace")
        except Exception:
            body = ""
        return FetchResult(
            url=url,
            status=exc.code or 0,
            content_type=exc.headers.get("Content-Type", "") if exc.headers else "",
            text=body,
            elapsed_ms=int((time.time() - start) * 1000),
            error=str(exc),
        )
    except URLError as exc:
        return FetchResult(
            url=url,
            status=0,
            content_type="",
            text="",
            elapsed_ms=int((time.time() - start) * 1000),
            error=str(exc),
        )
    except Exception as exc:
        return FetchResult(
            url=url,
            status=0,
            content_type="",
            text="",
            elapsed_ms=int((time.time() - start) * 1000),
            error=str(exc),
        )


def parse_sitemap_urls(xml_text: str) -> Tuple[List[str], List[str]]:
    urls: List[str] = []
    nested_sitemaps: List[str] = []

    # Try XML parser first.
    try:
        root = ET.fromstring(xml_text)
        ns = ""
        if root.tag.startswith("{") and "}" in root.tag:
            ns = root.tag.split("}")[0] + "}"
        if root.tag.endswith("sitemapindex"):
            for node in root.findall(f".//{ns}sitemap/{ns}loc"):
                if node.text:
                    nested_sitemaps.append(unescape(node.text.strip()))
        elif root.tag.endswith("urlset"):
            for node in root.findall(f".//{ns}url/{ns}loc"):
                if node.text:
                    urls.append(unescape(node.text.strip()))
        return urls, nested_sitemaps
    except ET.ParseError:
        pass

    # Fallback for malformed XML.
    for match in XML_LOC_RE.findall(xml_text):
        loc = unescape(match.strip())
        if loc.lower().endswith(".xml") or "sitemap" in loc.lower():
            nested_sitemaps.append(loc)
        else:
            urls.append(loc)
    return urls, nested_sitemaps


def collect_site_urls(sitemap_url: str, limit: int = 0) -> List[str]:
    visited_sitemaps: Set[str] = set()
    found_urls: Set[str] = set()
    queue = deque([sitemap_url])

    while queue:
        sm_url = queue.popleft()
        if sm_url in visited_sitemaps:
            continue
        visited_sitemaps.add(sm_url)

        result = fetch_text(sm_url)
        if result.status < 200 or result.status >= 300 or not result.text:
            continue

        urls, nested = parse_sitemap_urls(result.text)
        for url in urls:
            if url not in found_urls:
                found_urls.add(url)
                if limit and len(found_urls) >= limit:
                    return sorted(found_urls)
        for nested_url in nested:
            if nested_url not in visited_sitemaps:
                queue.append(nested_url)

    return sorted(found_urls)


def extract_jsonld_blocks(html: str) -> List[str]:
    return [m.strip() for m in SCRIPT_RE.findall(html)]


def flatten_jsonld(value) -> List[dict]:
    nodes: List[dict] = []
    if isinstance(value, dict):
        if isinstance(value.get("@graph"), list):
            for item in value["@graph"]:
                if isinstance(item, dict):
                    nodes.append(item)
        else:
            nodes.append(value)
    elif isinstance(value, list):
        for item in value:
            nodes.extend(flatten_jsonld(item))
    return nodes


def init_db(conn: sqlite3.Connection) -> None:
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            started_at TEXT NOT NULL,
            site_url TEXT NOT NULL,
            sitemap_url TEXT NOT NULL,
            pages_total INTEGER DEFAULT 0,
            pages_ok INTEGER DEFAULT 0,
            pages_with_schema INTEGER DEFAULT 0,
            schema_nodes_total INTEGER DEFAULT 0
        )
        """
    )
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id INTEGER NOT NULL,
            url TEXT NOT NULL,
            status_code INTEGER NOT NULL,
            content_type TEXT,
            fetch_ms INTEGER,
            error TEXT,
            schema_blocks INTEGER DEFAULT 0,
            schema_nodes INTEGER DEFAULT 0,
            created_at TEXT NOT NULL,
            FOREIGN KEY(run_id) REFERENCES runs(id)
        )
        """
    )
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS schema_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            block_index INTEGER NOT NULL,
            parse_ok INTEGER NOT NULL,
            parse_error TEXT,
            raw_jsonld TEXT NOT NULL,
            FOREIGN KEY(page_id) REFERENCES pages(id)
        )
        """
    )
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS schema_nodes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            block_id INTEGER NOT NULL,
            node_index INTEGER NOT NULL,
            schema_type TEXT,
            schema_id TEXT,
            schema_json TEXT NOT NULL,
            FOREIGN KEY(page_id) REFERENCES pages(id),
            FOREIGN KEY(block_id) REFERENCES schema_blocks(id)
        )
        """
    )
    conn.execute("CREATE INDEX IF NOT EXISTS idx_pages_run ON pages(run_id)")
    conn.execute("CREATE INDEX IF NOT EXISTS idx_schema_nodes_type ON schema_nodes(schema_type)")
    conn.commit()


def insert_run(conn: sqlite3.Connection, site_url: str, sitemap_url: str) -> int:
    cur = conn.execute(
        "INSERT INTO runs(started_at, site_url, sitemap_url) VALUES (?, ?, ?)",
        (now_utc(), site_url, sitemap_url),
    )
    conn.commit()
    return int(cur.lastrowid)


def update_run_stats(
    conn: sqlite3.Connection,
    run_id: int,
    pages_total: int,
    pages_ok: int,
    pages_with_schema: int,
    schema_nodes_total: int,
) -> None:
    conn.execute(
        """
        UPDATE runs
        SET pages_total = ?, pages_ok = ?, pages_with_schema = ?, schema_nodes_total = ?
        WHERE id = ?
        """,
        (pages_total, pages_ok, pages_with_schema, schema_nodes_total, run_id),
    )
    conn.commit()


def process_page(
    conn: sqlite3.Connection,
    run_id: int,
    url: str,
    timeout: int,
    retries: int,
) -> Tuple[int, int, bool]:
    result = fetch_text(url, timeout=timeout)
    attempt = 0
    while attempt < retries and result.status in (429, 503, 520, 522, 524):
        attempt += 1
        time.sleep(min(6, 1.5 * attempt))
        result = fetch_text(url, timeout=timeout)
    blocks = 0
    nodes_total = 0
    has_schema = False

    page_cur = conn.execute(
        """
        INSERT INTO pages(run_id, url, status_code, content_type, fetch_ms, error, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        """,
        (
            run_id,
            url,
            result.status,
            result.content_type,
            result.elapsed_ms,
            result.error,
            now_utc(),
        ),
    )
    page_id = int(page_cur.lastrowid)

    if result.status >= 200 and result.status < 300 and "text/html" in result.content_type.lower():
        jsonld_blocks = extract_jsonld_blocks(result.text)
        blocks = len(jsonld_blocks)

        for i, raw_block in enumerate(jsonld_blocks):
            parse_ok = 1
            parse_error = None
            parsed_value = None
            try:
                parsed_value = json.loads(raw_block)
            except json.JSONDecodeError as exc:
                parse_ok = 0
                parse_error = str(exc)

            block_cur = conn.execute(
                """
                INSERT INTO schema_blocks(page_id, block_index, parse_ok, parse_error, raw_jsonld)
                VALUES (?, ?, ?, ?, ?)
                """,
                (page_id, i, parse_ok, parse_error, raw_block),
            )
            block_id = int(block_cur.lastrowid)

            if parse_ok and parsed_value is not None:
                nodes = flatten_jsonld(parsed_value)
                for idx, node in enumerate(nodes):
                    schema_type = node.get("@type")
                    if isinstance(schema_type, list):
                        schema_type = ",".join(str(x) for x in schema_type)
                    schema_id = node.get("@id")
                    conn.execute(
                        """
                        INSERT INTO schema_nodes(page_id, block_id, node_index, schema_type, schema_id, schema_json)
                        VALUES (?, ?, ?, ?, ?, ?)
                        """,
                        (
                            page_id,
                            block_id,
                            idx,
                            str(schema_type) if schema_type is not None else None,
                            str(schema_id) if schema_id is not None else None,
                            json.dumps(node, ensure_ascii=False),
                        ),
                    )
                nodes_total += len(nodes)

        has_schema = nodes_total > 0

    conn.execute(
        "UPDATE pages SET schema_blocks = ?, schema_nodes = ? WHERE id = ?",
        (blocks, nodes_total, page_id),
    )
    conn.commit()

    return result.status, nodes_total, has_schema


def main() -> int:
    parser = argparse.ArgumentParser(description="Download active JSON-LD schema into SQLite.")
    parser.add_argument("--site", default="https://chromaela.com", help="Base site URL")
    parser.add_argument("--sitemap", default="", help="Sitemap URL (defaults to <site>/sitemap.xml)")
    parser.add_argument("--db", default="", help="SQLite output path")
    parser.add_argument("--limit", type=int, default=0, help="Max URLs to process (0 = all)")
    parser.add_argument("--timeout", type=int, default=20, help="Fetch timeout seconds")
    parser.add_argument("--delay-ms", type=int, default=250, help="Delay between page fetches")
    parser.add_argument("--retries", type=int, default=3, help="Retries for 429/5xx transient errors")
    args = parser.parse_args()

    site_url = normalize_site(args.site)
    sitemap_url = args.sitemap.strip() or urljoin(site_url + "/", "sitemap.xml")
    db_path = args.db.strip() or f"reports/active_schema_{datetime.now().strftime('%Y%m%d_%H%M%S')}.sqlite"
    db_dir = os.path.dirname(db_path)
    if db_dir:
        os.makedirs(db_dir, exist_ok=True)

    conn = sqlite3.connect(db_path)
    init_db(conn)
    run_id = insert_run(conn, site_url, sitemap_url)

    urls = collect_site_urls(sitemap_url, limit=args.limit)
    print(f"[schema-download] run_id={run_id}")
    print(f"[schema-download] sitemap={sitemap_url}")
    print(f"[schema-download] urls={len(urls)}")

    pages_ok = 0
    pages_with_schema = 0
    schema_nodes_total = 0

    for idx, url in enumerate(urls, start=1):
        status, nodes, has_schema = process_page(conn, run_id, url, args.timeout, args.retries)
        if 200 <= status < 300:
            pages_ok += 1
        if has_schema:
            pages_with_schema += 1
        schema_nodes_total += nodes
        if idx % 25 == 0 or idx == len(urls):
            print(
                f"[schema-download] processed={idx}/{len(urls)} "
                f"ok={pages_ok} with_schema={pages_with_schema} nodes={schema_nodes_total}"
            )
        if args.delay_ms > 0:
            time.sleep(args.delay_ms / 1000.0)

    update_run_stats(
        conn,
        run_id,
        pages_total=len(urls),
        pages_ok=pages_ok,
        pages_with_schema=pages_with_schema,
        schema_nodes_total=schema_nodes_total,
    )

    print(f"[schema-download] done db={db_path}")
    conn.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

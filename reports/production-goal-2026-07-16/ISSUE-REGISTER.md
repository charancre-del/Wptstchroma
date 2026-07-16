# Chroma Excellence 2.0 Staging Issue Register

> **Superseded:** Open statuses below are historical. Current code-level P0/P1 status is recorded in `STAGING-FINAL-READINESS-2026-07-16.md`.

**Audit date:** 2026-07-16
**Branch:** `codex/theme-redesign`
**Environment:** Staging only
**Production rule:** Never touch live under this audit. Production status is not inferred from staging.

## Status Definitions

- **Validated on staging:** The stated behavior has current staging evidence. The status applies only to the tested aspect.
- **Open - current staging:** Reproduced against the current staging host or current inventory artifacts.
- **Open - retest required:** A prior audit finding remains a release gate, but current post-edit staging proof is absent.
- **Not remeasured:** A prior numeric crawl result is retained as historical context only.
- **Management approval required:** The public claim lacks an authoritative approval record.
- **Positive control:** Current evidence supports parity or expected behavior, but is not a broad sign-off.

## Prioritized Register

| ID | Priority | Area | Current finding | Status | Required staging evidence |
| --- | --- | --- | --- | --- | --- |
| SEO-001 | P0 -> monitor | Near-me availability | Prior audit reported 530 failures. Current artifact reports `TOTAL=530 FAILED=0`; four English/Spanish samples returned 200 with one H1 and self-canonical. | Validated on staging for HTTP availability | Preserve the full 530-result log with release candidate; inspect representative content and logs before promotion |
| SEO-002 | P1 -> monitor | Sitemap duplication | Prior audit reported about 4,000 entries and 2,364 unique URLs. Current staging file contains exactly 2,364 total and 2,364 unique URLs. | Validated on staging inventory | Repeat export after final build and confirm 1,182 English / 1,182 Spanish with zero duplicate lines |
| SEO-003 | P1 | Canonical consistency | All 14 sampled core routes emitted canonicals, but targets split 7 staging / 7 live; `/blog/` canonicalized to live `/stories/`. | Open - current staging | Define intended staging and production behavior; crawl every sitemap URL and verify canonical path, host, and language |
| SEO-004 | P1 | Meta descriptions | Historical audit reported 729 missing and 209 overlong descriptions. Current inventory files do not contain description fields. | Not remeasured | Full current crawl with description presence, length, uniqueness, and intent |
| SEO-005 | P1 | Titles | Historical audit reported 320 titles over 60 characters. Current URL inventory does not remeasure titles. | Not remeasured | Full current crawl with title length, uniqueness, and template grouping |
| SEO-006 | P1 | Thin/generated content | The current sitemap still contains 2,364 URLs, including 530 near-me URLs. HTTP 200 does not resolve doorway/thin-content risk; the prior 586/1,674 counts were not rederived. | Open - current classification | Approve keep/improve/noindex/redirect by family using content uniqueness, accuracy, traffic, and backlinks |
| SEO-007 | P2 | Heading structure | Nineteen sampled pages had one H1, but the historical multi-H1/template fallback issue was not fully recrawled. | Open - retest required | Crawl all template families and inspect article-content fallback cases |
| SEO-008 | P2 | GEO/location parity | Live and staging exports each contain 24 location records. The GEO feed was not recaptured, so the prior reported feed count of 19 is unresolved. | Open - retest required | Capture feed, map, archive, sitemap, and schema counts from one approved active-campus source |
| SEO-009 | P2 | Staging index controls | `robots.txt` disallows `/`, while sampled pages lack an explicit noindex directive and emit mixed-host canonicals. | Open - current staging configuration | Document intended staging protection and explicit cutover steps for robots, noindex, canonicals, and sitemap host |
| PERF-001 | P1 | Rate limiting | Prior audit encountered Cloudflare 1015/429. Low-rate current checks did not reproduce a page-level 429. | Open - retest required | Controlled crawl/load test with response codes, rule IDs, request rate, cache state, and rollback-safe proposal |
| PERF-002 | P2 | MetaSync cache signal | A current staging home response included `x-metasync-otto-cache: RATE_LIMITED`, while the page itself returned 200. | Open - current staging observation | Identify the upstream call, user impact, retry/cache behavior, and whether it affects render or crawl output |
| FACT-001 | P1 | Campus count | Current staging shows 24+ on Home, 19+ on About and Employers, while both inventories contain 24 location records. | Management approval required | Active-campus roster, effective date, and approved public wording |
| FACT-002 | P1 | Founding story | Current About page contains `Established 2022` and `over the last decade`. | Management approval required | Approved origin timeline and exact replacement wording |
| FACT-003 | P1 | Parent platform | Current staging exposes Brightwheel, Procare, and LineLeader references. | Management approval required | Platform by campus, transition status, and neutral fallback |
| FACT-004 | P1 | Operating hours | Current staging exposes 6:00 and 6:30 closing language; campus schedules may vary. | Management approval required | Campus-level hours source and approved company-wide qualifier |
| FACT-005 | P1 | CAPS / GA Pre-K | Current Home includes participating-campus CAPS language, while broader claims remain in the prior issue list. | Management approval required | Participating campuses, eligibility, authorization, and availability language |
| FACT-006 | P1 | Accreditation | Current Home displays NAEYC and GAC claims and "accredited excellence" positioning. | Management approval required | Proof and scope by entity, campus, or program; otherwise remove/neutralize on staging |
| FACT-007 | P1 | Early Start services | Current Early Start page presents Speech, Occupational Therapy, and ABA as division services; campus/eligibility/provider scope is not established by the inventory. | Management approval required | Current service, provider, campus, eligibility, and enrollment source |
| FACT-008 | P2 | Ownership language | Current About page states locally owned; approved ownership/legal wording is not in the evidence set. | Management approval required | Legal/brand approval for exact ownership wording |
| FACT-009 | P2 | Ratings and scale | Current Home states 4.8 average parent rating and 4,000+ families served. | Management approval required | Source, calculation, count, date, and allowed attribution |
| CONV-001 | P1 | Contact form | Current Contact page says "Send us a note" but embeds parent-information form ID `848tl2LjoZVsUIhhNOxd`. | Open - current staging | Correct form purpose, labels, routing, confirmation, and lead-delivery readback |
| CONV-002 | P1 | Programs mobile | Prior audit placed the first tour CTA too low. Current cross-device position was not revalidated. | Open - retest required | Mobile/tablet screenshot and interaction matrix |
| CONV-003 | P1 | Schedule Tour UX | Prior audit found excessive campus/form scrolling. No current submission or cross-device flow was run. | Open - retest required | Keyboard and touch flow through campus selection, consent, submit, success, and delivery |
| CONV-004 | P2 | Locations UX | Prior audit found map-first/tablet card constraints. Current map/card behavior was not revalidated. | Open - retest required | Tablet/mobile map, filters, permission, cards, markers, fly-to, popup, and fallback tests |
| CONV-005 | P2 -> monitor | Legacy tour route | Staging `/schedule-tour/` is a single 301 to `/schedule-a-tour/`; the sampled Summer page HTML did not contain the old href. | Validated on staging for route behavior | Confirm intended CTA appears and works visually; include route in final redirect regression set |
| A11Y-001 | P1 | Mobile navigation | Prior focus trap, inert background, and focus restoration finding lacks current browser proof. | Open - retest required | Keyboard and screen-reader test at supported widths |
| A11Y-002 | P1 | Reviews carousel | Prior pause, reduced-motion, and inactive-slide-state finding lacks current proof. | Open - retest required | Pause control, motion preference, focus order, and accessibility-tree evidence |
| A11Y-003 | P1 | Location gallery | Prior autoplay and inactive-slide-state finding lacks current proof. | Open - retest required | Same carousel evidence on location singles |
| A11Y-004 | P2 | Communities | Prior search-label and heading hierarchy finding was not revalidated. | Open - retest required | Label/name and heading-outline check on archive and samples |
| A11Y-005 | P2 | Parents carousel | Prior generic alternatives and inactive-slide exposure were not revalidated. | Open - retest required | Image-alt source and accessibility-tree evidence |
| QA-001 | P2 | Inventory parity | All 403 exported published-record metadata rows match between live and staging. | Positive control | Repeat immediately before any separately approved release |
| QA-002 | P1 | Full staging matrix | Current evidence is inventory-heavy; no complete post-edit desktop/tablet/mobile form, map, accessibility, console, and resource matrix exists. | Open - current validation gap | Complete signed matrix with timestamps, build identifier, screenshots, and failures |

## Current Order

1. Fix and submit-test the Contact Us journey on staging.
2. Resolve or neutralize management-gated facts.
3. Normalize canonical behavior and run the current full metadata crawl.
4. Revalidate accessibility, responsive conversion, maps, and carousels.
5. Classify generated page families; do not bulk redirect solely because they were generated.
6. Capture controlled availability, Lighthouse/Core Web Vitals, analytics, and lead-delivery evidence.
7. Repeat inventory and route checks against the final release candidate.

## Guardrail

No row authorizes production work. "Validated on staging" does not mean fixed in source, deployed to production, or approved for release.

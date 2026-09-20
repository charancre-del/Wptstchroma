# QA media-security remediation status

This document records the boundary of the source-level hardening on branch
`codex/qa-141-security-hardening`. It is not a production-verification report
and does not assert that historical report media is private.

## Implemented in source

- Generated PDF/HTML exports and imported DOCX files use collision-resistant,
  mode-restricted temporary files outside WordPress uploads and known web roots.
- REST export responses use private/no-store/nosniff headers and synchronously
  delete the generated file after streaming, including exceptional paths.
- Approval-mail attachments are accepted only from the managed private temp
  directory and are deleted after synchronous `wp_mail` consumption.
- Daily cleanup targets abandoned private files and safely retains non-recursive
  cleanup of both historical upload-temp directory names.
- The service worker caches only an exact list of static plugin assets. API,
  HTML, report, photo, and other responses are network-only, and activation
  removes earlier QA Cache Storage entries.

`CQA_PRIVATE_TEMP_DIR` may specify the private directory. It must be an absolute,
writable, non-symlink path outside `ABSPATH`, `WP_CONTENT_DIR`, the document
root, and WordPress uploads. When it is unset, the plugin creates
`chroma-qa-reports-private` below the operating-system temporary directory and
fails closed if that location resolves inside a web root.

## Residual findings and required evidence

| Area | Status | Required before confidentiality can be called verified |
|---|---|---|
| Newly generated export/DOCX temporary files | PARTIAL | Deploy to staging; verify the resolved directory and permissions as the WordPress runtime user; exercise successful and failed stream/parser/mail paths; verify no file remains and direct HTTP requests cannot reach the directory. |
| Active report-photo storage in WordPress media | PARTIAL | Design and implement private storage plus an authenticated photo-by-ID delivery endpoint using the existing report access policy. Add a server-side byte resolver so PDF and AI consumers do not depend on public URLs. Do not move active photos until compatibility and rollback tests pass. |
| Google Drive report-photo permissions | BLOCKED_EXTERNAL_VERIFICATION | Inspect the real folder/file ACL and inherited sharing in staging/production with read-only credentials. Decide whether private proxy delivery or provider ACL repair is authoritative, then test direct anonymous denial. |
| Historical originals, thumbnails, aliases, and CDN copies | BLOCKED_EXTERNAL_VERIFICATION | Inventory by retained attachment/provider identifiers, not filename alone; approve a migration and rollback plan; migrate in a controlled release; verify original and generated-size URLs are denied; purge and read back CDN state. |
| Service-worker scope and old browser caches | PARTIAL | Deploy to staging, confirm effective scope and headers, upgrade an existing controlled browser, verify old QA caches are purged, and verify logout/account switching cannot replay report/API/photo content offline. |
| WordPress-to-standalone cutover | BLOCKED_EXTERNAL_VERIFICATION | Complete the standalone app migration plan, staged data reconciliation, UAT, release approval, rollback rehearsal, and approved cutover. This patch performs no cutover or production write. |

Until those gates pass, direct report-media confidentiality remains open. The
source patch narrows temporary-file and browser-cache exposure; it deliberately
does not perform an unverified partial migration of active or historical photos.

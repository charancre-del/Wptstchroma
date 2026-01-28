# QA Reports Audit: Meticulous Fix Plan

This roadmap provides a 1:1 mapping between the **Issue Register (QAR-001 to QAR-095)** and specific implementation tasks. It is prioritized by impact, ranging from fatal blockers to long-term technical debt.

---

## Phase 1: Fatal Core Fixes (Blockers & Data Loss)
**Goal**: Restore basic stability and prevent catastrophic data loss during active use.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-101 | QAR-001 | PDF Engine | Install Dompdf via Composer. Fail gracefully if missing. |
| FIX-102 | QAR-002, QAR-013 | AI Summary Payload | FE uses `executive_summary` key; sync BE naming with Schema. |
| FIX-103 | QAR-003, QAR-026 | Transactional Responses | Wrap `bulk_save` in SQL transactions. No partial deletes. |
| FIX-104 | QAR-012 | Transactional Summaries | Wrap `save_summary` in SQL transactions to prevent orphans. |
| FIX-105 | QAR-029 | Variable Scope Fix | Define `$folder_id` in `process_report_photos` to fix Drive pathing. |
| FIX-106 | QAR-005, QAR-025 | Role Isolation | Remove CQA caps from default standard WP roles. |
| FIX-107 | QAR-016 | Token Encryption | Use WP Salt-based encryption for usermeta token storage. |
| FIX-108 | QAR-019, QAR-050 | XSS Hardening | Escape DOM writes in notifications and legacy import views. |

---

## Phase 2: Logic & Integrity (Critical & Major)
**Goal**: Ensure the application behaves predictably and enforces consistent data rules.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-201 | QAR-004, QAR-037 | Temporal Alignment | Standardize on `inspection_date`. Fix UTC/Local drift. |
| FIX-202 | QAR-006, QAR-032 | Precision Concurrency | Implement `version_id` (INT) locking on Reports & Responses. |
| FIX-203 | QAR-023 | AI Response Cleaning | Replace string-stripping with deterministic JSON Extracting logic. |
| FIX-204 | QAR-031 | XXE Protection | Explicitly disable entity loading in `Document_Parser`. |
| FIX-205 | QAR-028 | Authorization Loop | Add Report-School ownership checks to photo update/delete routes. |
| FIX-206 | QAR-033, QAR-095 | Cap Granularity | Restrict API key management to `cqa_super_admin` only. |
| FIX-207 | QAR-021 | Prompt Injection | Move raw inspection text from DOCX to AI "Context" containers. |

---

## Phase 3: Performance & Resource Safety
**Goal**: Prevent memory overflows, database bloat, and server timeouts.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-301 | QAR-008, QAR-018 | Memory-Safe Uploads | Implement chunked multipart streaming for Drive uploads. |
| FIX-302 | QAR-015, QAR-027 | N+1 Elimination | refactor `Report::all` and `bulk_save` to use SQL JOINs/Bulk inserts. |
| FIX-303 | QAR-011, QAR-092 | Orphan Sync | Trigger `Google_Drive::delete_file` during orphan cleanup pulses. |
| FIX-304 | QAR-030, QAR-041 | Pulse Efficiency | move cleanup to Daily; fix the deactivator hook name. |
| FIX-305 | QAR-035 | Dash Join Indexing | Add indexes to `inspection_date` and remove `CAST` from JOINs. |
| FIX-306 | QAR-046, QAR-093 | Option Consolidation | Pack settings/flags into single array options. No per-flag writes. |

---

## Phase 4: Production Readiness & UX
**Goal**: Polish the user journey and resolve environment-specific friction.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-401 | QAR-014, QAR-007 | Bootstrapping | Move Cap/Flag injection from `admin_init` to Migration. |
| FIX-402 | QAR-017, QAR-034 | Identity & PWA | Fix SSO Approval flow and add Cache-Headers to Manifest. |
| FIX-403 | QAR-038, QAR-039 | Privacy & GDPR | Bundle Fonts locally; mask API secrets in Settings UI. |
| FIX-404 | QAR-044, QAR-045 | Layout Collisions | Fix Mobile CSS for WP Admin menu and PWA prompt overlays. |
| FIX-405 | QAR-048 | Scalability Buffer | Remove 100-school cap. Implement type-ahead school search. |
| FIX-406 | QAR-010, QAR-043 | Nav Resilience | Fix PHP fallbacks for Report List and Previous Report selects. |

---

## Phase 5: React UI Overhaul (Aesthetic Excellence)
**Goal**: Transform the UI into a premium, accessible, and high-performance "app" experience.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-501 | QAR-076, QAR-083 | Responsiveness | Replace static `h-` classes with flex/aspect-ratio logic. |
| FIX-502 | QAR-077, QAR-080 | Hydration Sync | Initialize Zustand with `initialData`. Memoize checklist items. |
| FIX-503 | QAR-078, QAR-084 | Accessibility | Screen-reader compliant RadioGroups and ARIA-live save status. |
| FIX-504 | QAR-079 | Media Memory | Use Blob URLs instead of Data URIs for photo previews. |
| FIX-505 | QAR-082, QAR-047 | Streaming UI | Skeleton screens for dashboard items; error UI for API failures. |
| FIX-506 | QAR-081 | Layering Fix | Sync Sidebar Z-Index with WP Admin menu (`z-9991` scope). |
| FIX-507 | QAR-085, QAR-049 | Identity Polish | Unified typography (Outfit). Localized role labels in UI. |

---

## Phase 6: Infrastructure & Protocol Security (Nuclear Resilience)
**Goal**: Hardened security and protocol stability against zero-day edge cases.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-601 | QAR-086 | CSRF Protection | Implement and verify `state` nonce in OAuth login callback. |
| FIX-602 | QAR-087 | Multisite Safety | Refactor `uninstall.php` to loop through all site IDs in network. |
| FIX-603 | QAR-088 | Session Purity | Remove global `SET time_zone`. Convert at logic layer. |
| FIX-604 | QAR-089 | Cache Control | Implement content-hash versioning for Service Worker assets. |
| FIX-605 | QAR-090 | Dynamic PWA | Inject base-path variables into manifest/SW at runtime. |
| FIX-606 | QAR-091 | OAuth Lock Guard | Singleton guard for refresh token concurrency. |
| FIX-607 | QAR-094 | SDK Migration | Migrate manual CURL calls to official Google Cloud PHP Client. |

---

## Phase 7: Developer Experience & Cleanup
**Goal**: Maintenance and legacy debt cleanup.

| Task ID | QAR Refs | Description | Acceptance Criteria |
|---------|----------|-------------|---------------------|
| FIX-701 | QAR-022 | Log Scrubbing | redact tokens and report content from `Logger` output. |
| FIX-702 | QAR-040 | Asset Optimization | Move GIS init to lazy-load on Login button click only. |
| FIX-703 | QAR-009 | Label Audit | Final mapping audit for all "Tier X" legacy string constants. |
| FIX-704 | QAR-010 | Legacy Cleanup | Remove unused PHP views replaced by React components. |

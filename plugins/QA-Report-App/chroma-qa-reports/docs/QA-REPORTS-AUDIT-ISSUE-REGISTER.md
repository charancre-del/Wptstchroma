# QA Reports Audit: Issue Register

## Executive Summary (Top 10 Blockers)
1. **QAR-001**: PDF Generator returns HTML instead of PDF due to missing libraries (TCPDF/Dompdf).
2. **QAR-002**: Wizard Crash - AI Summary payload discrepancy (`string` vs `object`).
3. **QAR-003**: Data Loss Risk - `Checklist_Response::bulk_save` deletes all data without transactions.
4. **QAR-004**: Field Desync - Frontend expects `visit_date`, Backend sends `inspection_date`.
5. **QAR-005**: Security Gap - `Subscriber` role has `cqa_create_reports` and `cqa_edit_own_reports` capabilities.
6. **QAR-006**: Race Condition - Auto-save (30s) vs Manual Save can desync due to 1s MySQL precision.
7. **QAR-007**: Capability Debt - Entry file forces caps on every `admin_init` instead of activation logic.
8. **QAR-008**: Performance Hazard - Base64 photo decoding in PHP blocks request execution for large uploads.
9. **QAR-009**: Broken Labels - Report types `tier1_tier2` mapped to "Not selected" in UI.
10. **QAR-010**: Legacy View Crash - `Admin_Menu` lacks `Report_List` view inclusion in برخی environments.
11. **QAR-011**: Resource Leak - `Photo::delete` leaves orphaned files on Drive/Local Disk.
12. **QAR-012**: Data Loss Risk - `Executive_Summary::save_summary` lacks DB transactions.
13. **QAR-013**: Prompt/DB Naming Conflict - Gemini uses `support_and_growth_plan`, DB uses `poi_json`.
14. **QAR-014**: Broken UX - Feature flags forced to `true` in entry file override user settings.
15. **QAR-015**: Performance Debt - N+1 query for School names in List View.
16. **QAR-016**: Security - **Plaintext Token Storage**: Google OAuh tokens stored unencrypted in usermeta.
17. **QAR-017**: Security - **Weak SSO Approval**: New users matching domain auto-granted `qa_officer` role.
18. **QAR-018**: Performance - **OOM Risk**: Drive upload reads entire multipart body into memory.
19. **QAR-019**: Security - **DOM-based XSS**: Insecure `.html()` call in legacy import view with file names.
20. **QAR-020**: Security - **API Key Exposure**: Gemini key passed in URL query params.
21. **QAR-021**: Security - **Prompt Injection**: Raw document text appended directly to AI instructions.
22. **QAR-022**: Security - **Unredacted Logging**: Logger saves full tokens and report data to disk.
23. **QAR-023**: Performance - **Brittle AI Parsing**: JSON extraction relies on string search vs system prompts.
24. **QAR-024**: Logic - **N+1 Migration**: School cleanup migration lacks batching.
25. **QAR-025**: Security - **Incomplete Isolation**: Subscriber role still has report creation caps.
26. **QAR-026**: Data Loss - **Destructive Bulk Save**: `Checklist_Response::bulk_save` deletes before insert without a transaction.
27. **QAR-027**: Performance - **N+1 Bulk Insert**: `bulk_save` runs individual inserts in a loop (100+ queries).
28. **QAR-028**: Security - **IDOR on Photos**: Photo update/delete lacks report-ownership verification.
29. **QAR-029**: Logic - **Undefined Var Crash**: `process_report_photos` uses an undefined `$folder_id` in REST Controller.
30. **QAR-030**: Performance - **Maintenance Gap**: Weekly photo cleanup is too infrequent for production scale.
31. **QAR-031**: Security - **XXE Hazard**: `Document_Parser` lacks explicit entity loader disabling for Older PHP.
32. **QAR-032**: Data Integrity - **Concurrence Gap**: `save_report_responses` lacks the precision locking found in `update_report`.
33. **QAR-033**: Security - **Settings Over-Privilege**: QA Officers can modify global API secrets (Gemini/Drive).
34. **QAR-034**: Performance - **Stale Manifest**: PWA manifest is hardcoded and lacks dynamic caching headers.
35. **QAR-035**: Performance - **Unoptimized Dashboard**: Search joins use `CAST` and `LIKE` on un-indexed calculated fields.
36. **QAR-036**: UX - **PWA Path Brittleness**: `manifest.json` uses hardcoded `/wp-admin/` paths.
37. **QAR-037**: UX - **Timezone Naivety**: Inspection dates default to server time, not local user time.
38. **QAR-038**: Privacy - **GDPR/Asset Gap**: External Google Fonts loaded without local fallback.
39. **QAR-039**: Security - **Unmasked Settings**: API keys displayed in plaintext in the admin form.
40. **QAR-040**: Performance - **GIS Redundancy**: Google Identity Services initialized on every click.
41. **QAR-041**: Lifecycle - **Cron Cleanup Gap**: Deactivator clears wrong hook (`cqa_daily_cleanup`).
42. **QAR-042**: Logic - **Capability Staleness**: Roles only update on activation, not updates.
43. **QAR-043**: UX - **Brittle Wizard Nav**: Previous reports selection lacks PHP fallback.
44. **QAR-044**: UI - **Aggressive Mobile CSS**: Admin menu hidden on mobile, breaking WP navigation.
45. **QAR-045**: UI - **Overlay Collision**: PWA install prompt overlaps sticky wizard footer.
46. **QAR-046**: Performance - **Option Bloat**: Settings stored as individual options vs array.
47. **QAR-047**: UX - **Missing AJAX Error UI**: JS `request` helper lacks failure notifications.
48. **QAR-048**: Scalability - **School Limit**: Admin wizard hardcoded to 100 schools only.
49. **QAR-049**: i18n - **Hardcoded Role Labels**: Role labels in `Admin_Menu` missing localization.
50. **QAR-050**: Security - **XSS in Notifications**: JS helper allows raw HTML injection.

## Issue Table
| ID | Severity | Area | Title | File(s) | Root Cause |
|----|----------|------|-------|---------|------------|
| QAR-001 | Blocker | PDF | HTML Fallback Failure | `class-pdf-generator.php` | Missing libraries/wrong mime |
| QAR-002 | Blocker | Frontend | AI Summary Wizard Crash | `StepReview.jsx` | Data type mismatch |
| QAR-003 | Critical | Data | Bulk Save Data Loss | `class-checklist-response.php` | Delete-then-insert no TX |
| QAR-004 | Critical | API | Date Field Alias Desync | `class-rest-controller.php` | Naming inconsistency |
| QAR-005 | Critical | Security | Subscriber Permissions | `class-activator.php` | Excessive cap assignment |
| QAR-006 | Major | Sync | Concurrency Race Condition | `useAutoSave.js` | 1s DB precision conflict |
| QAR-007 | Major | Arch | Entry File Cap Injection | `chroma-qa-reports.php` | Improper bootstrap logic |
| QAR-008 | Major | Perf | Base64 Decode Bottleneck | `class-rest-controller.php` | CPU/Mem intensive PHP processing |
| QAR-009 | Minor | UX | Label Mapping Mismatch | `StepReview.jsx` | String constant desync |
| QAR-010 | Minor | Storage | Orphaned Media Latency | `class-plugin.php` | Weekly cleanup too slow |
| QAR-011 | Major | Storage | Resource Leak (Orphans) | `class-photo.php` | Delete record but not file |
| QAR-012 | Critical | Data | Summary Save Data Loss | `class-executive-summary.php` | No transaction on summary update |
| QAR-013 | Major | API | POI Naming Conflict | Multiple | Prompts vs Schema vs params |
| QAR-014 | Major | UX | Forced Feature Flags | `chroma-qa-reports.php` | Override user settings in init |
| QAR-015 | Major | Perf | N+1 School Query | `class-report.php` | Lazy loading in list view |
| QAR-016 | Critical | Security | Plaintext OAuth Tokens | `class-google-oauth.php` | Unencrypted usermeta storage |
| QAR-017 | Major | Security | SSO Privilege Escalation | `class-google-oauth.php` | Auto-grant high-level roles |
| QAR-018 | Major | Perf | OOM Upload Hazard | `class-google-drive.php` | Full file read into memory |
| QAR-019 | Major | Security | DOM-based XSS | `legacy-import.php` | Insecure jQuery .html() use |
| QAR-020 | Major | Security | API Key in URL | `class-gemini-service.php` | Exposed in logs/headers |
| QAR-021 | Major | Security | Prompt Injection | `class-gemini-service.php` | Raw input in AI prompts |
| QAR-022 | Major | Security | Unredacted Logging | `class-logger.php` | Tokens/data saved to log files |
| QAR-023 | Major | Logic | Brittle JSON Cleaning | `class-gemini-service.php` | Non-deterministic AI parsing |
| QAR-024 | Minor | Perf | Migration Batching Missing | `class-upgrade-manager.php` | Unbounded DB selection |
| QAR-025 | Major | Security | Subscriber Isolation | `class-activator.php` | Cap leakage remains |
| QAR-026 | Critical | Data | Destructive Bulk Save | `class-checklist-response.php` | No transaction on delete-reinsert |
| QAR-027 | Major | Perf | N+1 Insert Loop | `class-checklist-response.php` | 100+ queries per save |
| QAR-028 | Major | Security | Photo IDOR | `class-rest-controller.php` | Missing ownership check |
| QAR-029 | Critical | Logic | Undefined Var Crash | `class-rest-controller.php` | `$folder_id` used but not defined |
| QAR-030 | Minor | Perf | CRON Latency | `class-plugin.php` | Weekly cleanup is too slow |
| QAR-031 | Major | Security | XXE Risk | `class-document-parser.php` | Missing XXE protection |
| QAR-032 | Major | Data | Response Race Condition | `class-rest-controller.php` | Missing version_id on responses |
| QAR-033 | Major | Security | Settings Over-Privilege | `class-rest-controller.php` | Caps too broad for API keys |
| QAR-034 | Minor | Perf | Hardcoded PWA Manifest | `class-plugin.php` | Lacks caching headers |
| QAR-035 | Major | Perf | Dashboard Join Bottleneck | `class-report.php` | Un-indexed CAST/LIKE queries |
| QAR-036 | Medium | UX | PWA Path Brittleness | `manifest.json` | Hardcoded /wp-admin/ paths |
| QAR-037 | Low | UX | Timezone Naivety | `report-create.php` | Defaults to server date |
| QAR-038 | Medium | Privacy | GDPR/Asset Gap | `class-admin-menu.php` | External Google Fonts |
| QAR-039 | Medium | Security | Unmasked Settings | `settings.php` | Plaintext API keys in UI |
| QAR-040 | Low | Perf | GIS Redundancy | `admin-scripts.js` | Repeat GIS init on click |
| QAR-041 | Medium | Logic | Cron Cleanup Gap | `class-deactivator.php` | Clears wrong hook |
| QAR-042 | Medium | Logic | Capability Staleness | `class-activator.php` | Roles don't update on re-activation |
| QAR-043 | Medium | UX | Brittle Wizard Nav | `report-create.php` | No PHP fallback for report select |
| QAR-044 | Medium | UI | Aggressive Mobile CSS | `mobile-styles.css` | Hides WP admin menu |
| QAR-045 | Low | UI | Overlay Collision | `mobile-styles.css` | PWA prompt vs Sticky footer |
| QAR-046 | Low | Perf | Option Bloat | `class-activator.php` | Individual options vs Array |
| QAR-047 | Medium | UX | Missing AJAX Error UI | `admin-scripts.js` | No global failure handler |
| QAR-048 | Medium | Perf | School Limit (100) | `report-create.php` | Hardcoded limit on school list |
| QAR-049 | Low | i18n | Hardcoded Role Labels | `class-admin-menu.php` | Labels missing translation |
| QAR-050 | Medium | Security | XSS in Notifications | `admin-scripts.js` | Unescaped notification HTML |
| QAR-076 | Major | React UI | Fixed Height Hazards | `Dashboard.jsx` | Static `h-60`/`h-500` classes |
| QAR-077 | Medium | React UI | Checklist Input Lag | `StepChecklist.jsx` | Missing React.memo on items |
| QAR-078 | Major | React UI | Accessibility Gap | `ChecklistItem.jsx` | Non-semantic button groups |
| QAR-079 | Major | React UI | Media Memory Leak | `StepPhotos.jsx` | High-res Data URIs in preview |
| QAR-080 | Low | React UI | Double Hydration Jump | `ReportWizard.jsx` | Query -> Zustand state sync |
| QAR-081 | Medium | React UI | Z-Index Layering | `Shell.jsx` | Collisions with WP Admin UI |
| QAR-082 | Low | React UI | Dead Spinner State | `StepAISummary.jsx` | Lack of streaming/skeleton UI |
| QAR-083 | Medium | React UI | Mobile Padding Overflow | Multiple | Hardcoded `px-8` on sm screens |
| QAR-084 | Medium | React UI | ARIA Status Gap | `useAutoSave.js` | No screen reader cues for save |
| QAR-085 | Low | React UI | Font Inconsistency | `tailwind.config.js` | Mixture of Inter/Outfit fonts |

### ☢️ Nuclear Audit: Zero-Day & Architectural (New)
| Issue ID | Area | Title | Severity | Root Cause |
|----------|------|-------|----------|------------|
| QAR-086 | Security | OAuth Linkage CSRF | **Critical** | Missing state/nonce verification in callback |
| QAR-087 | Data | Multisite Data Ghosting | **Major** | Uninstall script only handles single site prefix |
| QAR-088 | Environment | Selfish UTC Side-Effect | **Major** | Global `SET time_zone` on init breaks WP ecosystem |
| QAR-089 | Deployment | PWA Cache Poisoning | **Major** | `STATIC_CACHE` lacks versioning/hashing |
| QAR-090 | Deployment | PWA Path Brittleness | **Major** | Hardcoded plugin paths in Service Worker |
| QAR-091 | Auth | Token Refresh Race | **Major** | Multiple concurrent refreshes revoke account |
| QAR-092 | Compliance | Cloud Storage Leak | **Medium** | Orphan cleanup doesn't delete Drive files |
| QAR-093 | Performance | Option DB Bloat | **Low** | writes feature flags on every `admin_init` |
| QAR-094 | Maintenance | Protocol Fragility | **Medium** | Manual OAuth/Drive implementation vs Official SDK |
| QAR-095 | Security | Multisite Cap Leak | **Medium** | Global cap injection lacks network-admin context |

## Full Issue Details

### QAR-001: HTML Fallback Failure (PDF)
- **Severity**: Blocker
- **Category**: PDF
- **Affected files**: `includes/export/class-pdf-generator.php`, `includes/api/class-rest-controller.php`
- **Root cause**: Plugin code expects TCPDF/Dompdf but they are not in `composer.json` or `vendor/`. Fallback returns `.html` which is served as `text/html`.
- **Proposed fix**: Install Dompdf via Composer. Update `generate` to throw error if missing instead of returning HTML.

### QAR-002: AI Summary Wizard Crash
- **Severity**: Blocker
- **Category**: Frontend
- **Affected files**: `build-env/src/components/wizard/steps/StepReview.jsx`
- **Root cause**: Line 195 calls `.split('\n')` on `report.ai_summary`. Backend sends `ai_summary` as an object/array.
- **Proposed fix**: Change BE to send string version or update FE to access `report.ai_summary.executive_summary`.

### QAR-003: Bulk Save Data Loss Risk
- **Severity**: Critical
- **Category**: Data
- **Affected files**: `includes/models/class-checklist-response.php`
- **Root cause**: `bulk_save` performs `$wpdb->delete` then multiple `$wpdb->insert` without a transaction wrapper.
- **Proposed fix**: Wrap `bulk_save` logic in `$wpdb->query('START TRANSACTION')` and `COMMIT` / `ROLLBACK`.

### QAR-004: Date Field Alias Desync
- **Severity**: Critical
- **Category**: API
- **Affected files**: `includes/api/class-rest-controller.php`, `build-env/src/components/wizard/steps/StepReview.jsx`
- **Root cause**: Backend uses `inspection_date`. Frontend expects `visit_date`. Aliasing is inconsistent across endpoints.
- **Proposed fix**: Standardize on `inspection_date` in both layers.

### QAR-005: Subscriber Permissions Leak
- **Severity**: Critical
- **Category**: Security
- **Affected files**: `includes/class-activator.php`
- **Root cause**: Capabilities like `cqa_create_reports` are assigned to the `subscriber` role by default.
- **Proposed fix**: Remove CQA-specific capabilities from standard WP roles. Force use of custom CQA roles.

[Detailed logs for QAR-006 to QAR-010 omitted for brevity in audit summary but included in full documentation]

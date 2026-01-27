# QA Reports Plugin Audit — Issue Register

## Executive Summary (Top 10 Blockers/Criticals)
1. **QAR-001 — Dashboard stats endpoint hard-fails due to non-existent DB column (`rating`)**: `/stats` SQL queries reference `rating` instead of `overall_rating`, causing 500s and broken dashboard analytics. (Backend/API/Data)
2. **QAR-008 — Photo upload permission uses a non-existent capability (`cqa_edit_reports`)**: all uploads fail with 403, breaking evidence workflows. (Backend/Auth)
3. **QAR-010 — Photo update/delete guarded by read-only permission**: any viewer can mutate/delete evidence. (Security)
4. **QAR-012 — AI summary frontend expects `summary`, backend returns `executive_summary`**: summary UI appears blank; generation “succeeds” but shows nothing. (Frontend/API)
5. **QAR-004 — Autosave omits checklist responses/photos**: drafts appear to save but lose key data. (Frontend/Data)
6. **QAR-006 — Photo upload response mismatch (`response.data` vs `response.photos`)**: UI never updates with uploaded photos. (Frontend/API)
7. **QAR-007 — Photo delete endpoint mismatch**: UI calls `/reports/{id}/photos/{photoId}` but backend exposes `/photos/{id}`. (Frontend/API)
8. **QAR-017 — Settings endpoint permits report creators to update secrets**: any report author can read/write Google/Gemini secrets. (Security)
9. **QAR-002 — AI prompt is overwritten**: the Executive Summary builder discards report data, generating low-quality summaries. (Backend/AI)
10. **QAR-019 — PDF temp files accumulate without cleanup**: unbounded disk growth and potential outages. (Performance)

---

## Coverage Matrix (Routes/Screens)
| Route/Screen | Location | Status |
| --- | --- | --- |
| `/#/` Dashboard | `build-env/src/App.jsx` → `pages/Dashboard.jsx` | **Issue**: QAR-001, QAR-014 |
| `/#/schools` | `build-env/src/pages/Schools.jsx` | **Issue**: QAR-016 |
| `/#/reports` | `build-env/src/pages/Reports.jsx` | **Issue**: QAR-015 |
| `/#/reports/:id` View | `build-env/src/components/wizard/ReportWizard.jsx` | **Issue**: QAR-004, QAR-005, QAR-012 |
| `/#/edit/:id` Edit Wizard | `build-env/src/components/wizard/ReportWizard.jsx` | **Issue**: QAR-004, QAR-005, QAR-011 |
| `/#/create` Create Wizard | `build-env/src/components/wizard/ReportWizard.jsx` | **Issue**: QAR-011 |
| `/#/settings` | `build-env/src/pages/Settings.jsx` | **Issue**: QAR-017 |
| `/qa-reports/login` (public) | `public/class-frontend-controller.php` | **PASS** (no critical defects identified) |
| `/qa-reports/*` (frontend app shell) | `public/class-frontend-controller.php` | **PASS** (routing works; no blocking issues) |

## Coverage Matrix (API Endpoints)
| Endpoint | Method | Status |
| --- | --- | --- |
| `/cqa/v1/me` | GET | **PASS** |
| `/cqa/v1/schools` | GET/POST | **Issue**: QAR-016 (client-side filtering/overfetch); **PASS** for core response |
| `/cqa/v1/schools/{id}` | GET/PUT/DELETE | **PASS** |
| `/cqa/v1/reports` | GET/POST | **Issue**: QAR-015, QAR-014, QAR-022 |
| `/cqa/v1/reports/{id}` | GET/PUT/DELETE | **Issue**: QAR-018 |
| `/cqa/v1/reports/{id}/responses` | GET/POST | **PASS** |
| `/cqa/v1/reports/{id}/pdf` | GET | **Issue**: QAR-019 |
| `/cqa/v1/reports/{id}/generate-summary` | POST | **Issue**: QAR-012 |
| `/cqa/v1/reports/upload-doc` | POST | **PASS** |
| `/cqa/v1/reports/{id}/photos` | POST | **Issue**: QAR-006, QAR-008 |
| `/cqa/v1/photos/{id}` | PUT/DELETE | **Issue**: QAR-010 |
| `/cqa/v1/ai/parse-document` | POST | **PASS** |
| `/cqa/v1/checklists/{type}` | GET | **PASS** |
| `/cqa/v1/schools/{id}/reports` | GET | **PASS** |
| `/cqa/v1/settings` | GET/POST | **Issue**: QAR-017 |
| `/cqa/v1/stats` | GET | **Issue**: QAR-001 |
| `/cqa/v1/system-check` | GET | **PASS** |

---

## Issue Table (Sortable)
| ID | Severity | Area | Title | File(s) | Repro | Root Cause | Fix | Test |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| QAR-001 | Critical | API/Data | `/stats` query references non-existent `rating` column | `includes/api/class-rest-controller.php::get_stats` | Open dashboard → stats fail | SQL uses `rating` instead of `overall_rating` | Replace `rating` with `overall_rating` in all stats queries and mappings | API: GET `/cqa/v1/stats` |
| QAR-002 | Major | Backend/AI | AI prompt overwritten, discards report data | `includes/ai/class-executive-summary.php::build_prompt` | Generate AI summary → irrelevant output | `$prompt` is re-assigned to instructions, losing prior content | Append instructions instead of overwriting; add tests for prompt content | Generate summary; verify prompt logs |
| QAR-003 | Major | Frontend/Auth | Two separate auth stores → missing user/capability state | `build-env/src/App.jsx`, `build-env/src/pages/Dashboard.jsx`, `build-env/src/stores/index.js` | Load Dashboard → user name blank, perms incorrect | Dashboard uses different Zustand store than App | Use single auth store across app | Smoke test Dashboard/Sidebar identity |
| QAR-004 | Major | Frontend/Data | Autosave omits responses & photos | `build-env/src/components/wizard/ReportWizard.jsx`, `build-env/src/hooks/useAutoSave.js` | Fill checklist → wait autosave → reload → responses missing | Autosave receives only report draft and reads missing fields | Pass responses/photos to hook or merge into draft | Create report, autosave, reload |
| QAR-005 | Major | Frontend/Data | Wizard photo gallery reads `draft.photos` but store holds separate `photos` | `build-env/src/components/wizard/ReportWizard.jsx`, `build-env/src/components/wizard/steps/StepPhotos.jsx`, `build-env/src/stores/index.js` | Upload photos → gallery empty after reload | Photos stored in store, not in `report` draft | Use store `photos` as source of truth | Upload photo and refresh wizard |
| QAR-006 | Major | Frontend/API | Photo upload response shape mismatch | `build-env/src/components/wizard/steps/StepPhotos.jsx`, `includes/api/class-rest-controller.php::upload_report_photos` | Upload photo → success toast but no UI update | UI expects `response.data`, API returns `photos` | Update UI to read `response.photos` or change API payload | Upload photo and confirm list |
| QAR-007 | Major | Frontend/API | Photo delete endpoint mismatch | `build-env/src/components/wizard/steps/StepPhotos.jsx`, `includes/api/class-rest-controller.php` | Delete photo → 404 | UI calls `/reports/{id}/photos/{photoId}` but API exposes `/photos/{id}` | Update endpoint to `/photos/{id}` or add matching route | Delete photo and confirm server removal |
| QAR-008 | Critical | Auth/API | Photo upload always forbidden | `includes/api/class-rest-controller.php::upload_report_photos` | Upload photo → 403 | Permission checks `cqa_edit_reports`, which doesn’t exist | Use `check_edit_reports_permission` or `cqa_edit_*` caps | Upload with QA officer/admin |
| QAR-009 | Major | UX/Data | Photo thumbnails broken | `build-env/src/components/wizard/steps/StepPhotos.jsx`, `includes/api/class-rest-controller.php::prepare_report_response` | Load report with photos → broken images | UI expects `photo.url/preview`; API returns `thumbnail_url/view_url` | Map API fields to UI or update renderer | View report with existing photos |
| QAR-010 | Critical | Security | Any viewer can edit/delete photos | `includes/api/class-rest-controller.php::update_photo`, `delete_photo` | Log in as view-only user → delete photo | Permission callback uses `check_read_permission` | Require `check_edit_reports_permission` | Attempt delete as viewer |
| QAR-011 | Major | Data/UX | “No link” sentinel ignored; backend auto-links previous report | `build-env/src/components/wizard/steps/StepSchool.jsx`, `includes/api/class-rest-controller.php::create_report` | Select “Do Not Link” → backend still links previous | `previous_report_id=0` is treated as falsy, triggers auto-link | Treat `0` explicitly as “no link” and skip auto-link | Create report with “no link” |
| QAR-012 | Major | Frontend/API | AI summary output key mismatch (`summary` vs `executive_summary`) | `build-env/src/components/wizard/steps/StepAISummary.jsx`, `includes/ai/class-executive-summary.php` | Generate summary → UI blank | UI reads `result.summary`; backend returns `executive_summary` | Use `executive_summary` or adapt API response | Generate summary and display |
| QAR-013 | Major | Data/API | Edited AI summary never persisted | `build-env/src/components/wizard/steps/StepAISummary.jsx`, `includes/api/class-rest-controller.php::update_report` | Edit summary → reload → changes lost | No API field updates AI summary content | Add `ai_summary` update path or dedicated endpoint | Edit summary and reload |
| QAR-014 | Minor | Performance | Dashboard passes `limit` instead of `per_page` | `build-env/src/pages/Dashboard.jsx`, `includes/api/class-rest-controller.php::get_reports` | Load Dashboard → reports list not limited | API expects `per_page` | Change client to `per_page` | Dashboard loads 5 reports |
| QAR-015 | Major | Performance/UX | Reports page fetches all reports; filters client-side only | `build-env/src/pages/Reports.jsx`, `build-env/src/hooks/useQueries.js` | Large dataset → slow reports page | Filters not sent to API | Use server-side filters/pagination | Reports list with filters |
| QAR-016 | Major | Performance/UX | Schools page fetches all schools; filters client-side only | `build-env/src/pages/Schools.jsx`, `build-env/src/hooks/useQueries.js` | Large dataset → slow schools page | Filters/search not sent to API | Pass filters/per_page to API | Schools list with search |
| QAR-017 | Critical | Security | Settings endpoint open to report creators | `includes/api/class-rest-controller.php::check_settings_permission` | Role with `cqa_create_reports` can read API secrets | Permission overly broad | Restrict to `cqa_manage_settings` or `manage_options` | Try GET/POST `/settings` as non-admin |
| QAR-018 | Minor | UX | Conflict modal shows wrong “updated by” user | `includes/api/class-rest-controller.php::update_report` | Concurrent edit → conflict modal shows report author | Uses `report->user_id` not last editor | Track last editor on update | Simulate conflict with two users |
| QAR-019 | Major | Performance | PDF generation leaves temp files in uploads | `includes/export/class-pdf-generator.php` | Generate PDFs repeatedly → disk grows | No cleanup of `uploads/cqa-temp` | Add cleanup task or reuse files | Generate PDF then run cleanup |
| QAR-020 | Major | Data Integrity | No foreign keys or cascading for reports/photos/responses | `includes/class-activator.php` | Delete report → orphaned photos/responses | Schema lacks FK constraints and cascades | Add FK or cleanup hooks | Delete report, verify cleanup |
| QAR-021 | Minor | UX | Role label mismatch for `cqa_program_manager` | `includes/class-activator.php`, `admin/class-admin-menu.php`, `public/class-frontend-controller.php` | Program Manager shows as “User” | Role label uses `cqa_program_management` | Align role slug | Login as Program Manager |
| QAR-022 | Major | Data | Report creation does not validate required fields | `includes/api/class-rest-controller.php::create_report` | Create report with missing school/date → invalid DB rows | No server validation for `school_id`/`inspection_date` | Validate and return 422 | POST `/reports` with empty body |
| QAR-023 | Major | Frontend/UX | Missing runtime error boundary causes white-screen on JS errors | `build/index.js` (React entry) | Trigger JS error in React tree | No error boundary or global fallback | Add error boundary or runtime guard | Force a JS error and verify fallback |
| QAR-024 | Minor | Frontend/UX | Recharts container renders at zero size, warns about width/height | `build-env/src/components/dashboard/ComplianceChart.jsx` | Load dashboard → console warning | Responsive container height 100% with zero-height parent | Set explicit height/min-height | Load dashboard and confirm no Recharts warnings |
| QAR-025 | Major | Frontend/Data | Wizard step index out of range crashes report wizard | `build-env/src/components/wizard/ReportWizard.jsx` | Load wizard with persisted invalid step | `currentStep` not clamped to steps length | Clamp step value and recover | Open wizard with invalid persisted step |

---

## Full Issue Details

### QAR-001: `/stats` query references non-existent `rating` column
- **Severity:** Critical
- **Category:** API/Data
- **Affected files:** `includes/api/class-rest-controller.php::get_stats`
- **Steps to reproduce:**
  1. Open Dashboard in React app.
  2. Observe stats panel or call `GET /cqa/v1/stats`.
- **Expected:** Stats load without error.
- **Actual:** SQL errors (unknown column `rating`), dashboard fails to populate.
- **Root cause:** Stats queries reference `rating` instead of `overall_rating` and also compare to string labels not aligned with enum values.
- **Proposed fix:** Replace all `rating` references with `overall_rating`; normalize comparisons to enum values (`exceeds`, `meets`, `needs_improvement`).
- **Regression tests:** API GET `/cqa/v1/stats` with reports in all states.
- **Risk/notes:** High impact on dashboard; blocking for executives.

### QAR-002: AI prompt overwritten, discarding report data
- **Severity:** Major
- **Category:** Backend/AI
- **Affected files:** `includes/ai/class-executive-summary.php::build_prompt`
- **Steps to reproduce:**
  1. Generate AI summary for a report.
  2. Observe generic or off-topic summary.
- **Expected:** Summary grounded in report responses and school data.
- **Actual:** Prompt only includes instructions; report data discarded.
- **Root cause:** `$prompt` is re-assigned when instructions are added, erasing prior text.
- **Proposed fix:** Append instructions (`$prompt .=`) instead of overwriting; add unit tests for prompt content.
- **Regression tests:** AI summary generation with known responses.
- **Risk/notes:** AI output is untrustworthy until fixed.

### QAR-003: Two distinct auth stores cause user/capability mismatch
- **Severity:** Major
- **Category:** Frontend/Auth
- **Affected files:** `build-env/src/App.jsx`, `build-env/src/pages/Dashboard.jsx`, `build-env/src/stores/index.js`
- **Steps to reproduce:**
  1. Load Dashboard.
  2. Observe missing user name and capabilities inconsistencies in UI.
- **Expected:** Single source of truth for authenticated user and capabilities.
- **Actual:** Dashboard uses a different Zustand store than App; user info not populated.
- **Root cause:** `useAuthStore` is defined twice and imported inconsistently.
- **Proposed fix:** Standardize on `build-env/src/stores/useAuthStore.js` across app.
- **Regression tests:** App load, verify username and permission-based UI.
- **Risk/notes:** Leads to feature gating errors and broken navigation.

### QAR-004: Autosave omits responses/photos
- **Severity:** Major
- **Category:** Frontend/Data
- **Affected files:** `build-env/src/components/wizard/ReportWizard.jsx`, `build-env/src/hooks/useAutoSave.js`
- **Steps to reproduce:**
  1. Fill checklist, wait 30 seconds for autosave.
  2. Reload report.
- **Expected:** Checklist responses/photos preserved.
- **Actual:** Responses/photos are missing.
- **Root cause:** `useAutoSave` receives only `draft` (report) which lacks responses/photos and sends `currentDraft.responses`/`photos` as undefined.
- **Proposed fix:** Pass responses/photos to `useAutoSave` or merge store state into draft before autosave.
- **Regression tests:** Autosave then reload.
- **Risk/notes:** Drafts appear saved but lose core content.

### QAR-005: Wizard photo gallery uses wrong state source
- **Severity:** Major
- **Category:** Frontend/Data
- **Affected files:** `build-env/src/components/wizard/steps/StepPhotos.jsx`, `build-env/src/components/wizard/ReportWizard.jsx`, `build-env/src/stores/index.js`
- **Steps to reproduce:**
  1. Load an existing report with photos.
  2. Visit Photos step.
- **Expected:** Photos display.
- **Actual:** Gallery empty.
- **Root cause:** `StepPhotos` reads `draft.photos`, while photos are stored in a separate store key `photos`.
- **Proposed fix:** Use `photos` from the wizard store or hydrate `draft.photos`.
- **Regression tests:** Load existing report with photos and verify gallery.
- **Risk/notes:** Evidence appears missing, causing re-uploads.

### QAR-006: Photo upload response mismatch
- **Severity:** Major
- **Category:** Frontend/API
- **Affected files:** `build-env/src/components/wizard/steps/StepPhotos.jsx`, `includes/api/class-rest-controller.php::upload_report_photos`
- **Steps to reproduce:** Upload photos in wizard.
- **Expected:** Uploaded photos appear in gallery.
- **Actual:** Response handled as `response.data` (undefined); gallery not updated.
- **Root cause:** API returns `{ success: true, photos: [...] }`, UI expects `{ data: [...] }`.
- **Proposed fix:** Align response shape or update UI to read `photos`.
- **Regression tests:** Upload and verify gallery updates.

### QAR-007: Photo delete endpoint mismatch
- **Severity:** Major
- **Category:** Frontend/API
- **Affected files:** `build-env/src/components/wizard/steps/StepPhotos.jsx`, `includes/api/class-rest-controller.php::register_routes`
- **Steps to reproduce:** Delete a photo from the wizard.
- **Expected:** Photo deleted from backend.
- **Actual:** 404 because route doesn’t exist.
- **Root cause:** UI calls `/reports/{id}/photos/{photoId}` but API defines `/photos/{id}`.
- **Proposed fix:** Use `/photos/{id}` or add matching route.
- **Regression tests:** Delete photo and verify DB record removal.

### QAR-008: Photo upload permission uses missing capability
- **Severity:** Critical
- **Category:** Auth/API
- **Affected files:** `includes/api/class-rest-controller.php::upload_report_photos`
- **Steps to reproduce:** Upload a photo as a QA Officer or Admin.
- **Expected:** Upload succeeds.
- **Actual:** 403 Forbidden.
- **Root cause:** Permission check uses `cqa_edit_reports`, which is not a registered capability.
- **Proposed fix:** Use `check_edit_reports_permission` or `cqa_edit_all_reports`/`cqa_edit_own_reports`.
- **Regression tests:** Upload as QA Officer and Admin.

### QAR-009: Photo thumbnails broken
- **Severity:** Major
- **Category:** Frontend/UX
- **Affected files:** `build-env/src/components/wizard/steps/StepPhotos.jsx`, `includes/api/class-rest-controller.php::prepare_report_response`
- **Steps to reproduce:** View a report with existing photos.
- **Expected:** Thumbnails load.
- **Actual:** Broken images; UI expects `photo.url`/`photo.preview`.
- **Root cause:** API returns `thumbnail_url` and `view_url`, not `url`.
- **Proposed fix:** Map API fields in UI or extend API response.
- **Regression tests:** Load existing photos.

### QAR-010: Any viewer can edit/delete photos
- **Severity:** Critical
- **Category:** Security
- **Affected files:** `includes/api/class-rest-controller.php::update_photo`, `delete_photo`
- **Steps to reproduce:** Log in as user with `cqa_view_own_reports` only; call `DELETE /photos/{id}`.
- **Expected:** 403 Forbidden.
- **Actual:** Photo deleted.
- **Root cause:** `check_read_permission` used for mutating routes.
- **Proposed fix:** Require edit permissions (`check_edit_reports_permission`).
- **Regression tests:** Attempt delete as viewer.

### QAR-011: “Do Not Link” ignored; backend auto-links previous report
- **Severity:** Major
- **Category:** Data/UX
- **Affected files:** `build-env/src/components/wizard/steps/StepSchool.jsx`, `includes/api/class-rest-controller.php::create_report`
- **Steps to reproduce:** Select “Do Not Link (Fresh Start)” for previous report.
- **Expected:** `previous_report_id` stays null.
- **Actual:** Backend auto-links latest approved report.
- **Root cause:** UI sends `previous_report_id=0`; backend treats falsy and auto-links.
- **Proposed fix:** Use explicit sentinel (e.g., `null` with `skip_auto_link=true`) or treat `0` as “no link.”
- **Regression tests:** Create report with no-link option.

### QAR-012: AI summary key mismatch
- **Severity:** Major
- **Category:** Frontend/API
- **Affected files:** `build-env/src/components/wizard/steps/StepAISummary.jsx`, `includes/ai/class-executive-summary.php`
- **Steps to reproduce:** Generate AI summary.
- **Expected:** Summary text displayed.
- **Actual:** Summary blank because UI reads `result.summary`.
- **Root cause:** Backend returns `executive_summary`.
- **Proposed fix:** Update UI to read `executive_summary`.
- **Regression tests:** Generate summary and verify text.

### QAR-013: Edited AI summary never persisted
- **Severity:** Major
- **Category:** Data/API
- **Affected files:** `build-env/src/components/wizard/steps/StepAISummary.jsx`, `includes/api/class-rest-controller.php::update_report`
- **Steps to reproduce:** Edit summary in UI; refresh report.
- **Expected:** Edited summary persists.
- **Actual:** Changes lost.
- **Root cause:** `update_report` only accepts `summary_poi`, no field for edited summary text.
- **Proposed fix:** Add `ai_summary` update capability or new endpoint.
- **Regression tests:** Edit summary, reload.

### QAR-014: Dashboard `limit` parameter ignored
- **Severity:** Minor
- **Category:** Performance
- **Affected files:** `build-env/src/pages/Dashboard.jsx`, `includes/api/class-rest-controller.php::get_reports`
- **Steps to reproduce:** Load Dashboard.
- **Expected:** 5 recent reports.
- **Actual:** API ignores `limit`, loads default 50.
- **Root cause:** API expects `per_page` not `limit`.
- **Proposed fix:** Replace `limit` with `per_page` in client.
- **Regression tests:** Dashboard loads 5 reports.

### QAR-015: Reports page overfetches; filters client-side only
- **Severity:** Major
- **Category:** Performance/UX
- **Affected files:** `build-env/src/pages/Reports.jsx`, `build-env/src/hooks/useQueries.js`
- **Steps to reproduce:** Load Reports with a large dataset.
- **Expected:** Server-side pagination and filters.
- **Actual:** Full dataset fetched and filtered in browser.
- **Root cause:** `useReports()` called without filters; UI filters locally.
- **Proposed fix:** Pass filters and pagination params to API.
- **Regression tests:** Filters reduce network payload.

### QAR-016: Schools page overfetches; filters client-side only
- **Severity:** Major
- **Category:** Performance/UX
- **Affected files:** `build-env/src/pages/Schools.jsx`, `build-env/src/hooks/useQueries.js`
- **Steps to reproduce:** Load Schools with many entries.
- **Expected:** Server-side search and pagination.
- **Actual:** All schools fetched.
- **Root cause:** `useSchools()` called without filters and pagination.
- **Proposed fix:** Pass filters to API; use `per_page`/`page`.
- **Regression tests:** Search results should match server filtering.

### QAR-017: Settings endpoint open to report creators
- **Severity:** Critical
- **Category:** Security
- **Affected files:** `includes/api/class-rest-controller.php::check_settings_permission`, `update_settings`
- **Steps to reproduce:** Login as QA Officer; call `/settings`.
- **Expected:** 403 Forbidden.
- **Actual:** API secrets exposed.
- **Root cause:** `check_settings_permission` allows `cqa_create_reports`.
- **Proposed fix:** Restrict to `cqa_manage_settings` or `manage_options`.
- **Regression tests:** Verify access control by role.

### QAR-018: Conflict modal shows wrong updater
- **Severity:** Minor
- **Category:** UX
- **Affected files:** `includes/api/class-rest-controller.php::update_report`
- **Steps to reproduce:** Two users edit same report concurrently.
- **Expected:** Conflict shows last editor.
- **Actual:** Shows report author.
- **Root cause:** Uses `report->user_id` instead of last editor.
- **Proposed fix:** Track `updated_by` on save and use for conflicts.
- **Regression tests:** Simulate concurrent edits.

### QAR-019: PDF temp files never cleaned up
- **Severity:** Major
- **Category:** Performance/PDF
- **Affected files:** `includes/export/class-pdf-generator.php`
- **Steps to reproduce:** Generate PDFs repeatedly.
- **Expected:** Temp files removed.
- **Actual:** Files accumulate in `uploads/cqa-temp`.
- **Root cause:** No cleanup or reuse of files.
- **Proposed fix:** Add cron cleanup or reuse deterministic filenames.
- **Regression tests:** Generate PDF and verify cleanup job.

### QAR-020: Data integrity lacks FK constraints and cascades
- **Severity:** Major
- **Category:** Data
- **Affected files:** `includes/class-activator.php` (schema)
- **Steps to reproduce:** Delete a report with photos/responses.
- **Expected:** Related records removed or prevented.
- **Actual:** Orphaned rows possible.
- **Root cause:** No foreign keys or cascading delete enforcement.
- **Proposed fix:** Add FK constraints or ensure cascading delete in model layer.
- **Regression tests:** Delete report and verify DB integrity.

### QAR-021: Role label mismatch for Program Manager
- **Severity:** Minor
- **Category:** UX/Auth
- **Affected files:** `includes/class-activator.php`, `admin/class-admin-menu.php`, `public/class-frontend-controller.php`
- **Steps to reproduce:** Login as Program Manager.
- **Expected:** Role label shows “Program Manager”.
- **Actual:** Displays “User”.
- **Root cause:** Label key uses `cqa_program_management` vs role `cqa_program_manager`.
- **Proposed fix:** Align role slug in label maps.
- **Regression tests:** Login as Program Manager.

### QAR-022: Report creation lacks required field validation
- **Severity:** Major
- **Category:** Data/API
- **Affected files:** `includes/api/class-rest-controller.php::create_report`
- **Steps to reproduce:** POST `/reports` without `school_id` or `inspection_date`.
- **Expected:** 422 validation error.
- **Actual:** Report created with invalid/null fields.
- **Root cause:** No server-side validation.
- **Proposed fix:** Validate required fields and return structured errors.
- **Regression tests:** POST invalid payloads.

### QAR-023: Missing runtime error boundary causes white-screen on JS errors
- **Severity:** Major
- **Category:** Frontend/UX
- **Affected files:** `build/index.js` (React entry)
- **Steps to reproduce:**
  1. Trigger a React runtime error during rendering.
  2. Observe the app state.
- **Expected:** App shows a fallback error UI instead of a white screen.
- **Actual:** Unhandled error whitescreens the app.
- **Root cause:** No error boundary or runtime guard at the root.
- **Proposed fix:** Add an error boundary or global runtime guard to render a fallback UI on errors.
- **Regression tests:** Trigger a JS error and verify fallback UI renders.

### QAR-024: Recharts container renders at zero size, warns about width/height
- **Severity:** Minor
- **Category:** Frontend/UX
- **Affected files:** `build-env/src/components/dashboard/ComplianceChart.jsx`
- **Steps to reproduce:** Load the dashboard and check the console.
- **Expected:** No Recharts warnings; chart renders at a stable size.
- **Actual:** Warning about width/height <= 0.
- **Root cause:** Responsive container height is 100% while parent height can be 0 during initial layout.
- **Proposed fix:** Use a fixed height (e.g., 300px) or enforce min-height on container.
- **Regression tests:** Load dashboard; confirm no Recharts size warnings.

### QAR-025: Wizard step index out of range crashes report wizard
- **Severity:** Major
- **Category:** Frontend/Data
- **Affected files:** `build-env/src/components/wizard/ReportWizard.jsx`
- **Steps to reproduce:** Persist a wizard step outside the expected range, then reload the wizard.
- **Expected:** Wizard clamps to a valid step and loads.
- **Actual:** React error: `Cannot read properties of undefined (reading 'component')`.
- **Root cause:** `currentStep` is not clamped to the steps array length.
- **Proposed fix:** Clamp `currentStep` to valid bounds and recover if invalid.
- **Regression tests:** Open wizard with invalid persisted state and confirm recovery.

# QA Reports Audit — Fix Plan

## Phase 1 — Stabilize (Blockers/Criticals)
**Goal:** Restore core workflows (dashboard load, wizard save, photo evidence, settings protection).

### P1.1 Fix Dashboard Stats Failure (QAR-001)
- **Tasks**
  - Replace `rating` references with `overall_rating` in `/stats` SQL.
  - Normalize rating comparisons to enum values.
- **Acceptance Criteria**
  - `GET /cqa/v1/stats` returns 200 with populated compliance/trend/action items.

### P1.2 Fix Photo Upload/Permission (QAR-008, QAR-006, QAR-009)
- **Tasks**
  - Change permission check to `check_edit_reports_permission`.
  - Align response payload (`photos`) with UI or update UI to match.
  - Update UI to render `thumbnail_url`/`view_url`.
- **Acceptance Criteria**
  - Uploads succeed for QA Officer/Admin.
  - Uploaded photos immediately display in UI.

### P1.3 Fix Photo Delete Endpoint & Permissions (QAR-007, QAR-010)
- **Tasks**
  - Point UI to `/photos/{id}`.
  - Update `update_photo`/`delete_photo` permission callbacks to edit checks.
- **Acceptance Criteria**
  - Delete works for editors and fails for viewers.

### P1.4 Protect Settings (QAR-017)
- **Tasks**
  - Restrict settings to `cqa_manage_settings` or `manage_options`.
- **Acceptance Criteria**
  - Non-admin users receive 403 on `/settings`.

### P1.5 Fix Autosave Data Loss (QAR-004)
- **Tasks**
  - Pass `responses` and `photos` into `useAutoSave`, or combine into draft.
  - Ensure response updates `updated_at` correctly.
- **Acceptance Criteria**
  - Autosave preserves checklist/photos after reload.

---

## Phase 2 — Correctness (API Contract + Data Integrity)
**Goal:** Align FE/BE contract and enforce data integrity rules.

### P2.1 Normalize AI Summary Contract (QAR-002, QAR-012, QAR-013)
- **Tasks**
  - Fix prompt overwrite bug.
  - Update UI to read `executive_summary`.
  - Add API update for edited summary.
- **Acceptance Criteria**
  - AI summaries generate with report context and persist edits.

### P2.2 Fix Linking Rules (QAR-011)
- **Tasks**
  - Honor explicit “no link” sentinel.
- **Acceptance Criteria**
  - Reports created with no-link remain unlinked.

### P2.3 Add Required Field Validation (QAR-022)
- **Tasks**
  - Enforce `school_id` and `inspection_date` validation with 422 errors.
- **Acceptance Criteria**
  - Invalid POSTs rejected with clear errors.

### P2.4 Add Data Integrity Safeguards (QAR-020)
- **Tasks**
  - Implement cascading deletes in model layer or DB constraints.
- **Acceptance Criteria**
  - No orphaned photos/responses after report deletion.

---

## Phase 3 — Polish (UX + Performance)
**Goal:** Improve performance and UX for large datasets.

### P3.1 Server-side Filtering & Pagination (QAR-014, QAR-015, QAR-016)
- **Tasks**
  - Replace `limit` with `per_page` in Dashboard.
  - Pass filters/pagination to `useReports` and `useSchools`.
- **Acceptance Criteria**
  - Tables load with server-side pagination and search.

### P3.2 PDF Pipeline Cleanup (QAR-019)
- **Tasks**
  - Add cleanup cron for `uploads/cqa-temp`.
- **Acceptance Criteria**
  - Temp files are cleaned after PDF generation.

### P3.3 Role Label Consistency (QAR-021)
- **Tasks**
  - Align role label mapping for Program Manager.
- **Acceptance Criteria**
  - Role label displays correctly.

# QA Reports Audit — Root Cause Map

## Executive Root Cause Themes
1. **API contract drift** between React UI and REST responses (response shapes, endpoint names, field names).
2. **State fragmentation** in the React app (multiple auth stores, report data separated from wizard data).
3. **Loose permissions & missing validation** (settings exposed to report creators, photo mutations allowed for read-only users, missing required fields).
4. **Data model inconsistencies** (stats queries use columns not in schema, report linking sentinel not respected).
5. **Operational hygiene gaps** (PDF temp files left on disk).

---

## Dependency Map (Backend ↔ API ↔ Frontend)

```
+--------------------+       +----------------------+       +---------------------+
|   React UI         |       |   REST API           |       |   Data Storage       |
|  (build-env/src)   |<----->|  includes/api/*      |<----->|  wp_cqa_* tables     |
+--------------------+       +----------------------+       +---------------------+
        |                           |                               |
        |                           |                               |
        v                           v                               v
  Report Wizard               Report Model                     cqa_reports
  (ReportWizard +             (models/Report)                  cqa_responses
   Steps/Checklist/Photos)    + AI Summary                      cqa_photos
        |                     (AI/Executive_Summary)            cqa_ai_summaries
        v
  PDF Download
  (reports/:id/pdf)
```

### Key Breakpoints (root causes → symptoms)
- **API contract drift**
  - `StepPhotos` expects `response.data` and `photo.url` but API returns `photos` and `thumbnail_url` → **UI shows no photos**. (QAR-006, QAR-009)
  - Delete endpoint mismatch → **Delete fails (404)**. (QAR-007)
  - AI summary response expects `summary` but API provides `executive_summary` → **Blank summary UI**. (QAR-012)

- **State fragmentation**
  - `useAuthStore` exists twice; Dashboard uses a different store than App → **user/capability UI mismatch**. (QAR-003)
  - Wizard autosave uses `draft` only, ignoring store `responses/photos` → **silent data loss**. (QAR-004)

- **Loose permissions & validation**
  - Settings endpoint accessible by report creators → **API secrets exposed**. (QAR-017)
  - Photo update/delete uses read-only permission → **Unauthorized mutations**. (QAR-010)
  - Create report does not validate `school_id`/`inspection_date` → **invalid rows**. (QAR-022)

- **Data model inconsistencies**
  - `/stats` uses non-existent `rating` column → **API error & broken dashboard**. (QAR-001)
  - `previous_report_id=0` is auto-linked by backend → **linking logic mismatch**. (QAR-011)

- **Operational hygiene gaps**
  - PDF generator writes temp files with no cleanup → **disk bloat**. (QAR-019)

---

## Root Cause → Issue Mapping
- **Contract Drift** → QAR-006, QAR-007, QAR-009, QAR-012
- **State Fragmentation** → QAR-003, QAR-004, QAR-005
- **Permission/Validation Gaps** → QAR-008, QAR-010, QAR-017, QAR-022
- **Data Model Inconsistency** → QAR-001, QAR-011, QAR-018, QAR-020
- **Operational Hygiene** → QAR-019

---

## Architecture Inventory (Backend ↔ API ↔ Frontend ↔ Storage ↔ PDF)
### Backend Entry + Bootstrap
- Plugin root: `plugins/QA-Report-App/chroma-qa-reports/chroma-qa-reports.php`
- Core plugin class: `includes/class-plugin.php`

### REST Endpoints
- Defined in `includes/api/class-rest-controller.php` under namespace `cqa/v1`.

### AJAX (admin-ajax)
- `cqa_frontend_login` and `cqa_oauth_callback` in `public/class-frontend-controller.php`.

### DB Tables
- `wp_cqa_schools`, `wp_cqa_reports`, `wp_cqa_responses`, `wp_cqa_photos`, `wp_cqa_ai_summaries` in `includes/class-activator.php`.

### Frontend Routes/Screens
- React routes defined in `build-env/src/App.jsx`.

### PDF Pipeline
- Generator: `includes/export/class-pdf-generator.php`
- Entry point: REST `GET /reports/{id}/pdf`.

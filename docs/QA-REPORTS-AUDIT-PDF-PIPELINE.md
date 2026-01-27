# QA Reports Audit — PDF Pipeline

## Pipeline Overview
1. **Trigger**: `GET /cqa/v1/reports/{id}/pdf`
2. **Data Fetch**: Report model + checklist responses + photos + AI summary.
3. **Rendering**:
   - Build HTML in `PDF_Generator::get_report_html()`.
   - Render with `TCPDF` or `DOMPDF` if available.
   - Fallback: write HTML to temp file.
4. **Output**:
   - File stored under `uploads/cqa-temp/report-{id}-{timestamp}.{pdf|html}`.
   - Response streamed via `readfile()`.

---

## Inputs
- Report metadata: school, inspection date, report type, rating.
- Checklist responses: grouped by section.
- AI summary: `executive_summary`, `issues`, `poi`, `comparison`.
- Previous report (optional): comparison column and “improved/regressed” notes.
- Photos: not directly embedded in PDF HTML (no image placement logic seen in generator).

---

## Key Components
- **Entry:** `includes/api/class-rest-controller.php::generate_report_pdf`
- **Generator:** `includes/export/class-pdf-generator.php`

---

## Known Failure Modes
- **Library missing → HTML fallback:** If TCPDF/DOMPDF not installed, PDF output is HTML.
- **Temp file growth:** Files written to `uploads/cqa-temp` are never deleted (QAR-019).
- **Data mapping mismatches:** AI summary prompt issue (QAR-002) affects PDF summary content.

---

## Recommendations
1. **Enforce PDF engine availability** (ship TCPDF/DOMPDF or provide install check).
2. **Add cleanup job** to delete old `cqa-temp` files.
3. **Embed photo evidence** (if required) with proper file access and permissions.
4. **Return consistent response** for client to detect HTML fallback.

---

## Suggested Validation Steps
- Generate PDF for:
  - Report with previous report (comparison on).
  - Report without previous report (comparison off).
  - Report with/without AI summary.
- Validate no PHP warnings leak into PDF output.

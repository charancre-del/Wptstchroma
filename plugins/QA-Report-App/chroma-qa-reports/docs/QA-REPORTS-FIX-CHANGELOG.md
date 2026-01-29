# QA Reports Fix Changelog

Append-only log of all production fixes.

| Timestamp | FIX ID | QAR Refs | Files Touched | Summary |
|-----------|--------|----------|---------------|---------|
| 2026-01-27 | FIX-101 | QAR-001 | class-pdf-generator.php | Implemented graceful failure logic for missing libraries. |
| 2026-01-27 | FIX-102 | QAR-002, 013 | StepReview.jsx, StepAISummary.jsx | Standardized FE to use executive_summary sub-property. |
| 2026-01-27 | FIX-103 | QAR-003, 026 | class-checklist-response.php | Added START TRANSACTION / COMMIT to bulk_save. |
| 2026-01-27 | FIX-104 | QAR-012 | class-executive-summary.php | Added START TRANSACTION / COMMIT to save_summary. |

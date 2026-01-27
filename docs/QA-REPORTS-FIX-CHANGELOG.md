# QA Reports Fix Changelog (Append-only)

## 2026-01-27T09:42:01Z — Checkpoint 0 (Make it Run)
- **Files touched:**
  - `plugins/QA-Report-App/chroma-qa-reports/public/js/cqa-runtime-guard.js`
  - `plugins/QA-Report-App/chroma-qa-reports/admin/class-admin-menu.php`
  - `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php`
- **Summary:**
  - Added a runtime guard to prevent white-screen failures by rendering a fallback UI on JS errors (QAR-023).
  - Added debug-mode API request/response logging (toggled via `localStorage.cqaDebug=true`).
  - Normalized empty hash routes by forcing `#/` for the HashRouter baseline.

## 2026-01-27T09:52:21Z — Checkpoint 0 (Make it Run)
- **Files touched:**
  - `plugins/QA-Report-App/chroma-qa-reports/build-env/src/components/dashboard/ComplianceChart.jsx`
  - `plugins/QA-Report-App/chroma-qa-reports/build-env/src/components/wizard/ReportWizard.jsx`
  - `plugins/QA-Report-App/chroma-qa-reports/public/js/cqa-runtime-guard.js`
  - `plugins/QA-Report-App/chroma-qa-reports/admin/css/admin-styles.css`
  - `plugins/QA-Report-App/chroma-qa-reports/public/css/frontend-styles.css`
- **Summary:**
  - Added step clamping to prevent wizard crashes from invalid persisted state (QAR-025).
  - Fixed Recharts sizing warning by enforcing a stable chart height (QAR-024).
  - Added runtime guard validation for persisted wizard state and ensured chart containers have min-height.

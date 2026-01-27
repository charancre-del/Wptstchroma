# QA Reports Fix Verification Scripts

## Checkpoint 0 — Make it Run

### 1) Admin App Load
1. Open `wp-admin/admin.php?page=chroma-qa-reports`.
2. Confirm the React app loads without a white screen.
3. If a JS error occurs, confirm the fallback error panel is rendered.

### 2) Frontend Shell Load
1. Open `/qa-reports/` while logged in.
2. Confirm the React app loads without a white screen.
3. Confirm the hash is set to `#/` if initially empty.

### 3) Debug Mode Logging (Dev Only)
1. In browser console, set `localStorage.cqaDebug = "true"` and refresh.
2. Confirm API requests/responses log with `[CQA API Request]` / `[CQA API Response]`.
3. Set `localStorage.cqaDebug = "false"` to disable logging.

### 4) Dashboard Chart Size Warning
1. Open the dashboard and check the browser console.
2. Confirm no Recharts warnings about width/height <= 0.

### 5) Wizard Step Recovery
1. Set an invalid wizard step in localStorage (`cqa-wizard-draft`) and reload the wizard page.
2. Confirm the wizard loads without a React error and resets to a valid step.

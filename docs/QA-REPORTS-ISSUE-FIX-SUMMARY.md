# QA Reports Issue Fix Summary

## Scope
This artifact records the fixes implemented for Checkpoint 0 observability and runtime safety in the QA Reports plugin.

## Issues Addressed

### Debug-mode visibility gaps
**Problem:** Front-end and admin views could not consistently enable debug logging from the plugin’s debug flag.

**Fix:** Exposed the `debug` flag in localized data for admin scripts, wizard scripts, and the React app in admin + frontend contexts, so runtime guard logging can be toggled from `CQA_DEBUG`.

**Files updated:**
- `plugins/QA-Report-App/chroma-qa-reports/admin/class-admin-menu.php`
- `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php`

### Missing runtime error visibility and redaction
**Problem:** Runtime errors and REST requests in the React app were not logged with stack traces or safe redaction, making debugging difficult and potentially exposing sensitive information.

**Fix:** The runtime guard now:
- Logs errors with stack traces in debug mode.
- Logs REST API requests/responses with redacted headers/bodies.
- Continues clamping wizard draft state before React load to prevent invalid steps.

**Files updated:**
- `plugins/QA-Report-App/chroma-qa-reports/public/js/cqa-runtime-guard.js`

### REST API errors not captured server-side
**Problem:** REST errors were not logged server-side in debug mode, limiting visibility into failed requests.

**Fix:** Added REST error logging behind `CQA_DEBUG` with sanitization for sensitive fields before writing to `error_log`.

**Files updated:**
- `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php`

## Summary of Changes
- Added debug flag exposure for admin + frontend localized data.
- Added runtime guard logging with safe redaction and stack trace capture.
- Added server-side REST error logging in debug mode with sanitized payloads.

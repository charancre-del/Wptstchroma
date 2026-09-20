# Backup Care Backend Readiness

Date: 2026-08-20

Decision: **BACKEND UPDATED / LIVE WEBSITE UNCHANGED / STAGING PAYMENT ACCEPTANCE BLOCKED**

## Completed

- One order supports multiple children and dates at $115 per child-date.
- Two children across two dates creates four fixed $115 line items on one $460 GHL invoice.
- The invoice uses Stripe connected inside the GHL sub-account; WordPress no longer requires or loads a Stripe key or webhook secret.
- Payment fulfillment polls GHL every five minutes and requires exact invoice ID, paid status, contact, test/live mode, currency, and total readback.
- All 24 campus calendars are active with exactly one assigned campus user.
- Four appointments are created only after full payment and are idempotent across retries.
- Families choose directly from the configured campus list. Backup Care has no geolocation or Google geocoding route.
- Children are eligible from 6 weeks old through the day before the 13th birthday on each care date.
- Eligible family cancellations create a $115-per-unit refund work item for `billing@chromaela.com`; refunds are processed in GHL Payments.
- If Chroma closes a campus after payment, the parent chooses a full refund or free reschedule for each affected unit.
- All eight Backup Care workflows remain verified Draft with zero automatic triggers.
- 27 Python tests and eight PHP suites pass. The removed ninth PHP suite covered Google geocoding, which is no longer part of Backup Care.
- The private Services v2 pilot remains private. No website page, workflow, or live payment was activated.

## Still Required

1. Restore staging WordPress SSH access or add one of the current local public keys to the staging account. The expected `wordpress` private key is absent from both Windows and WSL; the remaining Windows keys and WSL `hostinger_openclaw` key are rejected by `x3yyadl@131.153.236.189`.
2. Install the prepared theme/plugin on staging only and set `CHROMA_BACKUP_CARE_GHL_TOKEN` in staging server configuration.
3. Send one $460 GHL test invoice to `charancre@gmail.com`, pay it through GHL's connected Stripe test mode, and verify four appointments plus GHL records and emails.
4. Run one GHL Payments partial-refund test and one reschedule test.
5. Load and test the 12-month closure calendar and the parent-choice closure path.
6. Archive or disable the legacy Booking Terms form before launch.
7. Complete final policy/legal approval and licensing/staffing attestation.

Evidence:

- `reports/backup-care-calendar-matrix-acceptance-20260820.json`
- `reports/backup-care-workflow-ui-readback-20260820.json`
- `reports/backup-care-runtime-preflight-20260820.json`
- `tests/php/backup-care-clients-test.php`
- `tests/php/backup-care-service-test.php`

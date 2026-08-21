# Backup Care Integration

## Release Boundary

The backend, GHL configuration, theme templates, and test harness are prepared. Nothing in this package authorizes publication.

- Do not activate checkout on the live site.
- Do not publish the private GHL Services v2 pilot.
- Keep all eight GHL workflows in Draft.
- Do not store the GHL private-integration token in Git or WordPress options.
- Use a staging WordPress install and a GHL test-mode invoice for the remaining acceptance run.

The native GHL Services v2 widget is not the launch checkout. Services v2 can create four separate bookings for two children across two dates, but it cannot turn those arbitrary dates into one family payment. The supported path is the `chroma-backup-care` WordPress coordinator, native GHL forms and Custom Objects, one GHL invoice paid through GHL's connected Stripe account, and one ordinary GHL campus-calendar appointment per paid child-date.

## Confirmed Rules

- $115 per child per care date, all-in
- Weekday care at any configured Chroma campus
- Any family is eligible; no ordinary campus approval
- Children must be 6 weeks through 12 years old on each care date
- Up to 365 days ahead
- At least 120 minutes' notice
- Same-day booking deadline at 7:30 AM
- Drop-off no later than 9:30 AM
- Multiple children and up to 31 dates in one order
- Full payment before confirmation
- Booking ceiling of 100 child-date units per campus/date
- Refundable cancellation and rescheduling until 72 hours before care
- No discretionary late exceptions
- Campus and `info@chromaela.com` notifications
- Refund operations routed to `billing@chromaela.com`

Commercial eligibility does not waive enrollment completeness, health preparation, licensing, staffing, or accommodation obligations. Medication, special-procedure, and accommodation answers create a non-blocking safety-review alert without putting medical details in email.

## System Ownership

| Concern | Owner |
| --- | --- |
| Public cart and rule enforcement | WordPress plugin |
| Short-lived capacity holds and invoice reconciliation ledger | WordPress database |
| Parent and child records | GHL, after parent email verification |
| Orders and child-date attendance readback | GHL Custom Objects |
| Operational child-date appointments | GHL campus calendars |
| Closures | GHL Backup Care Closure object |
| Payment | GHL invoice using Stripe connected inside GHL |
| Parent email verification code | WordPress transactional mail |
| Draft workflow enrollment | GHL |
| Campus, central, and billing email | WordPress mail transport |

The browser never supplies a trusted total. Before any child lookup, the coordinator requires a short-lived verification token proving control of the parent's email. Existing child records are reusable only when GHL also confirms the configured parent-to-child relationship. The coordinator prices every child/date unit from the manifest, signs the quote, reserves capacity under a database lock, creates one GHL invoice, and sends it from the selected campus user. Fulfillment requires GHL readback of the exact invoice ID, paid status, test/live mode, contact, amount, and currency plus a fresh parent/child relationship readback.

The encrypted family payload remains in WordPress only while payment fulfillment and idempotent notifications are incomplete. A notification transport failure leaves invoice reconciliation retryable without repeating GHL fulfillment. Calendar projection uses stable attendance IDs to recover an existing appointment before creating one, and stores the GHL event ID in both the WordPress hold ledger and GHL Attendance object. After notifications succeed, WordPress erases the encrypted payload and retains only the transaction, capacity, relationship, appointment, and audit identifiers required to manage the order.

## GHL Data Model

The standard Contact object is the parent or guardian. Four Custom Objects are live:

1. `custom_objects.backup_care_child`
2. `custom_objects.backup_care_order`
3. `custom_objects.backup_care_attendance`
4. `custom_objects.backup_care_closure`

Confirmed unique rules:

- Child: `child_record_key`
- Order: `order_id`
- Order: `client_request_id`
- Attendance: `attendance_id`
- Closure: `closure_key`

The first four are the requested booking-record uniqueness rules. Closure adds a fifth operational uniqueness rule.

Confirmed one-to-many relationships:

- Contact to children
- Contact to orders
- Order to attendance units
- Child to attendance units

Live IDs and readback evidence are in `infrastructure/ghl/backup-care/manifest.json` and `reports/backup-care-ghl-acceptance-20260819.json`.

## Native GHL Forms

Three native forms were saved in GHL:

- `BC | Family Profile` (`gH2g9JDSqv0EhBPuLk2n`)
- `BC | Child Enrollment` (`JSSCutlpdu1QdvPvq18d`)
- `BC | Booking Terms` (`0bn3ZGjBmOjWLMr5cb39`) - legacy, retirement required

Only Family Profile and Child Enrollment are public intake forms. The Child Enrollment handoff puts only an opaque server-keyed child record key in the URL; names, email, phone, birth date, health state, and payment state are never query parameters. The coordinator verifies every required enrollment field, affirmative emergency-medical authorization, verified parent email, and the GHL parent-to-child relationship before treating a record as bookable.

Booking Terms is not public configuration and is not authoritative. It must be archived or disabled before launch because its legacy fields can accept client-supplied order/payment state. Policy acceptance is captured in the signed WordPress order and GHL order records are written only after verified GHL invoice payment. The retirement checklist is `infrastructure/ghl/backup-care/booking-terms-retirement-plan.json`.

Original form payloads are preserved under `infrastructure/ghl/backup-care/form-backups/20260819/`.

## Calendar Projection

All 24 configured campus calendars are active and each has exactly one assigned campus user. A temporary Grayson acceptance run created four confirmed appointments for two children across two dates, read them back, and deleted all four. API writes used `toNotify=false`, so the test sent no GHL workflow or calendar notifications. Cancellation deletes the matching appointment; rescheduling moves it and keeps the same event count.

Evidence is in `reports/backup-care-calendar-inventory-20260820.json`, `reports/backup-care-calendar-matrix-acceptance-20260820.json`, and `reports/backup-care-calendar-field-20260820.json`.

## Draft Workflows

The signed-in GHL workflow builder was read back on August 20. All eight expected workflow IDs exist, remain Draft, have zero automatic triggers, and contain exactly one event-specific Add Contact Tag action:

1. Paid booking
2. Payment failed or expired
3. Mandatory record review
4. Eligible cancellation
5. Late cancellation
6. Eligible reschedule
7. Late reschedule
8. Arrival reminder

Operational emails are sent by coordinator handlers with idempotency keys. Keep these workflows inactive until staging proves that coordinator enrollment and future workflow content do not duplicate messages. Draft manifest workflows are not enrolled; enrollment starts only when an active workflow ID is explicitly configured or an approved release changes its manifest status to `active`. The official Workflows list API still omits these draft IDs; the UI evidence is recorded in `reports/backup-care-workflow-ui-readback-20260820.json`.

Eligible cancellations remove the affected appointment and create a GHL refund work item for `billing@chromaela.com`. Full or partial refunds are issued from GHL Payments so the transaction and connected Stripe account remain synchronized. For a Chroma-initiated closure, each affected unit is held in `closure_choice_pending` until the parent chooses a refund or free reschedule.

## WordPress Package

Plugin: `chroma-plugins/chroma-backup-care/`

- `[chroma_backup_care_cart]` renders multi-child/multi-date booking.
- `[chroma_backup_care_cart campus="grayson"]` preselects a campus.
- `[chroma_backup_care_manage]` renders token-protected cancellation and rescheduling.
- Families choose from the configured Chroma campus list. Backup Care has no browser geolocation or Google geocoding route.
- Secrets are accepted only from constants or environment variables.
- Checkout is disabled unless mode, feature flag, and the GHL token pass readiness checks.
- Live mode additionally requires every required manifest gate to be complete, `live_changes_allowed` to be true in the reviewed release manifest, and `CHROMA_BACKUP_CARE_LIVE_APPROVED` to be the boolean `true` in server configuration.

Theme source:

- `page-backup-care.php`
- `page-backup-care-confirmation.php`
- `page-backup-care-manage.php`
- `template-parts/home/backup-care.php`
- `template-parts/location/backup-care.php`

Location links pass a validated campus ID so the booking page opens with that campus selected.

## Staging Setup

1. Install the V2 theme and `chroma-backup-care` plugin on staging.
2. Preview page creation with `wp chroma backup-care provision-pages`, then create the three slug-routed pages with `--apply --confirm=PROVISION_BACKUP_CARE_STAGING_PAGES --status=publish`.
3. Supply `CHROMA_BACKUP_CARE_GHL_TOKEN` through server configuration. No direct Stripe key or webhook secret is used by the plugin.
4. Set plugin mode to Test and leave checkout disabled.
5. Confirm GHL invoice Test Mode and connected Stripe are enabled in the Chroma sub-account.
6. Fill `infrastructure/ghl/backup-care/closures.template.csv`, validate it locally, then apply it with the explicit closure confirmation phrase. The loader derives `campus_id__YYYY-MM-DD` or `all__YYYY-MM-DD` keys and performs exact GHL readback.
7. Prove parent email code delivery, expiry, single use, and the GHL parent-to-child authorization readback.
8. Archive or disable the legacy Booking Terms form and verify its widget no longer accepts submissions.
9. Send one four-unit, $460 GHL test invoice to `charancre@gmail.com`; pay it in test mode and prove exact invoice readback, four-calendar-event projection, partial cancellation/refund work item, and reschedule acceptance.
10. Verify GHL Contact, Child, Order, Attendance, associations, calendar events, campus email, central email, and billing email readback.
11. Enable staging checkout only for the acceptance window with `wp chroma backup-care enable-test --confirm=ENABLE_BACKUP_CARE_TEST_CHECKOUT`; disable it immediately afterward with `wp chroma backup-care disable`.

Secret-free staging status:

```bash
wp chroma backup-care status
```

Read-only impact report before adding a closure:

```bash
wp chroma backup-care closure-impact --date=2026-12-24 --campus=all
```

Closure validation and apply:

```bash
python scripts/backup_care_closures.py validate \
  --csv infrastructure/ghl/backup-care/closures.csv \
  --manifest infrastructure/ghl/backup-care/manifest.json

python scripts/backup_care_closures.py apply \
  --csv infrastructure/ghl/backup-care/closures.csv \
  --manifest infrastructure/ghl/backup-care/manifest.json \
  --confirm APPLY_BACKUP_CARE_CLOSURES
```

## Verification

```powershell
python -m unittest discover -s tests -p "test_backup_care*.py"
```

```bash
php tests/php/backup-care-domain-test.php
php tests/php/backup-care-config-test.php
php tests/php/backup-care-cli-test.php
php tests/php/backup-care-clients-test.php
php tests/php/backup-care-store-test.php
php tests/php/backup-care-ghl-client-test.php
php tests/php/backup-care-service-test.php
php tests/php/backup-care-rest-test.php
```

The service harness covers pre-I/O payload bounds, verified parent authorization, a two-child/two-date $460 GHL invoice, changed-payload replay rejection, exact paid-invoice readback, four GHL attendance units, four GHL calendar events, refund work-item creation, event-preserving rescheduling, age eligibility, and the 72-hour denial rule. These deterministic contract tests are not a substitute for the final GHL invoice test-mode transaction on staging.

## Remaining Inputs

- Staging WordPress deployment target and admin/deploy access
- Closure dates, campus scope, and reasons
- Final legal/policy copy approval
- Licensing and staffing-ratio attestation for the 100-unit technical ceiling
- Confirmation of staging email transport and safe test recipients
- Approved sender identity for parent verification codes
- Authorization to archive or disable the legacy GHL Booking Terms form
- Confirmation that campus email data has not changed

## References

- HighLevel record search: https://marketplace.gohighlevel.com/docs/ghl/objects/search-object-records/
- HighLevel record update: https://marketplace.gohighlevel.com/docs/ghl/objects/update-object-record/
- HighLevel record lookup: https://marketplace.gohighlevel.com/docs/2021-04-15/ghl/objects/get-record-by-id/
- HighLevel create appointment: https://marketplace.gohighlevel.com/docs/ghl/calendars/create-appointment/
- HighLevel update appointment: https://marketplace.gohighlevel.com/docs/ghl/calendars/edit-appointment/
- HighLevel delete event: https://marketplace.gohighlevel.com/docs/ghl/calendars/delete-event/
- HighLevel create invoice: https://marketplace.gohighlevel.com/docs/ghl/invoices/create-invoice/
- HighLevel send invoice: https://marketplace.gohighlevel.com/docs/ghl/invoices/send-invoice/
- HighLevel get invoice: https://marketplace.gohighlevel.com/docs/ghl/invoices/get-invoice/

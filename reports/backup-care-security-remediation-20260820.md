# Backup Care Security Remediation

Date: 2026-08-20
Branch: `codex/theme-redesign`
Security scan: `b56a81cb-575d-420d-9074-25d903b84109`
Frozen snapshot: `codex-security-snapshot/v1:sha256:1298c67828d74ee89a2bc234196456d450a1194140295dcac9768385eaa9da24`

The frozen 55-file working-tree review reported four medium and four low findings. The source remediations below were applied after that snapshot. Nothing was deployed to WordPress, no GHL workflow was activated, and no live GHL mutation was performed during remediation.

## Remediation Matrix

| Finding | Remediation | Verification |
| --- | --- | --- |
| Existing child reuse relied on family PII | Added short-lived email-code verification and required the exact GHL Contact-to-Child association before reuse, then rechecked both after payment. | PHP REST, service, and GHL client suites |
| Child lookup fanout preceded payload validation | Added pure order preflight enforcing child count, attendance count, distinct-date count, shape, format, and allowed fields before any GHL call. | Service tests assert zero GHL calls for oversized child and date payloads |
| Closure lookup fanout preceded date validation | Moved date and cardinality checks into the same pre-I/O preflight. | Domain and service suites |
| Google geocoding limit was non-atomic | Replaced transient read/write limiting with a database table, named lock, and atomic bucket update; raw IP and email values are not stored. | Store and REST threshold/reset tests |
| Quote response exposed enrollment state | Unverified requests cannot query GHL; missing, incomplete, and unauthorized child records now use one enrollment-required response shape. | Service authorization and response-shape tests |
| Family and child PII appeared in enrollment URLs | Enrollment handoff now carries only the opaque `child_record_key`; family intake uses a bare form URL. | Python manifest test and JavaScript static check |
| All-campus closure key did not match readers | Canonicalized `all` while retaining legacy `*` compatibility in the PHP and Python readers. | PHP domain/GHL tests and Python closure/order tests |
| Public Booking Terms form could forge payment state | Removed it from public configuration and embeds, made WordPress plus verified Stripe events authoritative, preserved a rollback export, and added a required retirement gate. | PHP config tests and Python manifest tests |

## Additional Operator Hardening

- The GHL service provisioner now refuses mutations unless both release gates explicitly allow live changes and calendar provisioning.
- The provisioner verifies that the credential's GHL location matches the reviewed manifest before any mutation.
- Tests prove false gates and a wrong tenant produce zero mutations.

## Current Verification

- Nine PHP suites passed.
- All 31 changed PHP files linted successfully.
- 25 Python tests passed.
- Four JavaScript files passed syntax checking.
- All 15 changed JSON artifacts parsed successfully.
- Theme production build passed and wrote nine asset mappings.
- Desktop 1440x900 and mobile 390x844 cart acceptance passed with zero horizontal overflow and zero console errors.

## Remaining Security Gates

- Archive or disable GHL form `0bn3ZGjBmOjWLMr5cb39` and verify that its public widget can no longer submit. The source no longer exposes or trusts it, but the live GHL artifact still exists.
- Prove verification-code delivery, expiry, replay rejection, and approved sender identity on staging email transport.
- Complete a Stripe test-mode multi-child/multi-date transaction on staging and read back Stripe, WordPress ledger, GHL records, associations, and recipient emails.
- Keep checkout disabled and all eight GHL workflows inactive until these gates pass.

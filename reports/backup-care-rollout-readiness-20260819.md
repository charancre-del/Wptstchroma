# Backup Care Rollout Readiness

Date: 2026-08-19
Branch: `codex/theme-redesign`
Status: **SUPERSEDED**

This snapshot is superseded by `reports/backup-care-backend-readiness-20260820.md` and manifest schema 10.

Current decisions:

- Payment is one GHL invoice using Stripe connected inside GHL.
- Direct Stripe Checkout keys and webhooks are not used by WordPress.
- Families choose a campus from the configured list; there is no Google geocoding.
- Eligible ages are 6 weeks through 12 years on the care date.
- Chroma-initiated closures give the parent a refund or free-reschedule choice.
- Nothing has been activated on the live WordPress website.

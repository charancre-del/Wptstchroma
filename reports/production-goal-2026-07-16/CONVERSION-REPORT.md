# Conversion and Journey Audit - Current Staging Status

> **Superseded:** Current post-fix conversion readiness and external GHL proof gates are recorded in `STAGING-FINAL-READINESS-2026-07-16.md`.

**Audit date:** 2026-07-16
**Target:** Staging only
**Production rule:** Do not alter live forms, CTAs, routing, tracking, or lead workflows.

## Decision

**Needs fix and end-to-end proof.** The current Contact Us mismatch is a release blocker. Other responsive journey findings remain open until the current staging build is tested across devices. No form submission, lead creation, notification, or analytics event was triggered during this reconciliation.

## Current Findings

### Contact Us - Open P1

- Current staging returns 200 with one H1.
- The page presents a general contact intent, including "Send us a note."
- The embedded iframe identifies itself as `PARENT INFORMATION - Chroma Early Learning`.
- The current form ID is `848tl2LjoZVsUIhhNOxd`.
- The page also contains tour-booking language and a booking iframe shell.
- Its sampled canonical points to the live Contact URL, not the staging host.

**Required proof:** Correct general-inquiry form, matching labels and privacy language, keyboard/mobile submission, success/failure states, routing, notification, source attribution, lead readback, and duplicate handling.

### Schedule a Tour - Retest Required

- `/schedule-a-tour/` currently returns 200 with one H1 and a staging self-canonical.
- Prior audit evidence found excessive selector/form scrolling at tablet/mobile widths.
- No current campus-selection or submission flow was completed.

**Required proof:** Campus search/selection, consent visibility, keyboard and touch flow, submit, failure recovery, success state, lead delivery, source/campus attribution, and audit logging.

### Legacy Tour Route - Validated on Staging

- `/schedule-tour/` currently returns a single 301 to `/schedule-a-tour/`.
- The current sampled Summer Camp HTML did not contain an old `/schedule-tour/` href.
- This does not prove the visual CTA is present, correctly labeled, or production-ready.

### Programs - Retest Required

The prior audit found the first mobile tour CTA too low in the journey. Current CTA position and interaction were not revalidated.

### Locations - Retest Required

The prior audit found tablet friction in the map-first/card presentation. Current geolocation permission, filters, cards, markers, fly-to behavior, popup containment, and non-map fallback were not revalidated.

## Journey Requirements

| Intent | Primary action | Required evidence |
| --- | --- | --- |
| Brand/home | Find a campus | Accurate trust copy, working location discovery, measurable click |
| Program research | Select age/program fit | Program detail, schedule/curriculum, early relevant CTA |
| Location research | View and contact a campus | Accurate address, phone, hours, programs, map/card parity |
| Parent question | Reach the correct team | Current policies/app/hours and department routing |
| General inquiry | Send a message | General form, confirmation, delivery, privacy and source proof |
| Tour/enrollment | Choose campus and submit | Valid selector, consent, confirmation, lead and notification proof |

## Form QA Matrix

For every staging form:

1. Match visible purpose, fields, destination, and confirmation.
2. Verify labels, names, instructions, required states, validation, and consent.
3. Complete keyboard-only, mobile, and tablet submissions.
4. Verify submit controls remain visible and scroll containment does not trap users.
5. Capture success, client/server failure, timeout, and duplicate-submission behavior.
6. Confirm lead delivery, source attribution, campus mapping, notification, and audit log.
7. Confirm privacy language matches actual processing and third-party embeds.
8. Confirm analytics fires only on the intended milestone and excludes false successes.

## Measurement

Do not claim conversion improvement without a baseline and validated events for campus search, geolocation outcome, card clicks, program selection, CTA clicks, form starts, validation errors, submissions, confirmed lead delivery, phone/email clicks, route, and device category.

## Readiness

**Status: Needs Fix.** The Contact form-purpose mismatch is current. The tour redirect is a narrow staging positive, while responsive journeys and delivery/analytics remain unverified.

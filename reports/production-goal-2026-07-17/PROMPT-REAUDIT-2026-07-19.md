# Governing Prompt Re-Audit

**Target:** staging only  
**Prompt reviewed:** `FULL-GOVERNING-PROMPT.md` (1,457 lines, 25 phases)  
**Requirement register:** `REQUIREMENT-EVIDENCE-MATRIX.csv` (1,010 extracted statements)  
**Re-audit date:** 2026-07-19

## Why this re-audit was required

The prior phase-level reports were too broad. The requirement matrix repeatedly states that exact item-level confirmation is still required, so the matrix cannot by itself prove that every prompt statement is complete. A manual visual review also found two regressions that the earlier automated pass did not identify clearly enough:

1. The mobile review carousel measured a stretched slide and retained excessive blank height.
2. Curriculum Playfair headings declared weight `500`, while the rest of the approved site-wide editorial system declared weight `400`.

Both theme defects were corrected and deployed to staging before the final re-audit.

## Corrected staging verification

The corrected QA run covered 27 representative template routes at six viewports: desktop, laptop, tablet landscape, tablet portrait, small tablet, and mobile.

| Check | Result |
| --- | ---: |
| Route/device checks | 162 |
| HTTP failures | 0 |
| Horizontal overflow | 0 |
| Broken images | 0 |
| Clipped text | 0 |
| Body-font mismatches | 0 |
| Unexpected fonts | 0 |
| Legacy serif fallbacks | 0 |
| Editorial font mismatches | 0 |
| Unavailable required fonts | 0 |
| Undersized text | 0 |
| Oversized text | 0 |
| Missing footers | 0 |

Interaction checks also passed:

- `Use My Location` sorted campuses by distance and displayed a mileage value.
- Clicking a campus card opened a complete map popup with campus name, city, address, phone, email, and campus link.
- The Curriculum program chart exposed all 11 program tabs.

Evidence: `qa/final-reaudit-20260719/` and `qa/curriculum-font-final-20260719/`.

## Flag classification

- **Oversized sections:** 67 route/device flags are long editorial, archive, team, calendar, FAQ, story, or legal sections. They do not contain clipping or overflow and preserve content the owner required us not to remove.
- **Fixed obstructions:** the campus tour panel and full-screen Parent Portal application are intentional component behavior. The Parent Portal screenshot renders correctly.
- **Unreachable footer:** limited to the six Parent Portal checks because the full-screen application owns the viewport. The footer is present.
- **Console findings:** limited to the deliberately requested 404 route.
- **Request failures:** aborted Google Analytics collection requests in headless Chrome; no visible theme regression.
- **Chat overlap:** the third-party chat teaser can overlay mobile content when expanded. This is vendor markup, not theme-owned markup.

## What is verified complete

- Current theme-owned responsive layout integrity for the tested template matrix.
- Current staging typography consistency: Outfit body copy and Playfair Display editorial headings, including Curriculum.
- Current theme-owned review carousel sizing and navigation behavior.
- Current location permission, distance sorting, region filtering, map focus, and popup behavior.
- Current Curriculum program slider structure and chart availability.
- Current build output, manifest wiring, and staging-only asset deployment.

## What is not honestly 100% complete

These governing-prompt gates remain external, production-only, owner-deferred, or require authenticated/manual evidence:

- Authenticated GA4/GTM conversion and attribution verification.
- Search Console, GBP, production redirect, canonical, sitemap, robots, CDN/cache, and post-cutover validation.
- Safari/iOS and manual screen-reader verification.
- Third-party chat-widget overlap/landmark acceptance or vendor correction.
- Production field performance and INP/RUM evidence.
- Owner-deferred Careers policy, Parent Resources, legal approval, and Early Start operating details.
- Ongoing factual maintenance for campus, program, tuition, availability, credentials, and operational claims.

## Completion standard going forward

Do not state that every prompt phase is fully complete merely because it appears in the matrix. Theme-owned work may be called staging-verified only when implementation, route/device evidence, and interaction evidence exist. External or waived items remain explicitly deferred until their own proof exists.

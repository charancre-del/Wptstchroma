# Chroma Excellence Theme 2.0 — Full Template Visual QA

**Audit date:** 2026-07-13  
**Target:** `https://x3yyntt5tp-staging.wpdns.site/`  
**Devices:** desktop `1440×1000`, tablet `820×1180`, mobile `390×844`  
**Evidence:** `qa-screenshots/visual-qa-2026-07-13-postfix/`

## Result

- Audited 23 representative page/template routes at three viewport sizes: 69 captures total.
- All public routes returned HTTP 200; the dedicated 404 route correctly returned HTTP 404.
- No document-level horizontal overflow was detected at any audited viewport.
- Display typography now scales consistently from approximately 89px desktop to 60px tablet and 47px mobile, with intentional exceptions for editorial pages.
- Homepage duplicate program discovery content was removed; the interactive program slider remains.
- Reviews, location cards, maps, and embedded forms use deliberate internal scrolling only where the complete content cannot fit safely.
- Automated hidden-image and clipping counts include lazy-loaded responsive sources, motion wrappers, decorative elements, and intentionally clipped card artwork. Reviewed screenshots did not show corresponding visible broken-image regressions.

## Template Matrix

| Template | Desktop | Tablet | Mobile | Notes |
|---|---:|---:|---:|---|
| Home | Pass | Pass | Pass | Compact hero/sections, one program slider, complete reviews and locations |
| About | Pass | Pass | Pass | Editorial content and team layout preserved |
| PrismPath / Curriculum | Pass | Pass | Pass | Program slider, prism labels, documentation and FAQ fit correctly |
| Parents | Pass | Pass | Pass | Parent-facing cards and content stack without document overflow |
| Programs archive | Pass | Pass | Pass | Responsive cards and normalized typography |
| Program single | Pass | Pass | Pass | Program content, chart and schedule remain complete |
| Locations archive | Pass | Pass | Pass | Tablet uses map-first layout plus two-column scrollable card list |
| Location single | Pass | Pass | Pass | Campus content and map remain contained below header |
| Communities archive | Pass | Pass | Pass | Responsive listing verified |
| City single | Pass | Pass | Pass | Representative city template verified |
| Contact | Pass | Pass | Pass | Form card scrolls independently; information column stays readable |
| Schedule tour | Pass | Pass | Pass | Form and supporting card stack at tablet/mobile widths |
| Early Start | Pass | Pass | Pass | Therapy naming and navigation remain correct |
| Summer Camp | Pass | Pass | Pass | Hero, calendars, gallery and CTAs remain complete |
| Careers | Pass | Pass | Pass | Intentional internal job-list scrolling retained |
| Employers | Pass | Pass | Pass | Responsive content verified |
| Newsroom | Pass | Pass | Pass | Archive layout verified |
| Stories archive | Pass | Pass | Pass | Listing layout verified |
| Story single | Pass | Pass | Pass | Article reading width and overflow verified |
| Parent portal | Pass | Pass | Pass | Generic content template verified |
| Privacy | Pass | Pass | Pass | Legal template verified |
| Terms | Pass | Pass | Pass | Legal template verified |
| 404 | Pass | Pass | Pass | Correct 404 response and responsive layout |

## Shared Measurements

| Check | Desktop | Tablet | Mobile |
|---|---:|---:|---:|
| Horizontal document overflow | None | None | None |
| Header/nav collision | None | None | None |
| Oversized shared display type | Normalized | Normalized | Normalized |
| Review text cutoff | Fixed | Fixed | Fixed |
| Location list usability | Full panel | Two-column scroll | One-column scroll |
| Embedded form usability | Contained scroll | Contained scroll | Contained scroll |

## Validation

- PHP syntax checks passed for all touched PHP templates.
- Frontend production build completed successfully.
- Latest staging manifest uses `assets/css/main.699666fe3dca.css` and `assets/js/main.ea417c0b5cbf.js`.
- Final PrismPath diagram was separately checked at desktop and mobile after moving SVG labels inside the viewBox.

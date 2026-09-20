# Staging Launch-Gate QA

**Date:** 2026-07-20
**Target:** `https://x3yyntt5tp-staging.wpdns.site/`
**Production:** Untouched

## Browser Matrix

| Browser | Routes | Viewports | Checks | Failures |
| --- | ---: | ---: | ---: | ---: |
| Google Chrome | 27 | 6 | 162 | 0 |
| Microsoft Edge | 10 high-risk templates | 3 | 30 | 0 |

Chrome viewports: desktop, laptop, tablet landscape, tablet portrait, small tablet, and mobile. Edge covered desktop, tablet portrait, and mobile on the highest-risk templates.

## Passed Integrity Gates

- Horizontal overflow: 0.
- Broken images: 0.
- Clipped text: 0.
- Typography mismatches: 0.
- Unexpected fonts or legacy serif use: 0.
- Curriculum editorial-weight mismatches: 0.
- Unavailable fonts: 0.
- Undersized or oversized text flags: 0.
- Console issues: 0.
- Missing footers: 0.
- `Use My Location`: passed and sorted campuses by distance.
- Campus card/map popup: passed with the full campus popup visible.
- Curriculum program slider: passed with 11 program tabs.

## Classified Non-Theme Signals

- The six Chrome footer-reachability flags are the intentional full-screen Parent Portal application, not missing page content.
- Fixed-position flags are the expected campus tour panel and Parent Portal application shell.
- Aborted WebP lazy requests did not create broken images.
- reCAPTCHA and Google conversion-measurement aborts are third-party/network behavior, not a theme rendering regression.
- Chat widget status is owner-confirmed fixed; the vendor widget did not render in the headless test context, so this is not presented as independent browser proof.

## Remaining Manual / Production-Only Evidence

- Real Safari/iOS and manual screen-reader spot checks remain deferred because they are not available in this Windows staging run.
- Production robots, sitemap, redirects, CDN/cache behavior, field INP/CrUX, and post-cutover crawl remain production-only.
- No launch or live-site switch was performed.

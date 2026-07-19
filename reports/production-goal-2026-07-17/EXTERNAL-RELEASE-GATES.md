# External Release Gates

These items cannot be completed safely from the staging theme repository or public HTML alone.

## 1. Management facts and proof

- Approve campus/program hours, directors, transportation, apps, amenities, tuition/availability policy, Early Start scope, HR details, parent resources, reviews, ratings, licensing, Quality Rated, GA Pre-K, NAEYC, GAC, awards, and safety claims.
- Supply current source links/documents and an accountable owner/review date.
- Replace any template fallback copy with owner-approved, program- or campus-specific operating facts where the fallback is intentionally general.

## 2. CRM and messaging

- Submit approved tests through Contact, Tour, campus, and Careers forms.
- Verify GHL records, routing, ownership, deduplication, confirmations, email, SMS, calendar, rescheduling, attribution, consent, and failure behavior.

## 3. Analytics and local platforms

- Validate GA4/GTM/Ads events, primary conversions, attribution persistence, consent mode, call tracking, and duplicate prevention.
- Validate Search Console ownership/indexation/sitemap and reconcile every campus with GBP.

## 4. Legal and security

- Legal approval for Privacy, Terms, cookie/consent mode, SMS/email language, vendor processing, retention, child data, and therapy/health-data routing.
- Complete approved production security scan and sensitive-data review.

## 5. Production cutover

- Create and verify production backup/rollback.
- Export and merge existing redirects.
- Confirm target theme/plugin versions, preferred domain, HTTPS, cache/CDN, and maintenance window.
- Remove staging index blocks only on production after approval.
- Re-crawl production and verify robots, sitemap, canonicals, hreflang, schema, forms, maps, messages, analytics, and high-traffic legacy URLs.

## 6. Third-party and field evidence

- Ask the chat-widget vendor to correct its landmark semantics or accept the documented external finding.
- Decide whether the chat teaser should auto-open on small screens; it can overlay primary page content even though the theme itself has no overflow.
- Purge or bypass the stale Cloudflare/Rocket `/robots.txt` cache object. The staging origin and cache-busted URL are correct, but the plain cached object still serves the older staging block.
- Measure production field INP/RUM/CrUX; Lighthouse cannot provide INP.
- Tune production TTFB/caching and reassess map/analytics costs after cache warm-up.

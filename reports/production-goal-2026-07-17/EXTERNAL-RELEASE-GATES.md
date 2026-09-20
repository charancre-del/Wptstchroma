# External Release Gates

These items cannot be completed safely from the staging theme repository or public HTML alone.

## 1. Management facts and proof

- Campus directors, transportation, pickup schools, hours, ages, amenities, and current claim documentation are accepted from the current live site by user direction.
- Campus licensing was verified against the official Georgia DECAL provider export on 2026-07-19; see `DECAL-LICENSE-VERIFICATION-2026-07-19.md`.
- Early Start operating details, careers policy, parent resources, and legal approval are intentionally deferred by user direction and are not blockers for this staging task.
- Replace any template fallback copy with owner-approved, program- or campus-specific operating facts where the fallback is intentionally general.

## 2. CRM and messaging

- User reports Contact, Tour, campus form, CRM routing, ownership, deduplication, confirmations, email, SMS, calendar, rescheduling, consent, and failure behavior as tested.

## 3. Analytics and local platforms

- **Closed for staging readiness:** GA4/GTM/Ads installation, authenticated conversion evidence, Search Console ownership/data collection, and 24-of-24 ELA GBP coverage are verified in `AUTHENTICATED-SYSTEMS-VERIFICATION-2026-07-20.md`.
- **After cutover only:** verify the production hostname in GA4 real time, inspect/re-submit the production sitemap and priority URLs in Search Console, and monitor newly added intent events after real traffic arrives.

## 4. Legal and security

- Legal approval for Privacy, Terms, cookie/consent mode, SMS/email language, vendor processing, retention, child data, and therapy/health-data routing is intentionally deferred by user direction and is not an active blocker in this scope.
- Complete approved production security scan and sensitive-data review.

## 5. Production cutover

- Create and verify production backup/rollback.
- Export and merge existing redirects.
- Confirm target theme/plugin versions, preferred domain, HTTPS, cache/CDN, and maintenance window.
- Remove staging index blocks only on production after approval.
- Re-crawl production and verify robots, sitemap, canonicals, hreflang, schema, forms, maps, messages, analytics, and high-traffic legacy URLs.

## 6. Third-party and field evidence

- Chat widget is owner-confirmed fixed. Recheck it during the post-cutover manual browser pass because the vendor widget does not render in headless QA.
- Purge or bypass the stale Cloudflare/Rocket `/robots.txt` cache object. The staging origin and cache-busted URL are correct, but the plain cached object still serves the older staging block.
- Measure production field INP/RUM/CrUX; Lighthouse cannot provide INP.
- Tune production TTFB/caching and reassess map/analytics costs after cache warm-up.
- Complete post-cutover Safari/iOS and manual screen-reader spot checks.

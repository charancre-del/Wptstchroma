# Performance and Availability Audit - Current Staging Status

> **Superseded:** Current cross-device and sequential Lighthouse evidence is recorded in `STAGING-FINAL-READINESS-2026-07-16.md`.

**Audit date:** 2026-07-16
**Target:** Staging only
**Production rule:** Do not change live CDN, Cloudflare, cache, server, plugin, or optimization settings.

## Current Availability Evidence

- `staging-near-me-status.txt` reports 530 tested near-me URLs and zero failures.
- Low-rate direct staging checks returned 200 for Home, Contact, Schedule a Tour, 14 sampled core routes, and four English/Spanish near-me pages.
- The legacy `/schedule-tour/` route returned the expected 301.
- The prior Cloudflare 1015/429 page-level failure was not reproduced during these low-rate checks.

This is availability evidence, not a load test and not a Core Web Vitals result.

## Current Header Observation

A sampled staging Home response returned 200 with:

- `cdn-cache-control: no-store`
- `Cache-Control: public, max-age=0, s-maxage=2592000`
- `cf-cache-status: BYPASS`
- `x-metasync-otto-cache: RATE_LIMITED`

The mixed cache directives and MetaSync rate-limit signal need ownership and impact analysis. The observation does not prove that the page failed, that Cloudflare rate-limited the request, or that users saw an error.

## Plugin Inventory

| Environment artifact | Active | Inactive | Must-use | Total |
| --- | ---: | ---: | ---: | ---: |
| Live point-in-time inventory | 9 | 19 | 2 | 30 |
| Current staging inventory | 10 | 19 | 2 | 31 |

- Staging-only plugin: active `metasync` version `2.6.16`.
- Active staging `metasync` and `webp-express` report updates available in the captured plugin inventory.
- Inactive cache/plugin copies are inventory and maintenance concerns, not proof of front-end slowdown.
- No plugin was activated, deactivated, updated, removed, or configured during this audit.

## Measurements Not Yet Captured

No current Lighthouse, CrUX, RUM, WebPageTest, or lab Core Web Vitals baseline is claimed. The final staging build needs:

- LCP, INP, CLS, TTFB, FCP, and Speed Index
- Request count, transferred bytes, compression, and cache-hit behavior
- Main-thread JavaScript, long tasks, unused code, and third-party cost
- Image dimensions, formats, lazy loading, decoding, and below-fold behavior
- Font loading, preloads, fallback behavior, and layout shifts
- Map initialization, tile loading, geolocation, and popup work
- Form iframe/embed loading and failure behavior
- Analytics, tag manager, chat, review, and marketing request waterfall
- Anonymous first view, repeat view, and bypassed-cache behavior

Test at minimum at 1440x1000, 1024x1366, 834x1112, and 390x844 under broadband and throttled mobile profiles.

## Controlled Availability Test

Before release approval:

1. Crawl all 2,364 current sitemap URLs at an agreed rate.
2. Record status distribution, redirect chains, retry behavior, response time, and cache headers.
3. Separate application errors, origin failures, Cloudflare limits, and third-party API limits.
4. Preserve the request rate, user agent, timestamp, rule/event identifiers, and failure samples.
5. Prepare any configuration proposal with narrow scope, monitoring, rollback, and abuse protection.
6. Do not apply a production rule under this audit.

## Performance Priorities

1. Confirm MetaSync rate-limit behavior does not alter rendered content or block requests.
2. Document the intended cache policy and eliminate contradictory behavior in the release candidate.
3. Keep maps and form embeds from blocking primary content.
4. Reserve dimensions for images, maps, and embeds.
5. Respect reduced motion and avoid unnecessary carousel work.
6. Load only third-party scripts tied to measured business requirements.
7. Repeat the full URL availability check after the final build.

## Readiness

**Status: Availability improved in current staging evidence; performance remains unmeasured.** No production performance improvement or rate-limit fix is claimed.

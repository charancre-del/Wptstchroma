# Staging Runtime Smoke - 20260504

Target: `https://x3yyntt5tp-staging.wpdns.site/`

## Summary

- Public homepage, REST index, robots, Yoast sitemap index, and WordPress sitemap all responded.
- `chroma-agent/v1` route groups are registered on staging, including virtual-page SEO, theme customizer/page/CPT/taxonomy meta, portal, schools, forms, leads, media, cache, sitemaps, translations, and LLM/settings routes.
- Unauthenticated Agent API reads/writes returned `401` missing API key, as expected.
- Parent portal debug routes `/wp-json/chroma-portal/v1/system-check` and `/wp-json/chroma-portal/v1/cookie-test` returned `401`, confirming the prior public debug-route fix is live.
- Sampled combo, Spanish combo, near-me, and Spanish near-me sitemap URLs rendered `200` with title, meta description, canonical, OpenGraph description, and JSON-LD.
- Playwright loaded the homepage at desktop and mobile widths with zero console errors. The only warning was the Meta Pixel traffic-permission warning from Facebook.

## Checks

- `/`: `200`, title `Chroma Academy | Top Daycare & Preschool in Metro Atlanta`, meta description present, canonical present, 4 JSON-LD blocks.
- `/wp-json/`: `200`, REST index public.
- `/sitemap_index.xml`: `200`, listed `sitemap.xml`, `sitemap-spanish.xml`, `sitemap-combos.xml`, `sitemap-combos-es.xml`, `sitemap-near-me.xml`, and `sitemap-near-me-es.xml`.
- `/sitemap-combos.xml`: `200`, 392 URLs.
- `/sitemap-combos-es.xml`: `200`, 392 URLs.
- `/sitemap-near-me.xml`: `200`, 255 URLs.
- `/sitemap-near-me-es.xml`: `200`, 255 URLs.
- Sample combo URL `/parents-day-out-in-kennesaw-ga/`: `200`, title/meta/canonical/OpenGraph/JSON-LD present.
- Sample Spanish combo URL `/es/parents-day-out-in-kennesaw-ga/`: `200`, title/meta/canonical/OpenGraph/JSON-LD present.
- Sample near-me URL `/daycare-near-me/`: `200`, title/meta/canonical/OpenGraph/JSON-LD present.
- Sample Spanish near-me URL `/es/daycare-near-me/`: `200`, title/meta/canonical/OpenGraph/JSON-LD present.

## Finding Fixed In This Pass

### SR-001 - Public Weather Endpoint Invalid Handler

- Severity: high
- URL: `GET /wp-json/chroma/v1/weather?lat=33.7490&lon=-84.3880`
- Staging evidence before patch: `500 {"code":"rest_invalid_handler","message":"The handler for the route is invalid."}`
- Cause: `plugins/chroma-school-dashboard/inc/class-api-routes.php` registered `/weather` with callback `get_weather_proxy`, but the method was missing.
- Fix: added `get_weather_proxy()` with latitude/longitude validation, out-of-range checks, `Chroma_Weather_Provider::get_weather()` delegation, and the direct weather object response expected by `assets/js/tv-dashboard.js`.
- Local verification: `php -l plugins/chroma-school-dashboard/inc/class-api-routes.php` passed.

## Notes

- `xmlrpc.php?rsd` returned `403` during link probing; this is consistent with security hardening and not a missing asset.
- Authenticated Agent API dry-run/write checks still require a valid Chroma Agent API key.

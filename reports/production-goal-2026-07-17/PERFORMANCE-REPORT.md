# Performance Report

## Before and after

| Surface | Device | Performance | LCP | TBT | CLS | Bytes | Requests |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Home baseline | Mobile | 61 | 3.90s | 1,096ms | 0.000 | 668,850 | 29 |
| Home current | Mobile | **81** | 4.29s | **69ms** | 0.000 | 671,309 | 29 |
| Locations baseline | Mobile | 55 | 9.39s | 467ms | 0.103 | 844,433 | 52 |
| Locations current | Mobile | **71** | **7.75s** | **112ms** | **0.041** | **814,267** | **51** |
| Home baseline | Desktop | 86 | 1.67s | 43ms | 0.001 | 666,946 | 29 |
| Home current | Desktop | **95** | **1.15s** | **15ms** | 0.000 | 670,104 | 29 |
| Curriculum baseline | Mobile | 77 | 2.26s | 786ms | 0.001 | 524,821 | 26 |
| Curriculum current | Mobile | **93** | 2.57s | **42ms** | 0.001 | 525,420 | 26 |

All current sampled accessibility scores are 100. Best Practices remains affected by third-party behavior. Staging SEO remains 69 because indexing is intentionally blocked.

## Interpretation

- Main-thread blocking improved sharply on every sampled page.
- Locations reduced weight, requests, LCP, TBT, and CLS but remains the largest mobile performance risk because interactive map tiles are loaded from a third party.
- Home mobile TBT improved substantially, although its LCP remains above the 2.5s target under the measured staging response.
- Desktop Home and mobile Curriculum are strong.
- Staging TTFB was roughly 1.65-2.26s with `no-store`/bypass behavior, so production cache and CDN behavior cannot be proven here.

## INP note

INP is a field metric and is unavailable from this Lighthouse lab run. TBT is reported as the lab responsiveness proxy. Production RUM or CrUX is required for real INP evidence.

## Remaining production actions

1. Enable and validate full-page caching, object cache, CDN, compression, and origin tuning.
2. Audit GTM/Ads and delay nonessential tags where consent and business requirements allow.
3. Capture production field INP and Core Web Vitals after launch.
4. If Locations remains slow, test an intent-activated or static map preview without harming location conversion.

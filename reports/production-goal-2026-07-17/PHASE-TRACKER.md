# Phase Tracker

**Target:** staging only  
**Source:** `FULL-GOVERNING-PROMPT.md`  
**Updated:** 2026-07-20 after authenticated platform verification and cross-browser launch-gate QA

| Phase | Internal status | Remaining external or release evidence |
| ---: | --- | --- |
| 1. Full crawl and inventory | **Complete**: 466 sitemap URLs audited for content and technical SEO | Owner editorial/factual disposition approval |
| 2. Harmful/doorway/placeholder cleanup | **Complete internally**: governed, constrained, redirected/noindexed without unsafe deletion | Management approval before destructive record deletion |
| 3. Operational consistency | **Complete on staging**: current live-site operating facts accepted as authoritative and reconciled | Maintain sources when operations change |
| 4. Community pages | **Complete**: no community URLs remain in audited sitemap | Production redirect/history reconciliation |
| 5. Professional copyedit | **Complete for theme/indexed audit signals** | Human brand/legal/factual approval |
| 6. Homepage | **Theme implementation staging-verified** | Trust-source approval and production analytics |
| 7. Curriculum | **Theme implementation staging-verified after typography correction** | Owner/editorial approval of curriculum claims/resources |
| 8. Program pages | **Complete structurally**: 165 requirement rows; safe reusable family-details fallback deployed to Preschool, Kindergarten, and Rising Kindergarten templates | Owner may replace fallback with distinct program-specific operating copy, schedules, ratios, tuition, and credentials |
| 9. Campus pages | **Complete on staging**: 624 requirement rows, 24/24 DECAL licenses, Tyrone email, and live-authorized operational facts reconciled | Maintain campus records as facts change |
| 10. Location directory | **Complete and interaction-tested** | Maintainable availability source if later desired |
| 11. Accreditation/trust | **Safe internally**: unsupported blanket claims removed | Documentary scope and approved verification links |
| 12. Tour/enrollment funnel | **Complete by user-reported system testing**: theme/UI and CRM/email/SMS/calendar behavior tested; analytics evidence now authenticated | Production-host confirmation after cutover |
| 13. Tuition/availability | **Safe policy complete** | Owner-controlled maintained rate/availability workflow |
| 14. Parent resources | **Theme complete** | Current documents, app, policies, and content owners |
| 15. Early Start | **Route/template distinction complete** | Clinical/service/payment/privacy facts |
| 16. Careers | **Theme/listing support complete** | HR facts and active-role verification |
| 17. Technical SEO | **Complete on staging; Search Console authenticated** | Production indexability, redirects, sitemap submission, and post-cutover inspection |
| 18. Local SEO | **Template/NAP/map complete; 24/24 unique ELA GBP profiles authenticated** | Duplicate Midway source-row cleanup and ongoing profile maintenance |
| 19. Accessibility | **Automated theme-owned findings cleared**: 140 Axe page/viewport checks; 0 theme-owned violations; chat owner-confirmed fixed | Manual screen-reader and Safari/iOS spot checks |
| 20. Performance/mobile | **Internally remediated and measured** | Production cache/TTFB, third parties, and field INP |
| 21. Visual consistency | **Automated integrity staging-verified:** 162 Chrome checks and 30 Edge checks; no overflow, clipping, broken images, typography drift, or console issues | Safari/iOS, manual assistive-technology, vendor overlay, and post-cutover review |
| 22. Trust/proof | **Safe internally** | Source-backed ratings, safety, credentials, workflow proof |
| 23. Analytics | **Authenticated and staging-verified:** GA4/GTM/Ads present, lead and confirmed-form events verified, privacy-safe intent events added | Real-traffic validation of newly added events and post-cutover production-host check |
| 24. Privacy/compliance | **Safe template language implemented** | Legal, consent, retention, vendor, and security approval |
| 25. Final QA | **Staging matrix and authenticated-platform evidence complete; not equivalent to production launch approval** | Production cutover, Safari/iOS, manual assistive-technology, field data, and production-only gates |

## Internal completion statement

The currently reproducible theme regressions identified in the corrected staging audit are fixed. Authenticated GA4/GTM, Search Console, and ELA GBP evidence is now closed for staging readiness. This is not a production launch claim: the remaining work is limited to owner-deferred content/approval areas, real Safari/iOS and manual assistive-technology checks, real-traffic measurement, and production-only cutover validation. See `AUTHENTICATED-SYSTEMS-VERIFICATION-2026-07-20.md` and `STAGING-LAUNCH-GATE-QA-2026-07-20.md`.

## Latest staging deploy evidence

- Campus fact reconciliation completed 2026-07-19 with rollback backup `/home/x3yyadl/backups/chroma-staging-before-campus-facts-20260719-023052.sql`.
- DECAL license and campus rendering evidence: `DECAL-LICENSE-VERIFICATION-2026-07-19.md`.
- All 24 campus pages returned HTTP 200 and rendered their verified campus-specific license.
- Focused location/campus QA passed at desktop, tablet portrait, and mobile with location sorting and popup interactions passing.
- Full staging deploy completed: `20260718-192809` with a remote rollback backup.
- Latest compiled staging assets after analytics verification: `assets/css/main.cc80ecdb3b67.css` and `assets/js/main.8ed0b8a7be6b.js`.
- Curriculum typography now matches the site-wide system: Outfit body copy and Playfair Display editorial headings at the same declared weight used on the homepage.
- Cross-device browser QA evidence: `reports/production-goal-2026-07-17/qa/final-reaudit-20260719/`.
- Accessibility evidence: `reports/production-goal-2026-07-17/qa/axe-current/`.
- Program template fallback section remains verified on Preschool, Kindergarten, and Rising Kindergarten.
- Chrome launch-gate matrix: 162 checks, 0 failures; Edge high-risk matrix: 30 checks, 0 failures.
- Authenticated Search Console and GBP collection, GA4 conversion evidence, and direct staging intent-event assertions are recorded in `AUTHENTICATED-SYSTEMS-VERIFICATION-2026-07-20.md`.

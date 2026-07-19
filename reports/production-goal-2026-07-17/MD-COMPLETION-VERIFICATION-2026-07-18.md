# Governing Markdown Completion Verification

**Source of truth:** `FULL-GOVERNING-PROMPT.md`  
**Target verified:** staging only - `https://x3yyntt5tp-staging.wpdns.site/`  
**Theme scope:** Chroma Excellence Theme 2.0  
**Verification date:** 2026-07-18

## Strict verdict

**All safe, internally actionable Theme 2.0 and staging work is complete and verified. The full governing prompt is not completely closed because it explicitly includes owner, authenticated-system, legal, vendor, production, and post-cutover requirements.**

This distinction is intentional: the repository must not fabricate campus facts, credentials, tuition, reviews, clinical scope, or system outcomes just to turn an external gate green.

## What was verified now

- The current staging sitemap contains 466 URLs; all returned HTTP 200 with 0 technical crawl issues.
- The content inventory contains the same 466 URLs with 0 duplicate titles, duplicate content, thin pages, missing CTAs, or unresolved internal links.
- The responsive browser matrix contains 162 checks: 27 representative routes at desktop, laptop, tablet landscape, tablet portrait, small tablet, and mobile. All passed with 0 horizontal overflow, clipped text, broken images, or missing footers.
- The accessibility matrix contains 140 checks: 28 pages at 5 viewports. It found 0 theme-owned violations. The 126 repeated `region` nodes belong to the external LeadConnector chat widget; 3 touch-target warnings were offscreen-card false positives whose measured links are 48 pixels tall with adequate gaps.
- Geolocation sorting, region filters, campus-card map focus, full campus popups, and the 11-program Curriculum slider passed interaction tests.
- Program emphasis is 50/50 present. The structural program matrix has 96 direct rows, 66 safe visible template fallbacks, and 3 schedule fallbacks; no program row is missing.
- The campus matrix has 624 rows across 24 campuses. Its 24 licensing gaps and Tyrone email gap require owner-supplied source facts; they are not missing theme components.
- The governing requirement matrix includes 1,010 statements across all 25 phases, including Phase 13.
- All eight required final deliverable files exist.
- PHP lint and the frontend production build passed. Staging is running the compiled assets in `assets/manifest.json`.

## Evidence-system defects found

1. The earlier Phase 13 omission is resolved; paragraph requirements are now captured and the matrix contains all 25 phases.
2. The 1,010-row matrix still uses phase evidence for broad prompt statements. Exact operating facts are therefore corroborated by the program and campus matrices and remain externally gated when no authoritative source exists.
3. Plain `/robots.txt` is being served from a stale Cloudflare/Rocket cache object. The WordPress origin, cache-busted URL, and generated content are correct and include the sitemap. A staging-only origin purge succeeded, but the plain CDN object did not clear.
4. The GHL chat widget can auto-open over mobile content and contributes vendor-owned landmark findings. Hiding or delaying it is a marketing/vendor decision, not an accessibility change the theme should make silently.

## Phase-by-phase result

| Phase | Strict verification result | What remains |
| ---: | --- | --- |
| 1 | Internally complete | Owner editorial and factual disposition approval |
| 2 | Internally governed | Management approval before destructive deletion or redirect decisions |
| 3 | Safe internal implementation complete | Source-backed company-wide operating facts and maintained owners |
| 4 | Complete on staging | Production redirect/history reconciliation |
| 5 | Internally complete | Human brand, legal, and factual approval |
| 6 | Theme and staging QA complete | Trust-source approval and production analytics proof |
| 7 | Theme and staging QA complete | Curriculum-owner approval and resource verification |
| 8 | Structurally complete with fallbacks | Program-specific schedules, ratios, tuition, credentials, and operating facts |
| 9 | Structural implementation complete | Owner-supplied licensing, email, directors, transport, credentials, reviews, and local facts |
| 10 | Interaction-tested on staging | Maintained availability source if availability is published |
| 11 | Safe claims implemented | Documentary accreditation and recognition scope |
| 12 | UI complete | Authenticated GHL, email, SMS, calendar, routing, and outcome tests |
| 13 | Safe policy implementation complete and included in matrix | Management-approved tuition strategy and maintained rates/availability workflow |
| 14 | Theme complete | Current handbooks, forms, menus, policies, calendars, portals, and content owners |
| 15 | Route/template complete | Clinical scope, payment, privacy, and service-availability facts |
| 16 | Theme/listing support complete | HR confirmation and active-role verification |
| 17 | Complete on staging | Production indexability, redirects, sitemap, canonicals, and Search Console |
| 18 | Template/local-data support complete | GBP reconciliation and credential validation per campus |
| 19 | Automated theme findings cleared | Manual screen-reader testing and third-party chat semantics |
| 20 | Staging remediation measured | Production TTFB/cache, third parties, field INP/RUM/CrUX |
| 21 | Automated responsive QA complete | Post-cutover browser and device spot-check |
| 22 | Safe unsupported-claim removal complete | Approved ratings, reviews, safety, credential, and workflow proof |
| 23 | Instrumentation-ready | Authenticated GA4, GTM, Ads, call tracking, consent, and CRM attribution proof |
| 24 | Safe template language implemented | Legal, consent, retention, vendor, security, and sensitive-data approval |
| 25 | Automated staging QA complete | Production cutover QA, authenticated system tests, Safari/iOS, and manual checks |

## Required deliverables

All required deliverable files exist:

1. `EXECUTIVE-SUMMARY.md`
2. `CHANGE-LOG.md`
3. `FACT-VERIFICATION-LIST.md`
4. `REDIRECT-MAP.md`
5. `SEO-REPORT.md`
6. `CONVERSION-REPORT.md`
7. `PERFORMANCE-REPORT.md`
8. `FINAL-WEBSITE-SCORE.md`

The current evidence-based staging score is **8.0/10**, not 10/10.

## Completion decision

- **Theme/staging implementation:** internally complete for all safe work that does not require owner facts or authenticated external systems.
- **Automated staging QA:** passed across the complete 162-check responsive matrix and 140-check accessibility matrix.
- **Git/release reproducibility:** completed by committing and pushing the intended source and evidence changes before handoff.
- **Full governing prompt:** not complete until the external and production gates in `EXTERNAL-RELEASE-GATES.md` are cleared.
- **Live launch:** not proven ready by this verification alone.

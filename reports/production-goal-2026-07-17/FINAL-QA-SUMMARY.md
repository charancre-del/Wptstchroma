# Final QA Summary

## Automated staging QA

| Test | Scope | Result |
| --- | --- | --- |
| Technical crawl | 466 sitemap URLs | 466 HTTP 200; 0 crawl or technical SEO issues |
| Content inventory | 466 URLs | 0 errors, duplicates, thin pages, or no-CTA pages |
| Corrected template/device matrix | 27 routes x 6 viewports | 162 checks; 0 HTTP, overflow, clipping, broken-image, font, text-size, or missing-footer failures |
| Visual integrity | All 162 checks | 0 overflow, clipped text, broken images, or missing footers |
| Axe | 28 pages x 5 viewports | 140 checks; 0 theme-owned violations |
| Interactions | Geolocation, filters, map popup, program slider | Passed |
| PHP lint/build | Touched PHP + frontend build | Passed |
| Focused program fallback deploy | Preschool, Kindergarten, Rising Kindergarten | Passed on staging HTML |
| Focused post-deploy browser pass | Home, Curriculum, Locations, Contact, 3 program routes x desktop/tablet/mobile | 0 failures |

## Latest evidence

- Corrected staging asset deploy backup: `/home/x3yyadl/backups/chroma-font-fix-20260719-153524`.
- Cross-device result folder: `reports/production-goal-2026-07-17/qa/final-reaudit-20260719/`.
- Focused Curriculum typography result folder: `reports/production-goal-2026-07-17/qa/curriculum-font-final-20260719/`.
- Accessibility result folder: `reports/production-goal-2026-07-17/qa/axe-current/`.
- Visual contact sheets: `reports/production-goal-2026-07-17/qa/template-current/contact-sheets/`.
- Governance artifact summary: `reports/production-goal-2026-07-17/governance-artifact-summary.json`.

## Remaining non-theme findings

- The embedded third-party chat widget creates 126 repeated landmark/region findings and can visually overlap mobile content when its teaser opens. It is outside theme markup and requires a vendor or marketing-owner decision.
- Plain `/robots.txt` is still served from a stale Cloudflare/Rocket cache object even though the origin and a cache-busted request return the correct sitemap-enabled file. Origin cache purge completed successfully; host/CDN cache ownership remains external.
- Google analytics/remarketing aborts and construct-stylesheet warnings are third-party noise unless they become visually or functionally disruptive.
- Manual screen-reader, authenticated form/CRM, Safari/iOS, production security, and production cache/indexability checks remain release gates.

## Decision

No internally reproducible theme regression remains in the corrected tested staging matrix. This is not a claim that every governing-prompt item or production launch gate is complete. Remaining gates are documented in `EXTERNAL-RELEASE-GATES.md` and `PROMPT-REAUDIT-2026-07-19.md`.

# Technical and Content SEO Audit - Current Staging Reconciliation

> **Superseded:** Current SEO cleanup evidence and launch-time indexation gates are recorded in `STAGING-FINAL-READINESS-2026-07-16.md`.

**Audit date:** 2026-07-16
**Target:** `https://x3yyntt5tp-staging.wpdns.site` only
**Production rule:** No live SEO request, setting, redirect, content, or deployment change is authorized.

## Executive Finding

The previously reported near-me HTTP failures and duplicate sitemap-line issue are not present in the current staging evidence. The SEO release gate is now canonical consistency, full metadata remeasurement, factual integrity, generated-page quality, language parity, and final index-control configuration.

## Current Inventory

| Measure | Current staging result | Evidence |
| --- | ---: | --- |
| Sitemap URLs | 2,364 | `staging-sitemap-urls.txt` |
| Unique sitemap URLs | 2,364 | Exact line deduplication |
| Duplicate lines | 0 | Exact line deduplication |
| English URLs | 1,182 | Paths outside `/es/` |
| Spanish URLs | 1,182 | Paths under `/es/` |
| Near-me URLs | 530 | `staging-near-me-urls.txt` |
| English near-me URLs | 265 | Five families x 53 places |
| Spanish near-me URLs | 265 | Five families x 53 places |
| Near-me failed requests | 0 | `staging-near-me-status.txt`: `TOTAL=530 FAILED=0` |

The five near-me families per language are childcare, daycare, infant care, preschool, and pre-K. Every near-me URL appears in the current sitemap inventory.

## Historical Findings Reconciled

| Prior audit claim | Current evidence | Current disposition |
| --- | --- | --- |
| 530 near-me URLs returned 500 | Current all-URL artifact reports 530 total / 0 failed; four direct English/Spanish samples returned 200 | Availability gate validated on staging; content quality still open |
| About 4,000 sitemap entries but 2,364 unique | Current file contains 2,364 total and 2,364 unique | Duplicate-line gate validated on staging |
| 732 URLs lacked canonicals | Current inventory does not include HTML metadata; 19 direct samples all emitted a canonical | Historical count not remeasured; sampled target consistency is open |
| 729 missing and 209 overlong descriptions | Current files do not contain descriptions | Historical count not remeasured |
| 320 titles over 60 characters | Current files do not contain titles for every URL | Historical count not remeasured |
| 586 thin pages and 1,674 generated service/location URLs | Current list confirms a large generated footprint but does not reproduce those classifications | Historical counts not rederived; family-level disposition remains open |
| Seven pages with multiple H1s | Nineteen current samples each emitted one H1 | Positive sample; full template crawl still required |
| GEO feed exposed 19 locations | Current live and staging content exports each contain 24 location records; feed was not recaptured | Feed parity remains open |

## Current Canonical Finding

All 14 sampled core routes returned 200 and emitted a canonical, but targets were inconsistent:

- Seven pointed to the staging host: About, Programs, Locations, Schedule a Tour, Summer Camp, Communities, and Terms.
- Seven pointed to the live host: Curriculum, Parents, Contact, Blog, Careers, Employers, and Privacy.
- `/blog/` pointed to the live `/stories/` path, adding a path-identity question beyond the host mismatch.
- Home and four sampled near-me routes emitted self-referencing staging canonicals.

This is current staging evidence, not proof that production canonical output is wrong. Define the intended staging policy and verify the production-target template behavior before any release.

## Staging Crawl Controls

- `robots.txt` currently returns `User-agent: *` and `Disallow: /`.
- Sampled HTML did not include an explicit `noindex` directive or an `X-Robots-Tag: noindex` header.
- Mixed-host canonicals plus robots blocking are acceptable only if deliberately documented; they must not be carried into production accidentally.

## Content and Trust Risks

Current staging still renders conflicting campus counts, timeline statements, app names, hours, and broad accreditation/service claims. These must be approved or neutralized before schema and visible copy can be treated as trustworthy.

HTTP 200 is not a quality verdict. The 530 near-me URLs and wider generated URL set still require:

- Unique local value beyond token replacement
- Accurate nearby campus relationships and travel context
- Verified program/service availability
- Human Spanish-language review
- Useful intent and non-duplicative headings/body copy
- Approved canonical, index/noindex, sitemap, and redirect disposition

## Required Full Crawl

For every current sitemap URL, record:

1. Final status and redirect chain
2. Canonical URL and host
3. Robots/indexability and sitemap membership
4. Title and meta-description presence, length, and uniqueness
5. H1 count and heading outline
6. `hreflang`/language pairing where applicable
7. Word count and normalized content hash
8. Structured-data types, validity, and fact parity
9. Template/content family and keep/improve/noindex/redirect decision

## Readiness

**Status: Improved staging availability; SEO release gate remains open.** Do not restore the historical P0 failure claim, and do not claim that metadata or content-quality issues are fixed until the current full crawl exists.

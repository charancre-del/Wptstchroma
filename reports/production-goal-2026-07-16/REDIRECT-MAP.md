# Staging Redirect and URL Disposition Map

**Audit date:** 2026-07-16
**Status:** Staging evidence and proposal only
**Production rule:** Do not request, add, remove, or change production redirects under this audit.

## Validated Staging Redirect

| Source | Staging result | Destination | Current status | Remaining gate |
| --- | --- | --- | --- | --- |
| `/schedule-tour/` | 301, one hop | `/schedule-a-tour/` | Validated on staging | Include in final regression set; production state remains unchecked and unchanged |

The current sampled Summer Camp HTML did not contain the old `/schedule-tour/` href. Visual CTA presence, label, and click behavior still require browser QA.

## Near-Me URL Families

The current staging inventory contains 530 unique near-me URLs and the current status artifact reports zero failures. The prior 500 response is no longer a reason to redirect these URLs.

| Language | Families | Places per family | URLs | Current HTTP disposition |
| --- | --- | ---: | ---: | --- |
| English | childcare, daycare, infant care, preschool, pre-K | 53 | 265 | Keep reachable during quality classification |
| Spanish | childcare, daycare, infant care, preschool, pre-K | 53 | 265 | Keep reachable during technical and human language review |

Do not bulk redirect, noindex, or remove these URLs solely because they are generated. Decide each family/cluster using intent, uniqueness, factual accuracy, nearby-campus relevance, traffic, backlinks, and approved language.

## Generated Page Disposition Rules

| Disposition | Use when | URL rule |
| --- | --- | --- |
| Keep and improve | Page provides accurate, distinctive local value and maps to active campuses/services | Self-canonical, indexable, in sitemap, unique content and metadata |
| Noindex, follow | Page is useful to users but not sufficiently distinct for search | Accessible, excluded from sitemap, no conflicting canonical |
| 301 redirect | Page is obsolete/duplicative and has one clearly stronger equivalent | Single hop to closest matching community, campus, program, or archive |
| 410 Gone | Page has no replacement or value after traffic/backlink review | Remove internal links and sitemap entry; approval required |

The historical 1,674 generated service/location count was not rederived from the current file. Use the current 2,364-URL inventory as the classification source and record an explicit disposition for every generated family.

## Canonical/Path Questions - Not Redirect Approvals

- Current staging `/blog/` emits a canonical to live `/stories/`.
- Seven of 14 sampled core routes point canonicals to live while seven point to staging.
- These observations require template/intent review; they are not authorization to redirect `/blog/` or any other route.

## Required Redirect QA

1. Record the source response before change.
2. Verify a single-hop destination that returns 200.
3. Preserve required query parameters and attribution.
4. Update internal links, canonicals, hreflang, and sitemaps so redirects are not internal navigation.
5. Test loops, chains, regex scope, case, trailing slash, language, and encoded paths.
6. Validate analytics attribution and 404 recovery.
7. Re-run the full route set on staging.
8. Obtain separate production approval before any live rule is considered.

## Current Approval State

Only the staging behavior of `/schedule-tour/` is validated. No bulk redirect list and no production redirect are approved.

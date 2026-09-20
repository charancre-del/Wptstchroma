# Published Content, URL, and Template Inventory

**Audit date:** 2026-07-16
**Environment:** Staging-only audit
**Production rule:** Do not change or request production/live during this audit.

## Evidence Scope

`live-published-content.json` and `staging-published-content.json` each contain 403 published records. For all 403 records, the exported ID, post type, title, slug, publish date, and modified date match exactly.

This proves published-record metadata parity only. The exports do not contain body copy, custom fields, media, menus, theme settings, form routing, SEO metadata, schema, redirects, or management truth.

## Published Record Counts

| Public content type | Live | Staging | Metadata parity | Primary template family |
| --- | ---: | ---: | --- | --- |
| Pages | 19 | 19 | Yes | Default and named page templates |
| Programs | 11 | 11 | Yes | Program archive and single program |
| Locations | 24 | 24 | Yes | Location archive and single location |
| Communities (`city`) | 50 | 50 | Yes | Community archive and single community |
| Posts | 219 | 219 | Yes | Blog/news/story single and archives |
| Careers | 62 | 62 | Yes | Careers archive and single career |
| Team members | 18 | 18 | Yes | About/team modules and optional singles |
| **Total** | **403** | **403** | **Yes** | Seven public content types |

## Staging URL Inventory

| URL set | Total | Unique | Notes |
| --- | ---: | ---: | --- |
| Current staging sitemap | 2,364 | 2,364 | 1,182 English and 1,182 Spanish |
| Current near-me set | 530 | 530 | 265 English and 265 Spanish; all are present in sitemap inventory |
| Near-me status result | 530 tested | 0 failed | HTTP availability only; not a content-quality decision |

The sitemap is much larger than the 403 published-record export because generated and translated URL families are not represented as one ordinary published record per URL.

## Plugin Inventory Parity

| Environment artifact | Active | Inactive | Must-use | Total |
| --- | ---: | ---: | ---: | ---: |
| Live point-in-time inventory | 9 | 19 | 2 | 30 |
| Current staging inventory | 10 | 19 | 2 | 31 |

Staging has one additional active plugin: `metasync` version `2.6.16`. All other captured plugin names, versions, and statuses align between the two inventory files. This is inventory evidence, not dependency or functional validation.

## Staging Site Options

- Home/site URL: `https://x3yyntt5tp-staging.wpdns.site`
- Template: `chroma-excellence-theme`
- Stylesheet: `chroma-excellence-theme`
- Permalink: `/%year%/%monthnum%/%day%/%postname%/`

## Core Pages

IDs, titles, and slugs below are identical in the live and staging record exports.

| ID | Title | Slug | Audit role |
| ---: | --- | --- | --- |
| 7720 | Home | `home` | Primary conversion landing page |
| 4252 | About Us | `about` | Company story, team, trust claims |
| 4226 | PrismPath | `curriculum` | Curriculum and methodology |
| 4331 | Parents | `parents` | Parent experience and policies |
| 5243 | Programs | `programs` | Program archive |
| 5046 | Locations | `locations` | Campus discovery archive |
| 5380 | Schedule a tour! | `schedule-a-tour` | Primary lead form |
| 4222 | Contact Us | `contact-us` | General inquiry conversion page |
| 6596 | Chroma Early Start | `chroma-early-start` | Pediatric therapy / Early Start information |
| 6727 | Summer Camp - Discover, Go | `summer-camp-discover-go` | Seasonal campaign page |
| 5097 | Communities | `communities` | Community archive |
| 4505 | Blog | `blog` | Editorial archive; current canonical needs path review |
| 4361 | Stories | `stories` | Story archive |
| 4374 | Newsroom | `newsroom` | News archive |
| 4367 | Careers | `careers` | Hiring archive |
| 4337 | Employers | `employers` | Employer partnership page |
| 6194 | Parent Portal | `parent-portal` | Parent access page |
| 4370 | Privacy Policy | `privacy-policy` | Privacy disclosure |
| 6044 | Terms of Service | `terms-of-service` | Terms disclosure |

## Programs

| ID | Program | Slug |
| ---: | --- | --- |
| 4219 | Infant Care | `infant-care` |
| 4293 | Toddlers | `toddler-care` |
| 4322 | Preschool | `preschool` |
| 4323 | Pre-K Prep | `pre-k-prep` |
| 4324 | Pre-K / GA Pre-K | `ga-pre-k` |
| 4325 | Schoolagers | `after-school` |
| 4326 | Camp (Summer, Winter, Fall) | `camp-summer-winter-fall` |
| 4327 | Parent's Day Out | `parents-day-out` |
| 6785 | Kindergarten | `kindergarten-1` |
| 7708 | Rising Pre-K | `rising-pre-k` |
| 7709 | Rising Kindergarten | `rising-kindergarten` |

## Locations

The 24 records below are published inventory, not an approved assertion that all 24 campuses are currently active.

| ID | Campus | Slug |
| ---: | --- | --- |
| 6779 | Chadwick Campus | `chroma-early-learning-academy-chadwick` |
| 4406 | Cherokee Academy by Chroma, Canton GA | `cherokee-campus` |
| 4289 | Downtown Duluth | `pleasanthill-campus-duluth` |
| 4410 | Ellenwood Campus | `ellenwood-campus` |
| 6781 | Grayson Campus | `chroma-early-learning-academy-grayson` |
| 4237 | Johns Creek Campus | `johns-creek` |
| 4414 | Jonesboro Campus | `jonesboro-campus` |
| 4288 | Lawrenceville Campus | `lawrenceville-campus` |
| 4407 | Lilburn Campus | `lilburn-campus` |
| 4408 | Marietta Campus (East) | `east-cobb-campus` |
| 4336 | McDonough Campus | `mcdonough` |
| 4417 | Midway Campus, Alpharetta GA | `midway-campus` |
| 4419 | North Hall Campus, Murrayville | `north-hall-campus-murraysville` |
| 8641 | Parklake Campus, Atlanta GA | `parklake-campus` |
| 4415 | Rivergreen Campus, Canton GA | `rivergreen-campus` |
| 4409 | Roswell Campus | `roswell-campus` |
| 4413 | Satellite Blvd Campus, Duluth, GA | `satellite-bvd-campus` |
| 4420 | Shenandoah Campus, Newnan GA | `newnan` |
| 4416 | South Cobb Campus, Austell | `south-cobb-campus-austell` |
| 6782 | Stockbridge Campus | `chroma-early-learning-academy-stockbridge` |
| 6780 | Sugarloaf Pkwy Campus | `chroma-early-learning-academy-sugarloaf-pkwy` |
| 4290 | Tramore Campus | `tramore-campus-austell` |
| 4287 | Tyrone Campus | `tyrone-campus` |
| 4411 | West Cobb Campus, Marietta | `west-cobb-campus` |

## Community Coverage

The record exports contain 50 `city` records: Alpharetta, Austell, Ballground, Canton, Clermont, Cumming, Dahlonega, Dawsonville, Decatur, Duluth, East Cobb, Ellenwood, Fairburn, Fayetteville, Gainesville, Griffin, Hampton, Jasper, Johns Creek, Jonesboro, Kennesaw, LaFayette, Lawrenceville, Lilburn, Lithia Springs, Locust Grove, Lovejoy, Mableton, Marietta, McDonough, Milton, Morrow, Murrayville, Newnan, Norcross, North Hall, Palmetto, Peachtree City, Peachtree Corners, Powder Springs, Rex, Roswell, Snellville, Stockbridge, Stone Mountain, Tucker, Tyrone, Waleska, West Cobb, and Woodstock.

These records and generated URL families require separate accuracy, uniqueness, active-campus relationship, and indexation review.

## Template QA Matrix

| Template/page type | Current evidence | Remaining release proof |
| --- | --- | --- |
| Home | 200, one H1, self-canonical; current fact conflicts and MetaSync rate-limit header observed | Full responsive, accessibility, performance, trust-copy, map, reviews, and CTA test |
| About | 200, one H1, self-canonical; 19+/24 and timeline conflicts current | Fact approval and responsive/content QA |
| Curriculum / PrismPath | 200, one H1; canonical points live | Canonical policy and full template QA |
| Parents | 200, one H1; canonical points live; Procare/hours language current | Fact approval, carousel, policy, and responsive QA |
| Programs archive | 200, one H1, self-canonical | CTA placement and responsive QA |
| Program single | Record parity only | Schedule, chart, metadata, CTA, and accessibility QA |
| Locations archive | 200, one H1, self-canonical | Permission, map, cards, filters, popups, fallback, and performance QA |
| Location single | Record parity only | Campus facts, gallery, programs, forms, schema, and accessibility QA |
| Contact | 200, one H1; wrong parent-information form current; canonical points live | Correct form and end-to-end submission/delivery proof |
| Schedule Tour | 200, one H1, self-canonical; no submission run | Selector, consent, submit, failure/success, delivery, and device QA |
| Summer Camp | 200, one H1, self-canonical; legacy route redirect works | Visual CTA and cross-device campaign QA |
| Early Start | 200; service-scope claims current | Management approval, outbound journey, accessibility, and canonical QA |
| Communities archive/single | Archive sample 200, one H1, self-canonical | Search labeling, generated-content quality, headings, and language QA |
| Blog/news/story archive/single | Blog sample 200, one H1; canonical points to live `/stories/` | Path intent, canonical, archive/single headings, metadata, and thin-content QA |
| Careers archive/single | Careers sample 200, one H1; canonical points live | Application journey, canonical, content, and form QA |
| Employers | 200, one H1; canonical points live; 19+ claim current | Fact approval, canonical, responsive, and conversion QA |
| Parent Portal | Record parity only | Authentication/link behavior and privacy QA |
| Privacy / Terms | Both records present; sampled canonicals are mixed-host | Legal version, canonical, analytics/privacy, and responsive QA |
| Near-me English/Spanish | 530/530 status artifact passes; four samples 200 with one H1/self-canonical | Content uniqueness, language, facts, metadata, schema, and disposition |
| 404 / search | Not included in current evidence set | Status, noindex, accessibility, search, and recovery-path QA |

## Caveats

- The live and staging files are point-in-time inventory evidence, not authorization to access or change live.
- The production permalink artifact and staging option artifact both record `/%year%/%monthnum%/%day%/%postname%/`.
- Slugs are recorded as exported; spelling and naming inconsistencies are not silently corrected.
- Published status does not prove factual accuracy, active-campus status, indexability, conversion function, or visual quality.
- No content, theme, plugin, redirect, option, cache, or deployment change was made during this reconciliation.

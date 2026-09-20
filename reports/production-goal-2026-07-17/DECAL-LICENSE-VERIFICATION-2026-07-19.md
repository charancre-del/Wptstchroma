# DECAL License Verification

**Verified:** 2026-07-19 02:31 ET
**Environment updated:** staging only
**Official source:** Georgia DECAL Provider Data Export, `https://families.decal.ga.gov/Provider/Data`
**API source:** `https://dcle2-decalapiprd.azurewebsites.net/api/Provider/Export`

The public DECAL provider export was downloaded for licensed Child Care Learning Centers and matched to the 24 published Chroma campus records by normalized street address and ZIP code. Phone and facility name were used as secondary confirmation where available. Legal provider names can differ from public Chroma campus names.

| Campus | WordPress ID | DECAL provider number | DECAL facility name |
| --- | ---: | --- | --- |
| Johns Creek Campus | 4237 | CCLC-53282 | Chroma Learning Academy of Johns Creek |
| Tyrone Campus | 4287 | CCLC-66995 | Chroma Early Learning Academy @Tyrone |
| Lawrenceville Campus | 4288 | CCLC-62649 | Chroma Early Learning Academy of Lawrenceville |
| Downtown Duluth | 4289 | CCLC-935 | Chroma Early Learning Academy @Pleasant Hill |
| Tramore Campus | 4290 | CCLC-1100 | Chroma Early Learning Academy @ Tramore |
| Mcdonough Campus | 4336 | CCLC-66359 | Chroma Early Learning Academy at Mcdonough |
| Cherokee Academy by Chroma, Canton GA | 4406 | CCLC-1444 | Cherokee Academy At Clayton |
| Lilburn Campus | 4407 | CCLC-56658 | Chroma Early Learning Academy of Lilburn |
| Marietta Campus (East) | 4408 | CCLC-61876 | Chroma Early Learning Academy of Marietta |
| Roswell Campus | 4409 | CCLC-61877 | Chroma Early Learning Academy of Roswell |
| Ellenwood Campus | 4410 | CCLC-63978 | Chroma Early Learning Academy of Ellenwood |
| West Cobb Campus, Marietta | 4411 | CCLC-52003 | Chroma Early Learning Academy of West Cobb |
| Satellite Blvd Campus, Duluth, GA | 4413 | CCLC-1348 | Chroma Early Learning Academy @ Satellite |
| Jonesboro Campus | 4414 | CCLC-27406 | Little Creations Learning Center |
| Rivergreen Campus, Canton GA | 4415 | CCLC-36295 | Chroma Early Learning @ Rivergreen |
| South Cobb Campus, Austell | 4416 | CCLC-67078 | Chroma Early Learning @SouthCobb |
| Midway Campus, Alpharetta GA | 4417 | CCLC-67077 | Chroma Early Learning Academy @ Midway |
| North Hall Campus, Murrayville | 4419 | CCLC-67901 | Chroma Early Learning Academy @ North Hall |
| Shenandoah Campus, Newnan GA | 4420 | CCLC-68286 | Chroma Early Learning Academy @ Shenandoah |
| Chadwick Campus | 6779 | CCLC-69004 | Chroma Early Learning @ Chadwick |
| Sugarloaf Pkwy Campus | 6780 | CCLC-69011 | Chroma Early Learning @ Sugarloaf |
| Grayson Campus | 6781 | CCLC-69013 | Chroma Early Learning @ Grayson |
| Stockbridge Campus | 6782 | CCLC-69012 | Chroma Early Learning @ Stockbridge |
| Parklake Campus, Atlanta GA | 8641 | CCLC-68916 | Chroma Early Learning Academy @ Parklake |

## Staging changes

- Populated `_chroma_license_number` for all 24 published campus records.
- Corrected Parklake from `CCLC-62442` to the address-matched DECAL record `CCLC-68916`.
- Added the user-confirmed Tyrone email `tyrone@chromaela.com`.
- Aligned South Cobb's staging phone with the current live-site record: `470-207-5661`.
- Preserved all live-site-authorized director, transportation, pickup-school, hours, ages, amenities, and claim content.
- Created rollback backup: `/home/x3yyadl/backups/chroma-staging-before-campus-facts-20260719-023052.sql`.

## Verification result

- Published staging campuses: **24**
- Campuses with DECAL license metadata: **24/24**
- Campus pages returning HTTP 200 and visibly containing their assigned license: **24/24**
- Desktop/tablet/mobile checks across the location archive and campus template: **6/6 passed**
- Location interactions: geolocation sorting passed; campus-card popup opened with complete contact content.
- Visual checks: **0** horizontal-overflow, clipped-text, broken-image, missing-footer, or route-status failures.
- Theme build: **passed**
- PHP lint: **112/112 files passed**
- Remaining browser console/request noise was limited to the existing third-party chat stylesheet warning and aborted Google analytics/remarketing requests; neither produced a visible regression.
- Live site modified: **No**

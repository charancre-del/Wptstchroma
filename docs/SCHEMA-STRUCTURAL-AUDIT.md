# Schema Structural Audit (Option 1)

- Generated: 2026-02-25T08:21:21+00:00
- DB: `reports/active_schema_20260225_031423.sqlite`
- Pages: **357**
- Nodes: **2876**
- Errors: **18**
- Warnings: **476**

## HTTP Status

| Status | Count |
|---|---:|
| 200 | 344 |
| 404 | 13 |

## By Route Bucket

| Bucket | Errors | Warnings | Pages |
|---|---:|---:|---:|
| `core` | 1 | 24 | 6 |
| `location-campus` | 0 | 19 | 19 |
| `location-geo` | 0 | 380 | 49 |
| `other` | 13 | 22 | 21 |
| `program` | 0 | 31 | 8 |
| `service-area-taxonomy` | 4 | 0 | 4 |

## Error Codes

| Code | Count | Pages |
|---|---:|---:|
| `page_http_non_200` | 13 | 13 |
| `local_missing_address` | 5 | 5 |

## Warning Codes

| Code | Count | Pages |
|---|---:|---:|
| `local_address_missing_streetAddress` | 113 | 62 |
| `local_address_missing_postalCode` | 113 | 62 |
| `local_address_missing_addressRegion` | 106 | 60 |
| `local_address_missing_addressLocality` | 105 | 62 |
| `unknown_schema_type` | 39 | 32 |

## Full Issue Inventory (Code -> URL)

### Errors

#### `local_missing_address`
- (1) https://chromaela.com/
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-cobb
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-gwinnett
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-northmetro
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-southmetro

#### `page_http_non_200`
- (1) https://chromaela.com/?post_type=team_member&p=4284
- (1) https://chromaela.com/?post_type=team_member&p=4295
- (1) https://chromaela.com/?post_type=team_member&p=4296
- (1) https://chromaela.com/?post_type=team_member&p=4297
- (1) https://chromaela.com/?post_type=team_member&p=4298
- (1) https://chromaela.com/?post_type=team_member&p=4299
- (1) https://chromaela.com/?post_type=team_member&p=4300
- (1) https://chromaela.com/?post_type=team_member&p=4301
- (1) https://chromaela.com/?post_type=team_member&p=4302
- (1) https://chromaela.com/?post_type=team_member&p=4303
- (1) https://chromaela.com/?post_type=team_member&p=4304
- (1) https://chromaela.com/?post_type=team_member&p=4305
- (1) https://chromaela.com/?post_type=team_member&p=4381

### Warnings

#### `local_address_missing_addressLocality`
- (2) https://chromaela.com/childcare/alpharetta/
- (2) https://chromaela.com/childcare/ballground/
- (2) https://chromaela.com/childcare/canton/
- (2) https://chromaela.com/childcare/cumming/
- (2) https://chromaela.com/childcare/dahlonega/
- (2) https://chromaela.com/childcare/dawsonville/
- (2) https://chromaela.com/childcare/decatur/
- (2) https://chromaela.com/childcare/duluth/
- (2) https://chromaela.com/childcare/east-cobb/
- (2) https://chromaela.com/childcare/ellenwood/
- (2) https://chromaela.com/childcare/fairburn/
- (2) https://chromaela.com/childcare/fayetteville/
- (2) https://chromaela.com/childcare/gainesville/
- (2) https://chromaela.com/childcare/griffin/
- (2) https://chromaela.com/childcare/jasper/
- (2) https://chromaela.com/childcare/lawrenceville/
- (2) https://chromaela.com/childcare/lilburn/
- (2) https://chromaela.com/childcare/lithia-springs/
- (2) https://chromaela.com/childcare/lovejoy/
- (2) https://chromaela.com/childcare/mableton/
- (2) https://chromaela.com/childcare/marietta/
- (2) https://chromaela.com/childcare/mcdonough/
- (2) https://chromaela.com/childcare/milton/
- (2) https://chromaela.com/childcare/morrow/
- (2) https://chromaela.com/childcare/murrayville/
- (2) https://chromaela.com/childcare/newnan/
- (2) https://chromaela.com/childcare/norcross/
- (2) https://chromaela.com/childcare/north-hall/
- (2) https://chromaela.com/childcare/palmetto/
- (2) https://chromaela.com/childcare/peachtree-city/
- (2) https://chromaela.com/childcare/peachtree-corners/
- (2) https://chromaela.com/childcare/powder-springs/
- (2) https://chromaela.com/childcare/rex/
- (2) https://chromaela.com/childcare/roswell/
- (2) https://chromaela.com/childcare/snellville/
- (2) https://chromaela.com/childcare/stockbridge/
- (2) https://chromaela.com/childcare/stone-mountain/
- (2) https://chromaela.com/childcare/tyrone/
- (2) https://chromaela.com/childcare/waleska/
- (2) https://chromaela.com/childcare/west-cobb/
- (2) https://chromaela.com/childcare/woodstock/
- (2) https://chromaela.com/employers/
- (2) https://chromaela.com/schedule-a-tour/
- (1) https://chromaela.com/about/
- (1) https://chromaela.com/childcare/austell/
- (1) https://chromaela.com/childcare/clermont/
- (1) https://chromaela.com/childcare/hampton/
- (1) https://chromaela.com/childcare/johns-creek/
- (1) https://chromaela.com/childcare/jonesboro/
- (1) https://chromaela.com/childcare/kennesaw/
- (1) https://chromaela.com/childcare/locust-grove/
- (1) https://chromaela.com/childcare/tucker/
- (1) https://chromaela.com/curriculum/
- (1) https://chromaela.com/parents/
- (1) https://chromaela.com/programs/after-school/
- (1) https://chromaela.com/programs/camp-summer-winter-fall/
- (1) https://chromaela.com/programs/ga-pre-k/
- (1) https://chromaela.com/programs/infant-care/
- (1) https://chromaela.com/programs/parents-day-out/
- (1) https://chromaela.com/programs/pre-k-prep/
- (1) https://chromaela.com/programs/preschool/
- (1) https://chromaela.com/programs/toddler-care/

#### `local_address_missing_addressRegion`
- (2) https://chromaela.com/childcare/alpharetta/
- (2) https://chromaela.com/childcare/ballground/
- (2) https://chromaela.com/childcare/canton/
- (2) https://chromaela.com/childcare/clermont/
- (2) https://chromaela.com/childcare/cumming/
- (2) https://chromaela.com/childcare/dahlonega/
- (2) https://chromaela.com/childcare/dawsonville/
- (2) https://chromaela.com/childcare/decatur/
- (2) https://chromaela.com/childcare/duluth/
- (2) https://chromaela.com/childcare/east-cobb/
- (2) https://chromaela.com/childcare/ellenwood/
- (2) https://chromaela.com/childcare/fairburn/
- (2) https://chromaela.com/childcare/fayetteville/
- (2) https://chromaela.com/childcare/gainesville/
- (2) https://chromaela.com/childcare/griffin/
- (2) https://chromaela.com/childcare/jasper/
- (2) https://chromaela.com/childcare/kennesaw/
- (2) https://chromaela.com/childcare/lawrenceville/
- (2) https://chromaela.com/childcare/lilburn/
- (2) https://chromaela.com/childcare/lithia-springs/
- (2) https://chromaela.com/childcare/locust-grove/
- (2) https://chromaela.com/childcare/lovejoy/
- (2) https://chromaela.com/childcare/mableton/
- (2) https://chromaela.com/childcare/marietta/
- (2) https://chromaela.com/childcare/mcdonough/
- (2) https://chromaela.com/childcare/milton/
- (2) https://chromaela.com/childcare/morrow/
- (2) https://chromaela.com/childcare/murrayville/
- (2) https://chromaela.com/childcare/newnan/
- (2) https://chromaela.com/childcare/norcross/
- (2) https://chromaela.com/childcare/north-hall/
- (2) https://chromaela.com/childcare/palmetto/
- (2) https://chromaela.com/childcare/peachtree-city/
- (2) https://chromaela.com/childcare/peachtree-corners/
- (2) https://chromaela.com/childcare/powder-springs/
- (2) https://chromaela.com/childcare/rex/
- (2) https://chromaela.com/childcare/roswell/
- (2) https://chromaela.com/childcare/snellville/
- (2) https://chromaela.com/childcare/stockbridge/
- (2) https://chromaela.com/childcare/stone-mountain/
- (2) https://chromaela.com/childcare/tucker/
- (2) https://chromaela.com/childcare/tyrone/
- (2) https://chromaela.com/childcare/waleska/
- (2) https://chromaela.com/childcare/west-cobb/
- (2) https://chromaela.com/childcare/woodstock/
- (2) https://chromaela.com/employers/
- (1) https://chromaela.com/about/
- (1) https://chromaela.com/childcare/austell/
- (1) https://chromaela.com/childcare/hampton/
- (1) https://chromaela.com/childcare/johns-creek/
- (1) https://chromaela.com/childcare/jonesboro/
- (1) https://chromaela.com/curriculum/
- (1) https://chromaela.com/parents/
- (1) https://chromaela.com/programs/after-school/
- (1) https://chromaela.com/programs/camp-summer-winter-fall/
- (1) https://chromaela.com/programs/ga-pre-k/
- (1) https://chromaela.com/programs/infant-care/
- (1) https://chromaela.com/programs/parents-day-out/
- (1) https://chromaela.com/programs/preschool/
- (1) https://chromaela.com/programs/toddler-care/

#### `local_address_missing_postalCode`
- (2) https://chromaela.com/childcare/alpharetta/
- (2) https://chromaela.com/childcare/austell/
- (2) https://chromaela.com/childcare/ballground/
- (2) https://chromaela.com/childcare/canton/
- (2) https://chromaela.com/childcare/clermont/
- (2) https://chromaela.com/childcare/cumming/
- (2) https://chromaela.com/childcare/dahlonega/
- (2) https://chromaela.com/childcare/dawsonville/
- (2) https://chromaela.com/childcare/decatur/
- (2) https://chromaela.com/childcare/duluth/
- (2) https://chromaela.com/childcare/east-cobb/
- (2) https://chromaela.com/childcare/ellenwood/
- (2) https://chromaela.com/childcare/fairburn/
- (2) https://chromaela.com/childcare/fayetteville/
- (2) https://chromaela.com/childcare/gainesville/
- (2) https://chromaela.com/childcare/griffin/
- (2) https://chromaela.com/childcare/hampton/
- (2) https://chromaela.com/childcare/jasper/
- (2) https://chromaela.com/childcare/johns-creek/
- (2) https://chromaela.com/childcare/jonesboro/
- (2) https://chromaela.com/childcare/kennesaw/
- (2) https://chromaela.com/childcare/lawrenceville/
- (2) https://chromaela.com/childcare/lilburn/
- (2) https://chromaela.com/childcare/lithia-springs/
- (2) https://chromaela.com/childcare/locust-grove/
- (2) https://chromaela.com/childcare/lovejoy/
- (2) https://chromaela.com/childcare/mableton/
- (2) https://chromaela.com/childcare/marietta/
- (2) https://chromaela.com/childcare/mcdonough/
- (2) https://chromaela.com/childcare/milton/
- (2) https://chromaela.com/childcare/morrow/
- (2) https://chromaela.com/childcare/murrayville/
- (2) https://chromaela.com/childcare/newnan/
- (2) https://chromaela.com/childcare/norcross/
- (2) https://chromaela.com/childcare/north-hall/
- (2) https://chromaela.com/childcare/palmetto/
- (2) https://chromaela.com/childcare/peachtree-city/
- (2) https://chromaela.com/childcare/peachtree-corners/
- (2) https://chromaela.com/childcare/powder-springs/
- (2) https://chromaela.com/childcare/rex/
- (2) https://chromaela.com/childcare/roswell/
- (2) https://chromaela.com/childcare/snellville/
- (2) https://chromaela.com/childcare/stockbridge/
- (2) https://chromaela.com/childcare/stone-mountain/
- (2) https://chromaela.com/childcare/tucker/
- (2) https://chromaela.com/childcare/tyrone/
- (2) https://chromaela.com/childcare/waleska/
- (2) https://chromaela.com/childcare/west-cobb/
- (2) https://chromaela.com/childcare/woodstock/
- (2) https://chromaela.com/employers/
- (2) https://chromaela.com/schedule-a-tour/
- (1) https://chromaela.com/about/
- (1) https://chromaela.com/curriculum/
- (1) https://chromaela.com/parents/
- (1) https://chromaela.com/programs/after-school/
- (1) https://chromaela.com/programs/camp-summer-winter-fall/
- (1) https://chromaela.com/programs/ga-pre-k/
- (1) https://chromaela.com/programs/infant-care/
- (1) https://chromaela.com/programs/parents-day-out/
- (1) https://chromaela.com/programs/pre-k-prep/
- (1) https://chromaela.com/programs/preschool/
- (1) https://chromaela.com/programs/toddler-care/

#### `local_address_missing_streetAddress`
- (2) https://chromaela.com/childcare/alpharetta/
- (2) https://chromaela.com/childcare/austell/
- (2) https://chromaela.com/childcare/ballground/
- (2) https://chromaela.com/childcare/canton/
- (2) https://chromaela.com/childcare/clermont/
- (2) https://chromaela.com/childcare/cumming/
- (2) https://chromaela.com/childcare/dahlonega/
- (2) https://chromaela.com/childcare/dawsonville/
- (2) https://chromaela.com/childcare/decatur/
- (2) https://chromaela.com/childcare/duluth/
- (2) https://chromaela.com/childcare/east-cobb/
- (2) https://chromaela.com/childcare/ellenwood/
- (2) https://chromaela.com/childcare/fairburn/
- (2) https://chromaela.com/childcare/fayetteville/
- (2) https://chromaela.com/childcare/gainesville/
- (2) https://chromaela.com/childcare/griffin/
- (2) https://chromaela.com/childcare/hampton/
- (2) https://chromaela.com/childcare/jasper/
- (2) https://chromaela.com/childcare/johns-creek/
- (2) https://chromaela.com/childcare/jonesboro/
- (2) https://chromaela.com/childcare/kennesaw/
- (2) https://chromaela.com/childcare/lawrenceville/
- (2) https://chromaela.com/childcare/lilburn/
- (2) https://chromaela.com/childcare/lithia-springs/
- (2) https://chromaela.com/childcare/locust-grove/
- (2) https://chromaela.com/childcare/lovejoy/
- (2) https://chromaela.com/childcare/mableton/
- (2) https://chromaela.com/childcare/marietta/
- (2) https://chromaela.com/childcare/mcdonough/
- (2) https://chromaela.com/childcare/milton/
- (2) https://chromaela.com/childcare/morrow/
- (2) https://chromaela.com/childcare/murrayville/
- (2) https://chromaela.com/childcare/newnan/
- (2) https://chromaela.com/childcare/norcross/
- (2) https://chromaela.com/childcare/north-hall/
- (2) https://chromaela.com/childcare/palmetto/
- (2) https://chromaela.com/childcare/peachtree-city/
- (2) https://chromaela.com/childcare/peachtree-corners/
- (2) https://chromaela.com/childcare/powder-springs/
- (2) https://chromaela.com/childcare/rex/
- (2) https://chromaela.com/childcare/roswell/
- (2) https://chromaela.com/childcare/snellville/
- (2) https://chromaela.com/childcare/stockbridge/
- (2) https://chromaela.com/childcare/stone-mountain/
- (2) https://chromaela.com/childcare/tucker/
- (2) https://chromaela.com/childcare/tyrone/
- (2) https://chromaela.com/childcare/waleska/
- (2) https://chromaela.com/childcare/west-cobb/
- (2) https://chromaela.com/childcare/woodstock/
- (2) https://chromaela.com/employers/
- (2) https://chromaela.com/schedule-a-tour/
- (1) https://chromaela.com/about/
- (1) https://chromaela.com/curriculum/
- (1) https://chromaela.com/parents/
- (1) https://chromaela.com/programs/after-school/
- (1) https://chromaela.com/programs/camp-summer-winter-fall/
- (1) https://chromaela.com/programs/ga-pre-k/
- (1) https://chromaela.com/programs/infant-care/
- (1) https://chromaela.com/programs/parents-day-out/
- (1) https://chromaela.com/programs/pre-k-prep/
- (1) https://chromaela.com/programs/preschool/
- (1) https://chromaela.com/programs/toddler-care/

#### `unknown_schema_type`
- (2) https://chromaela.com/2025/12/10/5-things-to-say-instead-of-stop-crying/
- (2) https://chromaela.com/2025/12/10/how-employee-benefits-surveys-boost-retention-and-support-business-growth/
- (2) https://chromaela.com/2025/12/10/how-workplace-culture-shapes-employee-retention-key-factors-and-practical-strategies/
- (2) https://chromaela.com/2025/12/10/learn-how-play-based-learning-transforms-early-childhood-education-for-holistic-child-development/
- (2) https://chromaela.com/2025/12/10/why-children-need-movement-every-day/
- (2) https://chromaela.com/2025/12/10/your-daycare-tour-questions-a-parents-essential-checklist/
- (2) https://chromaela.com/2025/12/11/4-things-to-say-when-your-child-isnt-listening-that-actually-work/
- (1) https://chromaela.com/
- (1) https://chromaela.com/about/
- (1) https://chromaela.com/contact-us/
- (1) https://chromaela.com/curriculum/
- (1) https://chromaela.com/locations/cherokee-campus/
- (1) https://chromaela.com/locations/east-cobb-campus/
- (1) https://chromaela.com/locations/ellenwood-campus/
- (1) https://chromaela.com/locations/johns-creek/
- (1) https://chromaela.com/locations/jonesboro-campus/
- (1) https://chromaela.com/locations/lawrenceville-campus/
- (1) https://chromaela.com/locations/lilburn-campus/
- (1) https://chromaela.com/locations/mcdonough/
- (1) https://chromaela.com/locations/midway-campus/
- (1) https://chromaela.com/locations/newnan/
- (1) https://chromaela.com/locations/north-hall-campus-murraysville/
- (1) https://chromaela.com/locations/pleasanthill-campus-duluth/
- (1) https://chromaela.com/locations/rivergreen-campus/
- (1) https://chromaela.com/locations/roswell-campus/
- (1) https://chromaela.com/locations/satellite-bvd-campus/
- (1) https://chromaela.com/locations/south-cobb-campus-austell/
- (1) https://chromaela.com/locations/tramore-campus-austell/
- (1) https://chromaela.com/locations/tyrone-campus/
- (1) https://chromaela.com/locations/west-cobb-campus/
- (1) https://chromaela.com/parents/
- (1) https://chromaela.com/schedule-a-tour/

## Questions For You

1. Should we treat all `unknown_schema_type` warnings as must-remove?
2. For `location-geo` pages, keep LocalBusiness/ChildCare with complete address or replace with Service/WebPage only?
3. Should we enforce `addressCountry` as required for all PostalAddress objects?
4. Should all 13 non-200 sitemap URLs be removed in the same release?

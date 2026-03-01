# Full Schema Warning/Error Audit

- Generated: 2026-02-25T07:09:43Z
- Snapshot DB: `reports/active_schema_20260225_020508.sqlite`
- Audit JSON: `reports/schema_validation_audit_20260225_020508.json`

## Scope and Coverage

- Sitemap URLs processed: **357**
- Schema nodes validated: **2302**
- Parse-error blocks: **0**
- HTTP 200 pages: **344**
- Non-200 pages: **13** (404: 13)

## Overall Results

- Total errors: **5**
- Total warnings: **609**
- Unique pages with any issue: **68**

## Issues by Route Bucket

| Bucket | Pages in Snapshot | Error Count | Warning Count | Affected Pages |
|---|---:|---:|---:|---:|
| `core` | 12 | 1 | 28 | 6 |
| `program` | 8 | 0 | 39 | 8 |
| `location-campus` | 19 | 0 | 0 | 0 |
| `location-geo` | 49 | 0 | 527 | 49 |
| `service-area-taxonomy` | 4 | 4 | 4 | 4 |
| `other` | 265 | 0 | 11 | 1 |

## Error Inventory (All)

| Code | Total | Unique Pages | Schema Types |
|---|---:|---:|---|
| `missing_address` | 5 | 5 | `ChildCare` |

### Error Details by URL

#### `missing_address`
- (1) https://chromaela.com/ - 3650 Club Dr, Lawrenceville, GA 30044
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-cobb
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-gwinnett
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-northmetro
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-southmetro

## Warning Inventory (All)

| Code | Total | Unique Pages | Schema Types |
|---|---:|---:|---|
| `missing_telephone` | 120 | 68 | `ChildCare`, `LocalBusiness` |
| `address_missing_postalCode` | 113 | 62 | `ChildCare`, `LocalBusiness` |
| `address_missing_streetAddress` | 113 | 62 | `ChildCare`, `LocalBusiness` |
| `address_missing_addressRegion` | 106 | 60 | `ChildCare`, `LocalBusiness` |
| `address_missing_addressLocality` | 105 | 62 | `ChildCare`, `LocalBusiness` |
| `missing_geo` | 52 | 52 | `LocalBusiness` |

### Warning Details by URL

#### `missing_telephone` - Get the phone number from the location page
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
- (2) https://chromaela.com/contact-us/
- (2) https://chromaela.com/employers/
- (2) https://chromaela.com/schedule-a-tour/
- (1) https://chromaela.com/
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-cobb
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-gwinnett
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-northmetro
- (1) https://chromaela.com/?taxonomy=location_region&term=service-areas-southmetro
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

#### `address_missing_postalCode` - Get the postal code from the closest location
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

#### `address_missing_streetAddress` - Get the street address for the locations
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

#### `address_missing_addressRegion` Get it for the closest location
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

#### `address_missing_addressLocality` Get it for the closest location
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

#### `missing_geo` get it for the closest location
- (1) https://chromaela.com/childcare/alpharetta/
- (1) https://chromaela.com/childcare/austell/
- (1) https://chromaela.com/childcare/ballground/
- (1) https://chromaela.com/childcare/canton/
- (1) https://chromaela.com/childcare/clermont/
- (1) https://chromaela.com/childcare/cumming/
- (1) https://chromaela.com/childcare/dahlonega/
- (1) https://chromaela.com/childcare/dawsonville/
- (1) https://chromaela.com/childcare/decatur/
- (1) https://chromaela.com/childcare/duluth/
- (1) https://chromaela.com/childcare/east-cobb/
- (1) https://chromaela.com/childcare/ellenwood/
- (1) https://chromaela.com/childcare/fairburn/
- (1) https://chromaela.com/childcare/fayetteville/
- (1) https://chromaela.com/childcare/gainesville/
- (1) https://chromaela.com/childcare/griffin/
- (1) https://chromaela.com/childcare/hampton/
- (1) https://chromaela.com/childcare/jasper/
- (1) https://chromaela.com/childcare/johns-creek/
- (1) https://chromaela.com/childcare/jonesboro/
- (1) https://chromaela.com/childcare/kennesaw/
- (1) https://chromaela.com/childcare/lawrenceville/
- (1) https://chromaela.com/childcare/lilburn/
- (1) https://chromaela.com/childcare/lithia-springs/
- (1) https://chromaela.com/childcare/locust-grove/
- (1) https://chromaela.com/childcare/lovejoy/
- (1) https://chromaela.com/childcare/mableton/
- (1) https://chromaela.com/childcare/marietta/
- (1) https://chromaela.com/childcare/mcdonough/
- (1) https://chromaela.com/childcare/milton/
- (1) https://chromaela.com/childcare/morrow/
- (1) https://chromaela.com/childcare/murrayville/
- (1) https://chromaela.com/childcare/newnan/
- (1) https://chromaela.com/childcare/norcross/
- (1) https://chromaela.com/childcare/north-hall/
- (1) https://chromaela.com/childcare/palmetto/
- (1) https://chromaela.com/childcare/peachtree-city/
- (1) https://chromaela.com/childcare/peachtree-corners/
- (1) https://chromaela.com/childcare/powder-springs/
- (1) https://chromaela.com/childcare/rex/
- (1) https://chromaela.com/childcare/roswell/
- (1) https://chromaela.com/childcare/snellville/
- (1) https://chromaela.com/childcare/stockbridge/
- (1) https://chromaela.com/childcare/stone-mountain/
- (1) https://chromaela.com/childcare/tucker/
- (1) https://chromaela.com/childcare/tyrone/
- (1) https://chromaela.com/childcare/waleska/
- (1) https://chromaela.com/childcare/west-cobb/
- (1) https://chromaela.com/childcare/woodstock/
- (1) https://chromaela.com/contact-us/
- (1) https://chromaela.com/employers/
- (1) https://chromaela.com/schedule-a-tour/

## Issues by Schema Type

| Schema Type | Issue Count |
|---|---:|
| `ChildCare` | 311 |
| `LocalBusiness` | 303 |

## Decision Questions For You

1. For `/childcare/*` geo pages, should we **remove `ChildCare`/`LocalBusiness` entirely** and keep only `Service`/`WebPage` schema? see my comments next to the heading
2. For core marketing pages (`/about/`, `/parents/`, `/curriculum/`, `/schedule-a-tour/`), should we enforce **Organization/WebPage only** unless a real physical campus is the primary entity? Yes
3. Should `telephone` and `geo` be treated as **required** for any emitted `LocalBusiness`/`ChildCare` and block output when missing? Yes
4. Should we remove service-area taxonomy pages from LocalBusiness/ChildCare output and keep them as `Service` pages only? Yes
5. Do you want the 13 broken team-member sitemap URLs removed immediately from sitemap output in the same fix batch? Yes
6. Confirm policy: keep campus detail pages (`/locations/*`) as the only pages allowed to carry full campus schema types. Yes

## Next Action Once You Confirm

- Implement route-level schema gating in SEO plugin.
- Re-run crawl + audit and attach before/after diff in this file.
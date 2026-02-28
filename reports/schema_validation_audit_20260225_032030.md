# Schema Validation Audit

- Generated: 2026-02-25T08:20:30+00:00
- Source DB: `reports/active_schema_20260225_031423.sqlite`
- Pages scanned: 357
- Schema nodes scanned: 2876
- Parse error blocks: 0
- Error issues: 5
- Warning issues: 609

## Top Error Codes

| Code | Count |
|---|---:|
| `missing_address` | 5 |

## Top Warning Codes

| Code | Count |
|---|---:|
| `missing_telephone` | 120 |
| `address_missing_streetAddress` | 113 |
| `address_missing_postalCode` | 113 |
| `address_missing_addressRegion` | 106 |
| `address_missing_addressLocality` | 105 |
| `missing_geo` | 52 |

## Most Affected Pages

| URL | Issues |
|---|---:|
| https://chromaela.com/childcare/alpharetta/ | 11 |
| https://chromaela.com/childcare/ballground/ | 11 |
| https://chromaela.com/childcare/canton/ | 11 |
| https://chromaela.com/childcare/cumming/ | 11 |
| https://chromaela.com/childcare/dahlonega/ | 11 |
| https://chromaela.com/childcare/dawsonville/ | 11 |
| https://chromaela.com/childcare/decatur/ | 11 |
| https://chromaela.com/childcare/duluth/ | 11 |
| https://chromaela.com/childcare/east-cobb/ | 11 |
| https://chromaela.com/childcare/ellenwood/ | 11 |
| https://chromaela.com/childcare/fairburn/ | 11 |
| https://chromaela.com/childcare/fayetteville/ | 11 |
| https://chromaela.com/childcare/gainesville/ | 11 |
| https://chromaela.com/childcare/griffin/ | 11 |
| https://chromaela.com/childcare/jasper/ | 11 |
| https://chromaela.com/childcare/lawrenceville/ | 11 |
| https://chromaela.com/childcare/lilburn/ | 11 |
| https://chromaela.com/childcare/lithia-springs/ | 11 |
| https://chromaela.com/childcare/lovejoy/ | 11 |
| https://chromaela.com/childcare/mableton/ | 11 |
| https://chromaela.com/childcare/marietta/ | 11 |
| https://chromaela.com/childcare/mcdonough/ | 11 |
| https://chromaela.com/childcare/milton/ | 11 |
| https://chromaela.com/childcare/morrow/ | 11 |
| https://chromaela.com/childcare/murrayville/ | 11 |
| https://chromaela.com/childcare/newnan/ | 11 |
| https://chromaela.com/childcare/norcross/ | 11 |
| https://chromaela.com/childcare/north-hall/ | 11 |
| https://chromaela.com/childcare/palmetto/ | 11 |
| https://chromaela.com/childcare/peachtree-city/ | 11 |
| https://chromaela.com/childcare/peachtree-corners/ | 11 |
| https://chromaela.com/childcare/powder-springs/ | 11 |
| https://chromaela.com/childcare/rex/ | 11 |
| https://chromaela.com/childcare/roswell/ | 11 |
| https://chromaela.com/childcare/snellville/ | 11 |
| https://chromaela.com/childcare/stockbridge/ | 11 |
| https://chromaela.com/childcare/stone-mountain/ | 11 |
| https://chromaela.com/childcare/tyrone/ | 11 |
| https://chromaela.com/childcare/waleska/ | 11 |
| https://chromaela.com/childcare/west-cobb/ | 11 |
| https://chromaela.com/childcare/woodstock/ | 11 |
| https://chromaela.com/employers/ | 11 |
| https://chromaela.com/childcare/clermont/ | 10 |
| https://chromaela.com/childcare/kennesaw/ | 10 |
| https://chromaela.com/childcare/locust-grove/ | 10 |
| https://chromaela.com/childcare/tucker/ | 10 |
| https://chromaela.com/childcare/austell/ | 9 |
| https://chromaela.com/childcare/hampton/ | 9 |
| https://chromaela.com/childcare/johns-creek/ | 9 |
| https://chromaela.com/childcare/jonesboro/ | 9 |

## Issues by Schema Type

| Schema Type | Issues |
|---|---:|
| `ChildCare` | 311 |
| `LocalBusiness` | 303 |

## Parse Errors

- None

## Sample Validation Issues

- [ERROR] `missing_address` on `ChildCare` at `https://chromaela.com/`: Missing required 'address' object.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/`: ChildCare missing 'telephone'.
- [ERROR] `missing_address` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-cobb`: Missing required 'address' object.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-cobb`: ChildCare missing 'telephone'.
- [ERROR] `missing_address` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-gwinnett`: Missing required 'address' object.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-gwinnett`: ChildCare missing 'telephone'.
- [ERROR] `missing_address` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-northmetro`: Missing required 'address' object.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-northmetro`: ChildCare missing 'telephone'.
- [ERROR] `missing_address` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-southmetro`: Missing required 'address' object.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/?taxonomy=location_region&term=service-areas-southmetro`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/about/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/about/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/about/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/about/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/about/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/alpharetta/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/alpharetta/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/alpharetta/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/alpharetta/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/austell/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/austell/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/austell/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/austell/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/austell/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/austell/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/austell/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/austell/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/austell/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/ballground/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/ballground/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/ballground/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/ballground/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/ballground/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/ballground/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/ballground/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/ballground/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/ballground/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/ballground/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/ballground/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/canton/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/canton/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/canton/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/canton/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/canton/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/canton/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/canton/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/canton/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/canton/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/canton/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/canton/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/clermont/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/clermont/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/clermont/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/clermont/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/clermont/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/clermont/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/clermont/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/clermont/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/clermont/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/clermont/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/cumming/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/cumming/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/cumming/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/cumming/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/cumming/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/cumming/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/cumming/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/cumming/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/cumming/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/cumming/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/cumming/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/dahlonega/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/dahlonega/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/dahlonega/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/dahlonega/`: ChildCare missing 'telephone'.
- [WARNING] `address_missing_streetAddress` on `LocalBusiness` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `LocalBusiness` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `LocalBusiness` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `LocalBusiness` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `LocalBusiness` at `https://chromaela.com/childcare/dawsonville/`: LocalBusiness missing 'telephone'.
- [WARNING] `missing_geo` on `LocalBusiness` at `https://chromaela.com/childcare/dawsonville/`: LocalBusiness missing 'geo'.
- [WARNING] `address_missing_streetAddress` on `ChildCare` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'streetAddress'.
- [WARNING] `address_missing_addressLocality` on `ChildCare` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'addressLocality'.
- [WARNING] `address_missing_addressRegion` on `ChildCare` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'addressRegion'.
- [WARNING] `address_missing_postalCode` on `ChildCare` at `https://chromaela.com/childcare/dawsonville/`: Address missing 'postalCode'.
- [WARNING] `missing_telephone` on `ChildCare` at `https://chromaela.com/childcare/dawsonville/`: ChildCare missing 'telephone'.

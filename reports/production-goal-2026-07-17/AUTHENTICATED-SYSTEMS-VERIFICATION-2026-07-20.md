# Authenticated Systems Verification

**Date:** 2026-07-20
**Scope:** Chroma Excellence Theme 2.0, staging readiness only
**Production launch:** Not performed

## Google Analytics and Tag Manager

- Staging renders GTM container `GTM-MNSGJWMB`, GA4 measurement ID `G-KNBS4JH2FH`, and Google Ads ID `AW-16447891023`.
- The authenticated GA4 export for property `430494947` contains the following 30-day evidence:
  - `page_view`: 24,608
  - `session_start`: 12,400
  - `scroll`: 3,149
  - `ads_conversion_Submit_lead_form_1`: 576 events / 576 conversions
  - `click`: 272
  - `file_download`: 46
  - `chroma_ghl_schedule_tour_confirmed`: 35
  - `chroma_ghl_form_confirmed`: 28
- Theme 2.0 now emits privacy-safe intent events for location use, region filtering, campus focus, calls, directions, tours, campus views, program views, Parent Portal, careers, and program/campus page views.
- No phone numbers, email addresses, form values, or visitor coordinates are sent by the new theme event layer.
- Direct staging assertions passed for `location_card_focus`, `view_campus`, `program_view`, and `schedule_tour_click`. Location permission, phone, directions, page-view, and location-sorting behavior were also verified during the staging interaction checks.
- Confirmed GHL events remain the source for completed form outcomes; click events are intent signals, not completed enrollments.

## Google Search Console

- Authenticated property: `sc-domain:chromaela.com`.
- Daily rows: 348.
- Coverage dates: 2025-08-02 through 2026-07-17.
- Last authenticated fetch: 2026-07-19T21:30:13Z.
- Page rows: 115,121.
- Query rows: 160,129.
- The Search Console data job is active, so authenticated ownership and ongoing collection are verified.

## Google Business Profile

- Latest comprehensive pull: 2026-07-18.
- Mixed-brand source rows: 42 total, including 25 ELA rows.
- Unique ELA Place IDs: 24 of 24 campuses.
- Latest performance pull: 2026-07-19, covering 2026-06-21 through 2026-07-19.
- Performance coverage: all 24 unique ELA profiles, plus one Chroma Early Start profile.
- One duplicate Midway ELA row exists in the source export; it does not represent a missing campus and should be cleaned up in the platform data when convenient.

## Interpretation

Authenticated GA4/GTM, Search Console, and ELA GBP access are no longer open evidence gates for staging readiness. Newly added intent events still require real traffic before trend reporting or conversion-rate conclusions are meaningful. Local preview referrals should be excluded from analytics reporting because historical preview traffic exists in the export.

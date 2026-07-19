# Conversion Report

## Implemented conversion architecture

- Primary intent: Schedule a Tour.
- Secondary intents: Use My Location, View Campus, Call, Directions, Contact, program exploration, resources, and careers.
- Standard conversion surfaces are present across Home, Programs, Locations, campus pages, Curriculum, Parents, Contact, and supporting templates.
- Location discovery supports opt-in geolocation, distance sorting, region filters, card/map synchronization, full popup focus, and campus links.
- Forms are responsive and attribution-aware; tuition copy avoids stale public pricing promises.

## Automated proof

- Location permission, sorting, distance display, region filters, card focus, and popup behavior passed.
- Curriculum selector exposed and changed program views.
- 78 route/device checks found no clipped or broken conversion surfaces.
- Theme-owned accessibility issues affecting conversion were cleared.

## Authenticated completion gates

The following cannot be proven from public staging HTML:

1. Correct GHL contact/opportunity creation and campus/program field mapping.
2. One-time confirmation, email, SMS, calendar, and owner assignment.
3. Consent storage, STOP language, and duplicate-submit handling.
4. First/last landing URL, UTM, click ID, referrer, and cross-domain persistence.
5. Careers routing and error/timeout behavior.
6. GA4/GTM/Ads primary and secondary conversion event accuracy.

## Readiness

- Theme/UI conversion readiness: **94/100**.
- End-to-end operational readiness: **pending authenticated CRM, messaging, and analytics proof**.

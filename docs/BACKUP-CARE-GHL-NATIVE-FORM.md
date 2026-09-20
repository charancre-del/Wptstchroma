# Backup Care Native GHL Form

## Current Boundary

The Backup Care page works without the `chroma-backup-care` plugin. WordPress
owns the reservation user interface and validation; GHL owns contact capture,
the Backup Care Order fields, payment, Stripe test mode, and form submission.
Nothing in this change publishes the WordPress page or enables live payment.

## GHL Configuration

- Form: `BC | Backup Care Reservation v2`
- Form ID: `fAe2ihMzdCX9kvqfNQyQ`
- Public embed: `https://api.leadconnectorhq.com/widget/form/fAe2ihMzdCX9kvqfNQyQ`
- Product: `BC | Backup Care Day | TEST`
- Unit price: `$115` per child per care date
- Payment provider: Stripe connected inside GHL
- Payment mode: Test
- Product quantity: visible and customer-editable
- Order field: `custom_objects.backup_care_order.reservation_details`
- Reservation Details presentation: hidden

The legacy `BC | Booking Terms` form remains unchanged. The v2 form is an
isolated copy so it can be accepted before any live-site cutover.

## Theme Runtime

`page-backup-care.php` mounts `assets/js/backup-care-ghl.js` directly. The
script validates the family, campus, children, dates, ages, booking horizon,
same-day notice, drop-off cutoff, and policy acceptance. It calculates:

```text
child-date units = number of children x number of care dates
total = child-date units x $115
```

Two children across two dates therefore produces four units and a `$460`
total. The parent must set the GHL product quantity to the unit count displayed
by the theme before submitting payment. GHL recalculates the order total and
Stripe amount from that quantity.

## Handoff Data

The theme passes the order ID, campus, unit amount, unit count, total, care-date
range, terms timestamp, and serialized reservation details to the native GHL
form. The public form accepts the reservation payload into the hidden Backup
Care Order field. No WordPress REST endpoint, plugin shortcode, plugin ledger,
direct Stripe key, or GHL private-integration token is used by the page.

Because native GHL prefill uses query parameters, reservation details are sent
to GHL only after the parent selects the payment step, but may appear in browser
and network logs. This privacy constraint must be included in launch review.

## Acceptance

- Theme PHP lint: pass
- Theme JavaScript syntax: pass
- No-plugin regression tests: pass
- Desktop and mobile layout: pass without horizontal overflow
- Native GHL product and Stripe test-mode render: pass
- Four-unit quantity recalculation to `$460`: pass
- Real Stripe payment: not attempted
- WordPress production publication: not attempted

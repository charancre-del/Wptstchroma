# Codebase Audit (Updated)

## Current Branch Check
- Reviewed the latest `work` branch (no remote configured; HEAD at `3dcdbad`) after the reported updates. The homepage stack remains unchanged from the prior audit—no new wizard, radar, schedule, or split tour modules exist under `template-parts/home` or in `front-page.php`.

## Overview
- WordPress theme `chroma-excellence-theme` plus three companion plugins deliver custom post types, ACF-powered templates, location mapping, and lead-capture forms documented in the README.

## Theme: chroma-excellence-theme
- `front-page.php` assembles fixed sections (hero, optional stats strip, programs preview, curriculum cards, locations preview, optional FAQ, and a basic tour CTA) with no flexible content to add new blocks such as schedules or interactive wizards.
- Hero pulls headline, subheadline, two CTAs, and featured image from helper accessors and renders the two-column layout; background accents and copy are static aside from those fields.
- Programs preview shows a static three-card grid (featured or latest programs) with single learn-more links, lacking the age-based selector, dynamic CTA set, or modal behaviors seen in the sample homepage.
- Curriculum section renders up to three informational cards and an optional CTA but offers no data visualization or age toggles (e.g., radar chart) from the sample concept.
- Locations preview provides an optional map plus up to three featured cards; it does not support county-grouped lists or expanded grids shown in the sample HTML.
- Tour CTA embeds a single-column shortcode form inside a gradient block; there is no split layout with a benefits sidebar like the sample page.
- Current head contains no new homepage modules beyond the above, so the Tailwind sample cannot be recreated without building additional custom blocks for the wizard, radar chart, schedule tabs, and county-grouped locations.

## Plugins
- **chroma-tour-form** shortcode now validates required fields server-side, sanitizes inputs, whitelists payload data before Lead Log insertion, and uses a validated redirect fallback to avoid empty Location headers on submission failures.
- **chroma-acquisitions-form** adds similar server-side required-field checks, sanitized payload logging to the Lead Log, and safe redirect defaults for submissions.
- **chroma-lead-log** registers the private Lead Log CPT with admin columns for type, name, email, and phone to centralize submissions from the two forms.

## Gaps vs. Requested Homepage
- Theme lacks components for the age-based program wizard, curriculum radar chart with age toggles, daily schedule tabs, benefits sidebar on the tour form, and county-grouped locations lists required to recreate the provided Tailwind homepage sample.

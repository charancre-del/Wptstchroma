# Meta Box Matrix (All Meta Box Pass)

Date: 2026-02-28
Repo: `C:\Users\chara\Documents\wptheme\Wptstchroma`

## Scope
- Source of truth used for this pass:
  - `reports/meta-box-file-matrix.csv`
  - `reports/meta-box-key-matrix.csv`
  - `reports/meta-box-per-key-matrix-2026-02-28.csv`
  - representative save handlers in theme/plugin meta-box files
- Coverage:
  - theme page/CPT meta boxes
  - `plugins/chroma-seo-pro-reset` meta boxes
  - portal/dashboard/admin-only meta boxes

## Access Token Clarification
- `chroma-agent/v1/*` write routes use the Agent API key:
  - `Authorization: Bearer {API_KEY}`
  - `x-api-key: {API_KEY}` also works
- `chroma-agent/v1/geo-feed` is public and requires no token.
- `chroma/v1/portal/*` uses the Google-auth session bearer token.
- `chroma-portal/v1/*` uses the parent-portal token header.

## Public GEO Guardrail
- Public exposure is now enforced in code by:
  - `plugins/chroma-agent-api/includes/routes/class-geo-routes.php`
- The public GEO feed uses:
  - curated allowlists for location/program meta that are safe to expose
  - an explicit denylist + prefix denylist as a hard backstop for sensitive meta (`_cp_*`, `_chroma_school_*`, schema review internals, lead payload keys)

## Generic Content Write Guardrail
- Generic meta writes are now enforced in code by:
  - `plugins/chroma-agent-api/includes/routes/class-content-routes.php`
- `content/{id}` blocks protected meta namespaces before any write occurs, including:
  - `_cp_*`
  - `_chroma_school_*`
  - `lead_*`
  - schema-internal keys that should go through `seo/meta/{id}` or `seo/schema/{id}`
- When blocked keys are attempted, the route returns:
  - `403 caa_write_policy_blocked`
  - structured `blocked_meta` details in the error payload
- Programmatic introspection is available at:
  - `GET /wp-json/chroma-agent/v1/write-policy`
  - optional query: `?meta_key=_cp_pin_hash`

### Write-Policy Response Example
```json
{
  "success": true,
  "data": {
    "route": "/wp-json/chroma-agent/v1/content/{id}",
    "enforcement": "denylist",
    "applies_to": [
      "POST /content",
      "PATCH/POST/PUT /content/{id}"
    ],
    "blocked_exact": [
      {
        "meta_key": "_chroma_post_schemas",
        "reason": "Schema payloads are managed by the dedicated SEO schema route.",
        "preferred_route": "/wp-json/chroma-agent/v1/seo/schema/{id}"
      }
    ],
    "blocked_prefixes": [
      {
        "prefix": "_cp_",
        "reason": "Parent portal meta must be updated through the parent portal workflow.",
        "preferred_route": "/wp-json/chroma-portal/v1/*"
      }
    ]
  }
}
```

## GEO Contract Introspection
- GEO contract discovery is available at:
  - `GET /wp-json/chroma-agent/v1/geo-contract`
- This route is keyed introspection (valid Agent API key), while `geo-feed` itself remains public.

### GEO Contract Response Example
```json
{
  "success": true,
  "data": {
    "route": "/wp-json/chroma-agent/v1/geo-feed",
    "contract_version": "2026-02-28",
    "public": true,
    "cache_ttl_seconds": 900,
    "top_level_fields": [
      "success",
      "cached",
      "contract_version",
      "generated_at_gmt",
      "source",
      "summary",
      "brand",
      "locations",
      "programs",
      "events"
    ],
    "field_groups": {
      "brand": ["name", "description", "site_url", "contact", "curriculum"],
      "locations": ["id", "campus_name", "slug", "url", "address", "phone_number", "email", "administrator_name", "programs_offered", "ages_accepted", "operating_hours", "facility_highlights", "service_areas", "coordinates", "media", "availability", "pricing", "aggregate_rating", "service_area_geo", "facility_profile", "admissions", "faqs", "events", "open_house_date"],
      "programs": ["id", "name", "slug", "url", "summary", "age_range", "cta_text", "features", "anchor_slug", "lesson_plan_url", "seo", "faqs", "locations_served", "prerequisites", "related_programs"],
      "events": ["location", "location_url", "name", "start", "description", "url"]
    }
  }
}
```

## Compatibility Legend
- `content/{id}` = `PATCH/POST /wp-json/chroma-agent/v1/content/{id}` with API key; primary machine-write path for almost all meta.
- `seo/meta/{id}` = `PATCH/POST /wp-json/chroma-agent/v1/seo/meta/{post_id}`; only works for the SEO allowlist (`_chroma_es_*`, schema keys, `chroma_faq_items`, etc.).
- `WP REST` = native WP REST exposure via `register_post_meta(... show_in_rest => true)`; requires WP-auth context, not the Agent API key.
- `geo-feed` = `GET /wp-json/chroma-agent/v1/geo-feed`; public read-only GEO surface.

## Theme Meta Boxes

| Meta box family | Source file | Meta keys | Sanitizer pattern | Overwrite risk | API / GEO compatibility |
| --- | --- | --- | --- | --- | --- |
| About page sections (9 boxes: Hero, Mission, Story, Educators, Values, Leadership, Nutrition, Philanthropy, CTA) | `chroma-excellence-theme/inc/about-page-meta.php` | Large `about_*` and `_chroma_es_about_*` set; seed flag `_about_defaults_seeded` | Mixed field map per nonce: `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | High: shared save loop plus save-time default seeding can backfill missing values on target pages | Write via `content/{id}`. `seo/meta/{id}` only for `_chroma_es_*` keys if allowlisted. Not in `geo-feed`. |
| Contact page sections (5 boxes: Hero, Form, Corporate, Careers, Press) | `chroma-excellence-theme/inc/contact-page-meta.php` | `contact_*`; seed flag `_contact_defaults_seeded` | `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | High: grouped save loop and default seeding on save | Write via `content/{id}`. Not in `geo-feed`. |
| Parents page sections (8 boxes: Hero, Resources, Events, Gallery, Nutrition, Safety, FAQ, Referral) | `chroma-excellence-theme/inc/parents-page-meta.php` | Large `parents_*` and `_chroma_es_parents_*` set; seed flag `_parents_defaults_seeded` | `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | High: large grouped save loop and save-time default seeding | Write via `content/{id}`. `seo/meta/{id}` additionally for allowlisted `chroma_faq_items` only, not these page keys. Not in `geo-feed`. |
| Curriculum page sections (6 boxes: Hero, Framework, Timeline, Environment, Milestones, CTA) | `chroma-excellence-theme/inc/curriculum-page-meta.php` | Large `curriculum_*` and `_chroma_es_curriculum_*` set; seed flag `_curriculum_defaults_seeded` | `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | High: grouped save loop and save-time default seeding | Write via `content/{id}`. Not in `geo-feed`. |
| Careers landing sections (4 boxes: Hero, Culture, Openings, CTA) | `chroma-excellence-theme/inc/careers-page-meta.php` | `careers_*`; seed flag `_careers_defaults_seeded` | Mostly `sanitize_text_field` / `sanitize_textarea_field`; one CTA URL uses `esc_url_raw`; some job URLs are only `sanitize_text_field` | Medium-High: grouped save loop and save-time default seeding; URL handling is inconsistent across fields | Write via `content/{id}`. Not in `geo-feed`. |
| Employers page sections (4 boxes) | `chroma-excellence-theme/inc/employers-page-meta.php` | `employers_*`; seed flag `_employers_defaults_seeded` | Same pattern as page-builder files: mixed text/textarea/url map | High: grouped save loop and default seeding | Write via `content/{id}`. Not in `geo-feed`. |
| Home page sections (5 boxes) | `chroma-excellence-theme/inc/home-page-meta.php` | `home_*` and `_chroma_es_home_*`; seed flag in file matrix (`dynamic home_*` writes) | Mixed mapped sanitizers by field type | High: grouped page-builder save logic writes many sibling fields together | Write via `content/{id}`. Only `_chroma_es_*` can overlap `seo/meta/{id}` if allowlisted. Not in `geo-feed` directly. |
| Privacy page sections (2 boxes) | `chroma-excellence-theme/inc/privacy-page-meta.php` | `privacy_*`, `privacy_last_updated`; seed flag `_privacy_defaults_seeded` | `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | Medium: grouped save loop; fewer keys, but save-time defaults still run | Write via `content/{id}`. Not in `geo-feed`. |
| Stories featured post | `chroma-excellence-theme/inc/stories-page-meta.php` | `stories_featured_post` | Cast to post ID / integer-like selection | Low-Medium: single-field box; blank submit can clear stored value | Write via `content/{id}`. Not in `geo-feed`. |
| General SEO | `chroma-excellence-theme/inc/general-seo-meta.php` | `meta_description`, `meta_keywords` | `sanitize_textarea_field` | Low: isolated save handler with two fields | Write via `content/{id}`. Not in `seo/meta/{id}` default allowlist. Not in `geo-feed`. |
| About SEO | `chroma-excellence-theme/inc/about-seo.php` | `about_meta_title`, `about_meta_description`, `about_structured_data` | `sanitize_text_field`, `sanitize_textarea_field` | Low-Medium: isolated, but `about_structured_data` is plain textarea sanitization, not JSON-preserving | Write via `content/{id}`. Not in `geo-feed`. |
| Program anchor and SEO intro | `chroma-excellence-theme/inc/cpt-programs.php` | `program_anchor_slug`, `program_seo_heading`, `program_seo_summary`, `program_seo_highlights`, `program_meta_title`, `program_meta_description`, `program_faq_items`, `program_lesson_plan_file` | `sanitize_title`, `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw`; also registered in WP with `show_in_rest` | Medium: save handler rewrites the full sibling set from form submit | Write via `content/{id}`. Native `WP REST` for registered keys. `program_faq_items` is not in Agent `seo/meta` allowlist. Not in `geo-feed`. |
| Program locations / details / single-page content (3 boxes) | `chroma-excellence-theme/inc/cpt-programs.php` | `program_locations`, `program_age_range`, `program_features`, `program_cta_text`, `program_cta_link`, `program_color_scheme`, plus single-page related `program_*` fields | IDs via `intval`; text via `sanitize_text_field`; textareas via `sanitize_textarea_field`; URLs typically `esc_url_raw` in field-specific saves | Medium: multi-box CPT saves overwrite sibling values per box submit | Write via `content/{id}`. `program_age_range`, `program_cta_text`, `program_features` are currently exposed in `geo-feed`; other keys are not. |
| Program enhancements (3 boxes: FAQs, Gallery, Testimonials) | `chroma-excellence-theme/inc/class-program-enhancements.php` | `program_faqs`, `program_gallery`, `program_testimonials` | Array normalization; FAQ/testimonial text sanitized per field; media IDs normalized | Medium: whole-array replacement on save; absent arrays delete meta | Write via `content/{id}`. Not in `geo-feed`. |
| Location main details | `chroma-excellence-theme/inc/cpt-locations.php` | Broad `location_*` set including address/contact/content fields plus `location_quality_rated` | Per-field dispatch: `sanitize_textarea_field`, `sanitize_email`, `esc_url_raw`, `sanitize_text_field`; checkbox for `location_quality_rated` | Medium-High: single save handler touches many `location_*` siblings | Write via `content/{id}`. Many keys are public in `geo-feed` (`location_address`, `location_city`, `location_state`, `location_zip`, `location_phone`, `location_email`, `location_director_name`, `location_special_programs`, `location_ages_served`, `location_hours`, `location_tagline`, `location_description`, `location_seo_content_*`, `location_service_areas`, `location_latitude`, `location_longitude`). |
| Team member title | `chroma-excellence-theme/inc/cpt-team-members.php` | `team_member_title` | `sanitize_text_field`; also registered with `show_in_rest` | Low: single-field isolated save | Write via `content/{id}`. Native `WP REST` also available. Not in `geo-feed`. |
| Career CPT details | `chroma-excellence-theme/inc/cpt-careers.php` | Dynamic career post fields (single meta-box save family) | Mostly `sanitize_text_field` | Low-Medium: small isolated form save | Write via `content/{id}`. Not in `geo-feed`. |
| Schema.org Organization | `chroma-excellence-theme/inc/schema-meta-boxes.php` | `schema_org_*` | Text + textarea + URL/email sanitization in dedicated save | Medium: manual schema overrides can replace fallback-generated data | Write via `content/{id}`. Not used by `geo-feed`. |
| Schema.org Location | `chroma-excellence-theme/inc/schema-meta-boxes.php` | `schema_loc_*` | Text + textarea + URL/email sanitization in dedicated save | Medium: manual schema overrides can drift from canonical `location_*` data | Write via `content/{id}`. Not used by `geo-feed`. |
| Schema.org Program | `chroma-excellence-theme/inc/schema-meta-boxes.php` | `schema_prog_*` | Text + textarea sanitization | Medium: manual schema overrides can drift from canonical `program_*` data | Write via `content/{id}`. Not used by `geo-feed`. |
| City slug helper | `chroma-excellence-theme/inc/city-slug-logic.php` | No post meta write path found in audit | UI/helper only | Low: no meta save surface mapped | No Agent write need for this box. Not in `geo-feed`. |
| Newsroom toggle (theme version) | `chroma-excellence-theme/inc/meta-boxes/class-post-newsroom.php` | `_chroma_show_in_newsroom` | Checkbox coercion to `'1'`/empty | Low: single boolean | Write via `content/{id}`. Not in `geo-feed`. |

## Plugin Meta Boxes

| Meta box | Source file | Meta keys | Sanitizer pattern | Overwrite risk | API / GEO compatibility |
| --- | --- | --- | --- | --- | --- |
| General LLM context | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-general-llm-context.php` | `seo_llm_primary_intent`, `seo_llm_target_queries`, `seo_llm_key_differentiators` | Text + array item filtering | Low: box-specific save, targeted keys only | Write via `content/{id}`. Not in default `seo/meta/{id}` allowlist. Not in `geo-feed`. |
| General LLM prompt | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-general-llm-prompt.php` | `seo_llm_description`, `seo_llm_when_to_recommend` | Description text + filtered array | Low | Write via `content/{id}`. Not in `geo-feed`. |
| Hreflang options | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-hreflang-options.php` | `alternate_url_en`, `alternate_url_es` | `esc_url_raw` | Low | Write via `content/{id}`. Not in `geo-feed`. |
| City landing SEO | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-city-landing-meta.php` | `city_county`, `city_intro_text`, `city_nearby_locations` | `sanitize_text_field`, `wp_kses_post`, `intval[]` | Medium: selected-location array is replaced wholesale; blank submit deletes it | Write via `content/{id}`. Not in `geo-feed`. |
| Location citation facts | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-citation-facts.php` | `seo_llm_citation_facts` | Structured array sanitization per item | Medium: full array replacement | Write via `content/{id}`. Not in `geo-feed`. |
| Location events | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-events.php` | `location_events` | Per-event `sanitize_text_field` + `esc_url_raw` | Medium: full array replacement | Write via `content/{id}`. Publicly exposed in `geo-feed` under `locations[*].events` and flattened `events`. |
| Location HowTo / enrollment steps | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-howto.php` | `location_enrollment_steps` | `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | Medium: full array replacement | Write via `content/{id}`. Not in current `geo-feed`. |
| Location LLM context | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-llm-context.php` | `seo_llm_primary_intent`, `seo_llm_target_queries`, `seo_llm_key_differentiators` | Text + filtered arrays | Low | Write via `content/{id}`. Not in `geo-feed`. |
| Location LLM prompt | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-llm-prompt.php` | `seo_llm_description`, `seo_llm_when_to_recommend` | Description text + filtered array | Low | Write via `content/{id}`. Not in `geo-feed`. |
| Location media / availability | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-media.php` | `location_video_tour_url`, `location_video_thumbnail`, `location_video_duration`, `location_availability_status`, `location_spots_available` | URL fields via `esc_url_raw`; other fields via `sanitize_text_field` | Low-Medium: explicit field list but multi-field sibling save | Write via `content/{id}`. Not in current `geo-feed`. |
| Location pricing | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-pricing.php` | `location_price_min`, `location_price_max`, `location_price_currency`, `location_price_frequency` | `sanitize_text_field` for all fields | Low-Medium: sibling values replaced together | Write via `content/{id}`. Not in current `geo-feed`. |
| Location reviews | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-reviews.php` | `seo_llm_aggregate_rating_value`, `seo_llm_aggregate_rating_count`, `seo_llm_aggregate_rating_best`, `seo_llm_aggregate_rating_worst` | Numeric normalization before update | Low | Write via `content/{id}`. Not in `geo-feed`. |
| Location service area | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-service-area.php` | `seo_llm_service_area_lat`, `seo_llm_service_area_lng`, `seo_llm_service_area_radius`, `seo_llm_service_area_cities`, `seo_llm_service_area_state` | Numeric normalization for lat/lng/radius; text/filter for city/state | Low-Medium: city list is replaced wholesale | Write via `content/{id}`. Not in current `geo-feed`. |
| Location advanced schema | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-advanced-schema.php` | `_chroma_license_number`, `_chroma_google_maps_cid`, `location_quality_rated`, `_chroma_open_house_date`, `_chroma_is_event_venue`, `_chroma_caps_accepted`, `_chroma_ga_pre_k_accepted`, `_chroma_security_cameras`, `_chroma_amenities` | Mostly `sanitize_text_field`; booleans normalized manually | Medium: overlapping ownership on `location_quality_rated` with theme location meta box | Write via `content/{id}`. `_chroma_open_house_date` is exposed in `geo-feed` public events; most others are not. |
| Post newsroom toggle (plugin version) | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-post-newsroom.php` | `_chroma_show_in_newsroom` | Checkbox coercion | Low | Write via `content/{id}`. Not in `geo-feed`. |
| Program relationships | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-program-relationships.php` | `program_locations_served`, `program_prerequisites`, `program_related` | `intval[]`; blank submit deletes arrays | Medium: array replacement/delete semantics | Write via `content/{id}`. Not in current `geo-feed`. |
| Universal FAQ | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-universal-faq.php` | `chroma_faq_items` | `sanitize_text_field` + `sanitize_textarea_field` per FAQ row | Medium: full array replacement | Write via `content/{id}` or `seo/meta/{id}` (this key is in the Agent SEO allowlist). Not in `geo-feed`. |
| Spanish content | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-spanish-content.php` | `_chroma_es_title`, `_chroma_es_content`, `_chroma_es_excerpt`, `_chroma_es_history` | `_content` via `wp_kses_post`; title/excerpt via `sanitize_text_field`; history array maintained separately | Medium: version restore can overwrite current translated content | Write via `content/{id}` or `seo/meta/{id}` for the allowlisted current translation keys. Not in `geo-feed`. |
| Schema editor | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-schema-editor-metabox.php` | `_chroma_post_schemas`, `_chroma_schema_confidence` (and related schema fields handled in adjacent logic) | Trusted schema storage with dedicated schema sanitizer path; confidence value normalized | Medium: direct schema replacement | Prefer `seo/schema/{id}` for schema payloads; `content/{id}` works for confidence/meta. Not in `geo-feed`. |
| Lead payload inspector | `chroma-plugins/chroma-lead-log/chroma-lead-log.php` | `_chroma_webhook_sent` plus read-only display of `lead_*` | Simple meta update for send flag | Low | Admin/internal only. Use `content/{id}` if needed. Never expose via GEO. |
| Parent portal docs/PIN/event meta boxes | `plugins/chroma-parent-portal/includes/class-meta-boxes.php` | `_cp_pdf_file_id`, `_cp_pin_hash`, `_cp_pin_simple_hash`, `_cp_priority`, `_cp_event_date` | `sanitize_text_field`; PIN hashes generated server-side (`wp_hash_password`, `md5`) | High for security sensitivity: API writes can bypass hash-generation workflow if you write raw meta directly | Prefer plugin-specific `chroma-portal/v1/*` flows for business logic. Do not expose in `geo-feed`. |
| School dashboard config/content | `plugins/chroma-school-dashboard/inc/class-post-type.php` | `_chroma_school_config`, `_chroma_school_director_email`, `_chroma_school_newsletter`, `_chroma_school_eom`, `_chroma_school_slideshow_title`, `_chroma_school_slideshow` | `sanitize_email`, `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw` | Medium: structured arrays replaced wholesale on save | Prefer dashboard route `PATCH /wp-json/chroma/v1/portal/school/{id}` for supported fields; `content/{id}` for raw meta only if you control full payload. Never expose in `geo-feed`. |

## Highest-Risk Overlap Areas
- `location_quality_rated` is written by both:
  - `chroma-excellence-theme/inc/cpt-locations.php`
  - `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-advanced-schema.php`
- `_chroma_show_in_newsroom` exists in both:
  - `chroma-excellence-theme/inc/meta-boxes/class-post-newsroom.php`
  - `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-post-newsroom.php`
- Large page-builder files (`about`, `parents`, `curriculum`, `contact`, `careers`, `home`, `employers`, `privacy`) use shared save loops and save-time default seeders. These are the easiest surfaces to accidentally overwrite if a partial form POST is emulated.

## GEO-Safe vs Not GEO-Safe
- Safe to expose publicly (already in `geo-feed` or strong candidates): canonical `location_*` campus facts, `location_events`, `_chroma_open_house_date`, core program facts (`program_age_range`, `program_cta_text`, `program_features`), site-level brand options.
- Keep authenticated/private: parent portal PIN/hash data, school dashboard internal content, webhook/send flags, editorial-only LLM prompting context, schema review/history internals unless intentionally published.

## Practical Write Guidance
- For machine writes, default to `content/{id}` with `strict_write=true`.
- `content/{id}` is no longer a universal catch-all for protected namespaces; use the dedicated route when a key is policy-blocked.
- Only use `seo/meta/{id}` for keys already in the allowlist:
  - `_chroma_es_title`
  - `_chroma_es_content`
  - `_chroma_es_excerpt`
  - `_chroma_es_seo_title`
  - `_chroma_es_meta_description`
  - `_chroma_post_schemas`
  - `_chroma_schema_override`
  - `_chroma_schema_type`
  - `_chroma_schema_data`
  - `_chroma_schema_confidence`
  - `_chroma_needs_review`
  - `_chroma_review_reason`
  - `_chroma_schema_history`
  - `_chroma_schema_validation_status`
  - `_chroma_schema_errors`
  - `chroma_faq_items`
- For public GEO, add fields to `class-geo-routes.php` deliberately instead of exposing admin-only meta wholesale.

<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$output = $root . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'meta-box-per-key-matrix-2026-02-28.csv';

$files = [
    'chroma-excellence-theme/inc/about-page-meta.php',
    'chroma-excellence-theme/inc/about-seo.php',
    'chroma-excellence-theme/inc/careers-page-meta.php',
    'chroma-excellence-theme/inc/class-program-enhancements.php',
    'chroma-excellence-theme/inc/contact-page-meta.php',
    'chroma-excellence-theme/inc/cpt-careers.php',
    'chroma-excellence-theme/inc/cpt-locations.php',
    'chroma-excellence-theme/inc/cpt-programs.php',
    'chroma-excellence-theme/inc/cpt-team-members.php',
    'chroma-excellence-theme/inc/curriculum-page-meta.php',
    'chroma-excellence-theme/inc/employers-page-meta.php',
    'chroma-excellence-theme/inc/general-seo-meta.php',
    'chroma-excellence-theme/inc/home-page-meta.php',
    'chroma-excellence-theme/inc/meta-boxes/class-post-newsroom.php',
    'chroma-excellence-theme/inc/parents-page-meta.php',
    'chroma-excellence-theme/inc/privacy-page-meta.php',
    'chroma-excellence-theme/inc/schema-meta-boxes.php',
    'chroma-excellence-theme/inc/stories-page-meta.php',
    'chroma-plugins/chroma-lead-log/chroma-lead-log.php',
    'plugins/chroma-parent-portal/includes/class-meta-boxes.php',
    'plugins/chroma-school-dashboard/inc/class-post-type.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-city-landing-meta.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-general-llm-context.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-general-llm-prompt.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-hreflang-options.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-advanced-schema.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-citation-facts.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-events.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-howto.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-llm-context.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-llm-prompt.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-media.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-pricing.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-reviews.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-location-service-area.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-post-newsroom.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-program-relationships.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-schema-editor-metabox.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-spanish-content.php',
    'plugins/chroma-seo-pro-reset/inc/meta-boxes/class-universal-faq.php',
];

$seoAllowlist = [
    '_chroma_es_title',
    '_chroma_es_content',
    '_chroma_es_excerpt',
    '_chroma_es_seo_title',
    '_chroma_es_meta_description',
    '_chroma_post_schemas',
    '_chroma_schema_override',
    '_chroma_schema_type',
    '_chroma_schema_data',
    '_chroma_schema_confidence',
    '_chroma_needs_review',
    '_chroma_review_reason',
    '_chroma_schema_history',
    '_chroma_schema_validation_status',
    '_chroma_schema_errors',
    'chroma_faq_items',
];

$geoAllowlist = [
    'location_video_tour_url',
    'location_video_thumbnail',
    'location_video_duration',
    'location_availability_status',
    'location_spots_available',
    'location_price_min',
    'location_price_max',
    'location_price_currency',
    'location_price_frequency',
    'seo_llm_aggregate_rating_value',
    'seo_llm_aggregate_rating_count',
    'seo_llm_aggregate_rating_best',
    'seo_llm_aggregate_rating_worst',
    'seo_llm_service_area_lat',
    'seo_llm_service_area_lng',
    'seo_llm_service_area_radius',
    'seo_llm_service_area_cities',
    'seo_llm_service_area_state',
    'location_enrollment_steps',
    'chroma_faq_items',
    '_chroma_open_house_date',
    '_chroma_is_event_venue',
    '_chroma_caps_accepted',
    '_chroma_ga_pre_k_accepted',
    '_chroma_security_cameras',
    '_chroma_amenities',
    'program_anchor_slug',
    'program_seo_heading',
    'program_seo_summary',
    'program_seo_highlights',
    'program_meta_title',
    'program_meta_description',
    'program_faq_items',
    'program_lesson_plan_file',
    'program_locations',
    'program_locations_served',
    'program_prerequisites',
    'program_related',
];

$neverPublicPrefixes = ['_cp_', '_chroma_school_', '_chroma_schema_', 'lead_'];
$neverPublicKeys = [
    '_chroma_post_schemas',
    '_chroma_needs_review',
    '_chroma_review_reason',
    '_chroma_schema_history',
    '_chroma_schema_validation_status',
    '_chroma_schema_errors',
    '_chroma_webhook_sent',
    'lead_payload',
];
$contentDenyKeys = [
    '_chroma_post_schemas',
    '_chroma_schema_override',
    '_chroma_schema_type',
    '_chroma_schema_data',
    '_chroma_schema_confidence',
    '_chroma_needs_review',
    '_chroma_review_reason',
    '_chroma_schema_history',
    '_chroma_schema_validation_status',
    '_chroma_schema_errors',
    '_chroma_webhook_sent',
    'lead_payload',
];
$contentDenyPrefixes = ['_cp_', '_chroma_school_', 'lead_'];

$patterns = [
    'update_post_meta' => '/update_post_meta\s*\(\s*\$post_id\s*,\s*\'([^\']+)\'/m',
    'delete_post_meta' => '/delete_post_meta\s*\(\s*\$post_id\s*,\s*\'([^\']+)\'/m',
    'register_post_meta' => '/register_post_meta\s*\(\s*\'[^\']+\'\s*,\s*\'([^\']+)\'/m',
    'field_map' => '/^\s*\'([A-Za-z0-9_]+)\'\s*=>\s*\'(?:sanitize_[^\']+|esc_url_raw|wp_kses_post)\'/m',
    'get_post_meta' => '/get_post_meta\s*\([^,]+,\s*\'([^\']+)\'\s*,\s*true\s*\)/m',
];

$rows = [];

foreach ($files as $relativePath) {
    $fullPath = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($fullPath)) {
        continue;
    }

    $contents = (string) file_get_contents($fullPath);
    $found = [];

    foreach ($patterns as $method => $pattern) {
        if (!preg_match_all($pattern, $contents, $matches)) {
            continue;
        }

        foreach ($matches[1] as $key) {
            $key = trim((string) $key);
            if (!isLikelyMetaKey($key)) {
                continue;
            }

            if (!isset($found[$key])) {
                $found[$key] = [];
            }

            $found[$key][$method] = true;
        }
    }

    foreach ($found as $key => $methods) {
        $rows[] = [
            'meta_key' => $key,
            'source_file' => str_replace('\\', '/', $relativePath),
            'discovery_methods' => implode('|', array_keys($methods)),
            'api_write' => inferApiWrite($key, $seoAllowlist),
            'content_write_policy' => inferContentWritePolicy($key, $contentDenyKeys, $contentDenyPrefixes),
            'geo_status' => inferGeoStatus($key, $geoAllowlist, $neverPublicKeys, $neverPublicPrefixes),
        ];
    }
}

usort($rows, static function (array $a, array $b): int {
    return [$a['meta_key'], $a['source_file']] <=> [$b['meta_key'], $b['source_file']];
});

$handle = fopen($output, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Failed to open output file: {$output}\n");
    exit(1);
}

fputcsv($handle, ['meta_key', 'source_file', 'discovery_methods', 'api_write', 'content_write_policy', 'geo_status']);
foreach ($rows as $row) {
    fputcsv($handle, $row);
}
fclose($handle);

echo "Wrote " . count($rows) . " rows to {$output}\n";

function isLikelyMetaKey(string $key): bool
{
    if ($key === '') {
        return false;
    }

    return (bool) preg_match(
        '/^(?:_[A-Za-z0-9]+|about_|contact_|parents_|curriculum_|careers_|employers_|home_|privacy_|location_|program_|schema_|meta_|team_|stories_|city_|alternate_|seo_|chroma_|lead_)/',
        $key
    );
}

function inferApiWrite(string $key, array $seoAllowlist): string
{
    if ($key === '_chroma_post_schemas' || strpos($key, '_chroma_schema_') === 0) {
        return 'seo/schema/{id} preferred; content/{id}';
    }

    if (in_array($key, $seoAllowlist, true)) {
        return 'content/{id}; seo/meta/{id}';
    }

    if (strpos($key, '_cp_') === 0) {
        return 'chroma-portal route preferred; content/{id} raw';
    }

    if (strpos($key, '_chroma_school_') === 0) {
        return 'school dashboard route preferred; content/{id} raw';
    }

    return 'content/{id}';
}

function inferGeoStatus(string $key, array $geoAllowlist, array $neverPublicKeys, array $neverPublicPrefixes): string
{
    if (in_array($key, $neverPublicKeys, true)) {
        return 'never public';
    }

    foreach ($neverPublicPrefixes as $prefix) {
        if ($prefix !== '' && strpos($key, $prefix) === 0) {
            return 'never public';
        }
    }

    if (in_array($key, $geoAllowlist, true)) {
        return 'allowed in geo-feed';
    }

    return 'private by default';
}

function inferContentWritePolicy(string $key, array $denyKeys, array $denyPrefixes): string
{
    if (in_array($key, $denyKeys, true)) {
        return 'blocked on content/{id}';
    }

    foreach ($denyPrefixes as $prefix) {
        if ($prefix !== '' && strpos($key, $prefix) === 0) {
            return 'blocked on content/{id}';
        }
    }

    return 'allowed on content/{id}';
}

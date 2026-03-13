<?php

declare(strict_types=1);

/**
 * Seed Summer Camp calendars onto Location posts.
 *
 * Usage:
 * - Dry run: php scripts/seed_summer_camp_calendars.php --dry-run
 * - Apply:   php scripts/seed_summer_camp_calendars.php
 * - Explicit bootstrap: php scripts/seed_summer_camp_calendars.php --wp-load=C:\path\to\wp-load.php
 */

$root = dirname(__DIR__);
$wpLoad = chroma_resolve_wp_load($argv ?? array(), $root);

if ($wpLoad === '') {
    fwrite(STDERR, "Unable to locate wp-load.php automatically.\n");
    fwrite(STDERR, "Pass --wp-load=C:\\path\\to\\wp-load.php when running this seeder.\n");
    exit(1);
}

require_once $wpLoad;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dryRun = in_array('--dry-run', $argv ?? array(), true);

$seedMap = array(
    'cherokee' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Cherokee.pdf',
        'aliases' => array(
            'Cherokee Academy by Chroma Early Learning',
            'Cherokee Academy',
            'Cherokee',
        ),
    ),
    'east-cobb' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\East Cobb.pdf',
        'aliases' => array(
            'East Cobb Campus',
            'East Cobb',
        ),
    ),
    'johns-creek' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Johns Creek.pdf',
        'aliases' => array(
            'Johns Creek',
            'Johns Creek Campus',
        ),
    ),
    'jonesboro' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Jonesboro.pdf',
        'aliases' => array(
            'Jonesboro Campus',
            'Jonesboro',
            'Jonesboro - Clayton',
            'Jonesboro Clayton',
        ),
    ),
    'midway' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Midway.pdf',
        'aliases' => array(
            'Midway Campus',
            'Midway',
            'Alpharetta - Midway',
            'Alpharetta Midway',
        ),
    ),
    'rivergreen' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Rivergreen.pdf',
        'aliases' => array(
            'Rivergreen Campus',
            'Rivergreen',
        ),
    ),
    'satellite' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Satellite.pdf',
        'aliases' => array(
            'Satellite Bvd Campus',
            'Satellite Blvd Campus',
            'Satellite Campus',
            'Satellite',
            'Duluth - Satellite',
            'Duluth Satellite',
        ),
    ),
    'south-cobb' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\South Cobb.pdf',
        'aliases' => array(
            'South Cobb Campus, Austell',
            'South Cobb Campus',
            'South Cobb',
            'Austell South Cobb',
        ),
    ),
    'tramore' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Tramore.pdf',
        'aliases' => array(
            'Tramore Campus',
            'Tramore',
            'Austell - Tramore',
            'Austell Tramore',
        ),
    ),
    'tyrone' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\Tyrone.pdf',
        'aliases' => array(
            'Tyrone Campus',
            'Tyrone',
        ),
    ),
    'west-cobb' => array(
        'source_path' => 'C:\\Users\\chara\\Downloads\\Summer Camp Calendars\\West Cobb.pdf',
        'aliases' => array(
            'West Cobb Campus',
            'West Cobb',
        ),
    ),
);

$locations = get_posts(array(
    'post_type' => 'location',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

if (empty($locations)) {
    fwrite(STDERR, "No published location posts found.\n");
    exit(1);
}

echo $dryRun ? "Running Summer Camp calendar seeder in DRY RUN mode.\n" : "Running Summer Camp calendar seeder.\n";
echo "Found " . count($locations) . " published locations.\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($seedMap as $seedKey => $seedItem) {
    $sourcePath = (string) $seedItem['source_path'];
    $locationId = chroma_find_location_for_summer_calendar($locations, (array) $seedItem['aliases']);

    if (!is_file($sourcePath)) {
        echo "[ERROR] {$seedKey}: file not found: {$sourcePath}\n";
        $errorCount++;
        continue;
    }

    if (!$locationId) {
        echo "[ERROR] {$seedKey}: no matching location found for aliases: " . implode(', ', $seedItem['aliases']) . "\n";
        $errorCount++;
        continue;
    }

    $locationTitle = get_the_title($locationId);
    echo "[MATCH] {$seedKey} -> {$locationTitle} (#{$locationId})\n";

    if ($dryRun) {
        continue;
    }

    $attachmentId = chroma_seed_summer_calendar_attachment($seedKey, $locationId, $locationTitle, $sourcePath);
    if (!$attachmentId) {
        echo "[ERROR] {$seedKey}: failed to create or reuse attachment.\n";
        $errorCount++;
        continue;
    }

    $calendarUrl = wp_get_attachment_url($attachmentId);
    if (!$calendarUrl) {
        echo "[ERROR] {$seedKey}: attachment {$attachmentId} has no URL.\n";
        $errorCount++;
        continue;
    }

    update_post_meta($locationId, 'location_summer_camp_calendar_url', esc_url_raw($calendarUrl));
    update_post_meta($locationId, 'summer_camp_calendar_url', esc_url_raw($calendarUrl));
    update_post_meta($locationId, 'location_summer_camp_calendar_attachment_id', $attachmentId);

    echo "[SEEDED] {$locationTitle} <- {$calendarUrl}\n";
    $successCount++;
}

echo "\nCompleted. ";
if ($dryRun) {
    echo "Dry run only; no data written.\n";
} else {
    echo "{$successCount} location(s) updated";
    if ($errorCount > 0) {
        echo ", {$errorCount} error(s)";
    }
    echo ".\n";
}

function chroma_find_location_for_summer_calendar(array $locations, array $aliases): int
{
    $normalizedAliases = array();

    foreach ($aliases as $alias) {
        $normalized = chroma_normalize_summer_calendar_label((string) $alias);
        if ($normalized !== '') {
            $normalizedAliases[$normalized] = true;
        }
    }

    foreach ($locations as $location) {
        $normalizedTitle = chroma_normalize_summer_calendar_label((string) $location->post_title);
        if (isset($normalizedAliases[$normalizedTitle])) {
            return (int) $location->ID;
        }
    }

    return 0;
}

function chroma_normalize_summer_calendar_label(string $value): string
{
    $value = strtolower(trim($value));
    $value = str_replace(array('&', '/'), ' ', $value);
    $value = str_replace('bvd', 'blvd', $value);
    $value = str_replace('academy by chroma early learning', '', $value);
    $value = preg_replace('/\bcampus\b/', ' ', $value);
    $value = preg_replace('/\blocation\b/', ' ', $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim((string) $value);
}

function chroma_seed_summer_calendar_attachment(string $seedKey, int $locationId, string $locationTitle, string $sourcePath): int
{
    $existing = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_chroma_summer_camp_source_path',
                'value' => $sourcePath,
            ),
            array(
                'key' => '_chroma_summer_camp_seed_key',
                'value' => $seedKey,
            ),
        ),
    ));

    if (!empty($existing)) {
        $attachmentId = (int) $existing[0];
        update_post_meta($attachmentId, '_chroma_summer_camp_location_id', $locationId);
        update_post_meta($attachmentId, '_chroma_summer_camp_source_path', $sourcePath);
        update_post_meta($attachmentId, '_chroma_summer_camp_seed_key', $seedKey);
        return $attachmentId;
    }

    $contents = file_get_contents($sourcePath);
    if ($contents === false) {
        return 0;
    }

    $upload = wp_upload_bits(basename($sourcePath), null, $contents);
    if (!empty($upload['error'])) {
        return 0;
    }

    $attachmentId = wp_insert_attachment(array(
        'post_mime_type' => 'application/pdf',
        'post_title' => 'Summer Camp Calendar - ' . $locationTitle,
        'post_content' => '',
        'post_status' => 'inherit',
    ), $upload['file']);

    if (is_wp_error($attachmentId) || !$attachmentId) {
        return 0;
    }

    $metadata = wp_generate_attachment_metadata($attachmentId, $upload['file']);
    if (!is_wp_error($metadata) && is_array($metadata)) {
        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    update_post_meta($attachmentId, '_chroma_summer_camp_location_id', $locationId);
    update_post_meta($attachmentId, '_chroma_summer_camp_source_path', $sourcePath);
    update_post_meta($attachmentId, '_chroma_summer_camp_seed_key', $seedKey);

    return (int) $attachmentId;
}

function chroma_resolve_wp_load(array $args, string $root): string
{
    foreach ($args as $arg) {
        if (strpos((string) $arg, '--wp-load=') === 0) {
            $explicitPath = substr((string) $arg, strlen('--wp-load='));
            return is_file($explicitPath) ? $explicitPath : '';
        }
    }

    $envPath = getenv('WP_LOAD_PATH');
    if (is_string($envPath) && $envPath !== '' && is_file($envPath)) {
        return $envPath;
    }

    $candidates = array(
        $root . DIRECTORY_SEPARATOR . 'wp-load.php',
        dirname($root) . DIRECTORY_SEPARATOR . 'wp-load.php',
        getcwd() . DIRECTORY_SEPARATOR . 'wp-load.php',
    );

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

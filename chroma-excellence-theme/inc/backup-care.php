<?php
/**
 * Backup care links shared by page and location templates.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolve a location post to the campus identifier used by the booking service.
 */
function chroma_backup_care_campus_id($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return '';
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return '';
    }

    $identity = sanitize_title(remove_accents($post->post_name . ' ' . $post->post_title));
    $aliases = array(
        'downtown-duluth' => array('downtown-duluth'),
        'johns-creek' => array('johns-creek', 'johnscreek'),
        'north-hall' => array('north-hall', 'northhall'),
        'south-cobb' => array('south-cobb', 'southcobb'),
        'west-cobb' => array('west-cobb', 'westcobb'),
        'east-cobb' => array('east-cobb', 'eastcobb'),
        'sugarloaf' => array('sugarloaf'),
        'satellite' => array('satellite'),
        'stockbridge' => array('stockbridge'),
        'lawrenceville' => array('lawrenceville'),
        'rivergreen' => array('rivergreen', 'river-green'),
        'shenandoah' => array('shenandoah'),
        'mcdonough' => array('mcdonough'),
        'chadwick' => array('chadwick'),
        'cherokee' => array('cherokee'),
        'tramore' => array('tramore'),
        'ellenwood' => array('ellenwood'),
        'grayson' => array('grayson'),
        'jonesboro' => array('jonesboro'),
        'lilburn' => array('lilburn'),
        'midway' => array('midway'),
        'parklake' => array('parklake', 'park-lake'),
        'roswell' => array('roswell'),
        'tyrone' => array('tyrone'),
    );

    foreach ($aliases as $campus_id => $needles) {
        foreach ($needles as $needle) {
            if (strpos($identity, $needle) !== false) {
                return $campus_id;
            }
        }
    }

    return '';
}

/**
 * Return the canonical booking URL, optionally preselecting a campus.
 */
function chroma_backup_care_url($campus_id = '')
{
    $url = home_url('/backup-care/');
    $campus_id = sanitize_key((string) $campus_id);
    return $campus_id ? add_query_arg('campus', $campus_id, $url) : $url;
}

/**
 * Keep payment-return and token-based management pages out of search results.
 */
function chroma_backup_care_private_page_robots($robots)
{
    if (is_page(array('backup-care-confirmation', 'backup-care-manage'))) {
        $robots['noindex'] = true;
        $robots['noarchive'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'chroma_backup_care_private_page_robots');

/**
 * Load the focused Backup Care page styles after the main theme bundle.
 */
function chroma_backup_care_page_assets()
{
    if (!is_page(array('backup-care', 'backup-care-confirmation', 'backup-care-manage'))) {
        return;
    }

    $page_relative_path = '/assets/css/backup-care-page.css';
    $page_absolute_path = CHROMA_THEME_DIR . $page_relative_path;

    wp_enqueue_style(
        'chroma-backup-care-page',
        get_theme_file_uri($page_relative_path),
        array('chroma-main'),
        file_exists($page_absolute_path) ? (string) filemtime($page_absolute_path) : CHROMA_THEME_VERSION
    );
}
add_action('wp_enqueue_scripts', 'chroma_backup_care_page_assets', 30);

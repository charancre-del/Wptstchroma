<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Determine whether map assets should be enqueued.
 */
function chroma_should_load_maps() {
        $should_load_maps = is_post_type_archive( 'location' ) || is_singular( 'location' ) || is_page( 'locations' );

        if ( is_front_page() && function_exists( 'chroma_home_locations_preview' ) ) {
                $locations_preview = chroma_home_locations_preview();
                $should_load_maps  = $should_load_maps || ( ! empty( $locations_preview['map_points'] ) );
        }

        return $should_load_maps;
}

/**
 * Enqueue theme styles and scripts
 */
function chroma_enqueue_assets() {
        $get_script_asset = static function ( $filename ) {
                $minified_path = CHROMA_THEME_DIR . "/assets/js/{$filename}.min.js";
                $source_path   = CHROMA_THEME_DIR . "/assets/js/{$filename}.js";

                if ( file_exists( $minified_path ) ) {
                        return array(
                                'src' => CHROMA_THEME_URI . "/assets/js/{$filename}.min.js",
                                'ver' => filemtime( $minified_path ),
                        );
                }

                return array(
                        'src' => CHROMA_THEME_URI . "/assets/js/{$filename}.js",
                        'ver' => file_exists( $source_path ) ? filemtime( $source_path ) : CHROMA_VERSION,
                );
        };

        // Google Fonts.
        wp_enqueue_style(
                'chroma-fonts',
                'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap',
                array(),
                null
        );

        // Font Awesome.
        wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                array(),
                '6.4.0'
        );

        // Compiled Tailwind CSS.
        $css_path    = CHROMA_THEME_DIR . '/assets/css/main.css';
        $css_version = file_exists( $css_path ) ? filemtime( $css_path ) : CHROMA_VERSION;

        wp_enqueue_style(
                'chroma-main',
                CHROMA_THEME_URI . '/assets/css/main.css',
                array(),
                $css_version
        );

        // Chart.js for curriculum radar (homepage).
        $script_dependencies = array();

        if ( is_front_page() ) {
                wp_enqueue_script(
                        'chartjs',
                        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                        array(),
                        '4.4.1',
                        true
                );

                wp_script_add_data( 'chartjs', 'defer', true );
                $script_dependencies[] = 'chartjs';
        }

        // Main JavaScript.
        $main_asset = $get_script_asset( 'main' );

        wp_enqueue_script(
                'chroma-main',
                $main_asset['src'],
                $script_dependencies,
                $main_asset['ver'],
                true
        );

        wp_script_add_data( 'chroma-main', 'defer', true );

        // Leaflet for maps (location archive, single locations, locations page, or home locations preview).
        $should_load_maps = chroma_should_load_maps();

        if ( $should_load_maps ) {
                $map_asset = $get_script_asset( 'map-layer' );

                wp_enqueue_style(
                        'leaflet',
                        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                        array(),
                        '1.9.4'
                );

                wp_enqueue_script(
                        'leaflet',
                        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                        array(),
                        '1.9.4',
                        true
                );

                wp_script_add_data( 'leaflet', 'defer', true );

                wp_enqueue_script(
                        'chroma-map-layer',
                        $map_asset['src'],
                        array( 'leaflet' ),
                        $map_asset['ver'],
                        true
                );

                wp_script_add_data( 'chroma-map-layer', 'defer', true );
        }

        // Localize script for AJAX and dynamic data.
        wp_localize_script(
                'chroma-main',
                'chromaData',
                array(
                        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'chroma_nonce' ),
                        'themeUrl' => CHROMA_THEME_URI,
                        'homeUrl'  => home_url(),
                )
        );
}
add_action( 'wp_enqueue_scripts', 'chroma_enqueue_assets' );

/**
 * Add resource hints for external assets to improve initial page performance.
 */
function chroma_resource_hints( $urls, $relation_type ) {
        if ( 'preconnect' === $relation_type ) {
                $urls[] = 'https://fonts.googleapis.com';
                $urls[] = array(
                        'href'        => 'https://fonts.gstatic.com',
                        'crossorigin' => 'anonymous',
                );
                $urls[] = 'https://cdnjs.cloudflare.com';

                if ( is_front_page() ) {
                        $urls[] = 'https://cdn.jsdelivr.net';
                }

                if ( chroma_should_load_maps() ) {
                        $urls[] = 'https://unpkg.com';
                }
        }

        if ( 'dns-prefetch' === $relation_type ) {
                $urls[] = '//fonts.googleapis.com';
                $urls[] = '//fonts.gstatic.com';
                $urls[] = '//cdnjs.cloudflare.com';

                if ( is_front_page() ) {
                        $urls[] = '//cdn.jsdelivr.net';
                }

                if ( chroma_should_load_maps() ) {
                        $urls[] = '//unpkg.com';
                }
        }

        return array_unique( $urls, SORT_REGULAR );
}
add_filter( 'wp_resource_hints', 'chroma_resource_hints', 10, 2 );

/**
 * Enqueue admin assets
 */
function chroma_enqueue_admin_assets( $hook ) {
        // Only load on post edit screens
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
                return;
        }

        // Font Awesome for icon previews in admin
        wp_enqueue_style(
                'font-awesome-admin',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                array(),
                '6.4.0'
        );

        // Media uploader
        wp_enqueue_media();

        // Custom admin script for media uploader
        $admin_asset = array(
                'src' => CHROMA_THEME_URI . '/assets/js/admin.js',
                'ver' => CHROMA_VERSION,
        );

        if ( file_exists( CHROMA_THEME_DIR . '/assets/js/admin.min.js' ) ) {
                $admin_asset = array(
                        'src' => CHROMA_THEME_URI . '/assets/js/admin.min.js',
                        'ver' => filemtime( CHROMA_THEME_DIR . '/assets/js/admin.min.js' ),
                );
        }

        wp_enqueue_script(
                'chroma-admin',
                $admin_asset['src'],
                array( 'jquery' ),
                $admin_asset['ver'],
                true
        );
}
add_action( 'admin_enqueue_scripts', 'chroma_enqueue_admin_assets' );

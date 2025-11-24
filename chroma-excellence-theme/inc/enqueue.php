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
 * Enqueue theme styles and scripts
 */
function chroma_enqueue_assets() {
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
                        'https://cdn.jsdelivr.net/npm/chart.js',
                        array(),
                        '4.4.1',
                        true
                );

                $script_dependencies[] = 'chartjs';
        }

        // Main JavaScript.
        $js_path    = CHROMA_THEME_DIR . '/assets/js/main.js';
        $js_version = file_exists( $js_path ) ? filemtime( $js_path ) : CHROMA_VERSION;

        wp_enqueue_script(
                'chroma-main',
                CHROMA_THEME_URI . '/assets/js/main.js',
                $script_dependencies,
                $js_version,
                true
        );

        // Leaflet for maps (location archive, single locations, locations page, or home locations preview).
        $should_load_maps = is_post_type_archive( 'location' ) || is_singular( 'location' ) || is_page( 'locations' );

        if ( is_front_page() && function_exists( 'chroma_home_locations_preview' ) ) {
                $locations_preview = chroma_home_locations_preview();
                $should_load_maps  = $should_load_maps || ( ! empty( $locations_preview['map_points'] ) );
        }

        if ( $should_load_maps ) {
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

                wp_enqueue_script(
                        'chroma-map-layer',
                        CHROMA_THEME_URI . '/assets/js/map-layer.js',
                        array( 'leaflet' ),
                        $js_version,
                        true
                );
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

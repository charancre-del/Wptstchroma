<?php
/**
 * KML Endpoint
 * Generates a KML file for all locations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_KML_Endpoint
{
    /**
     * Initialize
     */
    public static function init()
    {
        add_action('init', [__CLASS__, 'add_rewrite_rule']);
        add_action('template_redirect', [__CLASS__, 'render_kml']);
    }

    /**
     * Add rewrite rule for /locations.kml
     */
    public static function add_rewrite_rule()
    {
        add_rewrite_rule('^locations\.kml$', 'index.php?chroma_kml=1', 'top');
        add_rewrite_tag('%chroma_kml%', '([^&]+)');
    }

    /**
     * Render KML
     */
    public static function render_kml()
    {
        if (get_query_var('chroma_kml')) {
            ob_clean(); // Clean output buffer to prevent whitespace errors
            header('Content-Type: application/vnd.google-earth.kml+xml');
            header('Content-Disposition: attachment; filename="locations.kml"');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            ?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
                <Document>
                    <name><?php echo esc_html(get_bloginfo('name')); ?> Locations</name>
                    <description>Locations for <?php echo esc_html(get_bloginfo('name')); ?></description>
                    <?php
                    $locations = get_posts([
                        'post_type' => 'location',
                        'posts_per_page' => -1,
                        'post_status' => 'publish'
                    ]);

                    foreach ($locations as $location) {
                        $lat = get_post_meta($location->ID, 'location_latitude', true);
                        $lng = get_post_meta($location->ID, 'location_longitude', true);
                        $address = get_post_meta($location->ID, 'location_address', true);
                        $city = get_post_meta($location->ID, 'location_city', true);
                        $state = get_post_meta($location->ID, 'location_state', true);
                        $zip = get_post_meta($location->ID, 'location_zip', true);
                        $phone = get_post_meta($location->ID, 'location_phone', true);

                        if ($lat && $lng) {
                            ?>
                            <Placemark>
                                <name><?php echo esc_html($location->post_title); ?></name>
                                <description>
                                    <![CDATA[
                                    <p><strong>Address:</strong> <?php echo esc_html("$address, $city, $state $zip"); ?></p>
                                    <p><strong>Phone:</strong> <?php echo esc_html($phone); ?></p>
                                    <p><a href="<?php echo get_permalink($location->ID); ?>">View Details</a></p>
                                    ]]>
                                </description>
                                <Point>
                                    <coordinates><?php echo "$lng,$lat,0"; ?></coordinates>
                                </Point>
                            </Placemark>
                            <?php
                        } elseif ($address) {
                            // Fallback to address if coordinates are missing
                            ?>
                            <Placemark>
                                <name><?php echo esc_html($location->post_title); ?></name>
                                <description>
                                    <![CDATA[
                                    <p><strong>Address:</strong> <?php echo esc_html("$address, $city, $state $zip"); ?></p>
                                    <p><strong>Phone:</strong> <?php echo esc_html($phone); ?></p>
                                    <p><a href="<?php echo get_permalink($location->ID); ?>">View Details</a></p>
                                    <p><em>(Coordinates missing, using address)</em></p>
                                    ]]>
                                </description>
                                <address><?php echo esc_html("$address, $city, $state $zip"); ?></address>
                            </Placemark>
                            <?php
                        }
                    }
                    ?>
                </Document>
            </kml>
            <?php
            exit;
        }
    }
}
Chroma_KML_Endpoint::init();



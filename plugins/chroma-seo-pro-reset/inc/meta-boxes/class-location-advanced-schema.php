<?php
/**
 * Location Advanced Schema Meta Box
 * Adds fields for license, Google Maps CID, Open House, and Event Venue toggle
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_Advanced_Schema
{
    public function register()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_location', [$this, 'save_meta_box']);
    }

    /**
     * Add Meta Box to Location Post Type
     */
    public function add_meta_box()
    {
        add_meta_box(
            'chroma_location_advanced_schema',
            '🔍 Advanced SEO Schema',
            [$this, 'render_meta_box'],
            'location',
            'side',
            'default'
        );
    }

    /**
     * Render Meta Box Contents
     */
    public function render_meta_box($post)
    {
        wp_nonce_field('chroma_location_schema_nonce', 'chroma_location_schema_nonce');

        $license = get_post_meta($post->ID, '_chroma_license_number', true);
        $cid = get_post_meta($post->ID, '_chroma_google_maps_cid', true);
        $open_house = get_post_meta($post->ID, '_chroma_open_house_date', true);
        $is_venue = get_post_meta($post->ID, '_chroma_is_event_venue', true);
        $amenities = get_post_meta($post->ID, '_chroma_amenities', true);
        ?>
        <div style="margin-bottom: 15px;">
            <label for="chroma_license_number" style="display: block; margin-bottom: 5px; font-weight: bold;">
                📜 License Number
            </label>
            <input type="text" id="chroma_license_number" name="chroma_license_number" 
                   value="<?php echo esc_attr($license); ?>" class="widefat"
                   placeholder="e.g., DECAL-123456">
            <p class="description">Georgia DECAL license number for schema.org hasCredential</p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="chroma_google_maps_cid" style="display: block; margin-bottom: 5px; font-weight: bold;">
                📍 Google Maps CID
            </label>
            <input type="text" id="chroma_google_maps_cid" name="chroma_google_maps_cid" 
                   value="<?php echo esc_attr($cid); ?>" class="widefat"
                   placeholder="e.g., 12345678901234567890">
            <p class="description">Find in Google Maps URL: maps.google.com/?cid=<strong>THIS</strong></p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="chroma_open_house_date" style="display: block; margin-bottom: 5px; font-weight: bold;">
                🎉 Next Open House Date
            </label>
            <input type="datetime-local" id="chroma_open_house_date" name="chroma_open_house_date" 
                   value="<?php echo esc_attr($open_house); ?>" class="widefat">
            <p class="description">Generates Event schema for this date</p>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="chroma_is_event_venue" value="1" <?php checked($is_venue, '1'); ?>>
                <span><strong>🏛️ Is Event Venue</strong></span>
            </label>
            <p class="description">Adds EventVenue schema type (for party rentals, etc.)</p>
        </div>

        <?php if (!empty($amenities) && is_array($amenities)): ?>
        <div style="margin-bottom: 15px; padding: 10px; background: #f0f9ff; border-radius: 4px;">
            <strong>🛡️ Safety Amenities (AI-Generated)</strong>
            <ul style="margin: 5px 0 0 15px; padding: 0; list-style: disc;">
                <?php foreach ($amenities as $a): ?>
                    <li style="margin: 2px 0;"><?php echo esc_html($a); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Save Meta Box Data
     */
    public function save_meta_box($post_id)
    {
        if (!isset($_POST['chroma_location_schema_nonce']) ||
            !wp_verify_nonce($_POST['chroma_location_schema_nonce'], 'chroma_location_schema_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // License Number
        if (isset($_POST['chroma_license_number'])) {
            update_post_meta($post_id, '_chroma_license_number', sanitize_text_field($_POST['chroma_license_number']));
        }

        // Google Maps CID
        if (isset($_POST['chroma_google_maps_cid'])) {
            update_post_meta($post_id, '_chroma_google_maps_cid', sanitize_text_field($_POST['chroma_google_maps_cid']));
        }

        // Open House Date
        if (isset($_POST['chroma_open_house_date'])) {
            update_post_meta($post_id, '_chroma_open_house_date', sanitize_text_field($_POST['chroma_open_house_date']));
        }

        // Is Event Venue
        $is_venue = isset($_POST['chroma_is_event_venue']) ? '1' : '';
        update_post_meta($post_id, '_chroma_is_event_venue', $is_venue);
    }
}

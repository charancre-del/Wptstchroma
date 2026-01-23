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
            '🔍 Location Facts & Advanced Schema',
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
        $quality = get_post_meta($post->ID, 'location_quality_rated', true);
        $open_house = get_post_meta($post->ID, '_chroma_open_house_date', true);
        $is_venue = get_post_meta($post->ID, '_chroma_is_event_venue', true);
        $caps = get_post_meta($post->ID, '_chroma_caps_accepted', true);
        $prek = get_post_meta($post->ID, '_chroma_ga_pre_k_accepted', true);
        $cameras = get_post_meta($post->ID, '_chroma_security_cameras', true);
        $amenities = get_post_meta($post->ID, '_chroma_amenities', true);
        ?>
        <div style="margin-bottom: 15px;">
            <label for="chroma_license_number" style="display: block; margin-bottom: 5px; font-weight: bold;">
                📜 License Number
            </label>
            <input type="text" id="chroma_license_number" name="chroma_license_number" 
                   value="<?php echo esc_attr($license); ?>" class="widefat"
                   placeholder="e.g., DECAL-123456">
            <p class="description">Georgia DECAL license number.</p>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="location_quality_rated" style="display: block; margin-bottom: 5px; font-weight: bold;">
                ⭐ Quality Rated Level
            </label>
            <input type="text" id="location_quality_rated" name="location_quality_rated" 
                   value="<?php echo esc_attr($quality); ?>" class="widefat"
                   placeholder="e.g., 2-Star, 3-Star">
            <p class="description">Official Quality Rated status.</p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="chroma_google_maps_cid" style="display: block; margin-bottom: 5px; font-weight: bold;">
                📍 Google Maps CID
            </label>
            <input type="text" id="chroma_google_maps_cid" name="chroma_google_maps_cid" 
                   value="<?php echo esc_attr($cid); ?>" class="widefat"
                   placeholder="e.g., 12345678901234567890">
            <p class="description">Find in Google Maps URL.</p>
        </div>

        <div style="margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 10px;">
            <strong>Verification Flags</strong>
            
            <p style="margin-bottom: 5px;">
                <label style="cursor: pointer;">
                    <input type="checkbox" name="chroma_caps_accepted" value="1" <?php checked($caps, '1'); ?>>
                    <span>Accepts CAPS (Subsidies)</span>
                </label>
            </p>

            <p style="margin-bottom: 5px;">
                <label style="cursor: pointer;">
                    <input type="checkbox" name="chroma_ga_pre_k_accepted" value="1" <?php checked($prek, '1'); ?>>
                    <span>Offers GA Lottery Pre-K</span>
                </label>
            </p>

            <p style="margin-bottom: 5px;">
                <label style="cursor: pointer;">
                    <input type="checkbox" name="chroma_security_cameras" value="1" <?php checked($cameras, '1'); ?>>
                    <span>Security Cameras</span>
                </label>
            </p>
            
            <p style="margin-bottom: 5px;">
                <label style="cursor: pointer;">
                    <input type="checkbox" name="chroma_is_event_venue" value="1" <?php checked($is_venue, '1'); ?>>
                    <span>Is Event Venue (Rentals)</span>
                </label>
            </p>
        </div>

        <div style="margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 10px;">
            <label for="chroma_open_house_date" style="display: block; margin-bottom: 5px; font-weight: bold;">
                🎉 Next Open House Date
            </label>
            <input type="datetime-local" id="chroma_open_house_date" name="chroma_open_house_date" 
                   value="<?php echo esc_attr($open_house); ?>" class="widefat">
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

        // Text Fields
        $text_fields = [
            '_chroma_license_number' => 'chroma_license_number',
            '_chroma_google_maps_cid' => 'chroma_google_maps_cid',
            'location_quality_rated' => 'location_quality_rated',
            '_chroma_open_house_date' => 'chroma_open_house_date'
        ];

        foreach ($text_fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            }
        }

        // Checkbox Fields
        $checkboxes = [
            '_chroma_is_event_venue' => 'chroma_is_event_venue',
            '_chroma_caps_accepted' => 'chroma_caps_accepted',
            '_chroma_ga_pre_k_accepted' => 'chroma_ga_pre_k_accepted',
            '_chroma_security_cameras' => 'chroma_security_cameras'
        ];

        foreach ($checkboxes as $meta_key => $post_key) {
            $val = isset($_POST[$post_key]) ? '1' : '';
            update_post_meta($post_id, $meta_key, $val);
        }
    }
}



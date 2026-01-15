<?php
/**
 * Offers & Pricing Meta Box
 * Handles tuition ranges and special offers
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_Pricing_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_location_pricing';
    }

    /**
     * Get meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return __('Tuition & Offers', 'chroma-excellence');
    }

    /**
     * Get allowed post types
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['location'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    public function render_fields($post)
    {
        $price_min = get_post_meta($post->ID, 'location_price_min', true);
        $price_max = get_post_meta($post->ID, 'location_price_max', true);
        $currency = get_post_meta($post->ID, 'location_price_currency', true) ?: 'USD';
        $frequency = get_post_meta($post->ID, 'location_price_frequency', true) ?: 'Month';

        ?>
        <div class="chroma-meta-section">
            <h4><?php _e('Tuition Range', 'chroma-excellence'); ?></h4>
            <p class="description">
                <?php _e('Providing a price range helps qualify leads and improves click-through rate.', 'chroma-excellence'); ?>
            </p>

            <div class="chroma-meta-field-row" style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label for="location_price_min"><?php _e('Min Price', 'chroma-excellence'); ?></label>
                    <input type="number" id="location_price_min" name="location_price_min"
                        value="<?php echo esc_attr($price_min); ?>" placeholder="800" />
                </div>
                <div style="flex: 1;">
                    <label for="location_price_max"><?php _e('Max Price', 'chroma-excellence'); ?></label>
                    <input type="number" id="location_price_max" name="location_price_max"
                        value="<?php echo esc_attr($price_max); ?>" placeholder="1200" />
                </div>
                <div style="width: 80px;">
                    <label for="location_price_currency"><?php _e('Currency', 'chroma-excellence'); ?></label>
                    <input type="text" id="location_price_currency" name="location_price_currency"
                        value="<?php echo esc_attr($currency); ?>" />
                </div>
                <div style="flex: 1;">
                    <label for="location_price_frequency"><?php _e('Frequency', 'chroma-excellence'); ?></label>
                    <select id="location_price_frequency" name="location_price_frequency" class="widefat">
                        <option value="Week" <?php selected($frequency, 'Week'); ?>>Per Week</option>
                        <option value="Month" <?php selected($frequency, 'Month'); ?>>Per Month</option>
                        <option value="Year" <?php selected($frequency, 'Year'); ?>>Per Year</option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save the meta box fields
     *
     * @param int $post_id Post ID
     */
    public function save_fields($post_id)
    {
        $fields = [
            'location_price_min',
            'location_price_max',
            'location_price_currency',
            'location_price_frequency',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

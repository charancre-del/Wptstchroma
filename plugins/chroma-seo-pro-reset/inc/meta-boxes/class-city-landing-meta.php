<?php
/**
 * City Landing Meta Box
 * Fields for Hyperlocal City Pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_City_Landing_Meta extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get the meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_city_landing_meta';
    }

    /**
     * Get the meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return __('City Landing Configuration', 'chroma-excellence');
    }

    /**
     * Get post types this meta box applies to
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['city'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    public function render_fields($post)
    {
        // Get all published locations
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $selected_locations = get_post_meta($post->ID, 'city_nearby_locations', true);
        if (!is_array($selected_locations)) {
            $selected_locations = [];
        }

        $intro_text = get_post_meta($post->ID, 'city_intro_text', true);
        $city_county = get_post_meta($post->ID, 'city_county', true);

        // Available counties in Metro Atlanta
        $counties = [
            '' => __('Select County...', 'chroma-excellence'),
            'Cobb' => 'Cobb County',
            'Gwinnett' => 'Gwinnett County',
            'Fulton' => 'Fulton County (North)',
            'DeKalb' => 'DeKalb County',
            'Cherokee' => 'Cherokee County',
            'Forsyth' => 'Forsyth County',
            'Clayton' => 'Clayton County',
            'Henry' => 'Henry County',
            'Douglas' => 'Douglas County',
            'Paulding' => 'Paulding County',
            'Fayette' => 'Fayette County',
            'Rockdale' => 'Rockdale County',
            'Other' => 'Other Areas'
        ];
        ?>
        <div class="chroma-field-wrapper">
            <p class="description">
                <?php _e('Configure this page as a Hyperlocal City Landing Page. Select the locations that serve this city.', 'chroma-excellence'); ?>
            </p>

            <div style="margin-bottom: 20px;">
                <label for="city_county"><strong><?php _e('County:', 'chroma-excellence'); ?></strong></label>
                <select id="city_county" name="city_county" class="widefat" style="max-width: 300px;">
                    <?php foreach ($counties as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($city_county, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php _e('Select the county this city belongs to. Used for grouping on the Communities archive page.', 'chroma-excellence'); ?>
                </p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="city_intro_text"><?php _e('Local Intro Text (SEO Optimized):', 'chroma-excellence'); ?></label>
                <textarea id="city_intro_text" name="city_intro_text" rows="5" class="widefat"
                    placeholder="<?php _e('Example: Parents in Canton trust Chroma for...', 'chroma-excellence'); ?>"><?php echo esc_textarea($intro_text); ?></textarea>
                <p class="description">
                    <?php _e('Write a unique introduction about childcare in this specific city.', 'chroma-excellence'); ?>
                </p>
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php _e('Nearby Locations to Display:', 'chroma-excellence'); ?></label>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    <?php foreach ($locations as $location): ?>
                        <div style="margin-bottom: 5px;">
                            <label>
                                <input type="checkbox" name="city_nearby_locations[]" value="<?php echo esc_attr($location->ID); ?>"
                                    <?php checked(in_array($location->ID, $selected_locations)); ?> />
                                <?php echo esc_html($location->post_title); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php _e('Select the locations that are relevant to this city.', 'chroma-excellence'); ?>
                </p>
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
        // Save county
        if (isset($_POST['city_county'])) {
            update_post_meta($post_id, 'city_county', sanitize_text_field($_POST['city_county']));
        }

        if (isset($_POST['city_intro_text'])) {
            update_post_meta($post_id, 'city_intro_text', wp_kses_post($_POST['city_intro_text']));
        }

        if (isset($_POST['city_nearby_locations'])) {
            $locations = array_map('intval', $_POST['city_nearby_locations']);
            update_post_meta($post_id, 'city_nearby_locations', $locations);
        } else {
            delete_post_meta($post_id, 'city_nearby_locations');
        }
    }
}



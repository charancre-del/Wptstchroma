<?php
/**
 * Meta Box Base Class
 * Provides reusable framework for creating meta boxes with consistent patterns
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

abstract class Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get the meta box ID
     *
     * @return string
     */
    abstract public function get_id();

    /**
     * Get the meta box title
     *
     * @return string
     */
    abstract public function get_title();

    /**
     * Get post types this meta box applies to
     *
     * @return array
     */
    abstract public function get_post_types();

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    abstract public function render_fields($post);

    /**
     * Save the meta box fields
     *
     * @param int $post_id Post ID
     */
    abstract public function save_fields($post_id);

    /**
     * Register the meta box
     */
    public function register()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
    }

    /**
     * Add the meta box to WordPress
     */
    public function add_meta_box()
    {
        add_meta_box(
            $this->get_id(),
            $this->get_title(),
            [$this, 'render'],
            $this->get_post_types(),
            'normal',
            'default'
        );
    }

    /**
     * Render the meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render($post)
    {
        wp_nonce_field($this->get_id() . '_save', $this->get_id() . '_nonce');
        echo '<div class="chroma-advanced-seo-meta-box">';
        $this->render_fields($post);
        echo '</div>';
    }

    /**
     * Save the meta box
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_meta_box($post_id, $post)
    {
        // Check nonce
        $nonce_name = $this->get_id() . '_nonce';
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $this->get_id() . '_save')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check post type
        if (!in_array($post->post_type, $this->get_post_types())) {
            return;
        }

        // Call child class save method
        $this->save_fields($post_id);
    }

    /**
     * Render a text input field
     *
     * @param array $args Field arguments
     */
    protected function render_text_field($args)
    {
        $defaults = [
            'id' => '',
            'label' => '',
            'value' => '',
            'description' => '',
            'fallback_notice' => '',
            'placeholder' => '',
            'class' => 'regular-text',
        ];
        $args = wp_parse_args($args, $defaults);

        echo '<div class="chroma-field-wrapper">';
        if ($args['label']) {
            echo '<label for="' . esc_attr($args['id']) . '">' . esc_html($args['label']) . '</label>';
        }
        echo '<input type="text" 
			id="' . esc_attr($args['id']) . '" 
			name="' . esc_attr($args['id']) . '" 
			value="' . esc_attr($args['value']) . '" 
			placeholder="' . esc_attr($args['placeholder']) . '"
			class="' . esc_attr($args['class']) . '" />';

        if ($args['description']) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        if ($args['fallback_notice'] && empty($args['value'])) {
            echo '<p class="description fallback-notice"><em>Fallback: ' . esc_html($args['fallback_notice']) . '</em></p>';
        }
        echo '</div>';
    }

    /**
     * Render a number input field
     *
     * @param array $args Field arguments
     */
    protected function render_number_field($args)
    {
        $defaults = [
            'id' => '',
            'label' => '',
            'value' => '',
            'description' => '',
            'fallback_notice' => '',
            'placeholder' => '',
            'step' => '1',
            'min' => '',
            'max' => '',
            'class' => 'small-text',
        ];
        $args = wp_parse_args($args, $defaults);

        echo '<div class="chroma-field-wrapper">';
        if ($args['label']) {
            echo '<label for="' . esc_attr($args['id']) . '">' . esc_html($args['label']) . '</label>';
        }
        echo '<input type="number" 
			id="' . esc_attr($args['id']) . '" 
			name="' . esc_attr($args['id']) . '" 
			value="' . esc_attr($args['value']) . '" 
			placeholder="' . esc_attr($args['placeholder']) . '"
			step="' . esc_attr($args['step']) . '"
			' . ($args['min'] !== '' ? 'min="' . esc_attr($args['min']) . '"' : '') . '
			' . ($args['max'] !== '' ? 'max="' . esc_attr($args['max']) . '"' : '') . '
			class="' . esc_attr($args['class']) . '" />';

        if ($args['description']) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        if ($args['fallback_notice'] && empty($args['value'])) {
            echo '<p class="description fallback-notice"><em>Fallback: ' . esc_html($args['fallback_notice']) . '</em></p>';
        }
        echo '</div>';
    }

    /**
     * Render a textarea field
     *
     * @param array $args Field arguments
     */
    protected function render_textarea_field($args)
    {
        $defaults = [
            'id' => '',
            'label' => '',
            'value' => '',
            'description' => '',
            'fallback_notice' => '',
            'placeholder' => '',
            'rows' => 4,
            'class' => 'large-text',
        ];
        $args = wp_parse_args($args, $defaults);

        echo '<div class="chroma-field-wrapper">';
        if ($args['label']) {
            echo '<label for="' . esc_attr($args['id']) . '">' . esc_html($args['label']) . '</label>';
        }
        echo '<textarea 
			id="' . esc_attr($args['id']) . '" 
			name="' . esc_attr($args['id']) . '" 
			placeholder="' . esc_attr($args['placeholder']) . '"
			rows="' . esc_attr($args['rows']) . '"
			class="' . esc_attr($args['class']) . '">' . esc_textarea($args['value']) . '</textarea>';

        if ($args['description']) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        if ($args['fallback_notice'] && empty($args['value'])) {
            echo '<p class="description fallback-notice"><em>Fallback: ' . esc_html($args['fallback_notice']) . '</em></p>';
        }
        echo '</div>';
    }

    /**
     * Render a repeater field
     *
     * @param array $args Field arguments
     */
    protected function render_repeater_field($args)
    {
        $defaults = [
            'id' => '',
            'label' => '',
            'values' => [],
            'description' => '',
            'button_text' => 'Add Item',
            'placeholder' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        echo '<div class="chroma-field-wrapper chroma-repeater-field" data-field-id="' . esc_attr($args['id']) . '">';
        if ($args['label']) {
            echo '<label>' . esc_html($args['label']) . '</label>';
        }

        echo '<div class="chroma-repeater-items">';
        if (!empty($args['values'])) {
            foreach ($args['values'] as $index => $value) {
                echo '<div class="chroma-repeater-item">';
                echo '<input type="text" name="' . esc_attr($args['id']) . '[]" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($args['placeholder']) . '" />';
                echo '<button type="button" class="button chroma-remove-item">Remove</button>';
                echo '</div>';
            }
        }
        echo '</div>';

        echo '<button type="button" class="button chroma-add-item">' . esc_html($args['button_text']) . '</button>';

        if ($args['description']) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        echo '</div>';
    }
}



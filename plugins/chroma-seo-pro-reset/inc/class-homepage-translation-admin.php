<?php
/**
 * Homepage Translation Admin Page
 * Provides a dedicated interface for translating homepage content from Customizer.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Homepage_Translation_Admin {
    
    /**
     * Option key for storing homepage translations
     */
    const OPTION_KEY = 'chroma_homepage_translations_es';

    /**
     * Initialize
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_chroma_translate_homepage', [$this, 'ajax_translate_homepage']);
    }

    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'themes.php',
            __('Homepage Translation', 'chroma-excellence'),
            __('Homepage Translation (ES)', 'chroma-excellence'),
            'manage_options',
            'chroma-homepage-translation',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('chroma_homepage_translation', self::OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize_translations']
        ]);
    }

    /**
     * Sanitize translations
     */
    public function sanitize_translations($input) {
        $sanitized = [];
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                if (strpos($key, 'content') !== false || strpos($key, 'heading') !== false) {
                    $sanitized[$key] = wp_kses_post($value);
                } else {
                    $sanitized[$key] = sanitize_text_field($value);
                }
            }
        }
        return $sanitized;
    }

    /**
     * Get translation fields
     */
    private function get_fields() {
        return [
            'hero' => [
                'title' => __('Hero Section', 'chroma-excellence'),
                'fields' => [
                    'chroma_home_hero_heading' => ['label' => 'Heading', 'type' => 'textarea', 'rows' => 3],
                    'chroma_home_hero_subheading' => ['label' => 'Subheading', 'type' => 'textarea', 'rows' => 2],
                    'chroma_home_hero_cta_label' => ['label' => 'Primary CTA Label', 'type' => 'text'],
                    'chroma_home_hero_secondary_label' => ['label' => 'Secondary CTA Label', 'type' => 'text'],
                ]
            ],
            'prismpath' => [
                'title' => __('Prismpath Section', 'chroma-excellence'),
                'fields' => [
                    'chroma_home_prismpath_eyebrow' => ['label' => 'Eyebrow', 'type' => 'text'],
                    'chroma_home_prismpath_heading' => ['label' => 'Heading', 'type' => 'text'],
                    'chroma_home_prismpath_subheading' => ['label' => 'Subheading', 'type' => 'textarea', 'rows' => 2],
                    'chroma_home_prismpath_cta_label' => ['label' => 'CTA Label', 'type' => 'text'],
                ]
            ],
            'locations' => [
                'title' => __('Locations Section', 'chroma-excellence'),
                'fields' => [
                    'chroma_home_locations_heading' => ['label' => 'Heading', 'type' => 'text'],
                    'chroma_home_locations_subheading' => ['label' => 'Subheading', 'type' => 'textarea', 'rows' => 2],
                    'chroma_home_locations_cta_label' => ['label' => 'CTA Label', 'type' => 'text'],
                ]
            ],
            'faq' => [
                'title' => __('FAQ Section', 'chroma-excellence'),
                'fields' => [
                    'chroma_home_faq_heading' => ['label' => 'Heading', 'type' => 'text'],
                    'chroma_home_faq_subheading' => ['label' => 'Subheading', 'type' => 'textarea', 'rows' => 2],
                ]
            ]
        ];
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $saved = get_option(self::OPTION_KEY, []);
        $sections = $this->get_fields();
        ?>
        <div class="wrap">
            <h1><?php _e('Homepage Translation (Spanish)', 'chroma-excellence'); ?></h1>
            <p class="description"><?php _e('Translate the homepage content for Spanish visitors (/es/). Leave fields blank to use the default English content.', 'chroma-excellence'); ?></p>
            
            <div style="margin: 20px 0; padding: 15px; background: #f0f6fc; border-left: 4px solid #0073aa;">
                <button type="button" id="chroma-auto-translate-homepage" class="button button-primary button-large">
                    <span class="dashicons dashicons-translation" style="line-height: 28px; margin-right: 5px;"></span>
                    <?php _e('Auto-Fill with AI Translation', 'chroma-excellence'); ?>
                </button>
                <span class="spinner" id="chroma-translate-spinner" style="float: none; margin-left: 10px;"></span>
                <span id="chroma-translate-status" style="margin-left: 10px; font-weight: bold;"></span>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('chroma_homepage_translation'); ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <?php foreach ($sections as $section_id => $section): ?>
                    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;"><?php echo esc_html($section['title']); ?></h2>
                        <table class="form-table">
                            <?php foreach ($section['fields'] as $key => $field): 
                                $value = $saved[$key] ?? '';
                                $en_value = get_theme_mod($key, '');
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($field['label']); ?></label>
                                    <?php if ($en_value): ?>
                                    <p style="font-weight: normal; font-size: 11px; color: #777; margin: 5px 0 0;">
                                        EN: <?php echo esc_html(mb_substr(wp_strip_all_tags($en_value), 0, 50)); ?>...
                                    </p>
                                    <?php endif; ?>
                                </th>
                                <td>
                                    <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea 
                                        id="<?php echo esc_attr($key); ?>" 
                                        name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr($key); ?>]" 
                                        rows="<?php echo $field['rows'] ?? 3; ?>"
                                        class="large-text"
                                        placeholder="<?php echo esc_attr($en_value); ?>"
                                    ><?php echo esc_textarea($value); ?></textarea>
                                    <?php else: ?>
                                    <input 
                                        type="text" 
                                        id="<?php echo esc_attr($key); ?>" 
                                        name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr($key); ?>]" 
                                        value="<?php echo esc_attr($value); ?>"
                                        class="regular-text"
                                        placeholder="<?php echo esc_attr($en_value); ?>"
                                    />
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php submit_button(__('Save Spanish Translations', 'chroma-excellence')); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#chroma-auto-translate-homepage').click(function(e) {
                e.preventDefault();
                var btn = $(this);
                var spinner = $('#chroma-translate-spinner');
                var status = $('#chroma-translate-status');
                
                if(!confirm('This will translate all homepage fields to Spanish using AI. Continue?')) {
                    return;
                }

                btn.prop('disabled', true);
                spinner.addClass('is-active');
                status.text(' Translating... please wait.');

                $.post(ajaxurl, {
                    action: 'chroma_translate_homepage',
                    nonce: '<?php echo wp_create_nonce('chroma_homepage_translate'); ?>'
                }, function(response) {
                    btn.prop('disabled', false);
                    spinner.removeClass('is-active');
                    
                    if(response.success) {
                        status.text(' Done! Populating fields...').css('color', 'green');
                        
                        // Populate fields
                        Object.keys(response.data).forEach(function(key) {
                            var $field = $('#' + key);
                            if ($field.length) {
                                $field.val(response.data[key]);
                            }
                        });
                        
                        status.text(' Done! Click Save to keep changes.');
                    } else {
                        status.text(' Error: ' + (response.data.message || 'Unknown')).css('color', 'red');
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    spinner.removeClass('is-active');
                    status.text(' Request Failed').css('color', 'red');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Translate homepage fields
     */
    public function ajax_translate_homepage() {
        check_ajax_referer('chroma_homepage_translate', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Collect English fields from Customizer
        $fields_to_translate = [];
        $sections = $this->get_fields();
        
        foreach ($sections as $section) {
            foreach ($section['fields'] as $key => $field) {
                $en_value = get_theme_mod($key, '');
                if (!empty($en_value)) {
                    $fields_to_translate[$key] = $en_value;
                }
            }
        }

        if (empty($fields_to_translate)) {
            wp_send_json_error(['message' => 'No content to translate']);
        }

        // Use Translation Engine
        if (!class_exists('Chroma_Translation_Engine')) {
            wp_send_json_error(['message' => 'Translation Engine not available']);
        }

        $translated = Chroma_Translation_Engine::translate_bulk(
            $fields_to_translate, 
            'es', 
            'Translate for a childcare early learning academy website homepage. Use friendly, professional Spanish (Latin American).',
            true // Force fresh translation
        );

        if (isset($translated['_error'])) {
            wp_send_json_error(['message' => $translated['_error']]);
        }

        wp_send_json_success($translated);
    }
}

// Initialize
add_action('plugins_loaded', function() {
    (new Chroma_Homepage_Translation_Admin())->init();
});



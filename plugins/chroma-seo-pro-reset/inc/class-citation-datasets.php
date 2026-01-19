<?php
/**
 * Citation Datasets & Global Facts
 * Provides an Options Page for managing global facts and a JSON endpoint for AI
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Citation_Datasets
{
    /**
     * Option name
     */
    const OPTION_NAME = 'chroma_citation_facts';

    /**
     * Initialize the module
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'register_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_api_endpoint']);
    }

    /**
     * Register Options Page
     */
    public function register_options_page()
    {
        add_submenu_page(
            'edit.php?post_type=location',
            'Citation Datasets',
            'Citation Datasets',
            'manage_options',
            'chroma-citation-datasets',
            [$this, 'render_page']
        );
    }

    /**
     * Register Settings
     */
    public function register_settings()
    {
        register_setting(self::OPTION_NAME, self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize_facts']
        ]);
    }

    /**
     * Sanitize Facts
     */
    public function sanitize_facts($input)
    {
        $clean = [];
        if (isset($input['label']) && is_array($input['label'])) {
            for ($i = 0; $i < count($input['label']); $i++) {
                if (!empty($input['label'][$i])) {
                    $clean[] = [
                        'label' => sanitize_text_field($input['label'][$i]),
                        'value' => sanitize_text_field($input['value'][$i]),
                        'source' => esc_url_raw($input['source'][$i]),
                        'date' => sanitize_text_field($input['date'][$i]),
                    ];
                }
            }
        }
        return $clean;
    }

    /**
     * Render Options Page
     */
    public function render_page()
    {
        $facts = get_option(self::OPTION_NAME, []);
        ?>
        <div class="wrap">
            <h1><?php _e('Citation Datasets & Global Facts', 'chroma-excellence'); ?></h1>
            <p class="description">
                <?php _e('Manage global facts about your organization. These are exposed via JSON for AI bots (ChatGPT, Perplexity) to cite as truth.', 'chroma-excellence'); ?>
            </p>

            <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px;">
                <h3><?php _e('AI Data Endpoint', 'chroma-excellence'); ?></h3>
                <p>
                    <?php _e('Your structured data is available at:', 'chroma-excellence'); ?>
                    <br>
                    <code><?php echo esc_url(rest_url('chroma/v1/citation-facts')); ?></code>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION_NAME); ?>

                <table class="widefat striped" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Fact Label (e.g., "Total Students")</th>
                            <th>Value (e.g., "500+")</th>
                            <th>Source URL (Optional)</th>
                            <th>Last Verified (Date)</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="chroma-facts-list">
                        <?php if (!empty($facts)): ?>
                            <?php foreach ($facts as $fact): ?>
                                <tr>
                                    <td><input type="text" name="<?php echo self::OPTION_NAME; ?>[label][]"
                                            value="<?php echo esc_attr($fact['label']); ?>" class="widefat"></td>
                                    <td><input type="text" name="<?php echo self::OPTION_NAME; ?>[value][]"
                                            value="<?php echo esc_attr($fact['value']); ?>" class="widefat"></td>
                                    <td><input type="url" name="<?php echo self::OPTION_NAME; ?>[source][]"
                                            value="<?php echo esc_url($fact['source']); ?>" class="widefat"></td>
                                    <td><input type="date" name="<?php echo self::OPTION_NAME; ?>[date][]"
                                            value="<?php echo esc_attr($fact['date']); ?>" class="widefat"></td>
                                    <td><button type="button" class="button chroma-remove-fact">×</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Empty Row Template -->
                        <tr class="chroma-fact-template">
                            <td><input type="text" name="<?php echo self::OPTION_NAME; ?>[label][]" class="widefat"
                                    placeholder="Fact Label"></td>
                            <td><input type="text" name="<?php echo self::OPTION_NAME; ?>[value][]" class="widefat"
                                    placeholder="Value"></td>
                            <td><input type="url" name="<?php echo self::OPTION_NAME; ?>[source][]" class="widefat"
                                    placeholder="https://..."></td>
                            <td><input type="date" name="<?php echo self::OPTION_NAME; ?>[date][]" class="widefat"></td>
                            <td><button type="button" class="button chroma-remove-fact">×</button></td>
                        </tr>
                    </tbody>
                </table>

                <p><button type="button" class="button"
                        id="chroma-add-fact"><?php _e('+ Add Fact', 'chroma-excellence'); ?></button></p>

                <?php submit_button(); ?>
            </form>

            <script>
                jQuery(document).ready(function ($) {
                    $('#chroma-add-fact').on('click', function () {
                        var $row = $('.chroma-fact-template').first().clone();
                        $row.removeClass('chroma-fact-template');
                        $row.find('input').val('');
                        $('#chroma-facts-list').append($row);
                    });

                    $(document).on('click', '.chroma-remove-fact', function () {
                        if ($('#chroma-facts-list tr').length > 1) {
                            $(this).closest('tr').remove();
                        } else {
                            $(this).closest('tr').find('input').val('');
                        }
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * Register API Endpoint
     */
    public function register_api_endpoint()
    {
        register_rest_route('chroma/v1', '/citation-facts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_facts_json'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Get Facts JSON
     */
    public function get_facts_json()
    {
        $facts = get_option(self::OPTION_NAME, []);

        return new WP_REST_Response([
            'organization' => get_bloginfo('name'),
            'url' => home_url(),
            'generated_at' => current_time('c'),
            'facts' => $facts
        ], 200);
    }
}



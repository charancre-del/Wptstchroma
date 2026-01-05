<?php
/**
 * Content Translation Inspector
 * Overview of translation status across all content types.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Content_Inspector
{
    public function init()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('wp_ajax_chroma_bulk_translate_all', [$this, 'ajax_bulk_translate_all']);
    }

    public function register_menu()
    {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Content Inspector',
            'Content Inspector',
            'manage_options',
            'chroma-content-inspector',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        $post_types = ['page', 'location', 'program', 'city'];
        $posts = get_posts([
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'orderby' => 'post_type',
            'order' => 'ASC'
        ]);

        // Calculate stats
        $total = count($posts);
        $translated = 0;
        $untranslated_ids = [];
        foreach ($posts as $post) {
            // Check for content OR specific meta keys depending on type
            $is_translated = get_post_meta($post->ID, '_chroma_es_content', true);
            
            if (!$is_translated) {
                if ($post->post_type === 'location') {
                    $is_translated = get_post_meta($post->ID, '_chroma_es_location_address', true);
                } elseif ($post->post_type === 'program') {
                    $is_translated = get_post_meta($post->ID, '_chroma_es_program_age_range', true);
                } elseif ($post->post_type === 'city') {
                    $is_translated = get_post_meta($post->ID, '_chroma_es_city_state', true);
                }
            }

            if ($is_translated) {
                $translated++;
            } else {
                $untranslated_ids[] = $post->ID;
            }
        }
        $percent = $total > 0 ? round(($translated / $total) * 100) : 0;

        ?>
        <div class="wrap chroma-seo-dashboard">
            <h1>🌎 Content Translation Inspector</h1>
            <p>Overview of English content and their Spanish counterparts.</p>

            <!-- Progress Stats -->
            <div class="card" style="padding: 20px; max-width: 600px; margin-bottom: 20px;">
                <h3>📊 Translation Progress</h3>
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <progress value="<?php echo $translated; ?>" max="<?php echo $total; ?>" style="width: 100%; height: 30px;"></progress>
                    </div>
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo $percent == 100 ? 'green' : '#333'; ?>">
                        <?php echo $percent; ?>%
                    </div>
                </div>
                <p>
                    <strong><?php echo $translated; ?></strong> of <strong><?php echo $total; ?></strong> pages translated
                    <?php if (count($untranslated_ids) > 0): ?>
                        <span style="color: #856404;">(<?php echo count($untranslated_ids); ?> pending)</span>
                    <?php else: ?>
                        <span style="color: green;">✅ All done!</span>
                    <?php endif; ?>
                </p>
                
                <?php if (count($untranslated_ids) > 0): ?>
                <div style="margin-top: 15px;">
                    <button id="chroma-bulk-translate-all" class="button button-primary button-large">
                        <span class="dashicons dashicons-translation" style="line-height: 28px;"></span>
                        Translate All Missing (<?php echo count($untranslated_ids); ?> pages)
                    </button>
                    <span id="bulk-status" style="margin-left: 10px;"></span>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    var untranslated = <?php echo json_encode($untranslated_ids); ?>;
                    var current = 0;
                    
                    $('#chroma-bulk-translate-all').click(function() {
                        if (!confirm('This will use AI tokens to translate ' + untranslated.length + ' pages. Continue?')) return;
                        
                        $(this).prop('disabled', true);
                        translateNext();
                    });
                    
                    function translateNext() {
                        if (current >= untranslated.length) {
                            $('#bulk-status').text('Done! Refresh to see results.').css('color', 'green');
                            return;
                        }
                        
                        var postId = untranslated[current];
                        $('#bulk-status').text('Translating ' + (current + 1) + ' of ' + untranslated.length + '...');
                        
                        $.post(ajaxurl, {
                            action: 'chroma_auto_translate_post',
                            post_id: postId,
                            nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                        }, function(response) {
                            if (response.success) {
                                // Data is saved by the endpoint now
                            }
                            current++;
                            translateNext();
                        }).fail(function() {
                            current++;
                            translateNext();
                        });
                    }
                });
                </script>
                <?php endif; ?>
            </div>

            <div class="card" style="padding: 20px; max-width: 1200px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 25%;">Title (English)</th>
                            <th style="width: 25%;">English URL</th>
                            <th style="width: 25%;">Spanish URL (Calculated)</th>
                            <th style="width: 10%;">ES Content?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): 
                            $en_url = get_permalink($post->ID);
                            
                            $alternates = [];
                            if(class_exists('Chroma_Multilingual_Manager')) {
                                $alternates = Chroma_Multilingual_Manager::get_alternates($post->ID);
                            }
                            $es_url = $alternates['es'] ?? 'N/A';
                            
                            $has_content = get_post_meta($post->ID, '_chroma_es_content', true);
                            
                            // Enhanced check for table rows
                            if (!$has_content) {
                                if ($post->post_type === 'location') {
                                    $has_content = get_post_meta($post->ID, '_chroma_es_location_address', true);
                                } elseif ($post->post_type === 'program') {
                                    $has_content = get_post_meta($post->ID, '_chroma_es_program_age_range', true);
                                } elseif ($post->post_type === 'city') {
                                    $has_content = get_post_meta($post->ID, '_chroma_es_city_state', true);
                                }
                            }

                            $manual_url = get_post_meta($post->ID, 'alternate_url_es', true);
                            
                            $status_icon = $has_content ? '<span class="dashicons dashicons-yes" style="color:green"></span>' : '<span class="dashicons dashicons-minus" style="color:#ccc"></span>';
                            if ($manual_url) $status_icon .= ' <span class="dashicons dashicons-admin-links" title="Manual Link"></span>';
                        ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst($post->post_type)); ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </td>
                            <td><a href="<?php echo esc_url($en_url); ?>" target="_blank">View EN</a></td>
                            <td>
                                <?php if ($es_url !== 'N/A'): ?>
                                    <a href="<?php echo esc_url($es_url); ?>" target="_blank">View ES</a>
                                    <?php if($manual_url) echo ' (Manual)'; ?>
                                <?php else: ?>
                                    <span style="color:red;">Error</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;"><?php echo $status_icon; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Save bulk translation result
     */
    public function ajax_bulk_translate_all()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $post_id = intval($_POST['post_id']);
        $data = isset($_POST['data']) ? $_POST['data'] : [];

        foreach ($data as $key => $value) {
            if (strpos($key, '_chroma_es_') === 0) {
                update_post_meta($post_id, $key, wp_kses_post($value));
            }
        }

        wp_send_json_success();
    }
}

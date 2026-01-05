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
                <?php endif; ?>
                <?php if (!empty($untranslated_ids)): ?>
                    <script>window.chromaUntranslated = <?php echo json_encode($untranslated_ids); ?>;</script>
                <?php endif; ?>
            </div>
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
                            <th style="width: 15%;">Actions</th>
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
                            <td style="text-align:center;" class="status-cell" data-post-id="<?php echo $post->ID; ?>"><?php echo $status_icon; ?></td>
                            <td style="text-align:center;">
                                <button type="button" class="button chroma-translate-single" data-post-id="<?php echo $post->ID; ?>" title="<?php esc_attr_e('Force AI Translation', 'chroma-excellence'); ?>">
                                    <span class="dashicons dashicons-translation" style="line-height: 28px;"></span> AI Translate
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // BULK ALL
            $('#chroma-bulk-translate-all').click(function() {
                var untranslated = window.chromaUntranslated || [];
                if (untranslated.length === 0) return;
                
                if (!confirm('This will use AI tokens to translate ' + untranslated.length + ' pages. Continue?')) return;
                
                $(this).prop('disabled', true);
                var current = 0;
                
                function translateNext() {
                    if (current >= untranslated.length) {
                        $('#bulk-status').text('Done! Refresh to see results.').css('color', 'green');
                        setTimeout(function(){ location.reload(); }, 2000);
                        return;
                    }
                    
                    var postId = untranslated[current];
                    $('#bulk-status').text('Translating ' + (current + 1) + ' of ' + untranslated.length + '...');
                    
                    translateSinglePost(postId, function() {
                        current++;
                        translateNext();
                    });
                }
                
                translateNext();
            });

            // SINGLE ROW TRANSLATE (Event Delegation)
            $(document).on('click', '.chroma-translate-single', function() {
                var btn = $(this);
                var postId = btn.data('post-id');
                
                // Indicate loading
                btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span>');
                
                translateSinglePost(postId, function(success, msg) {
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-translation" style="line-height:28px;"></span> AI Translate');
                    if (success) {
                        // Update status icon
                        $('.status-cell[data-post-id="' + postId + '"]').html('<span class="dashicons dashicons-yes" style="color:green"></span>');
                        // Flash row green
                        btn.closest('tr').css('background-color', '#e6fffa');
                    } else {
                        alert('Translation failed: ' + (msg || 'Unknown error'));
                    }
                });
            });

            function translateSinglePost(postId, callback) {
                $.post(ajaxurl, {
                    action: 'chroma_auto_translate_post',
                    post_id: postId,
                    force: 'true', // Always force
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                }, function(response) {
                    if (callback) callback(response.success, response.data && response.data.message);
                }).fail(function() {
                    if (callback) callback(false, 'Network error');
                });
            }
        });
        </script>
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

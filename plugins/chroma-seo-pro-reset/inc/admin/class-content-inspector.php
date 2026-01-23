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
        $post_types = ['page', 'location', 'program', 'city', 'post', 'team_member'];
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
                } elseif ($post->post_type === 'team_member') {
                    $is_translated = get_post_meta($post->ID, '_chroma_es_team_member_title', true);
                }
                
                // Front Page Special Check
                if (!$is_translated && (int)$post->ID === (int)get_option('page_on_front')) {
                    $is_translated = get_post_meta($post->ID, '_chroma_es_home_hero_heading', true);
                }

                // Fallback: If specific key is missing, check for Title (universal)
                if (!$is_translated) {
                    $is_translated = get_post_meta($post->ID, '_chroma_es_title', true);
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
                                } elseif ($post->post_type === 'team_member') {
                                    $has_content = get_post_meta($post->ID, '_chroma_es_team_member_title', true);
                                }
                                
                                // Front Page Special Check
                                if (!$has_content && (int)$post->ID === (int)get_option('page_on_front')) {
                                    $has_content = get_post_meta($post->ID, '_chroma_es_home_hero_heading', true);
                                }

                                // Fallback
                                if (!$has_content) {
                                    $has_content = get_post_meta($post->ID, '_chroma_es_title', true);
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
                                <?php 
                                    echo esc_html($post->post_title); 
                                    if ((int)$post->ID === (int)get_option('page_on_front')) {
                                        echo ' <span class="badge badge-primary" style="background:#007cba; color:#fff; padding:2px 6px; border-radius:4px; font-size:10px; vertical-align:middle; margin-left:5px;">HOME PAGE</span>';
                                    }
                                ?>
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
    
        <?php
        // Dynamic Combo Pages Section
        if (class_exists('Chroma_Combo_Page_Generator')) {
            $combos = Chroma_Combo_Page_Generator::get_all_combos();
            if (!empty($combos)) :
        ?>
            <div style="margin-top: 40px; border-top: 2px solid #ddd; padding-top: 20px;">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-admin-links"></span>
                    Dynamic Combo Pages (<?php echo count($combos); ?>)
                    <button type="button" id="chroma-bulk-translate-combos" class="button button-secondary button-small" style="margin-left: 10px;">
                        <span class="dashicons dashicons-translation" style="line-height: 26px;"></span> Translate All Combos
                    </button>
                    <span id="bulk-combo-status" style="margin-left: 10px; font-weight: normal; font-size: 13px;"></span>
                </h2>
                <p class="description">These pages are generated dynamically based on City + Program combinations. Translations are inherited from their respective City and Program templates.</p>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Combo Title</th>
                                <th>English URL</th>
                                <th>Spanish URL</th>
                                <th>Spanish URL</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($combos as $combo): 
                                $en_url = $combo['url'];
                                $es_url = str_replace(home_url('/'), home_url('/es/'), $en_url);
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($combo['program']->post_title . ' in ' . $combo['city']); ?></strong></td>
                                    <td><a href="<?php echo esc_url($en_url); ?>" target="_blank">View EN</a></td>
                                    <td><a href="<?php echo esc_url($es_url); ?>" target="_blank">View ES</a></td>
                                    <td style="text-align: center;">
                                        <button type="button" class="button chroma-combo-translate" 
                                            data-program="<?php echo esc_attr($combo['program']->post_name); ?>"
                                            data-city="<?php echo esc_attr($combo['city']); ?>"
                                            data-state="<?php echo esc_attr($combo['state']); ?>"
                                            title="AI Translate Only (Inherited Content)"
                                        >
                                            <span class="dashicons dashicons-translation" style="line-height: 28px;"></span> AI Translate
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php 
            endif;
        } 
        ?>
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
                    if (typeof response === 'string' && response.trim() === '-1') {
                         console.error('AJAX Nonce Failure');
                         if (callback) callback(false, 'Session Expired (Nonce)');
                         return;
                    }
                    if (callback) callback(response.success, (response.data && response.data.message) ? response.data.message : 'Invalid Resp: ' + JSON.stringify(response));
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Fail', xhr, status, error);
                    if (callback) callback(false, 'Net Err: ' + status + ' ' + error);
                });
            }
        });

        // Combo Page Translate Handler
        $(document).on('click', '.chroma-combo-translate', function() {
            var btn = $(this);
            var program = btn.data('program');
            var city = btn.data('city');
            var state = btn.data('state');

            if (!confirm('Translate this Combo Page content (Intro, Neighborhoods) to Spanish using AI?')) return;

            btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

            $.post(ajaxurl, {
                action: 'chroma_combo_ai_translate', // Utilizes the Theme's handler
                nonce: '<?php echo wp_create_nonce('chroma_combo_ai'); ?>', // Must match the theme's expected nonce
                program_slug: program,
                city_slug: city,
                state: state
            }, function(response) {
                if (response.success) {
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="color:green"></span> done');
                    btn.closest('tr').css('background-color', '#e6fffa');
                } else {
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-warning" style="color:red"></span> retry');
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                btn.prop('disabled', false).text('Error');
                alert('Network Error');
            });
        });
        // Bulk Combo Translate
        $('#chroma-bulk-translate-combos').click(function() {
            var $allBtns = $('.chroma-combo-translate:not(:disabled)');
            if ($allBtns.length === 0) {
                alert('No untranslated combo pages found (or all are disabled).');
                return;
            }
            
            if (!confirm('This will sequentially translate ' + $allBtns.length + ' combo pages. Continue?')) return;
            
            var $mainBtn = $(this);
            $mainBtn.prop('disabled', true);
            
            var total = $allBtns.length;
            var current = 0;
            
            function processNextCombo() {
                if (current >= total) {
                    $('#bulk-combo-status').text('All Done!').css('color', 'green');
                    $mainBtn.prop('disabled', false);
                    alert('Batch translation complete.');
                    return;
                }
                
                $('#bulk-combo-status').text('Processing ' + (current + 1) + '/' + total + '...');
                
                var $btn = $allBtns.eq(current);
                var program = $btn.data('program');
                var city = $btn.data('city');
                var state = $btn.data('state');
                
                $btn.prop('disabled', true).html('⏳');
                
                $.post(ajaxurl, {
                    action: 'chroma_combo_ai_translate',
                    nonce: '<?php echo wp_create_nonce('chroma_combo_ai'); ?>',
                    program_slug: program,
                    city_slug: city,
                    state: state
                }, function(response) {
                    if (response.success) {
                        $btn.html('<span class="dashicons dashicons-yes" style="color:green"></span>');
                        $btn.closest('tr').css('background-color', '#e6fffa');
                    } else {
                        $btn.html('<span class="dashicons dashicons-warning" style="color:red"></span>');
                        console.error('Translation failed for ' + program + '/' + city, response);
                    }
                    // Next
                    current++;
                    processNextCombo();
                }).fail(function() {
                    $btn.text('Err');
                    current++;
                    processNextCombo();
                });
            }
            
            processNextCombo();
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



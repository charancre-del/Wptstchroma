<?php
/**
 * LLM Admin Settings Page
 * Unified settings for all LLM and SEO features
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLM_Admin_Settings
{
    const MENU_SLUG = 'chroma-llm-settings';
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page'], 15); // Priority 15 - register submenus after parent
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Add admin menu pages as subpages under SEO & LLM
     */
    public function add_menu_page() {
        // Add as submenu under SEO & LLM dashboard
        add_submenu_page(
            'chroma-seo-dashboard',     // Parent slug
            'LLM Settings',
            'LLM Settings',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'chroma-seo-dashboard',
            'LLM Usage',
            'LLM Usage',
            'manage_options',
            'chroma-llm-usage',
            [$this, 'render_usage_page']
        );
        
        add_submenu_page(
            'chroma-seo-dashboard',
            'Bulk Operations',
            'Bulk Operations',
            'manage_options',
            'chroma-llm-bulk',
            [$this, 'render_bulk_page']
        );
        
        add_submenu_page(
            'chroma-seo-dashboard',
            'Review Queue',
            'Review Queue',
            'manage_options',
            'chroma-llm-review',
            [$this, 'render_review_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings
        register_setting('chroma_llm_settings', 'chroma_openai_api_key');
        register_setting('chroma_llm_settings', 'chroma_google_places_api_key');
        register_setting('chroma_llm_settings', 'chroma_llm_model');
        register_setting('chroma_llm_settings', 'chroma_llm_base_url');
        register_setting('chroma_llm_settings', 'chroma_llm_rate_limit');
        register_setting('chroma_llm_settings', 'chroma_llm_rate_limit');
        register_setting('chroma_llm_settings', 'chroma_llm_cache_duration');
        
        // Organization Settings (migrated from Theme Mods)
        register_setting('chroma_llm_settings', 'chroma_seo_phone');
        register_setting('chroma_llm_settings', 'chroma_seo_email');

        // Schema Conflict Settings
        register_setting('chroma_llm_settings', 'chroma_faq_schema_disabled', [
            'type' => 'string',
            'sanitize_callback' => function($input) {
                return ($input === 'yes') ? 'yes' : 'no';
            },
            'default' => 'no'
        ]);
        register_setting('chroma_llm_settings', 'chroma_breadcrumbs_schema_disabled', [
            'type' => 'string',
            'sanitize_callback' => function($input) {
                return ($input === 'yes') ? 'yes' : 'no';
            },
            'default' => 'no'
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'chroma-llm') === false) return;
        
        wp_enqueue_style('chroma-llm-admin', CHROMA_SEO_URL . 'assets/css/admin-llm.css', [], '1.0.0');
        wp_enqueue_script('chroma-llm-admin', CHROMA_SEO_URL . 'assets/js/admin-llm.js', ['jquery'], '1.0.0', true);
        
        wp_localize_script('chroma-llm-admin', 'chromaLLM', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chroma_seo_nonce')
        ]);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>🤖 LLM & Schema Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('chroma_llm_settings'); ?>
                
                <h2>API Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th>Gemini API Key</th>
                        <td>
                            <input type="password" name="chroma_openai_api_key" 
                                value="<?php echo esc_attr(get_option('chroma_openai_api_key')); ?>" 
                                class="regular-text" placeholder="AIza...">
                            <p class="description">Required for AI schema generation (uses Gemini API)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Google Places API Key</th>
                        <td>
                            <input type="password" name="chroma_google_places_api_key" 
                                value="<?php echo esc_attr(get_option('chroma_google_places_api_key')); ?>" 
                                class="regular-text">
                            <p class="description">Optional. Enables syncing reviews/hours from GMB</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Model</th>
                        <td>
                            <select name="chroma_llm_model" id="chroma_llm_model">
                                <?php 
                                $current = get_option('chroma_llm_model', 'gemini-2.0-flash-exp');
                                $cached_models = get_option('chroma_llm_available_models', []);
                                
                                // Default models if none fetched yet
                                if (empty($cached_models)) {
                                    $cached_models = [
                                        'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)',
                                        'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast)',
                                        'gemini-1.5-pro' => 'Gemini 1.5 Pro (Best Quality)',
                                        'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B (Cheapest)',
                                    ];
                                }
                                foreach ($cached_models as $m => $label): ?>
                                    <option value="<?php echo esc_attr($m); ?>" <?php selected($current, $m); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="chroma-fetch-models" class="button button-secondary">
                                <span class="dashicons dashicons-update" style="line-height: 28px;"></span>
                                Fetch Available Models
                            </button>
                            <span id="chroma-fetch-models-status" style="margin-left: 10px;"></span>
                            <p class="description">Click "Fetch" to load models from your Gemini API (requires valid API key)</p>
                        </td>
                    </tr>

                    <tr>
                        <th>Base URL</th>
                        <td>
                            <input type="text" name="chroma_llm_base_url" 
                                value="<?php echo esc_attr(get_option('chroma_llm_base_url', 'https://generativelanguage.googleapis.com/v1beta')); ?>" 
                                class="regular-text">
                            <p class="description">Default: https://generativelanguage.googleapis.com/v1beta</p>
                        </td>
                    </tr>
                </table>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#chroma-fetch-models').on('click', function() {
                        var $btn = $(this);
                        var $status = $('#chroma-fetch-models-status');
                        var $select = $('#chroma_llm_model');
                        
                        $btn.prop('disabled', true);
                        $status.text('Fetching...').css('color', '');
                        
                        $.post(ajaxurl, {
                            action: 'chroma_fetch_available_models',
                            nonce: '<?php echo wp_create_nonce('chroma_fetch_models'); ?>'
                        }, function(response) {
                            $btn.prop('disabled', false);
                            
                            if (response.success && response.data.models) {
                                var currentVal = $select.val();
                                $select.empty();
                                
                                $.each(response.data.models, function(key, label) {
                                    $select.append($('<option>', {
                                        value: key,
                                        text: label,
                                        selected: (key === currentVal)
                                    }));
                                });
                                
                                $status.text('✓ Found ' + Object.keys(response.data.models).length + ' models').css('color', 'green');
                            } else {
                                $status.text('✗ ' + (response.data.message || 'Failed to fetch')).css('color', 'red');
                            }
                        }).fail(function() {
                            $btn.prop('disabled', false);
                            $status.text('✗ Request failed').css('color', 'red');
                        });
                    });
                });
                </script>

                </table>

                <h2>Organization Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Main Phone Number</th>
                        <td>
                            <input type="text" name="chroma_seo_phone" 
                                value="<?php echo esc_attr(get_option('chroma_seo_phone')); ?>" 
                                class="regular-text" placeholder="(555) 123-4567">
                        </td>
                    </tr>
                    <tr>
                        <th>Main Email Address</th>
                        <td>
                            <input type="email" name="chroma_seo_email" 
                                value="<?php echo esc_attr(get_option('chroma_seo_email')); ?>" 
                                class="regular-text" placeholder="info@example.com">
                        </td>
                    </tr>
                </table>
                
                <h2>Rate Limiting & Caching</h2>
                <table class="form-table">
                    <tr>
                        <th>Rate Limit (per minute)</th>
                        <td>
                            <input type="number" name="chroma_llm_rate_limit" 
                                value="<?php echo esc_attr(get_option('chroma_llm_rate_limit', 60)); ?>" 
                                class="small-text" min="1" max="1000">
                        </td>
                    </tr>
                    <tr>
                        <th>Cache Duration</th>
                        <td>
                            <select name="chroma_llm_cache_duration">
                                <?php 
                                $current = get_option('chroma_llm_cache_duration', DAY_IN_SECONDS);
                                $options = [
                                    HOUR_IN_SECONDS => '1 Hour',
                                    12 * HOUR_IN_SECONDS => '12 Hours',
                                    DAY_IN_SECONDS => '24 Hours',
                                    WEEK_IN_SECONDS => '1 Week'
                                ];
                                foreach ($options as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php selected($current, $val); ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2>Schema Conflicts & Compatibility</h2>
                <table class="form-table">
                    <tr>
                        <th>Disable Chroma FAQ Schema</th>
                        <td>
                            <label>
                                <input type="hidden" name="chroma_faq_schema_disabled" value="no">
                                <input type="checkbox" name="chroma_faq_schema_disabled" value="yes" 
                                    <?php checked(get_option('chroma_faq_schema_disabled', 'no'), 'yes'); ?>>
                                Disable built-in FAQ Schema (Recommended if using Otto or other FAQ plugins)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Disable Chroma Breadcrumbs</th>
                        <td>
                            <label>
                                <input type="hidden" name="chroma_breadcrumbs_schema_disabled" value="no">
                                <input type="checkbox" name="chroma_breadcrumbs_schema_disabled" value="yes" 
                                    <?php checked(get_option('chroma_breadcrumbs_schema_disabled', 'no'), 'yes'); ?>>
                                Disable built-in Breadcrumb Schema (Recommended if using Yoast/RankMath)
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render usage dashboard
     */
    public function render_usage_page() {
        $stats = Chroma_LLM_Client::get_usage_stats();
        $model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $cost = Chroma_LLM_Client::estimate_cost($stats['total_tokens'], $model);
        ?>
        <div class="wrap">
            <h1>📊 Usage Dashboard</h1>
            
            <div class="chroma-usage-grid">
                <div class="chroma-stat-card">
                    <h3>This Month</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_tokens']); ?></div>
                    <div class="stat-label">Total Tokens</div>
                </div>
                
                <div class="chroma-stat-card">
                    <h3>Requests</h3>
                    <div class="stat-value"><?php echo number_format($stats['requests']); ?></div>
                    <div class="stat-label">API Calls</div>
                </div>
                
                <div class="chroma-stat-card">
                    <h3>Est. Cost</h3>
                    <div class="stat-value">$<?php echo number_format($cost, 4); ?></div>
                    <div class="stat-label">Based on <?php echo esc_html($model); ?></div>
                </div>
            </div>
            
            <h2>Usage by Post Type</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Post Type</th>
                        <th>Tokens</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['by_post_type'] ?? [] as $type => $tokens): ?>
                    <tr>
                        <td><?php echo esc_html($type); ?></td>
                        <td><?php echo number_format($tokens); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>Daily Usage</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tokens</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $days = array_slice($stats['by_day'] ?? [], -7, null, true);
                    foreach (array_reverse($days, true) as $day => $tokens): ?>
                    <tr>
                        <td><?php echo esc_html($day); ?></td>
                        <td><?php echo number_format($tokens); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render bulk operations page
     */
    public function render_bulk_page() {
        $status = Chroma_LLM_Bulk_Processor::get_status();
        $gaps = Chroma_LLM_Bulk_Processor::detect_gaps();
        ?>
        <div class="wrap">
            <h1>⚡ Bulk Operations</h1>
            
            <?php if ($status['in_progress']): ?>
            <div class="notice notice-info">
                <p>
                    <strong>Processing:</strong> 
                    <?php echo $status['completed']; ?> / <?php echo $status['total']; ?> completed
                    (<?php echo $status['failed']; ?> failed)
                    <button class="button" id="chroma-cancel-bulk">Cancel</button>
                </p>
            </div>
            <?php endif; ?>
            
            <h2>Content Gaps (<?php echo count($gaps); ?> posts)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-gaps"></th>
                        <th>Post</th>
                        <th>Type</th>
                        <th>Missing</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gaps as $post_id => $gap): ?>
                    <tr>
                        <td><input type="checkbox" class="gap-checkbox" value="<?php echo $post_id; ?>"></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank">
                                <?php echo esc_html($gap['title']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($gap['post_type']); ?></td>
                        <td><?php echo esc_html(implode(', ', $gap['missing'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <button class="button button-primary" id="chroma-generate-selected">
                    Generate Schema for Selected
                </button>
                <button class="button" id="chroma-sync-gmb-selected">
                    Sync GMB Data for Selected
                </button>
            </p>
        </div>
        
        <script>
        jQuery(function($) {
            $('#select-all-gaps').on('change', function() {
                $('.gap-checkbox').prop('checked', $(this).is(':checked'));
            });
            
            $('#chroma-generate-selected').on('click', function() {
                var selected = $('.gap-checkbox:checked').map(function() { 
                    return $(this).val(); 
                }).get();
                
                if (!selected.length) {
                    alert('Select at least one post');
                    return;
                }
                
                $.post(chromaLLM.ajaxUrl, {
                    action: 'chroma_bulk_generate_start',
                    nonce: chromaLLM.nonce,
                    post_ids: selected,
                    type: 'schema'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
            
            // GMB Sync for selected posts
            $('#chroma-sync-gmb-selected').on('click', function() {
                var selected = $('.gap-checkbox:checked').map(function() { 
                    return $(this).val(); 
                }).get();
                
                if (!selected.length) {
                    alert('Select at least one post');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Syncing...');
                
                var completed = 0;
                var errors = [];
                var total = selected.length;
                
                // Process each post sequentially
                function syncNext(index) {
                    if (index >= selected.length) {
                        $btn.prop('disabled', false).text('Sync GMB Data for Selected');
                        if (errors.length) {
                            alert('Completed: ' + completed + '/' + total + '\nErrors: ' + errors.join(', '));
                        } else {
                            alert('Successfully synced GMB data for ' + completed + ' posts!');
                        }
                        location.reload();
                        return;
                    }
                    
                    $.post(chromaLLM.ajaxUrl, {
                        action: 'chroma_sync_gmb_data',
                        nonce: chromaLLM.nonce,
                        post_id: selected[index]
                    }, function(response) {
                        if (response.success) {
                            completed++;
                        } else {
                            errors.push(selected[index] + ': ' + response.data.message);
                        }
                        $btn.text('Syncing... (' + (index + 1) + '/' + total + ')');
                        syncNext(index + 1);
                    }).fail(function() {
                        errors.push(selected[index] + ': Request failed');
                        syncNext(index + 1);
                    });
                }
                
                syncNext(0);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render review queue page
     */
    public function render_review_page() {
        $pending = Chroma_Schema_Review_Queue::get_pending();
        ?>
        <div class="wrap">
            <h1>👁️ Review Queue (<?php echo count($pending); ?>)</h1>
            
            <?php if (empty($pending)): ?>
            <p>No items pending review. 🎉</p>
            <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Reason</th>
                        <th>Confidence</th>
                        <th>Flagged</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $item): ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link($item['post_id']); ?>" target="_blank">
                                <?php echo esc_html($item['title']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($item['reason']); ?></td>
                        <td><?php echo round($item['confidence'] * 100); ?>%</td>
                        <td><?php echo esc_html($item['flagged_at']); ?></td>
                        <td>
                            <button class="button approve-btn" data-post="<?php echo $item['post_id']; ?>">
                                Approve
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <script>
            jQuery('.approve-btn').on('click', function() {
                var postId = jQuery(this).data('post');
                jQuery.post(chromaLLM.ajaxUrl, {
                    action: 'chroma_review_schema',
                    nonce: chromaLLM.nonce,
                    post_id: postId,
                    review_action: 'approve'
                }, function() {
                    location.reload();
                });
            });
            </script>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize
new Chroma_LLM_Admin_Settings();



<?php
/**
 * Link Equity Analyzer
 * Analyzes internal link structure and provides recommendations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Link_Equity_Analyzer
{
    public function __construct() {
        add_action('admin_menu', [$this, 'add_dashboard_page'], 20);
    }
    
    /**
     * Analyze all pages
     */
    public static function analyze() {
        $posts = get_posts([
            'post_type' => ['post', 'page', 'location', 'program'],
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $results = [];
        $site_url = home_url();
        
        foreach ($posts as $post) {
            $url = get_permalink($post);
            $content = $post->post_content;
            
            // Count outgoing internal links
            preg_match_all('/<a[^>]+href=["\'](' . preg_quote($site_url, '/') . '[^"\']*)["\'][^>]*>/i', $content, $matches);
            $outgoing = count($matches[1] ?? []);
            
            $results[$post->ID] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => $url,
                'type' => $post->post_type,
                'outgoing' => $outgoing,
                'incoming' => 0 // Will be calculated
            ];
        }
        
        // Count incoming links
        foreach ($posts as $post) {
            $url = get_permalink($post);
            
            foreach ($posts as $other_post) {
                if ($other_post->ID === $post->ID) continue;
                
                if (strpos($other_post->post_content, $url) !== false) {
                    $results[$post->ID]['incoming']++;
                }
            }
        }
        
        // Calculate score (simple PageRank-like)
        foreach ($results as &$r) {
            $r['score'] = ($r['incoming'] * 2) + ($r['outgoing'] * 0.5);
        }
        
        // Sort by score
        uasort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $results;
    }
    
    /**
     * Get orphan pages (0 incoming links)
     */
    public static function get_orphans() {
        $analysis = self::analyze();
        
        return array_filter($analysis, function($r) {
            return $r['incoming'] === 0;
        });
    }
    
    /**
     * Get recommendations with detailed action items
     */
    public static function get_recommendations() {
        $analysis = self::analyze();
        $recommendations = [];
        
        // Get all pages for matching
        $all_pages = get_posts([
            'post_type' => ['post', 'page', 'location', 'program'],
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        // Find orphans
        $orphans = array_filter($analysis, fn($r) => $r['incoming'] === 0);
        foreach ($orphans as $orphan) {
            $suggested = self::find_suggested_sources($orphan, $all_pages);
            
            $recommendations[] = [
                'type' => 'orphan',
                'post_id' => $orphan['id'],
                'title' => $orphan['title'],
                'post_type' => $orphan['type'],
                'url' => $orphan['url'],
                'message' => 'This page has no internal links pointing to it.',
                'suggested_sources' => $suggested,
                'suggested_anchor' => $orphan['title'],
                'ai_can_fix' => !empty($suggested)
            ];
        }
        
        // Find pages with too many outgoing links
        $heavy = array_filter($analysis, fn($r) => $r['outgoing'] > 20);
        foreach ($heavy as $h) {
            $recommendations[] = [
                'type' => 'too_many_links',
                'post_id' => $h['id'],
                'title' => $h['title'],
                'post_type' => $h['type'],
                'url' => $h['url'],
                'message' => 'This page has ' . $h['outgoing'] . ' outgoing links. Consider consolidating.',
                'suggested_sources' => [],
                'ai_can_fix' => false
            ];
        }
        
        // Find low-score important pages
        $important_low = array_filter($analysis, function($r) {
            return in_array($r['type'], ['location', 'program']) && $r['incoming'] < 3 && $r['incoming'] > 0;
        });
        foreach ($important_low as $il) {
            $suggested = self::find_suggested_sources($il, $all_pages);
            
            $recommendations[] = [
                'type' => 'needs_links',
                'post_id' => $il['id'],
                'title' => $il['title'],
                'post_type' => $il['type'],
                'url' => $il['url'],
                'message' => 'This ' . $il['type'] . ' only has ' . $il['incoming'] . ' incoming links.',
                'suggested_sources' => $suggested,
                'suggested_anchor' => $il['title'],
                'ai_can_fix' => !empty($suggested)
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Find pages that should link to target page
     */
    private static function find_suggested_sources($target, $all_pages) {
        $suggestions = [];
        $target_title = strtolower($target['title']);
        $target_words = array_filter(explode(' ', $target_title), fn($w) => strlen($w) > 3);
        
        foreach ($all_pages as $page) {
            if ($page->ID == $target['id']) continue;
            
            $content = strtolower($page->post_content . ' ' . $page->post_title);
            $relevance = 0;
            
            // Check for keyword matches
            foreach ($target_words as $word) {
                if (strpos($content, $word) !== false) {
                    $relevance += 0.2;
                }
            }
            
            // Boost if same type or related types
            if ($page->post_type === 'post') {
                $relevance += 0.3; // Blog posts are good link sources
            }
            if ($page->post_type === $target['type']) {
                $relevance += 0.1;
            }
            
            // Boost high-score pages
            if (isset($GLOBALS['chroma_link_scores'][$page->ID])) {
                $relevance += min(0.2, $GLOBALS['chroma_link_scores'][$page->ID] / 50);
            }
            
            if ($relevance >= 0.3) {
                $suggestions[] = [
                    'id' => $page->ID,
                    'title' => $page->post_title,
                    'type' => $page->post_type,
                    'edit_url' => get_edit_post_link($page->ID, 'raw'),
                    'relevance' => min(1, $relevance)
                ];
            }
        }
        
        // Sort by relevance
        usort($suggestions, fn($a, $b) => $b['relevance'] <=> $a['relevance']);
        
        return array_slice($suggestions, 0, 5);
    }
    
    /**
     * Add dashboard page
     */
    public function add_dashboard_page() {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Link Equity',
            'Link Equity',
            'manage_options',
            'chroma-link-equity',
            [$this, 'render_dashboard']
        );
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $analysis = self::analyze();
        $recommendations = self::get_recommendations();
        $orphans = self::get_orphans();
        $nonce = wp_create_nonce('chroma_link_equity');
        ?>
        <div class="wrap">
            <h1>🔗 Link Equity Analysis</h1>
            
            <div class="equity-stats">
                <div class="stat-box">
                    <h3><?php echo count($analysis); ?></h3>
                    <p>Total Pages</p>
                </div>
                <div class="stat-box warning">
                    <h3><?php echo count($orphans); ?></h3>
                    <p>Orphan Pages</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo count(array_filter($recommendations, fn($r) => $r['ai_can_fix'] ?? false)); ?></h3>
                    <p>AI Fixable</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo count($recommendations); ?></h3>
                    <p>Recommendations</p>
                </div>
            </div>
            
            <?php if (!empty($recommendations)): ?>
            <h2>Recommendations</h2>
            <table class="wp-list-table widefat fixed striped" id="recommendations-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Page</th>
                        <th style="width: 20%;">Issue</th>
                        <th style="width: 35%;">Suggested Link Sources</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recommendations, 0, 30) as $rec): ?>
                    <tr data-post-id="<?php echo $rec['post_id']; ?>" 
                        data-title="<?php echo esc_attr($rec['title']); ?>"
                        data-url="<?php echo esc_attr($rec['url'] ?? ''); ?>">
                        <td>
                            <a href="<?php echo $rec['url'] ?? get_permalink($rec['post_id']); ?>" target="_blank">
                                <strong><?php echo esc_html($rec['title']); ?></strong>
                            </a>
                            <br><small style="color: #666;"><?php echo esc_html($rec['post_type'] ?? ''); ?></small>
                        </td>
                        <td>
                            <span class="issue-type issue-<?php echo $rec['type']; ?>">
                                <?php echo $rec['type'] === 'orphan' ? '⚠️ Orphan' : ($rec['type'] === 'needs_links' ? '📉 Low Links' : '⚡ Too Many'); ?>
                            </span>
                            <br><small><?php echo esc_html($rec['message']); ?></small>
                        </td>
                        <td>
                            <?php if (!empty($rec['suggested_sources'])): ?>
                                <div class="suggested-sources">
                                    <?php foreach (array_slice($rec['suggested_sources'], 0, 3) as $src): ?>
                                        <div class="source-item" 
                                             data-source-id="<?php echo $src['id']; ?>"
                                             data-source-title="<?php echo esc_attr($src['title']); ?>">
                                            <a href="<?php echo esc_url($src['edit_url']); ?>" target="_blank" title="Edit this page">
                                                <?php echo esc_html(wp_trim_words($src['title'], 5)); ?>
                                            </a>
                                            <span class="relevance" style="color: #00a32a; font-size: 10px;">
                                                <?php echo round($src['relevance'] * 100); ?>%
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">No suggestions</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($rec['post_id']); ?>" class="button button-small">Edit</a>
                            <?php if ($rec['ai_can_fix'] ?? false): ?>
                                <button type="button" class="button button-small ai-fix-btn" 
                                        data-target-id="<?php echo $rec['post_id']; ?>"
                                        data-target-title="<?php echo esc_attr($rec['title']); ?>"
                                        data-target-url="<?php echo esc_attr($rec['url'] ?? ''); ?>"
                                        data-sources='<?php echo esc_attr(json_encode($rec['suggested_sources'] ?? [])); ?>'>
                                    🤖 AI Fix
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <h2>All Pages by Link Score</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Type</th>
                        <th>Incoming</th>
                        <th>Outgoing</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($analysis, 0, 50) as $page): ?>
                    <tr class="<?php echo $page['incoming'] === 0 ? 'orphan-row' : ''; ?>">
                        <td>
                            <a href="<?php echo esc_url($page['url']); ?>" target="_blank">
                                <?php echo esc_html($page['title']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($page['type']); ?></td>
                        <td><?php echo $page['incoming']; ?></td>
                        <td><?php echo $page['outgoing']; ?></td>
                        <td><strong><?php echo round($page['score'], 1); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- AI Fix Preview Modal -->
        <div id="ai-fix-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 100000;">
            <div style="background: #fff; max-width: 700px; margin: 40px auto; padding: 25px; border-radius: 8px; max-height: 85vh; overflow-y: auto;">
                <h2>🤖 AI Link Insertion Preview</h2>
                <p id="ai-fix-description"></p>
                
                <div style="margin: 20px 0;">
                    <label><strong>Source Page:</strong></label>
                    <select id="ai-fix-source" style="width: 100%; padding: 8px; margin-top: 5px;"></select>
                </div>
                
                <div style="margin: 20px 0;">
                    <label><strong>Link to insert:</strong></label>
                    <div id="ai-fix-link-preview" style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin-top: 5px; font-family: monospace;"></div>
                </div>
                
                <div style="margin: 20px 0;">
                    <label><strong>Preview (AI will find natural placement):</strong></label>
                    <div id="ai-fix-preview" style="background: #fffbe6; padding: 15px; border: 1px solid #ffe58f; border-radius: 4px; margin-top: 5px; min-height: 100px;">
                        <em>Click "Generate Preview" to see AI suggestion...</em>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="ai-fix-preview-btn" class="button">Generate Preview</button>
                    <button type="button" id="ai-fix-apply-btn" class="button button-primary" disabled>Apply Changes</button>
                    <button type="button" id="ai-fix-close-btn" class="button">Cancel</button>
                </div>
            </div>
        </div>
        
        <style>
            .equity-stats { display: flex; gap: 20px; margin: 20px 0; }
            .stat-box { background: #fff; border: 1px solid #ccc; padding: 20px; border-radius: 8px; text-align: center; min-width: 120px; }
            .stat-box h3 { font-size: 32px; margin: 0; color: #0073aa; }
            .stat-box.warning h3 { color: #d63638; }
            .stat-box p { margin: 10px 0 0; color: #666; }
            .orphan-row { background: #fff8e5 !important; }
            .suggested-sources { display: flex; flex-direction: column; gap: 4px; }
            .source-item { display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
            .issue-orphan { color: #d63638; }
            .issue-needs_links { color: #dba617; }
            .issue-too_many_links { color: #0073aa; }
        </style>
        
        <script>
        jQuery(function($) {
            var nonce = '<?php echo $nonce; ?>';
            var currentTarget = null;
            var currentSources = [];
            
            // AI Fix button click
            $('.ai-fix-btn').on('click', function() {
                currentTarget = {
                    id: $(this).data('target-id'),
                    title: $(this).data('target-title'),
                    url: $(this).data('target-url')
                };
                currentSources = $(this).data('sources') || [];
                
                $('#ai-fix-description').html('Insert a link to <strong>' + currentTarget.title + '</strong> into one of these source pages:');
                $('#ai-fix-link-preview').html('<a href="' + currentTarget.url + '">' + currentTarget.title + '</a>');
                
                // Populate source dropdown
                var $select = $('#ai-fix-source').empty();
                currentSources.forEach(function(src) {
                    $select.append('<option value="' + src.id + '">' + src.title + ' (' + Math.round(src.relevance * 100) + '% match)</option>');
                });
                
                $('#ai-fix-preview').html('<em>Click "Generate Preview" to see AI suggestion...</em>');
                $('#ai-fix-apply-btn').prop('disabled', true);
                $('#ai-fix-modal').fadeIn(200);
            });
            
            // Close modal
            $('#ai-fix-close-btn, #ai-fix-modal').on('click', function(e) {
                if (e.target === this) $('#ai-fix-modal').fadeOut(200);
            });
            
            // Generate preview
            $('#ai-fix-preview-btn').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Generating...');
                var sourceId = $('#ai-fix-source').val();
                
                $.post(ajaxurl, {
                    action: 'chroma_link_equity_ai_preview',
                    nonce: nonce,
                    source_id: sourceId,
                    target_id: currentTarget.id,
                    target_title: currentTarget.title,
                    target_url: currentTarget.url
                }, function(response) {
                    $btn.prop('disabled', false).text('Generate Preview');
                    if (response.success) {
                        $('#ai-fix-preview').html(response.data.preview);
                        $('#ai-fix-apply-btn').prop('disabled', false);
                    } else {
                        $('#ai-fix-preview').html('<span style="color: red;">Error: ' + response.data + '</span>');
                    }
                });
            });
            
            // Apply changes
            $('#ai-fix-apply-btn').on('click', function() {
                if (!confirm('This will modify the source page content. Continue?')) return;
                
                var $btn = $(this).prop('disabled', true).text('Applying...');
                var sourceId = $('#ai-fix-source').val();
                
                $.post(ajaxurl, {
                    action: 'chroma_link_equity_ai_apply',
                    nonce: nonce,
                    source_id: sourceId,
                    target_id: currentTarget.id,
                    target_title: currentTarget.title,
                    target_url: currentTarget.url
                }, function(response) {
                    $btn.text('Apply Changes');
                    if (response.success) {
                        alert('Link inserted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// AJAX handlers for AI fix
add_action('wp_ajax_chroma_link_equity_ai_preview', function() {
    check_ajax_referer('chroma_link_equity', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }
    
    global $chroma_llm_client;
    if (!$chroma_llm_client) {
        wp_send_json_error('LLM client not available');
    }
    
    $source_id = intval($_POST['source_id']);
    $target_title = sanitize_text_field($_POST['target_title']);
    $target_url = esc_url($_POST['target_url']);
    
    $source = get_post($source_id);
    if (!$source) {
        wp_send_json_error('Source post not found');
    }
    
    // Use LLM to find best insertion point
    $prompt = "You are editing a webpage to add an internal link. 

SOURCE PAGE CONTENT (first 1500 chars):
" . wp_trim_words(strip_tags($source->post_content), 250) . "

LINK TO INSERT:
<a href=\"{$target_url}\">{$target_title}</a>

Find a natural context to introduce this link. Write a short 1-2 sentence \"Related Reading\" paragraph that could be appended to the end of the article. Return ONLY the HTML paragraph (e.g. <p>Related Reading: ...</p>), no explanation.";

    $response = $chroma_llm_client->make_request([
        'model' => get_option('chroma_llm_model', 'gpt-4o-mini'),
        'messages' => [
            ['role' => 'system', 'content' => 'You are a content editor. Insert links naturally into existing content.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }
    
    $preview = $response['choices'][0]['message']['content'] ?? '';
    
    wp_send_json_success(['preview' => wp_kses_post($preview)]);
});

add_action('wp_ajax_chroma_link_equity_ai_apply', function() {
    check_ajax_referer('chroma_link_equity', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }
    
    $source_id = intval($_POST['source_id']);
    $target_title = sanitize_text_field($_POST['target_title']);
    $target_url = esc_url($_POST['target_url']);
    
    $source = get_post($source_id);
    if (!$source) {
        wp_send_json_error('Source post not found');
    }
    
    // Simple append approach (safer than AI replacement)
    $link = '<a href="' . esc_url($target_url) . '">' . esc_html($target_title) . '</a>';
    $new_content = $source->post_content . "\n\n<p>Related: {$link}</p>";
    
    $result = wp_update_post([
        'ID' => $source_id,
        'post_content' => $new_content
    ]);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(['message' => 'Link inserted']);
});

new Chroma_Link_Equity_Analyzer();



<?php
/**
 * Schema Editor Metabox
 * Rich results preview, GMB sync, version history in post editor
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Schema_Editor_Metabox
{
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_metabox']);
        add_action('save_post', [$this, 'on_save_post'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Register metabox for location post type
     */
    public function register_metabox() {
        add_meta_box(
            'chroma_schema_tools',
            '🔧 Schema Tools',
            [$this, 'render_metabox'],
            ['location', 'program', 'post', 'page'],
            'side',
            'high'
        );
    }
    
    /**
     * Render the metabox
     */
    public function render_metabox($post) {
        wp_nonce_field('chroma_schema_tools', 'chroma_schema_nonce');
        
        $schema = get_post_meta($post->ID, '_chroma_schema_data', true);
        $has_schema = !empty($schema);
        $gmb_synced = get_post_meta($post->ID, '_gmb_last_sync', true);
        $needs_review = get_post_meta($post->ID, '_chroma_needs_review', true);
        $confidence = get_post_meta($post->ID, '_chroma_schema_confidence', true);
        ?>
        
        <div class="chroma-schema-tools">
            <!-- Status -->
            <div class="schema-status">
                <?php if ($has_schema): ?>
                    <span class="status-badge good">✓ Schema exists</span>
                <?php else: ?>
                    <span class="status-badge warning">⚠ No schema</span>
                <?php endif; ?>
                
                <?php if ($needs_review): ?>
                    <span class="status-badge review">👁 Needs review</span>
                <?php endif; ?>
            </div>
            
            <!-- Confidence Score -->
            <?php if ($confidence): ?>
            <div class="confidence-display">
                <label>Confidence:</label>
                <div class="confidence-bar">
                    <div class="fill <?php echo $confidence >= 0.8 ? 'high' : ($confidence >= 0.5 ? 'medium' : 'low'); ?>" 
                         style="width: <?php echo round($confidence * 100); ?>%;"></div>
                </div>
                <span><?php echo round($confidence * 100); ?>%</span>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="schema-actions">
                <button type="button" class="button button-primary" id="chroma-generate-schema" data-post="<?php echo $post->ID; ?>">
                    🤖 Generate Schema
                </button>
                
                <?php if (get_post_type($post) === 'location'): ?>
                <button type="button" class="button" id="chroma-sync-gmb" data-post="<?php echo $post->ID; ?>">
                    📍 Sync from GMB
                </button>
                <?php endif; ?>
            </div>
            
            <!-- GMB Status -->
            <?php if (get_post_type($post) === 'location'): ?>
            <div class="gmb-status">
                <?php if ($gmb_synced): ?>
                    <small>GMB synced: <?php echo human_time_diff(strtotime($gmb_synced)); ?> ago</small>
                <?php else: ?>
                    <small>GMB not synced</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Preview Toggle -->
            <hr>
            <button type="button" class="button" id="chroma-toggle-preview">
                👁 Show SERP Preview
            </button>
            
            <div id="chroma-serp-preview" style="display:none; margin-top:10px;">
                <div class="serp-mock">
                    <div class="serp-title"><?php echo esc_html(get_the_title($post)); ?></div>
                    <div class="serp-url"><?php echo esc_url(get_permalink($post)); ?></div>
                    <div class="serp-description">
                        <?php 
                        $desc = $schema[0]['data']['description'] ?? get_the_excerpt($post);
                        echo esc_html(wp_trim_words($desc, 25)); 
                        ?>
                    </div>
                    <?php 
                    $rating = get_post_meta($post->ID, '_gmb_rating', true);
                    if ($rating): 
                    ?>
                    <div class="serp-rating">
                        ⭐ <?php echo $rating; ?> 
                        (<?php echo get_post_meta($post->ID, '_gmb_review_count', true) ?: '0'; ?> reviews)
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Version History -->
            <?php
            $history = Chroma_Schema_History::get_history($post->ID);
            if (!empty($history)):
            ?>
            <hr>
            <details>
                <summary>📜 Version History (<?php echo count($history); ?>)</summary>
                <ul class="version-list">
                    <?php foreach (array_reverse($history, true) as $i => $version): ?>
                    <li>
                        <?php echo esc_html($version['timestamp']); ?>
                        by <?php echo esc_html($version['user_name'] ?? 'System'); ?>
                        <button type="button" class="button-link restore-version" data-index="<?php echo $i; ?>">
                            Restore
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
            
            <!-- Image Analysis -->
            <?php if (has_post_thumbnail($post)): ?>
            <hr>
            <button type="button" class="button" id="chroma-analyze-image" data-post="<?php echo $post->ID; ?>">
                🖼️ Analyze Featured Image
            </button>
            <div id="image-analysis-result" style="display:none; margin-top:10px; font-size:12px;"></div>
            <?php endif; ?>
        </div>
        
        <style>
            .chroma-schema-tools { font-size: 13px; }
            .chroma-schema-tools .schema-status { margin-bottom: 10px; }
            .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-right: 5px; }
            .status-badge.good { background: #d4edda; color: #155724; }
            .status-badge.warning { background: #fff3cd; color: #856404; }
            .status-badge.review { background: #cce5ff; color: #004085; }
            .schema-actions { display: flex; flex-direction: column; gap: 8px; margin: 10px 0; }
            .schema-actions .button { width: 100%; }
            .gmb-status { color: #666; }
            .confidence-display { display: flex; align-items: center; gap: 8px; margin: 10px 0; }
            .confidence-bar { width: 60px; height: 8px; background: #eee; border-radius: 4px; }
            .confidence-bar .fill { height: 100%; border-radius: 4px; }
            .confidence-bar .fill.high { background: #46b450; }
            .confidence-bar .fill.medium { background: #ffb900; }
            .confidence-bar .fill.low { background: #dc3232; }
            .serp-mock { background: #f9f9f9; padding: 10px; border-radius: 4px; font-family: Arial, sans-serif; }
            .serp-title { color: #1a0dab; font-size: 16px; }
            .serp-url { color: #006621; font-size: 12px; }
            .serp-description { color: #545454; font-size: 13px; line-height: 1.4; margin-top: 4px; }
            .serp-rating { color: #e7711b; font-size: 12px; margin-top: 4px; }
            .version-list { margin: 10px 0; padding-left: 15px; font-size: 11px; }
            .version-list li { margin: 5px 0; }
        </style>
        
        <script>
        jQuery(function($) {
            // Toggle preview
            $('#chroma-toggle-preview').on('click', function() {
                $('#chroma-serp-preview').toggle();
                $(this).text($(this).text().includes('Show') ? '👁 Hide SERP Preview' : '👁 Show SERP Preview');
            });
            
            // Generate schema
            $('#chroma-generate-schema').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Generating...');
                
                $.post(ajaxurl, {
                    action: 'chroma_generate_schema',
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>',
                    post_id: $btn.data('post'),
                    schema_type: 'ChildCare'
                }, function(response) {
                    if (response.success) {
                        $btn.text('✓ Generated!');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        $btn.text('Error').prop('disabled', false);
                        alert(response.data.message || 'Error');
                    }
                });
            });
            
            // Sync GMB
            $('#chroma-sync-gmb').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Syncing...');
                
                $.post(ajaxurl, {
                    action: 'chroma_sync_gmb_data',
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>',
                    post_id: $btn.data('post')
                }, function(response) {
                    if (response.success) {
                        $btn.text('✓ Synced!');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        $btn.text('Error').prop('disabled', false);
                        alert(response.data.message || 'Error');
                    }
                });
            });
            
            // Analyze image
            $('#chroma-analyze-image').on('click', function() {
                var $btn = $(this);
                var $result = $('#image-analysis-result');
                $btn.prop('disabled', true).text('Analyzing...');
                
                var imageUrl = '<?php echo esc_url(get_the_post_thumbnail_url($post, 'large')); ?>';
                
                $.post(ajaxurl, {
                    action: 'chroma_analyze_image',
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>',
                    image_url: imageUrl
                }, function(response) {
                    $btn.prop('disabled', false).text('🖼️ Analyze Featured Image');
                    if (response.success) {
                        $result.html(
                            '<strong>Alt Text:</strong> ' + (response.data.alt_text || 'N/A') + '<br>' +
                            '<strong>Caption:</strong> ' + (response.data.caption || 'N/A')
                        ).show();
                    } else {
                        $result.html('<span style="color:red;">Error: ' + response.data.message + '</span>').show();
                    }
                });
            });
            
            // Restore version
            $('.restore-version').on('click', function() {
                if (!confirm('Restore this version?')) return;
                
                // Would need AJAX handler
                alert('Version restore functionality - would restore version ' + $(this).data('index'));
            });
        });
        </script>
        <?php
    }
    
    /**
     * Auto-save version and calculate confidence on post save
     */
    public function on_save_post($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        // Auto-save version history
        $schema = get_post_meta($post_id, '_chroma_schema_data', true);
        if (!empty($schema)) {
            Chroma_Schema_History::save_version($post_id, $schema);
        }
        
        // Calculate and store confidence
        if (!empty($schema)) {
            $meta = get_post_meta($post_id);
            $source_data = [];
            foreach ($meta as $k => $v) {
                if (strpos($k, '_') !== 0) {
                    $source_data[$k] = $v[0] ?? '';
                }
            }
            
            $generated_data = $schema[0]['data'] ?? [];
            $confidence = Chroma_Field_Confidence::calculate($generated_data, $source_data);
            $overall = Chroma_Field_Confidence::get_overall($confidence);
            
            update_post_meta($post_id, '_chroma_schema_confidence', $overall);
            
            // Auto-flag for review if low confidence
            if ($overall < 0.6) {
                Chroma_Schema_Review_Queue::flag_for_review(
                    $post_id, 
                    'Low confidence: ' . round($overall * 100) . '%',
                    $overall,
                    $generated_data
                );
            }
        }
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        
        // Already have inline styles/scripts in metabox
    }
}

// Initialize
new Chroma_Schema_Editor_Metabox();



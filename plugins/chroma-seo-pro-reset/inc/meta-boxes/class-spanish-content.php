<?php
/**
 * Spanish Content Meta Box
 * Allows manual overrides for Spanish translations of content and meta fields.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Spanish_Content_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get the meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_spanish_content';
    }

    /**
     * Get the meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return __('Spanish Content Overrides', 'chroma-excellence');
    }

    /**
     * Get post types this meta box applies to
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['page', 'post', 'location', 'program'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    public function render_fields($post)
    {
        // Universal Fields
        $this->render_universal_fields($post);

        // Type-Specific Fields
        $post_type = get_post_type($post);
        if ($post_type === 'location') {
            $this->render_location_fields($post);
        } elseif ($post_type === 'program') {
            $this->render_program_fields($post);
        }
        
        // Template-Specific Fields
        $template = get_page_template_slug($post->ID);
        if (empty($template) && (int)$post->ID === (int)get_option('page_on_front')) {
            $template = 'front-page.php';
        }
        if ($template) {
            $this->render_template_fields($post, $template);
        }
        
        // AI Auto-Fill Button
        echo '<div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #ddd;">';
        echo '<button type="button" id="chroma-auto-translate-btn" class="button button-primary button-large">';
        echo '<span class="dashicons dashicons-translation" style="line-height: 28px; margin-right: 5px;"></span> ' . __('Auto-Fill with AI Translation', 'chroma-excellence');
        echo '</button>';
        echo ' <button type="button" id="chroma-diff-preview-btn" class="button button-secondary">';
        echo '<span class="dashicons dashicons-controls-repeat"></span> ' . __('Diff Preview', 'chroma-excellence');
        echo '</button>';
        echo ' <button type="button" id="chroma-rollback-btn" class="button button-secondary">';
        echo '<span class="dashicons dashicons-backup"></span> ' . __('Rollback', 'chroma-excellence');
        echo '</button>';
        echo '<span class="spinner" id="chroma-translate-spinner" style="float: none; margin-left: 10px;"></span>';
        echo '<span id="chroma-translate-status" style="margin-left: 10px; font-weight: bold;"></span>';
        echo '<p class="description" style="margin-top: 5px;">' . __('Automatically translates the current English content and populates the fields above. Review before saving.', 'chroma-excellence') . '</p>';
        echo '</div>';

        // Diff Preview Modal
        ?>
        <div id="chroma-diff-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:99999;">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:30px; border-radius:8px; max-width:900px; width:90%; max-height:80vh; overflow:auto;">
                <h2 style="margin-top:0;"><?php _e('Translation Diff Preview', 'chroma-excellence'); ?></h2>
                <div id="chroma-diff-content" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <h4>English (Original)</h4>
                        <pre id="chroma-diff-en" style="white-space:pre-wrap; background:#f5f5f5; padding:15px; border-radius:4px; max-height:300px; overflow:auto;"></pre>
                    </div>
                    <div>
                        <h4>Spanish (Translation)</h4>
                        <pre id="chroma-diff-es" style="white-space:pre-wrap; background:#e8f5e9; padding:15px; border-radius:4px; max-height:300px; overflow:auto;"></pre>
                    </div>
                </div>
                <div style="margin-top:20px; text-align:right;">
                    <button type="button" id="chroma-diff-close" class="button button-primary"><?php _e('Close', 'chroma-excellence'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Rollback Modal -->
        <div id="chroma-rollback-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:99999;">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:30px; border-radius:8px; max-width:600px; width:90%;">
                <h2 style="margin-top:0;"><?php _e('Translation History', 'chroma-excellence'); ?></h2>
                <div id="chroma-rollback-list"></div>
                <div style="margin-top:20px; text-align:right;">
                    <button type="button" id="chroma-rollback-close" class="button"><?php _e('Close', 'chroma-excellence'); ?></button>
                </div>
            </div>
        </div>
        <?php

        // JS for Translation
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#chroma-auto-translate-btn').click(function(e) {
                e.preventDefault();
                var btn = $(this);
                var spinner = $('#chroma-translate-spinner');
                var status = $('#chroma-translate-status');
                
                if(!confirm('This will overwrite any existing Spanish content in the fields. Continue?')) {
                    return;
                }

                btn.prop('disabled', true);
                spinner.addClass('is-active');
                status.text(' Translating... please wait.');

                // Collect English Fields
                // Note: We need to grab them from the editor if possible, or assume saved post data?
                // Ideally, we translate what's currently on screen or in DB.
                // For simplicity, let's trigger a server-side generation based on Post ID.
 
                $.post(ajaxurl, {
                    action: 'chroma_auto_translate_post',
                    post_id: <?php echo $post->ID; ?>,
                    force: 'true',
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                }, function(response) {
                    btn.prop('disabled', false);
                    spinner.removeClass('is-active');
                    
                    if(response.success) {
                        var data = response.data;
                        status.text(' Done!').css('color', 'green');
                        
                        // Populate Fields
                        if(data._chroma_es_title) $('#_chroma_es_title').val(data._chroma_es_title);
                        if(data._chroma_es_content) $('#_chroma_es_content').val(data._chroma_es_content);
                        if(data._chroma_es_excerpt) $('#_chroma_es_excerpt').val(data._chroma_es_excerpt);
                        
                        // Location
                        if(data._chroma_es_location_city) $('#_chroma_es_location_city').val(data._chroma_es_location_city);
                        if(data._chroma_es_location_address) $('#_chroma_es_location_address').val(data._chroma_es_location_address);
                        if(data._chroma_es_location_hero_subtitle) $('#_chroma_es_location_hero_subtitle').val(data._chroma_es_location_hero_subtitle);
                        if(data._chroma_es_location_tagline) $('#_chroma_es_location_tagline').val(data._chroma_es_location_tagline);
                        if(data._chroma_es_location_description) $('#_chroma_es_location_description').val(data._chroma_es_location_description);
                        if(data._chroma_es_location_ages_served) $('#_chroma_es_location_ages_served').val(data._chroma_es_location_ages_served);
                        if(data._chroma_es_location_open_text) $('#_chroma_es_location_open_text').val(data._chroma_es_location_open_text);
                        if(data._chroma_es_location_director_bio) $('#_chroma_es_location_director_bio').val(data._chroma_es_location_director_bio);
                        if(data._chroma_es_location_hero_review_text) $('#_chroma_es_location_hero_review_text').val(data._chroma_es_location_hero_review_text);
                        if(data._chroma_es_location_hero_review_author) $('#_chroma_es_location_hero_review_author').val(data._chroma_es_location_hero_review_author);
                        if(data._chroma_es_location_seo_content_title) $('#_chroma_es_location_seo_content_title').val(data._chroma_es_location_seo_content_title);
                        if(data._chroma_es_location_seo_content_text) $('#_chroma_es_location_seo_content_text').val(data._chroma_es_location_seo_content_text);
                        if(data._chroma_es_location_school_pickups) $('#_chroma_es_location_school_pickups').val(data._chroma_es_location_school_pickups);
                        
                        // Program
                        if(data._chroma_es_program_age_range) $('#_chroma_es_program_age_range').val(data._chroma_es_program_age_range);
                        if(data._chroma_es_program_cta_text) $('#_chroma_es_program_cta_text').val(data._chroma_es_program_cta_text);
                        if(data._chroma_es_program_features) $('#_chroma_es_program_features').val(data._chroma_es_program_features);
                        if(data._chroma_es_program_hero_title) $('#_chroma_es_program_hero_title').val(data._chroma_es_program_hero_title);
                        if(data._chroma_es_program_hero_description) $('#_chroma_es_program_hero_description').val(data._chroma_es_program_hero_description);
                        if(data._chroma_es_program_prism_description) $('#_chroma_es_program_prism_description').val(data._chroma_es_program_prism_description);
                        if(data._chroma_es_program_prism_focus_items) $('#_chroma_es_program_prism_focus_items').val(data._chroma_es_program_prism_focus_items);
                        if(data._chroma_es_program_schedule_title) $('#_chroma_es_program_schedule_title').val(data._chroma_es_program_schedule_title);
                        if(data._chroma_es_program_schedule_items) $('#_chroma_es_program_schedule_items').val(data._chroma_es_program_schedule_items);

                        // Generic Template Fields and others
                        Object.keys(data).forEach(function(key) {
                            if (key.startsWith('_chroma_es_')) {
                                var $field = $('#' + key);
                                if ($field.length) {
                                    $field.val(data[key]);
                                }
                            }
                        });
                        
                    } else {
                        status.text(' Error: ' + (response.data.message || 'Unknown')).css('color', 'red');
                    }
                }).fail(function() {
                     btn.prop('disabled', false);
                     spinner.removeClass('is-active');
                     status.text(' Request Failed').css('color', 'red');
                });
            });

            // DIFF PREVIEW
            $('#chroma-diff-preview-btn').click(function() {
                var enContent = '<?php echo esc_js($post->post_title); ?>\n\n<?php echo esc_js(substr($post->post_content, 0, 500)); ?>...';
                var esContent = $('#_chroma_es_title').val() + '\n\n' + $('#_chroma_es_content').val().substring(0, 500) + '...';
                
                $('#chroma-diff-en').text(enContent);
                $('#chroma-diff-es').text(esContent || '(No Spanish translation yet)');
                $('#chroma-diff-modal').fadeIn(200);
            });

            $('#chroma-diff-close, #chroma-diff-modal').click(function(e) {
                if(e.target === this) $('#chroma-diff-modal').fadeOut(200);
            });

            // ROLLBACK
            $('#chroma-rollback-btn').click(function() {
                $.post(ajaxurl, {
                    action: 'chroma_get_translation_history',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                }, function(response) {
                    if(response.success && response.data.history.length > 0) {
                        var html = '<table class="wp-list-table widefat striped"><thead><tr><th>Date</th><th>Title</th><th></th></tr></thead><tbody>';
                        response.data.history.forEach(function(item, idx) {
                            html += '<tr><td>' + item.date + '</td><td>' + item.title.substring(0,50) + '...</td>';
                            html += '<td><button type="button" class="button chroma-restore-version" data-index="' + idx + '">Restore</button></td></tr>';
                        });
                        html += '</tbody></table>';
                        $('#chroma-rollback-list').html(html);
                    } else {
                        $('#chroma-rollback-list').html('<p>No translation history available.</p>');
                    }
                    $('#chroma-rollback-modal').fadeIn(200);
                });
            });

            $('#chroma-rollback-close, #chroma-rollback-modal').click(function(e) {
                if(e.target === this) $('#chroma-rollback-modal').fadeOut(200);
            });

            $(document).on('click', '.chroma-restore-version', function() {
                var idx = $(this).data('index');
                $.post(ajaxurl, {
                    action: 'chroma_restore_translation',
                    post_id: <?php echo $post->ID; ?>,
                    version_index: idx,
                    nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render universal fields (Title, Content, Excerpt)
     *
     * @param WP_Post $post
     */
    private function render_universal_fields($post)
    {
        $es_title = get_post_meta($post->ID, '_chroma_es_title', true);
        $es_content = get_post_meta($post->ID, '_chroma_es_content', true);
        $es_excerpt = get_post_meta($post->ID, '_chroma_es_excerpt', true);

        echo '<div class="chroma-section-header"><h3>' . __('Core Content', 'chroma-excellence') . '</h3></div>';
        echo '<p class="description">' . __('Provide Spanish translations for the main content areas. If left blank, the system may attempt to auto-translate or fallback to English.', 'chroma-excellence') . '</p>';

        $this->render_text_field([
            'id' => '_chroma_es_title',
            'label' => __('Spanish Page Title', 'chroma-excellence'),
            'value' => $es_title,
            'placeholder' => get_the_title($post),
            'description' => __('Translated title of the page.', 'chroma-excellence'),
        ]);

        // Editor for Content (Simulated with textarea for now, or use wp_editor if possible)
        // Using wp_editor inside a meta box can be tricky with AJAX saves, but let's try a standard textarea first 
        // to match the base class style, or just direct HTML output.
        // Given existing base class, let's use a large textarea.
        $this->render_textarea_field([
            'id' => '_chroma_es_content',
            'label' => __('Spanish Main Content', 'chroma-excellence'),
            'value' => $es_content,
            'rows' => 12,
            'description' => __('Translated main content (HTML supported).', 'chroma-excellence'),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_excerpt',
            'label' => __('Spanish Excerpt/Introduction', 'chroma-excellence'),
            'value' => $es_excerpt,
            'rows' => 4,
            'description' => __('Short summary or intro text.', 'chroma-excellence'),
        ]);

        // SEO-Specific Fields (#20)
        echo '<div class="chroma-section-header" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;"><h3>' . __('Spanish SEO Fields', 'chroma-excellence') . '</h3></div>';
        
        $es_seo_title = get_post_meta($post->ID, '_chroma_es_seo_title', true);
        $es_meta_desc = get_post_meta($post->ID, '_chroma_es_meta_description', true);

        $this->render_text_field([
            'id' => '_chroma_es_seo_title',
            'label' => __('Spanish SEO Title', 'chroma-excellence'),
            'value' => $es_seo_title,
            'placeholder' => $es_title ?: get_the_title($post),
            'description' => __('Custom title for search results (60 chars max).', 'chroma-excellence'),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_meta_description',
            'label' => __('Spanish Meta Description', 'chroma-excellence'),
            'value' => $es_meta_desc,
            'rows' => 3,
            'description' => __('Description for search results (160 chars max).', 'chroma-excellence'),
        ]);
    }

    /**
     * Render specific fields for Locations
     *
     * @param WP_Post $post
     */
    private function render_location_fields($post)
    {
        $city_es = get_post_meta($post->ID, '_chroma_es_location_city', true);
        $address_es = get_post_meta($post->ID, '_chroma_es_location_address', true);
        $subtitle_es = get_post_meta($post->ID, '_chroma_es_location_hero_subtitle', true);
        $ages_es = get_post_meta($post->ID, '_chroma_es_location_ages_served', true);
        $open_text_es = get_post_meta($post->ID, '_chroma_es_location_open_text', true);

        echo '<div class="chroma-section-header" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;"><h3>' . __('Location Details', 'chroma-excellence') . '</h3></div>';

        $this->render_text_field([
            'id' => '_chroma_es_location_city',
            'label' => __('City (Spanish)', 'chroma-excellence'),
            'value' => $city_es,
            'placeholder' => get_post_meta($post->ID, 'location_city', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_location_address',
            'label' => __('Address (Spanish)', 'chroma-excellence'),
            'value' => $address_es,
            'placeholder' => get_post_meta($post->ID, 'location_address', true),
            'description' => __('Only translate if necessary (e.g. "Calle" vs "Street").', 'chroma-excellence'),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_location_hero_subtitle',
            'label' => __('Hero Subtitle', 'chroma-excellence'),
            'value' => $subtitle_es,
            'placeholder' => get_post_meta($post->ID, 'location_hero_subtitle', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_location_ages_served',
            'label' => __('Ages Served', 'chroma-excellence'),
            'value' => $ages_es,
            'placeholder' => get_post_meta($post->ID, 'location_ages_served', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_location_tagline',
            'label' => __('Tagline (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_tagline', true),
            'placeholder' => get_post_meta($post->ID, 'location_tagline', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_location_description',
            'label' => __('Main Description (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_description', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'location_description', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_location_director_bio',
            'label' => __('Director Bio (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_director_bio', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'location_director_bio', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_location_hero_review_text',
            'label' => __('Hero Review Text (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_hero_review_text', true),
            'placeholder' => get_post_meta($post->ID, 'location_hero_review_text', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_location_hero_review_author',
            'label' => __('Hero Review Author (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_hero_review_author', true),
            'placeholder' => get_post_meta($post->ID, 'location_hero_review_author', true),
        ]);

        echo '<div class="chroma-section-header" style="margin-top: 20px; border-top: 1px dotted #ccc;"><h4>' . __('SEO Content Section', 'chroma-excellence') . '</h4></div>';

        $this->render_text_field([
            'id' => '_chroma_es_location_seo_content_title',
            'label' => __('SEO Content Title (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_seo_content_title', true),
            'placeholder' => get_post_meta($post->ID, 'location_seo_content_title', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_location_seo_content_text',
            'label' => __('SEO Content Text (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_seo_content_text', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'location_seo_content_text', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_location_school_pickups',
            'label' => __('School Pickups (One per line)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_location_school_pickups', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'location_school_pickups', true),
        ]);
        
        $this->render_text_field([
            'id' => '_chroma_es_location_open_text',
            'label' => __('Open Text (e.g. "Now Open")', 'chroma-excellence'),
            'value' => $open_text_es,
        ]);
    }

    /**
     * Render specific fields for Programs
     *
     * @param WP_Post $post
     */
    private function render_program_fields($post)
    {
        $age_range_es = get_post_meta($post->ID, '_chroma_es_program_age_range', true);
        $cta_text_es = get_post_meta($post->ID, '_chroma_es_program_cta_text', true);
        $features_es = get_post_meta($post->ID, '_chroma_es_program_features', true);

        echo '<div class="chroma-section-header" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;"><h3>' . __('Program Details', 'chroma-excellence') . '</h3></div>';

        $this->render_text_field([
            'id' => '_chroma_es_program_age_range',
            'label' => __('Age Range', 'chroma-excellence'),
            'value' => $age_range_es,
            'placeholder' => get_post_meta($post->ID, 'program_age_range', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_program_hero_title',
            'label' => __('Hero Title (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_hero_title', true),
            'placeholder' => get_post_meta($post->ID, 'program_hero_title', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_program_hero_description',
            'label' => __('Hero Description (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_hero_description', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'program_hero_description', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_program_prism_title',
            'label' => __('Prismpath Title (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_prism_title', true),
            'placeholder' => get_post_meta($post->ID, 'program_prism_title', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_program_prism_description',
            'label' => __('Prismpath Description (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_prism_description', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'program_prism_description', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_program_prism_focus_items',
            'label' => __('Prism Focus Items (One per line)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_prism_focus_items', true),
            'rows' => 4,
            'placeholder' => get_post_meta($post->ID, 'program_prism_focus_items', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_program_schedule_title',
            'label' => __('Schedule Title (Spanish)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_schedule_title', true),
            'placeholder' => get_post_meta($post->ID, 'program_schedule_title', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_program_schedule_items',
            'label' => __('Schedule Items (Time|Title|Copy, one per line)', 'chroma-excellence'),
            'value' => get_post_meta($post->ID, '_chroma_es_program_schedule_items', true),
            'rows' => 6,
            'placeholder' => get_post_meta($post->ID, 'program_schedule_items', true),
        ]);

        $this->render_text_field([
            'id' => '_chroma_es_program_cta_text',
            'label' => __('CTA Button Text', 'chroma-excellence'),
            'value' => $cta_text_es,
            'placeholder' => get_post_meta($post->ID, 'program_cta_text', true),
        ]);

        $this->render_textarea_field([
            'id' => '_chroma_es_program_features',
            'label' => __('Program Features (One per line)', 'chroma-excellence'),
            'value' => $features_es,
            'rows' => 6,
            'placeholder' => get_post_meta($post->ID, 'program_features', true),
        ]);
    }

    /**
     * Render fields for specific page templates
     */
    private function render_template_fields($post, $template)
    {
        $keys = Chroma_Translation_Engine::get_keys_for_template($template);
        if (empty($keys)) return;

        $template_name = str_replace(['page-', '.php'], '', $template);
        $template_name = ucwords(str_replace('-', ' ', $template_name));

        echo '<div class="chroma-section-header" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;"><h3>' . sprintf(__('%s Template Content', 'chroma-excellence'), $template_name) . '</h3></div>';

        foreach ($keys as $key) {
            $es_key = '_chroma_es_' . $key;
            $value = get_post_meta($post->ID, $es_key, true);
            $placeholder = get_post_meta($post->ID, $key, true);
            $label = ucwords(str_replace(['_', 'careers', 'curriculum', 'about', 'contact', 'home'], [' ', '', '', '', '', ''], $key));

            if (strpos($key, 'desc') !== false || strpos($key, 'text') !== false || strpos($key, 'content') !== false || strpos($key, 'bullet') !== false) {
                $this->render_textarea_field([
                    'id' => $es_key,
                    'label' => $label,
                    'value' => $value,
                    'placeholder' => $placeholder,
                    'rows' => 3
                ]);
            } else {
                $this->render_text_field([
                    'id' => $es_key,
                    'label' => $label,
                    'value' => $value,
                    'placeholder' => $placeholder,
                ]);
            }
        }
    }

    /**
     * Save the meta box fields
     *
     * @param int $post_id Post ID
     */
    public function save_fields($post_id)
    {
        // Universal
        $fields = [
            '_chroma_es_title',
            '_chroma_es_content', // Note: Needs careful sanitization if HTML is allowed
            '_chroma_es_excerpt',
            // Location
            '_chroma_es_location_city',
            '_chroma_es_location_address',
            '_chroma_es_location_hero_subtitle',
            '_chroma_es_location_tagline',
            '_chroma_es_location_description',
            '_chroma_es_location_ages_served',
            '_chroma_es_location_open_text',
            '_chroma_es_location_director_bio',
            '_chroma_es_location_hero_review_text',
            '_chroma_es_location_hero_review_author',
            '_chroma_es_location_seo_content_title',
            '_chroma_es_location_seo_content_text',
            '_chroma_es_location_school_pickups',
            // Program
            '_chroma_es_program_age_range',
            '_chroma_es_program_cta_text',
            '_chroma_es_program_features',
            '_chroma_es_program_hero_title',
            '_chroma_es_program_hero_description',
            '_chroma_es_program_prism_title',
            '_chroma_es_program_prism_description',
            '_chroma_es_program_prism_focus_items',
            '_chroma_es_program_schedule_title',
            '_chroma_es_program_schedule_items',
            // SEO Fields
            '_chroma_es_seo_title',
            '_chroma_es_meta_description',
        ];

        // Template-Specific Keys
        $template = get_page_template_slug($post_id);
        if (empty($template) && (int)$post_id === (int)get_option('page_on_front')) {
            $template = 'front-page.php';
        }
        $template_keys = Chroma_Translation_Engine::get_keys_for_template($template);
        foreach ($template_keys as $tkey) {
            $fields[] = '_chroma_es_' . $tkey;
        }

    foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                // For content fields, we might want wp_kses_post
                if ($field === '_chroma_es_content') {
                    update_post_meta($post_id, $field, wp_kses_post($_POST[$field]));
                } else {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
        }

        // Store version history for rollback
        $this->store_version_history($post_id);
    }

    /**
     * Store translation version history
     */
    private function store_version_history($post_id)
    {
        $current = [
            'date' => current_time('Y-m-d H:i:s'),
            'title' => get_post_meta($post_id, '_chroma_es_title', true),
            'content' => get_post_meta($post_id, '_chroma_es_content', true),
            'excerpt' => get_post_meta($post_id, '_chroma_es_excerpt', true),
        ];

        // Only store if we have content
        if (empty($current['title']) && empty($current['content'])) return;

        $history = get_post_meta($post_id, '_chroma_es_history', true) ?: [];
        
        // Add to front; keep max 10 versions
        array_unshift($history, $current);
        $history = array_slice($history, 0, 10);
        
        update_post_meta($post_id, '_chroma_es_history', $history);
    }

    /**
     * Register AJAX handlers (call from bootstrap or init)
     */
    public static function register_ajax_handlers()
    {
        add_action('wp_ajax_chroma_get_translation_history', [__CLASS__, 'ajax_get_history']);
        add_action('wp_ajax_chroma_restore_translation', [__CLASS__, 'ajax_restore_translation']);
    }

    /**
     * AJAX: Get translation history
     */
    public static function ajax_get_history()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error();

        $post_id = intval($_POST['post_id']);
        $history = get_post_meta($post_id, '_chroma_es_history', true) ?: [];

        wp_send_json_success(['history' => $history]);
    }

    /**
     * AJAX: Restore a translation version
     */
    public static function ajax_restore_translation()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error();

        $post_id = intval($_POST['post_id']);
        $index = intval($_POST['version_index']);
        
        $history = get_post_meta($post_id, '_chroma_es_history', true) ?: [];
        
        if (isset($history[$index])) {
            $version = $history[$index];
            update_post_meta($post_id, '_chroma_es_title', $version['title'] ?? '');
            update_post_meta($post_id, '_chroma_es_content', $version['content'] ?? '');
            update_post_meta($post_id, '_chroma_es_excerpt', $version['excerpt'] ?? '');
            
            wp_send_json_success(['message' => 'Restored']);
        }
        
        wp_send_json_error(['message' => 'Version not found']);
    }
}

// Register AJAX handlers
Chroma_Spanish_Content_Meta_Box::register_ajax_handlers();



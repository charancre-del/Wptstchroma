<?php
/**
 * Location Citation Facts Meta Box
 * Allows editing citable facts for LLM systems
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_Citation_Facts_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    public function get_id()
    {
        return 'chroma_location_citation_facts';
    }

    public function get_title()
    {
        return __('Citation Facts (LLM)', 'chroma-excellence');
    }

    public function get_post_types()
    {
        return ['location'];
    }

    public function render_fields($post)
    {
        // Get current values - stored as serialized array of fact objects
        $facts = get_post_meta($post->ID, 'seo_llm_citation_facts', true) ?: [];

        $fallback_facts = Chroma_Fallback_Resolver::get_citation_facts($post->ID);

        echo '<div style="margin-bottom: 20px;">';
        echo '<p class="description"><strong>Structured facts that AI systems can confidently citeabout this location.</strong></p>';

        if (!empty($fallback_facts) && empty($facts)) {
            echo '<p class="description fallback-notice"><em>Auto-generated facts available: ';
            echo count($fallback_facts) . ' facts from existing data</em></p>';
        }
        echo '</div>';

        // Render fact items
        echo '<div id="citation-facts-container">';

        if (!empty($facts)) {
            foreach ($facts as $index => $fact) {
                $this->render_fact_item($index, $fact);
            }
        } else {
            // Show one empty item
            $this->render_fact_item(0, []);
        }

        echo '</div>';

        echo '<button type="button" class="button" id="add-citation-fact">+ Add Fact</button>';

        // JavaScript for repeater
        ?>
        <script>
            jQuery(document).ready(function ($) {
                var factIndex = <?php echo !empty($facts) ? count($facts) : 1; ?>;

                $('#add-citation-fact').on('click', function (e) {
                    e.preventDefault();
                    var html = `<div class="citation-fact-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                    <div style="margin-bottom: 10px;">
                        <label>Fact Label</label>
                        <input type="text" name="seo_llm_citation_facts[${factIndex}][label]" class="regular-text" placeholder="State Quality Rating" />
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label>Value</label>
                        <input type="text" name="seo_llm_citation_facts[${factIndex}][value]" class="regular-text" placeholder="3-Star Quality Rated" />
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label>Source (optional)</label>
                        <input type="text" name="seo_llm_citation_facts[${factIndex}][source]" class="regular-text" placeholder="Georgia DECAL" />
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label>Context (optional)</label>
                        <textarea name="seo_llm_citation_facts[${factIndex}][context]" rows="2" class="large-text"></textarea>
                    </div>
                    <button type="button" class="button citation-fact-remove">Remove Fact</button>
                </div>`;

                    $('#citation-facts-container').append(html);
                    factIndex++;
                });

                $(document).on('click', '.citation-fact-remove', function () {
                    $(this).closest('.citation-fact-item').remove();
                });
            });
        </script>
        <?php
    }

    private function render_fact_item($index, $fact)
    {
        $label = isset($fact['label']) ? $fact['label'] : '';
        $value = isset($fact['value']) ? $fact['value'] : '';
        $source = isset($fact['source']) ? $fact['source'] : '';
        $context = isset($fact['context']) ? $fact['context'] : '';

        ?>
        <div class="citation-fact-item"
            style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
            <div style="margin-bottom: 10px;">
                <label><?php _e('Fact Label', 'chroma-excellence'); ?></label>
                <input type="text" name="seo_llm_citation_facts[<?php echo $index; ?>][label]"
                    value="<?php echo esc_attr($label); ?>" class="regular-text" placeholder="State Quality Rating" />
            </div>
            <div style="margin-bottom: 10px;">
                <label><?php _e('Value', 'chroma-excellence'); ?></label>
                <input type="text" name="seo_llm_citation_facts[<?php echo $index; ?>][value]"
                    value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="3-Star Quality Rated" />
            </div>
            <div style="margin-bottom: 10px;">
                <label><?php _e('Source (optional)', 'chroma-excellence'); ?></label>
                <input type="text" name="seo_llm_citation_facts[<?php echo $index; ?>][source]"
                    value="<?php echo esc_attr($source); ?>" class="regular-text"
                    placeholder="Georgia DECAL Bright from the Start" />
            </div>
            <div style="margin-bottom: 10px;">
                <label><?php _e('Context (optional)', 'chroma-excellence'); ?></label>
                <textarea name="seo_llm_citation_facts[<?php echo $index; ?>][context]" rows="2"
                    class="large-text"><?php echo esc_textarea($context); ?></textarea>
            </div>
            <button type="button" class="button citation-fact-remove"><?php _e('Remove Fact', 'chroma-excellence'); ?></button>
        </div>
        <?php
    }

    public function save_fields($post_id)
    {
        if (!isset($_POST['seo_llm_citation_facts'])) {
            update_post_meta($post_id, 'seo_llm_citation_facts', []);
            return;
        }

        $facts = $_POST['seo_llm_citation_facts'];
        $sanitized_facts = [];

        foreach ($facts as $fact) {
            // Only save if at least label and value are provided
            if (empty($fact['label']) || empty($fact['value'])) {
                continue;
            }

            $sanitized_facts[] = [
                'label' => Chroma_Field_Sanitizer::sanitize_text($fact['label']),
                'value' => Chroma_Field_Sanitizer::sanitize_text($fact['value']),
                'source' => isset($fact['source']) ? Chroma_Field_Sanitizer::sanitize_text($fact['source']) : '',
                'context' => isset($fact['context']) ? Chroma_Field_Sanitizer::sanitize_textarea($fact['context']) : '',
                'verifiedDate' => date('Y-m-d'),
            ];
        }

        update_post_meta($post_id, 'seo_llm_citation_facts', $sanitized_facts);
    }
}



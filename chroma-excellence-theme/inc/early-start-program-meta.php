<?php
/**
 * Chroma Early Start Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function chroma_is_early_start_template($template)
{
    return in_array($template, ['template-program-early-start.php', 'page-early-start.php'], true);
}

function chroma_get_current_admin_post_id()
{
    if (isset($_GET['post'])) {
        return absint($_GET['post']);
    }

    if (isset($_POST['post_ID'])) {
        return absint($_POST['post_ID']);
    }

    return 0;
}

function chroma_early_start_page_meta_boxes()
{
    $post_id = chroma_get_current_admin_post_id();
    if (!$post_id) {
        return;
    }

    $template = get_post_meta($post_id, '_wp_page_template', true);
    if (!chroma_is_early_start_template($template)) {
        return;
    }

    add_meta_box(
        'chroma-early-start-hero',
        __('Chroma Early Start: Hero', 'chroma-excellence'),
        'chroma_early_start_hero_meta_box_render',
        'program',
        'normal',
        'high'
    );

    add_meta_box(
        'chroma-early-start-synergy',
        __('Chroma Early Start: Therapy + Education', 'chroma-excellence'),
        'chroma_early_start_synergy_meta_box_render',
        'program',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-early-start-services',
        __('Chroma Early Start: Services', 'chroma-excellence'),
        'chroma_early_start_services_meta_box_render',
        'program',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-early-start-cta',
        __('Chroma Early Start: Final CTA', 'chroma-excellence'),
        'chroma_early_start_cta_meta_box_render',
        'program',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'chroma_early_start_page_meta_boxes');

function chroma_early_start_template_note($post)
{
    $template = get_post_meta($post->ID, '_wp_page_template', true);
    echo '<p class="description" style="margin-bottom:16px;">';
    echo esc_html__('These fields are used by the "Early Start" program template.', 'chroma-excellence');
    if (!chroma_is_early_start_template($template)) {
        echo ' ';
        echo esc_html__('This program is not currently using that template.', 'chroma-excellence');
    }
    echo '</p>';
}

function chroma_early_start_render_text($name, $label, $value, $placeholder = '', $class = 'large-text')
{
    ?>
    <tr>
        <th><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <input type="text" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>" class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>" />
        </td>
    </tr>
    <?php
}

function chroma_early_start_render_url($name, $label, $value, $placeholder = '')
{
    ?>
    <tr>
        <th><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <input type="url" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>" class="large-text"
                placeholder="<?php echo esc_attr($placeholder); ?>" />
        </td>
    </tr>
    <?php
}

function chroma_early_start_render_textarea($name, $label, $value, $placeholder = '', $rows = 3)
{
    ?>
    <tr>
        <th><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <textarea id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>"
                rows="<?php echo esc_attr($rows); ?>" class="large-text"
                placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea($value); ?></textarea>
        </td>
    </tr>
    <?php
}

function chroma_early_start_hero_meta_box_render($post)
{
    wp_nonce_field('chroma_early_start_hero_meta', 'chroma_early_start_hero_nonce');
    chroma_early_start_template_note($post);
    ?>
    <table class="form-table">
        <?php
        chroma_early_start_render_text('early_start_hero_badge', __('Badge', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_badge', true), 'Specialized Pediatric Therapy');
        chroma_early_start_render_textarea('early_start_hero_title', __('Hero Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_title', true), 'Every child blooms at their own pace.', 2);
        chroma_early_start_render_textarea('early_start_hero_description', __('Hero Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_description', true), 'Chroma Early Start is our dedicated therapeutic division...', 4);
        chroma_early_start_render_text('early_start_primary_cta_text', __('Primary Button Text', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_primary_cta_text', true), 'Explore Early Start');
        chroma_early_start_render_url('early_start_primary_cta_url', __('Primary Button URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_primary_cta_url', true), 'https://earlystart.chromaela.com');
        chroma_early_start_render_text('early_start_secondary_cta_text', __('Secondary Button Text', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_secondary_cta_text', true), 'Visit Early Start Site');
        chroma_early_start_render_url('early_start_secondary_cta_url', __('Secondary Button URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_secondary_cta_url', true), 'https://earlystart.chromaela.com');
        chroma_early_start_render_url('early_start_hero_image', __('Hero Image URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_image', true), 'Leave blank to use the bundled fallback image');
        ?>
    </table>
    <?php
}

function chroma_early_start_synergy_meta_box_render($post)
{
    wp_nonce_field('chroma_early_start_synergy_meta', 'chroma_early_start_synergy_nonce');
    chroma_early_start_template_note($post);
    ?>
    <table class="form-table">
        <?php
        chroma_early_start_render_text('early_start_synergy_eyebrow', __('Section Eyebrow', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_eyebrow', true), 'The Chroma Advantage');
        chroma_early_start_render_textarea('early_start_synergy_title', __('Section Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_title', true), 'Where Therapy Meets Education.', 2);
        chroma_early_start_render_textarea('early_start_synergy_intro_one', __('Intro Paragraph 1', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_intro_one', true), 'Traditionally, parents have to juggle preschool drop-offs...', 3);
        chroma_early_start_render_textarea('early_start_synergy_intro_two', __('Intro Paragraph 2', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_intro_two', true), 'By integrating our Early Start therapists directly...', 4);
        chroma_early_start_render_text('early_start_synergy_bullet_one', __('Bullet 1', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_bullet_one', true), 'Reduced transitions and travel for parents');
        chroma_early_start_render_text('early_start_synergy_bullet_two', __('Bullet 2', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_bullet_two', true), 'Real-time collaboration between teachers and clinicians');
        chroma_early_start_render_text('early_start_synergy_bullet_three', __('Bullet 3', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_bullet_three', true), 'Inclusive, neurodiversity-affirming environments');
        chroma_early_start_render_text('early_start_push_title', __('Push-In Card Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_push_title', true), 'Push-In Therapy');
        chroma_early_start_render_textarea('early_start_push_description', __('Push-In Card Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_push_description', true), 'Therapists support children right inside their Chroma Academy classrooms.', 3);
        chroma_early_start_render_text('early_start_pull_title', __('Pull-Out Card Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_pull_title', true), 'Pull-Out Therapy');
        chroma_early_start_render_textarea('early_start_pull_description', __('Pull-Out Card Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_pull_description', true), 'Dedicated sensory gyms and quiet clinic spaces...', 3);
        chroma_early_start_render_url('early_start_synergy_image_one', __('Synergy Image 1 URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_image_one', true), 'Leave blank to use the bundled fallback image');
        chroma_early_start_render_url('early_start_synergy_image_two', __('Synergy Image 2 URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_image_two', true), 'Leave blank to use the bundled fallback image');
        ?>
    </table>
    <?php
}

function chroma_early_start_services_meta_box_render($post)
{
    wp_nonce_field('chroma_early_start_services_meta', 'chroma_early_start_services_nonce');
    chroma_early_start_template_note($post);
    ?>
    <table class="form-table">
        <?php
        chroma_early_start_render_text('early_start_services_title', __('Section Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_services_title', true), 'Our Core Clinical Services');
        chroma_early_start_render_textarea('early_start_services_description', __('Section Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_services_description', true), 'Comprehensive pediatric therapies tailored to your child.', 3);

        for ($i = 1; $i <= 3; $i++) {
            echo '<tr><th colspan="2" style="padding-top:18px;"><strong>' . esc_html(sprintf(__('Service Card %d', 'chroma-excellence'), $i)) . '</strong></th></tr>';
            chroma_early_start_render_text("early_start_service_{$i}_title", __('Title', 'chroma-excellence'), get_post_meta($post->ID, "early_start_service_{$i}_title", true));
            chroma_early_start_render_textarea("early_start_service_{$i}_description", __('Description', 'chroma-excellence'), get_post_meta($post->ID, "early_start_service_{$i}_description", true), '', 3);
            chroma_early_start_render_url("early_start_service_{$i}_url", __('Service URL', 'chroma-excellence'), get_post_meta($post->ID, "early_start_service_{$i}_url", true), 'https://earlystart.chromaela.com/');
        }
        ?>
    </table>
    <?php
}

function chroma_early_start_cta_meta_box_render($post)
{
    wp_nonce_field('chroma_early_start_cta_meta', 'chroma_early_start_cta_nonce');
    chroma_early_start_template_note($post);
    ?>
    <table class="form-table">
        <?php
        chroma_early_start_render_textarea('early_start_cta_title', __('CTA Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_title', true), 'Ready to take the next step?', 2);
        chroma_early_start_render_textarea('early_start_cta_description', __('CTA Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_description', true), 'Visit the official Chroma Early Start website...', 4);
        chroma_early_start_render_text('early_start_cta_button_text', __('CTA Button Text', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_button_text', true), 'Go to Early Start Website');
        chroma_early_start_render_url('early_start_cta_button_url', __('CTA Button URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_button_url', true), 'https://earlystart.chromaela.com');
        ?>
    </table>
    <?php
}

function chroma_save_early_start_page_meta($post_id)
{
    if (get_post_type($post_id) !== 'program') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $meta_boxes = [
        'chroma_early_start_hero_nonce' => [
            'early_start_hero_badge' => 'sanitize_text_field',
            'early_start_hero_title' => 'sanitize_textarea_field',
            'early_start_hero_description' => 'sanitize_textarea_field',
            'early_start_primary_cta_text' => 'sanitize_text_field',
            'early_start_primary_cta_url' => 'esc_url_raw',
            'early_start_secondary_cta_text' => 'sanitize_text_field',
            'early_start_secondary_cta_url' => 'esc_url_raw',
            'early_start_hero_image' => 'esc_url_raw',
        ],
        'chroma_early_start_synergy_nonce' => [
            'early_start_synergy_eyebrow' => 'sanitize_text_field',
            'early_start_synergy_title' => 'sanitize_textarea_field',
            'early_start_synergy_intro_one' => 'sanitize_textarea_field',
            'early_start_synergy_intro_two' => 'sanitize_textarea_field',
            'early_start_synergy_bullet_one' => 'sanitize_text_field',
            'early_start_synergy_bullet_two' => 'sanitize_text_field',
            'early_start_synergy_bullet_three' => 'sanitize_text_field',
            'early_start_push_title' => 'sanitize_text_field',
            'early_start_push_description' => 'sanitize_textarea_field',
            'early_start_pull_title' => 'sanitize_text_field',
            'early_start_pull_description' => 'sanitize_textarea_field',
            'early_start_synergy_image_one' => 'esc_url_raw',
            'early_start_synergy_image_two' => 'esc_url_raw',
        ],
        'chroma_early_start_services_nonce' => [
            'early_start_services_title' => 'sanitize_text_field',
            'early_start_services_description' => 'sanitize_textarea_field',
            'early_start_service_1_title' => 'sanitize_text_field',
            'early_start_service_1_description' => 'sanitize_textarea_field',
            'early_start_service_1_url' => 'esc_url_raw',
            'early_start_service_2_title' => 'sanitize_text_field',
            'early_start_service_2_description' => 'sanitize_textarea_field',
            'early_start_service_2_url' => 'esc_url_raw',
            'early_start_service_3_title' => 'sanitize_text_field',
            'early_start_service_3_description' => 'sanitize_textarea_field',
            'early_start_service_3_url' => 'esc_url_raw',
        ],
        'chroma_early_start_cta_nonce' => [
            'early_start_cta_title' => 'sanitize_textarea_field',
            'early_start_cta_description' => 'sanitize_textarea_field',
            'early_start_cta_button_text' => 'sanitize_text_field',
            'early_start_cta_button_url' => 'esc_url_raw',
        ],
    ];

    foreach ($meta_boxes as $nonce_field => $fields) {
        if (!isset($_POST[$nonce_field])) {
            continue;
        }

        $nonce_action = str_replace('_nonce', '_meta', $nonce_field);
        if (!wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
            continue;
        }

        foreach ($fields as $field_name => $sanitize_function) {
            if (!isset($_POST[$field_name])) {
                continue;
            }

            $value = call_user_func($sanitize_function, wp_unslash($_POST[$field_name]));
            update_post_meta($post_id, $field_name, $value);
        }
    }
}
add_action('save_post', 'chroma_save_early_start_page_meta');

function chroma_seed_early_start_page_defaults($post_id)
{
    if (get_post_type($post_id) !== 'program') {
        return;
    }

    $template = get_post_meta($post_id, '_wp_page_template', true);
    if (!chroma_is_early_start_template($template)) {
        return;
    }

    $already_seeded = get_post_meta($post_id, '_early_start_defaults_seeded', true);
    if ($already_seeded) {
        return;
    }

    $defaults = [
        'early_start_hero_badge' => 'Specialized Pediatric Therapy',
        'early_start_hero_title' => 'Every child blooms at their own pace.',
        'early_start_hero_description' => 'Chroma Early Start is our dedicated therapeutic division, providing Speech, Occupational, and ABA therapies. We seamlessly bridge the gap between clinical intervention and early childhood education.',
        'early_start_primary_cta_text' => 'Explore Early Start',
        'early_start_primary_cta_url' => 'https://earlystart.chromaela.com',
        'early_start_secondary_cta_text' => 'Visit Early Start Site',
        'early_start_secondary_cta_url' => 'https://earlystart.chromaela.com',
        'early_start_synergy_eyebrow' => 'The Chroma Advantage',
        'early_start_synergy_title' => 'Where Therapy Meets Education.',
        'early_start_synergy_intro_one' => 'Traditionally, parents have to juggle preschool drop-offs with driving across town to therapy clinics. Chroma Early Start solves this.',
        'early_start_synergy_intro_two' => 'By integrating our Early Start therapists directly with our Early Learning Academy teachers, we create a unified, collaborative care plan for your child. Strategies used in therapy are reinforced in the classroom, leading to faster, more sustainable progress.',
        'early_start_synergy_bullet_one' => 'Reduced transitions and travel for parents',
        'early_start_synergy_bullet_two' => 'Real-time collaboration between teachers and clinicians',
        'early_start_synergy_bullet_three' => 'Inclusive, neurodiversity-affirming environments',
        'early_start_push_title' => 'Push-In Therapy',
        'early_start_push_description' => 'Therapists support children right inside their Chroma Academy classrooms.',
        'early_start_pull_title' => 'Pull-Out Therapy',
        'early_start_pull_description' => 'Dedicated sensory gyms and quiet clinic spaces for focused, one-on-one sessions.',
        'early_start_services_title' => 'Our Core Clinical Services',
        'early_start_services_description' => 'Comprehensive pediatric therapies tailored to your child\'s unique developmental profile.',
        'early_start_service_1_title' => 'Speech & Language',
        'early_start_service_1_description' => 'Helping children find their voice. From articulation and expressive language delays to pragmatic social communication and AAC device support.',
        'early_start_service_1_url' => 'https://earlystart.chromaela.com/speech-therapy',
        'early_start_service_2_title' => 'Occupational Therapy',
        'early_start_service_2_description' => 'Building independence in daily living. We focus on fine motor skills, sensory processing, feeding challenges, and self-regulation techniques.',
        'early_start_service_2_url' => 'https://earlystart.chromaela.com/occupational-therapy',
        'early_start_service_3_title' => 'ABA Therapy',
        'early_start_service_3_description' => 'Play-based, naturalistic Applied Behavior Analysis focused on communication, social skills, and reducing barriers to learning.',
        'early_start_service_3_url' => 'https://earlystart.chromaela.com/aba-therapy',
        'early_start_cta_title' => 'Ready to take the next step?',
        'early_start_cta_description' => 'Visit the official Chroma Early Start website to meet our clinical directors, view accepted insurances, and request an initial evaluation.',
        'early_start_cta_button_text' => 'Go to Early Start Website',
        'early_start_cta_button_url' => 'https://earlystart.chromaela.com',
    ];

    foreach ($defaults as $meta_key => $default_value) {
        update_post_meta($post_id, $meta_key, $default_value);
    }

    update_post_meta($post_id, '_early_start_defaults_seeded', '1');
}
add_action('save_post', 'chroma_seed_early_start_page_defaults', 5);

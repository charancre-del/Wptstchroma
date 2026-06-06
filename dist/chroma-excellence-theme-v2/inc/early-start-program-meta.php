<?php
/**
 * Chroma Early Learning Support Page Meta Boxes
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
        __('Chroma Early Learning: Hero', 'chroma-excellence'),
        'chroma_early_start_hero_meta_box_render',
        'program',
        'normal',
        'high'
    );

    add_meta_box(
        'chroma-early-start-synergy',
        __('Chroma Early Learning: Support + Education', 'chroma-excellence'),
        'chroma_early_start_synergy_meta_box_render',
        'program',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-early-start-services',
        __('Chroma Early Learning: Services', 'chroma-excellence'),
        'chroma_early_start_services_meta_box_render',
        'program',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-early-start-cta',
        __('Chroma Early Learning: Final CTA', 'chroma-excellence'),
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
    echo esc_html__('These fields are used by the "Early Learning Support" program template.', 'chroma-excellence');
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
        chroma_early_start_render_text('early_start_hero_badge', __('Badge', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_badge', true), 'Early Learning Support');
        chroma_early_start_render_textarea('early_start_hero_title', __('Hero Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_title', true), 'Every child blooms at their own pace.', 2);
        chroma_early_start_render_textarea('early_start_hero_description', __('Hero Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_hero_description', true), 'Chroma Early Learning brings classroom care, developmental support, and family partnership together...', 4);
        chroma_early_start_render_text('early_start_primary_cta_text', __('Primary Button Text', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_primary_cta_text', true), 'Explore Early Learning');
        chroma_early_start_render_url('early_start_primary_cta_url', __('Primary Button URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_primary_cta_url', true), home_url('/programs/'));
        chroma_early_start_render_text('early_start_secondary_cta_text', __('Secondary Button Text', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_secondary_cta_text', true), 'Schedule a Tour');
        chroma_early_start_render_url('early_start_secondary_cta_url', __('Secondary Button URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_secondary_cta_url', true), home_url('/schedule-a-tour/'));
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
        chroma_early_start_render_textarea('early_start_synergy_title', __('Section Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_title', true), 'Where Support Meets Education.', 2);
        chroma_early_start_render_textarea('early_start_synergy_intro_one', __('Intro Paragraph 1', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_intro_one', true), 'Traditionally, parents have to juggle preschool drop-offs...', 3);
        chroma_early_start_render_textarea('early_start_synergy_intro_two', __('Intro Paragraph 2', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_intro_two', true), 'By aligning our Chroma Early Learning teachers directly...', 4);
        chroma_early_start_render_text('early_start_synergy_bullet_one', __('Bullet 1', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_bullet_one', true), 'Reduced transitions and travel for parents');
        chroma_early_start_render_text('early_start_synergy_bullet_two', __('Bullet 2', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_bullet_two', true), 'Real-time collaboration between teachers and support specialists');
        chroma_early_start_render_text('early_start_synergy_bullet_three', __('Bullet 3', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_synergy_bullet_three', true), 'Inclusive, neurodiversity-affirming environments');
        chroma_early_start_render_text('early_start_push_title', __('Push-In Card Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_push_title', true), 'Push-In Support');
        chroma_early_start_render_textarea('early_start_push_description', __('Push-In Card Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_push_description', true), 'Support specialists help children right inside their Chroma classrooms.', 3);
        chroma_early_start_render_text('early_start_pull_title', __('Pull-Out Card Title', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_pull_title', true), 'Focused Support');
        chroma_early_start_render_textarea('early_start_pull_description', __('Pull-Out Card Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_pull_description', true), 'Dedicated sensory-friendly spaces for focused, one-on-one support.', 3);
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
        chroma_early_start_render_textarea('early_start_services_description', __('Section Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_services_description', true), 'Warm learning support tailored to your child.', 3);

        for ($i = 1; $i <= 3; $i++) {
            echo '<tr><th colspan="2" style="padding-top:18px;"><strong>' . esc_html(sprintf(__('Service Card %d', 'chroma-excellence'), $i)) . '</strong></th></tr>';
            chroma_early_start_render_text("early_start_service_{$i}_title", __('Title', 'chroma-excellence'), get_post_meta($post->ID, "early_start_service_{$i}_title", true));
            chroma_early_start_render_textarea("early_start_service_{$i}_description", __('Description', 'chroma-excellence'), get_post_meta($post->ID, "early_start_service_{$i}_description", true), '', 3);
            chroma_early_start_render_url("early_start_service_{$i}_url", __('Service URL', 'chroma-excellence'), get_post_meta($post->ID, "early_start_service_{$i}_url", true), home_url('/programs/'));
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
        chroma_early_start_render_textarea('early_start_cta_description', __('CTA Description', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_description', true), 'Schedule a tour with Chroma Early Learning...', 4);
        chroma_early_start_render_text('early_start_cta_button_text', __('CTA Button Text', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_button_text', true), 'Schedule a Tour');
        chroma_early_start_render_url('early_start_cta_button_url', __('CTA Button URL', 'chroma-excellence'), get_post_meta($post->ID, 'early_start_cta_button_url', true), home_url('/schedule-a-tour/'));
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
        'early_start_hero_badge' => 'Early Learning Support',
        'early_start_hero_title' => 'Every child blooms at their own pace.',
        'early_start_hero_description' => 'Chroma Early Learning brings classroom care, developmental support, and family partnership together so children can thrive in the rhythm of their day.',
        'early_start_primary_cta_text' => 'Explore Early Learning',
        'early_start_primary_cta_url' => home_url('/programs/'),
        'early_start_secondary_cta_text' => 'Schedule a Tour',
        'early_start_secondary_cta_url' => home_url('/schedule-a-tour/'),
        'early_start_synergy_eyebrow' => 'The Chroma Advantage',
        'early_start_synergy_title' => 'Where Support Meets Education.',
        'early_start_synergy_intro_one' => 'Families should not have to piece together care, learning, and developmental guidance across disconnected settings. Chroma Early Learning brings that support into one warm school community.',
        'early_start_synergy_intro_two' => 'By aligning classroom teachers, family communication, and child development support, we create a unified care plan for each child. Strategies are reinforced in daily routines, leading to steadier confidence and growth.',
        'early_start_synergy_bullet_one' => 'Reduced transitions and travel for parents',
        'early_start_synergy_bullet_two' => 'Real-time collaboration between teachers and support specialists',
        'early_start_synergy_bullet_three' => 'Inclusive, neurodiversity-affirming environments',
        'early_start_push_title' => 'Push-In Support',
        'early_start_push_description' => 'Support specialists help children right inside their Chroma classrooms.',
        'early_start_pull_title' => 'Focused Support',
        'early_start_pull_description' => 'Dedicated sensory-friendly spaces for focused, one-on-one support.',
        'early_start_services_title' => 'Child Development Support',
        'early_start_services_description' => 'Warm learning support tailored to your child\'s unique developmental profile.',
        'early_start_service_1_title' => 'Speech & Language',
        'early_start_service_1_description' => 'Helping children find their voice. From articulation and expressive language delays to pragmatic social communication and AAC device support.',
        'early_start_service_1_url' => home_url('/programs/'),
        'early_start_service_2_title' => 'Motor & Sensory Support',
        'early_start_service_2_description' => 'Building independence in daily living. We focus on fine motor skills, sensory processing, feeding challenges, and self-regulation techniques.',
        'early_start_service_2_url' => home_url('/programs/'),
        'early_start_service_3_title' => 'Behavioral Learning Support',
        'early_start_service_3_description' => 'Play-based, naturalistic Applied Behavior Analysis focused on communication, social skills, and reducing barriers to learning.',
        'early_start_service_3_url' => home_url('/programs/'),
        'early_start_cta_title' => 'Ready to take the next step?',
        'early_start_cta_description' => 'Schedule a tour with Chroma Early Learning to meet our team, explore classrooms, and talk through the support your child needs.',
        'early_start_cta_button_text' => 'Schedule a Tour',
        'early_start_cta_button_url' => home_url('/schedule-a-tour/'),
    ];

    foreach ($defaults as $meta_key => $default_value) {
        update_post_meta($post_id, $meta_key, $default_value);
    }

    update_post_meta($post_id, '_early_start_defaults_seeded', '1');
}
add_action('save_post', 'chroma_seed_early_start_page_defaults', 5);

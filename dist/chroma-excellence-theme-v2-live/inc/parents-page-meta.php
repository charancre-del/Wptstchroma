<?php
/**
 * Parents Page Meta Boxes
 *
 * @package Chroma_Excellence
 */

/**
 * Register Meta Boxes
 */
function chroma_parents_page_meta_boxes()
{
    global $post;
    if ('page-parents.php' !== get_post_meta($post->ID, '_wp_page_template', true)) {
        return;
    }

    add_meta_box(
        'chroma-parents-hero',
        __('Hero Section', 'chroma-excellence'),
        'chroma_parents_hero_meta_box_render',
        'page',
        'normal',
        'high'
    );

    add_meta_box(
        'chroma-parents-resources',
        __('Quick Links / Resources', 'chroma-excellence'),
        'chroma_parents_resources_meta_box_render',
        'page',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-parents-events',
        __('Events Section', 'chroma-excellence'),
        'chroma_parents_events_meta_box_render',
        'page',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-parents-gallery',
        __('Moments of Joy Gallery', 'chroma-excellence'),
        'chroma_parents_gallery_meta_box_render',
        'page',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-parents-nutrition',
        __('Nutrition Section', 'chroma-excellence'),
        'chroma_parents_nutrition_meta_box_render',
        'page',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-parents-safety',
        __('Safety Section', 'chroma-excellence'),
        'chroma_parents_safety_meta_box_render',
        'page',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-parents-faq',
        __('FAQ Section', 'chroma-excellence'),
        'chroma_parents_faq_meta_box_render',
        'page',
        'normal',
        'default'
    );

    add_meta_box(
        'chroma-parents-referral',
        __('Referral Banner', 'chroma-excellence'),
        'chroma_parents_referral_meta_box_render',
        'page',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'chroma_parents_page_meta_boxes');

/**
 * Hero Section Meta Box
 */
function chroma_parents_hero_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_hero_meta', 'chroma_parents_hero_nonce');

    $hero_badge = get_post_meta($post->ID, 'parents_hero_badge', true);
    $hero_title = get_post_meta($post->ID, 'parents_hero_title', true);
    $hero_description = get_post_meta($post->ID, 'parents_hero_description', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_hero_badge">Badge Text</label></th>
            <td>
                <input type="text" id="parents_hero_badge" name="parents_hero_badge"
                    value="<?php echo esc_attr($hero_badge); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_hero_badge" name="_chroma_es_parents_hero_badge"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_hero_badge', true)); ?>" class="large-text" placeholder="[ES] Badge Text" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_hero_title">Headline</label></th>
            <td>
                <input type="text" id="parents_hero_title" name="parents_hero_title"
                    value="<?php echo esc_attr($hero_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_hero_title" name="_chroma_es_parents_hero_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_hero_title', true)); ?>" class="large-text" placeholder="[ES] Headline" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_hero_description">Description</label></th>
            <td>
                <textarea id="parents_hero_description" name="parents_hero_description" rows="3"
                    class="large-text"><?php echo esc_textarea($hero_description); ?></textarea>
                <br>
                <textarea id="_chroma_es_parents_hero_description" name="_chroma_es_parents_hero_description" rows="3"
                    class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, '_chroma_es_parents_hero_description', true)); ?></textarea>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Resources Meta Box
 */
function chroma_parents_resources_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_resources_meta', 'chroma_parents_resources_nonce');

    $essentials_title = get_post_meta($post->ID, 'parents_essentials_title', true);

    $resources = array(
        'procare' => 'Family App (Procare)',
        'tuition' => 'Pay Tuition',
        'handbook' => 'Parent Handbook',
        'enrollment' => 'Enrollment Forms',
        'prekga' => 'Pre-K GA',
        'waitlist' => 'Join Waitlist',
    );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_essentials_title">Section Title</label></th>
            <td>
                <input type="text" id="parents_essentials_title" name="parents_essentials_title"
                    value="<?php echo esc_attr($essentials_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_essentials_title" name="_chroma_es_parents_essentials_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_essentials_title', true)); ?>" class="large-text" placeholder="[ES] Section Title" style="margin-top: 5px;" />
            </td>
        </tr>
    </table>

    <?php foreach ($resources as $key => $label): ?>
        <?php
        $icon = get_post_meta($post->ID, "parents_resource_{$key}_icon", true);
        $title = get_post_meta($post->ID, "parents_resource_{$key}_title", true);
        $desc = get_post_meta($post->ID, "parents_resource_{$key}_desc", true);
        $url = get_post_meta($post->ID, "parents_resource_{$key}_url", true);
        ?>
        <hr style="margin: 20px 0;" />
        <h4 style="margin: 15px 0;"><?php echo esc_html($label); ?></h4>
        <table class="form-table">
            <tr>
                <th><label for="parents_resource_<?php echo $key; ?>_icon">Icon Class</label></th>
                <td>
                    <input type="text" id="parents_resource_<?php echo $key; ?>_icon"
                        name="parents_resource_<?php echo $key; ?>_icon" value="<?php echo esc_attr($icon); ?>" />
                    <p class="description">e.g. fa-solid fa-mobile-screen</p>
                </td>
            </tr>
            <tr>
                <th><label for="parents_resource_<?php echo $key; ?>_title">Title</label></th>
                <td>
                    <input type="text" id="parents_resource_<?php echo $key; ?>_title"
                        name="parents_resource_<?php echo $key; ?>_title" value="<?php echo esc_attr($title); ?>"
                        class="large-text" />
                    <br>
                    <input type="text" id="_chroma_es_parents_resource_<?php echo $key; ?>_title"
                        name="_chroma_es_parents_resource_<?php echo $key; ?>_title" value="<?php echo esc_attr(get_post_meta($post->ID, "_chroma_es_parents_resource_{$key}_title", true)); ?>"
                        class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
                </td>
            </tr>
            <tr>
                <th><label for="parents_resource_<?php echo $key; ?>_desc">Description</label></th>
                <td>
                    <textarea id="parents_resource_<?php echo $key; ?>_desc" name="parents_resource_<?php echo $key; ?>_desc"
                        rows="2" class="large-text"><?php echo esc_textarea($desc); ?></textarea>
                    <br>
                    <textarea id="_chroma_es_parents_resource_<?php echo $key; ?>_desc" name="_chroma_es_parents_resource_<?php echo $key; ?>_desc"
                        rows="2" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, "_chroma_es_parents_resource_{$key}_desc", true)); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="parents_resource_<?php echo $key; ?>_url">URL</label></th>
                <td>
                    <input type="url" id="parents_resource_<?php echo $key; ?>_url"
                        name="parents_resource_<?php echo $key; ?>_url" value="<?php echo esc_attr($url); ?>"
                        class="large-text" placeholder="https://" />
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php
}

/**
 * Events Section Meta Box
 */
function chroma_parents_events_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_events_meta', 'chroma_parents_events_nonce');

    $events_badge = get_post_meta($post->ID, 'parents_events_badge', true);
    $events_title = get_post_meta($post->ID, 'parents_events_title', true);
    $events_description = get_post_meta($post->ID, 'parents_events_description', true);
    $events_image = get_post_meta($post->ID, 'parents_events_image', true);

    $event_items = array(
        1 => 'Event 1',
        2 => 'Event 2',
        3 => 'Event 3',
    );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_events_badge">Badge</label></th>
            <td>
                <input type="text" id="parents_events_badge" name="parents_events_badge"
                    value="<?php echo esc_attr($events_badge); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_events_badge" name="_chroma_es_parents_events_badge"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_events_badge', true)); ?>" class="large-text" placeholder="[ES] Badge" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_events_title">Title</label></th>
            <td>
                <input type="text" id="parents_events_title" name="parents_events_title"
                    value="<?php echo esc_attr($events_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_events_title" name="_chroma_es_parents_events_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_events_title', true)); ?>" class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_events_description">Description</label></th>
            <td>
                <textarea id="parents_events_description" name="parents_events_description" rows="3"
                    class="large-text"><?php echo esc_textarea($events_description); ?></textarea>
                <br>
                <textarea id="_chroma_es_parents_events_description" name="_chroma_es_parents_events_description" rows="3"
                    class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, '_chroma_es_parents_events_description', true)); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="parents_events_image">Side Image URL</label></th>
            <td>
                <input type="url" id="parents_events_image" name="parents_events_image"
                    value="<?php echo esc_attr($events_image); ?>" class="large-text" />
            </td>
        </tr>
    </table>

    <?php foreach ($event_items as $num => $label): ?>
        <?php
        $icon = get_post_meta($post->ID, "parents_event{$num}_icon", true);
        $title = get_post_meta($post->ID, "parents_event{$num}_title", true);
        $desc = get_post_meta($post->ID, "parents_event{$num}_desc", true);
        ?>
        <hr style="margin: 20px 0;" />
        <h4 style="margin: 15px 0;"><?php echo esc_html($label); ?></h4>
        <table class="form-table">
            <tr>
                <th><label for="parents_event<?php echo $num; ?>_icon">Icon</label></th>
                <td>
                    <input type="text" id="parents_event<?php echo $num; ?>_icon" name="parents_event<?php echo $num; ?>_icon"
                        value="<?php echo esc_attr($icon); ?>" />
                    <p class="description">FontAwesome class</p>
                </td>
            </tr>
            <tr>
                <th><label for="parents_event<?php echo $num; ?>_title">Title</label></th>
                <td>
                    <input type="text" id="parents_event<?php echo $num; ?>_title" name="parents_event<?php echo $num; ?>_title"
                        value="<?php echo esc_attr($title); ?>" class="large-text" />
                    <br>
                    <input type="text" id="_chroma_es_parents_event<?php echo $num; ?>_title" name="_chroma_es_parents_event<?php echo $num; ?>_title"
                        value="<?php echo esc_attr(get_post_meta($post->ID, "_chroma_es_parents_event{$num}_title", true)); ?>" class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
                </td>
            </tr>
            <tr>
                <th><label for="parents_event<?php echo $num; ?>_desc">Description</label></th>
                <td>
                    <textarea id="parents_event<?php echo $num; ?>_desc" name="parents_event<?php echo $num; ?>_desc" rows="2"
                        class="large-text"><?php echo esc_textarea($desc); ?></textarea>
                    <br>
                    <textarea id="_chroma_es_parents_event<?php echo $num; ?>_desc" name="_chroma_es_parents_event<?php echo $num; ?>_desc" rows="2"
                        class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, "_chroma_es_parents_event{$num}_desc", true)); ?></textarea>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php
}

/**
 * Nutrition Section Meta Box
 */
function chroma_parents_nutrition_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_nutrition_meta', 'chroma_parents_nutrition_nonce');

    $nutrition_badge = get_post_meta($post->ID, 'parents_nutrition_badge', true);
    $nutrition_title = get_post_meta($post->ID, 'parents_nutrition_title', true);
    $nutrition_description = get_post_meta($post->ID, 'parents_nutrition_description', true);
    $nutrition_image = get_post_meta($post->ID, 'parents_nutrition_image', true);

    $menu_items = array(
        1 => 'Menu Item 1',
        2 => 'Menu Item 2',
        3 => 'Menu Item 3',
    );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_nutrition_badge">Badge</label></th>
            <td>
                <input type="text" id="parents_nutrition_badge" name="parents_nutrition_badge"
                    value="<?php echo esc_attr($nutrition_badge); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_nutrition_badge" name="_chroma_es_parents_nutrition_badge"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_nutrition_badge', true)); ?>" class="large-text" placeholder="[ES] Badge" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_nutrition_title">Title</label></th>
            <td>
                <input type="text" id="parents_nutrition_title" name="parents_nutrition_title"
                    value="<?php echo esc_attr($nutrition_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_nutrition_title" name="_chroma_es_parents_nutrition_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_nutrition_title', true)); ?>" class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_nutrition_description">Description</label></th>
            <td>
                <textarea id="parents_nutrition_description" name="parents_nutrition_description" rows="3"
                    class="large-text"><?php echo esc_textarea($nutrition_description); ?></textarea>
                <br>
                <textarea id="_chroma_es_parents_nutrition_description" name="_chroma_es_parents_nutrition_description" rows="3"
                    class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, '_chroma_es_parents_nutrition_description', true)); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="parents_nutrition_image">Image URL</label></th>
            <td>
                <input type="url" id="parents_nutrition_image" name="parents_nutrition_image"
                    value="<?php echo esc_attr($nutrition_image); ?>" class="large-text" />
            </td>
        </tr>
    </table>

    <?php foreach ($menu_items as $num => $label): ?>
        <?php
        $icon = get_post_meta($post->ID, "parents_menu{$num}_icon", true);
        $title = get_post_meta($post->ID, "parents_menu{$num}_title", true);
        $subtitle = get_post_meta($post->ID, "parents_menu{$num}_subtitle", true);
        $url = get_post_meta($post->ID, "parents_menu{$num}_url", true);
        ?>
        <hr style="margin: 20px 0;" />
        <h4 style="margin: 15px 0;"><?php echo esc_html($label); ?></h4>
        <table class="form-table">
            <tr>
                <th><label for="parents_menu<?php echo $num; ?>_icon">Icon</label></th>
                <td>
                    <input type="text" id="parents_menu<?php echo $num; ?>_icon" name="parents_menu<?php echo $num; ?>_icon"
                        value="<?php echo esc_attr($icon); ?>" />
                    <p class="description">FontAwesome class</p>
                </td>
            </tr>
            <tr>
                <th><label for="parents_menu<?php echo $num; ?>_title">Title</label></th>
                <td>
                    <input type="text" id="parents_menu<?php echo $num; ?>_title" name="parents_menu<?php echo $num; ?>_title"
                        value="<?php echo esc_attr($title); ?>" class="large-text" />
                </td>
            </tr>
            <tr>
                <th><label for="parents_menu<?php echo $num; ?>_subtitle">Subtitle</label></th>
                <td>
                    <input type="text" id="parents_menu<?php echo $num; ?>_subtitle"
                        name="parents_menu<?php echo $num; ?>_subtitle" value="<?php echo esc_attr($subtitle); ?>"
                        class="large-text" />
                </td>
            </tr>
            <tr>
                <th><label for="parents_menu<?php echo $num; ?>_url">Download URL</label></th>
                <td>
                    <input type="url" id="parents_menu<?php echo $num; ?>_url" name="parents_menu<?php echo $num; ?>_url"
                        value="<?php echo esc_attr($url); ?>" class="large-text" placeholder="https://" />
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php
}

/**
 * Safety Section Meta Box
 */
function chroma_parents_safety_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_safety_meta', 'chroma_parents_safety_nonce');

    $safety_title = get_post_meta($post->ID, 'parents_safety_title', true);
    $safety_description = get_post_meta($post->ID, 'parents_safety_description', true);

    $safety_items = array(
        1 => 'Item 1 (24/7 Monitored Cameras)',
        2 => 'Item 2 (Real-Time Updates)',
        3 => 'Item 3 (Secure Access Control)',
    );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_safety_title">Section Title</label></th>
            <td>
                <input type="text" id="parents_safety_title" name="parents_safety_title"
                    value="<?php echo esc_attr($safety_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_safety_title" name="_chroma_es_parents_safety_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_safety_title', true)); ?>" class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_safety_description">Description</label></th>
            <td>
                <textarea id="parents_safety_description" name="parents_safety_description" rows="3"
                    class="large-text"><?php echo esc_textarea($safety_description); ?></textarea>
                <br>
                <textarea id="_chroma_es_parents_safety_description" name="_chroma_es_parents_safety_description" rows="3"
                    class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, '_chroma_es_parents_safety_description', true)); ?></textarea>
            </td>
        </tr>
    </table>

    <?php foreach ($safety_items as $num => $label): ?>
        <?php
        $icon = get_post_meta($post->ID, "parents_safety{$num}_icon", true);
        $title = get_post_meta($post->ID, "parents_safety{$num}_title", true);
        $desc = get_post_meta($post->ID, "parents_safety{$num}_desc", true);
        ?>
        <hr style="margin: 20px 0;" />
        <h4 style="margin: 15px 0;"><?php echo esc_html($label); ?></h4>
        <table class="form-table">
            <tr>
                <th><label for="parents_safety<?php echo $num; ?>_icon">Icon</label></th>
                <td>
                    <input type="text" id="parents_safety<?php echo $num; ?>_icon" name="parents_safety<?php echo $num; ?>_icon"
                        value="<?php echo esc_attr($icon); ?>" />
                    <p class="description">FontAwesome class</p>
                </td>
            </tr>
            <tr>
                <th><label for="parents_safety<?php echo $num; ?>_title">Title</label></th>
                <td>
                    <input type="text" id="parents_safety<?php echo $num; ?>_title"
                        name="parents_safety<?php echo $num; ?>_title" value="<?php echo esc_attr($title); ?>"
                        class="large-text" />
                    <br>
                    <input type="text" id="_chroma_es_parents_safety<?php echo $num; ?>_title"
                        name="_chroma_es_parents_safety<?php echo $num; ?>_title" value="<?php echo esc_attr(get_post_meta($post->ID, "_chroma_es_parents_safety{$num}_title", true)); ?>"
                        class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
                </td>
            </tr>
            <tr>
                <th><label for="parents_safety<?php echo $num; ?>_desc">Description</label></th>
                <td>
                    <textarea id="parents_safety<?php echo $num; ?>_desc" name="parents_safety<?php echo $num; ?>_desc" rows="3"
                        class="large-text"><?php echo esc_textarea($desc); ?></textarea>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php
}

/**
 * FAQ Section Meta Box
 */
function chroma_parents_faq_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_faq_meta', 'chroma_parents_faq_nonce');

    $faq_title = get_post_meta($post->ID, 'parents_faq_title', true);
    $faq_description = get_post_meta($post->ID, 'parents_faq_description', true);

    $faq_items = array(
        1 => 'FAQ 1',
        2 => 'FAQ 2',
        3 => 'FAQ 3',
    );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_faq_title">Section Title</label></th>
            <td>
                <input type="text" id="parents_faq_title" name="parents_faq_title"
                    value="<?php echo esc_attr($faq_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_faq_title" name="_chroma_es_parents_faq_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_faq_title', true)); ?>" class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_faq_description">Description</label></th>
            <td>
                <textarea id="parents_faq_description" name="parents_faq_description" rows="2"
                    class="large-text"><?php echo esc_textarea($faq_description); ?></textarea>
                <br>
                <textarea id="_chroma_es_parents_faq_description" name="_chroma_es_parents_faq_description" rows="2"
                    class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, '_chroma_es_parents_faq_description', true)); ?></textarea>
            </td>
        </tr>
    </table>

    <?php foreach ($faq_items as $num => $label): ?>
        <?php
        $question = get_post_meta($post->ID, "parents_faq{$num}_question", true);
        $answer = get_post_meta($post->ID, "parents_faq{$num}_answer", true);
        ?>
        <hr style="margin: 20px 0;" />
        <h4 style="margin: 15px 0;"><?php echo esc_html($label); ?></h4>
        <table class="form-table">
            <tr>
                <th><label for="parents_faq<?php echo $num; ?>_question">Question</label></th>
                <td>
                    <input type="text" id="parents_faq<?php echo $num; ?>_question"
                        name="parents_faq<?php echo $num; ?>_question" value="<?php echo esc_attr($question); ?>"
                        class="large-text" />
                    <br>
                    <input type="text" id="_chroma_es_parents_faq<?php echo $num; ?>_question"
                        name="_chroma_es_parents_faq<?php echo $num; ?>_question" value="<?php echo esc_attr(get_post_meta($post->ID, "_chroma_es_parents_faq{$num}_question", true)); ?>"
                        class="large-text" placeholder="[ES] Question" style="margin-top: 5px;" />
                </td>
            </tr>
            <tr>
                <th><label for="parents_faq<?php echo $num; ?>_answer">Answer</label></th>
                <td>
                    <textarea id="parents_faq<?php echo $num; ?>_answer" name="parents_faq<?php echo $num; ?>_answer" rows="3"
                        class="large-text"><?php echo esc_textarea($answer); ?></textarea>
                    <br>
                    <textarea id="_chroma_es_parents_faq<?php echo $num; ?>_answer" name="_chroma_es_parents_faq<?php echo $num; ?>_answer" rows="3"
                        class="large-text" placeholder="[ES] Answer" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, "_chroma_es_parents_faq{$num}_answer", true)); ?></textarea>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php
}

/**
 * Referral Banner Meta Box
 */
function chroma_parents_referral_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_referral_meta', 'chroma_parents_referral_nonce');

    $referral_title = get_post_meta($post->ID, 'parents_referral_title', true);
    $referral_description = get_post_meta($post->ID, 'parents_referral_description', true);
    $referral_button_text = get_post_meta($post->ID, 'parents_referral_button_text', true);
    $referral_button_url = get_post_meta($post->ID, 'parents_referral_button_url', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_referral_title">Title</label></th>
            <td>
                <input type="text" id="parents_referral_title" name="parents_referral_title"
                    value="<?php echo esc_attr($referral_title); ?>" class="large-text" />
                <br>
                <input type="text" id="_chroma_es_parents_referral_title" name="_chroma_es_parents_referral_title"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_referral_title', true)); ?>" class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_referral_description">Description</label></th>
            <td>
                <textarea id="parents_referral_description" name="parents_referral_description" rows="2"
                    class="large-text"><?php echo esc_textarea($referral_description); ?></textarea>
                <br>
                <textarea id="_chroma_es_parents_referral_description" name="_chroma_es_parents_referral_description" rows="2"
                    class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea(get_post_meta($post->ID, '_chroma_es_parents_referral_description', true)); ?></textarea>
                <p class="description">You can use &lt;strong&gt; tags for bold text</p>
            </td>
        </tr>
        <tr>
            <th><label for="parents_referral_button_text">Button Text</label></th>
            <td>
                <input type="text" id="parents_referral_button_text" name="parents_referral_button_text"
                    value="<?php echo esc_attr($referral_button_text); ?>" />
                <br>
                <input type="text" id="_chroma_es_parents_referral_button_text" name="_chroma_es_parents_referral_button_text"
                    value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_es_parents_referral_button_text', true)); ?>" placeholder="[ES] Button Text" style="margin-top: 5px;" />
            </td>
        </tr>
        <tr>
            <th><label for="parents_referral_button_url">Button URL</label></th>
            <td>
                <input type="url" id="parents_referral_button_url" name="parents_referral_button_url"
                    value="<?php echo esc_attr($referral_button_url); ?>" class="large-text"
                    placeholder="https:// or mailto:" />
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Gallery Meta Box
 */
function chroma_parents_gallery_meta_box_render($post)
{
    wp_nonce_field('chroma_parents_gallery_meta', 'chroma_parents_gallery_nonce');
    $gallery = get_post_meta($post->ID, 'parents_moments_gallery', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="parents_moments_gallery"><?php _e('Image Gallery (URLs)', 'chroma-excellence'); ?></label>
            </th>
            <td>
                <textarea id="parents_moments_gallery" name="parents_moments_gallery" rows="5" class="large-text"
                    placeholder="Enter image URLs, one per line"><?php echo esc_textarea($gallery); ?></textarea>
                <p class="description">
                    <?php _e('Enter one image URL per line. These will be displayed in the carousel.', 'chroma-excellence'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save Parents Page Meta
 */
function chroma_save_parents_page_meta($post_id)
{
    // Check if this is a page
    if (get_post_type($post_id) !== 'page') {
        return;
    }

    // Define all meta fields
    $meta_boxes = array(
        'chroma_parents_hero_nonce' => array(
            'parents_hero_badge' => 'sanitize_text_field',
            '_chroma_es_parents_hero_badge' => 'sanitize_text_field',
            'parents_hero_title' => 'sanitize_text_field',
            '_chroma_es_parents_hero_title' => 'sanitize_text_field',
            'parents_hero_description' => 'sanitize_textarea_field',
            '_chroma_es_parents_hero_description' => 'sanitize_textarea_field',
        ),
        'chroma_parents_resources_nonce' => array(
            'parents_essentials_title' => 'sanitize_text_field',
            '_chroma_es_parents_essentials_title' => 'sanitize_text_field',
            // Procare
            'parents_resource_procare_icon' => 'sanitize_text_field',
            'parents_resource_procare_title' => 'sanitize_text_field',
            '_chroma_es_parents_resource_procare_title' => 'sanitize_text_field',
            'parents_resource_procare_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_resource_procare_desc' => 'sanitize_textarea_field',
            'parents_resource_procare_url' => 'esc_url_raw',
            // Tuition
            'parents_resource_tuition_icon' => 'sanitize_text_field',
            'parents_resource_tuition_title' => 'sanitize_text_field',
            '_chroma_es_parents_resource_tuition_title' => 'sanitize_text_field',
            'parents_resource_tuition_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_resource_tuition_desc' => 'sanitize_textarea_field',
            'parents_resource_tuition_url' => 'esc_url_raw',
            // Handbook
            'parents_resource_handbook_icon' => 'sanitize_text_field',
            'parents_resource_handbook_title' => 'sanitize_text_field',
            '_chroma_es_parents_resource_handbook_title' => 'sanitize_text_field',
            'parents_resource_handbook_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_resource_handbook_desc' => 'sanitize_textarea_field',
            'parents_resource_handbook_url' => 'esc_url_raw',
            // Enrollment
            'parents_resource_enrollment_icon' => 'sanitize_text_field',
            'parents_resource_enrollment_title' => 'sanitize_text_field',
            '_chroma_es_parents_resource_enrollment_title' => 'sanitize_text_field',
            'parents_resource_enrollment_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_resource_enrollment_desc' => 'sanitize_textarea_field',
            'parents_resource_enrollment_url' => 'esc_url_raw',
            // Pre-K GA
            'parents_resource_prekga_icon' => 'sanitize_text_field',
            'parents_resource_prekga_title' => 'sanitize_text_field',
            '_chroma_es_parents_resource_prekga_title' => 'sanitize_text_field',
            'parents_resource_prekga_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_resource_prekga_desc' => 'sanitize_textarea_field',
            'parents_resource_prekga_url' => 'esc_url_raw',
            // Waitlist
            'parents_resource_waitlist_icon' => 'sanitize_text_field',
            'parents_resource_waitlist_title' => 'sanitize_text_field',
            '_chroma_es_parents_resource_waitlist_title' => 'sanitize_text_field',
            'parents_resource_waitlist_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_resource_waitlist_desc' => 'sanitize_textarea_field',
            'parents_resource_waitlist_url' => 'esc_url_raw',
        ),
        'chroma_parents_events_nonce' => array(
            'parents_events_badge' => 'sanitize_text_field',
            '_chroma_es_parents_events_badge' => 'sanitize_text_field',
            'parents_events_title' => 'sanitize_text_field',
            '_chroma_es_parents_events_title' => 'sanitize_text_field',
            'parents_events_description' => 'sanitize_textarea_field',
            '_chroma_es_parents_events_description' => 'sanitize_textarea_field',
            'parents_events_image' => 'esc_url_raw',
            'parents_event1_icon' => 'sanitize_text_field',
            'parents_event1_title' => 'sanitize_text_field',
            '_chroma_es_parents_event1_title' => 'sanitize_text_field',
            'parents_event1_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_event1_desc' => 'sanitize_textarea_field',
            'parents_event2_icon' => 'sanitize_text_field',
            'parents_event2_title' => 'sanitize_text_field',
            '_chroma_es_parents_event2_title' => 'sanitize_text_field',
            'parents_event2_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_event2_desc' => 'sanitize_textarea_field',
            'parents_event3_icon' => 'sanitize_text_field',
            'parents_event3_title' => 'sanitize_text_field',
            '_chroma_es_parents_event3_title' => 'sanitize_text_field',
            'parents_event3_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_event3_desc' => 'sanitize_textarea_field',
        ),
        'chroma_parents_gallery_nonce' => array(
            'parents_moments_gallery' => 'sanitize_textarea_field',
        ),
        'chroma_parents_nutrition_nonce' => array(
            'parents_nutrition_badge' => 'sanitize_text_field',
            '_chroma_es_parents_nutrition_badge' => 'sanitize_text_field',
            'parents_nutrition_title' => 'sanitize_text_field',
            '_chroma_es_parents_nutrition_title' => 'sanitize_text_field',
            'parents_nutrition_description' => 'sanitize_textarea_field',
            '_chroma_es_parents_nutrition_description' => 'sanitize_textarea_field',
            'parents_nutrition_image' => 'esc_url_raw',
            'parents_menu1_icon' => 'sanitize_text_field',
            'parents_menu1_title' => 'sanitize_text_field',
            '_chroma_es_parents_menu1_title' => 'sanitize_text_field',
            'parents_menu1_subtitle' => 'sanitize_text_field',
            '_chroma_es_parents_menu1_subtitle' => 'sanitize_text_field',
            'parents_menu1_url' => 'esc_url_raw',
            'parents_menu2_icon' => 'sanitize_text_field',
            'parents_menu2_title' => 'sanitize_text_field',
            '_chroma_es_parents_menu2_title' => 'sanitize_text_field',
            'parents_menu2_subtitle' => 'sanitize_text_field',
            '_chroma_es_parents_menu2_subtitle' => 'sanitize_text_field',
            'parents_menu2_url' => 'esc_url_raw',
            'parents_menu3_icon' => 'sanitize_text_field',
            'parents_menu3_title' => 'sanitize_text_field',
            '_chroma_es_parents_menu3_title' => 'sanitize_text_field',
            'parents_menu3_subtitle' => 'sanitize_text_field',
            '_chroma_es_parents_menu3_subtitle' => 'sanitize_text_field',
            'parents_menu3_url' => 'esc_url_raw',
        ),
        'chroma_parents_safety_nonce' => array(
            'parents_safety_title' => 'sanitize_text_field',
            '_chroma_es_parents_safety_title' => 'sanitize_text_field',
            'parents_safety_description' => 'sanitize_textarea_field',
            '_chroma_es_parents_safety_description' => 'sanitize_textarea_field',
            'parents_safety1_icon' => 'sanitize_text_field',
            'parents_safety1_title' => 'sanitize_text_field',
            '_chroma_es_parents_safety1_title' => 'sanitize_text_field',
            'parents_safety1_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_safety1_desc' => 'sanitize_textarea_field',
            'parents_safety2_icon' => 'sanitize_text_field',
            'parents_safety2_title' => 'sanitize_text_field',
            '_chroma_es_parents_safety2_title' => 'sanitize_text_field',
            'parents_safety2_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_safety2_desc' => 'sanitize_textarea_field',
            'parents_safety3_icon' => 'sanitize_text_field',
            'parents_safety3_title' => 'sanitize_text_field',
            '_chroma_es_parents_safety3_title' => 'sanitize_text_field',
            'parents_safety3_desc' => 'sanitize_textarea_field',
            '_chroma_es_parents_safety3_desc' => 'sanitize_textarea_field',
        ),
        'chroma_parents_faq_nonce' => array(
            'parents_faq_title' => 'sanitize_text_field',
            '_chroma_es_parents_faq_title' => 'sanitize_text_field',
            'parents_faq_description' => 'sanitize_textarea_field',
            '_chroma_es_parents_faq_description' => 'sanitize_textarea_field',
            'parents_faq1_question' => 'sanitize_text_field',
            '_chroma_es_parents_faq1_question' => 'sanitize_text_field',
            'parents_faq1_answer' => 'sanitize_textarea_field',
            '_chroma_es_parents_faq1_answer' => 'sanitize_textarea_field',
            'parents_faq2_question' => 'sanitize_text_field',
            '_chroma_es_parents_faq2_question' => 'sanitize_text_field',
            'parents_faq2_answer' => 'sanitize_textarea_field',
            '_chroma_es_parents_faq2_answer' => 'sanitize_textarea_field',
            'parents_faq3_question' => 'sanitize_text_field',
            '_chroma_es_parents_faq3_question' => 'sanitize_text_field',
            'parents_faq3_answer' => 'sanitize_textarea_field',
            '_chroma_es_parents_faq3_answer' => 'sanitize_textarea_field',
        ),
        'chroma_parents_referral_nonce' => array(
            'parents_referral_title' => 'sanitize_text_field',
            '_chroma_es_parents_referral_title' => 'sanitize_text_field',
            'parents_referral_description' => 'sanitize_textarea_field',
            '_chroma_es_parents_referral_description' => 'sanitize_textarea_field',
            'parents_referral_button_text' => 'sanitize_text_field',
            '_chroma_es_parents_referral_button_text' => 'sanitize_text_field',
            'parents_referral_button_url' => 'esc_url_raw',
        ),
    );

    // Process each meta box
    foreach ($meta_boxes as $nonce_field => $fields) {
        if (!isset($_POST[$nonce_field])) {
            continue;
        }

        $nonce_action = str_replace('_nonce', '_meta', $nonce_field);
        if (!wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
            continue;
        }

        // Save each field
        foreach ($fields as $field_name => $sanitize_function) {
            if (isset($_POST[$field_name])) {
                $value = call_user_func($sanitize_function, $_POST[$field_name]);
                update_post_meta($post_id, $field_name, $value);
            }
        }
    }
}
add_action('save_post', 'chroma_save_parents_page_meta');

/**
 * Seed Default Values on Activation/First Save
 */
function chroma_seed_parents_page_defaults($post_id)
{
    if (get_post_type($post_id) !== 'page') {
        return;
    }
    // Only seed if verified template
    $template = get_post_meta($post_id, '_wp_page_template', true);
    if ('page-parents.php' !== $template && 'parents' !== $template) {
        return;
    }

    // Check if already seeded to avoid overwriting
    if (get_post_meta($post_id, '_parents_defaults_seeded', true)) {
        return;
    }

    $defaults = array(
        'parents_hero_badge' => 'Parents Zone',
        'parents_hero_title' => 'Partnering in your child\'s growth',
        'parents_hero_description' => 'We believe that education is a partnership between school and home. Here you\'ll find everything you need to stay connected and informed.',

        'parents_essentials_title' => 'Parent Essentials',

        'parents_events_badge' => 'Community',
        'parents_events_title' => 'Events & Celebrations',
        'parents_events_description' => 'Join us for our upcoming community events designed to bring families together.',
        'parents_events_image' => 'https://images.unsplash.com/photo-1543269865-cbf427effbad?q=80&w=1200&auto=format&fit=crop',

        'parents_event1_icon' => 'fa-solid fa-calendar-days',
        'parents_event1_title' => 'Quarterly Family Events',
        'parents_event1_desc' => 'Every season brings a reason to gather. From our Fall Festival and Winter "Cookies & Cocoa" to our Spring Art Show and Summer Splash Days, we create memories for the whole family.',

        'parents_event2_icon' => 'fa-solid fa-star',
        'parents_event2_title' => 'Pre-K Graduation',
        'parents_event2_desc' => 'A cap-and-gown ceremony celebrating our 4 and 5-year-olds as they transition to Kindergarten. It\'s the highlight of our academic year!',

        'parents_event3_icon' => 'fa-solid fa-handshake',
        'parents_event3_title' => 'Parent-Teacher Conferences',
        'parents_event3_desc' => 'Twice a year, we sit down to review your child\'s developmental portfolio, set goals, and celebrate their individual growth curve.',

        // Nutrition Section
        'parents_nutrition_badge' => 'Wellness',
        'parents_nutrition_title' => 'What\'s for lunch?',
        'parents_nutrition_description' => 'Our in-house chef prepares fresh, balanced meals daily. We accommodate allergies and dietary restrictions with care.',
        'parents_nutrition_image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?q=80&w=1200&auto=format&fit=crop',

        'parents_menu1_icon' => 'fa-solid fa-apple-whole',
        'parents_menu1_title' => 'Breakfast & Snacks',
        'parents_menu1_subtitle' => 'Whole grains & fresh fruit',
        'parents_menu1_url' => '#',

        'parents_menu2_icon' => 'fa-solid fa-utensils',
        'parents_menu2_title' => 'Hot Lunch',
        'parents_menu2_subtitle' => 'Protein, veggie & starch',
        'parents_menu2_url' => '#',

        'parents_menu3_icon' => 'fa-solid fa-carrot',
        'parents_menu3_title' => 'Allergen Info',
        'parents_menu3_subtitle' => 'Nut-free facility',
        'parents_menu3_url' => '#',

        // Safety
        'parents_safety_title' => 'Safety First',
        'parents_safety_description' => 'Your child\'s security is our top priority. We maintain strict protocols to ensure a safe learning environment.',

        'parents_safety1_icon' => 'fa-solid fa-video',
        'parents_safety1_title' => 'Secure Monitoring',
        'parents_safety1_desc' => 'Our facility is monitored 24/7 with secure access control systems and security cameras in common areas.',

        'parents_safety2_icon' => 'fa-solid fa-user-shield',
        'parents_safety2_title' => 'Trained Staff',
        'parents_safety2_desc' => 'All staff members are CPR/First Aid certified and undergo rigorous background checks before joining our team.',

        'parents_safety3_icon' => 'fa-solid fa-pump-medical',
        'parents_safety3_title' => 'Health Protocols',
        'parents_safety3_desc' => 'We follow strict sanitation guidelines and wellness policies to keep our community healthy and thriving.',

        // FAQ
        'parents_faq_title' => 'Frequently Asked Questions',
        'parents_faq_description' => 'Quick answers to common questions about our programs and policies.',

        'parents_faq1_question' => 'What is the teacher-to-student ratio?',
        'parents_faq1_answer' => 'We maintain low ratios to ensure individual attention. Infants 1:4, Toddlers 1:6, Preschool 1:10.',

        'parents_faq2_question' => 'Do you offer part-time care?',
        'parents_faq2_answer' => 'Yes! We offer 2-day, 3-day, and 5-day programs to accommodate different family schedules.',

        'parents_faq3_question' => 'What is the late pickup policy?',
        'parents_faq3_answer' => 'We close promptly at 6:00 PM. A late fee of $1 per minute is charged to your account for pickups after 6:05 PM to compensate our staff who stay late.',

        // Referral Banner
        'parents_referral_title' => 'Love the Chroma family?',
        'parents_referral_description' => 'Refer a friend and receive a <strong>$100 tuition credit</strong> when they enroll.',
        'parents_referral_button_text' => 'Refer a Friend',
        'parents_referral_button_url' => 'mailto:director@chromaela.com?subject=Parent%20Referral',
    );

    // Populate all default values
    foreach ($defaults as $meta_key => $default_value) {
        update_post_meta($post_id, $meta_key, $default_value);
    }

    // Mark as seeded
    update_post_meta($post_id, '_parents_defaults_seeded', '1');
}
add_action('save_post', 'chroma_seed_parents_page_defaults', 5);

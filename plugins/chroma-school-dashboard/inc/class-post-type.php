<?php

class Chroma_School_Post_Type
{
    public function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta']);
    }

    public function register_cpt()
    {
        register_post_type('chroma_school', [
            'labels' => [
                'name' => 'Schools',
                'singular_name' => 'School',
                'menu_name' => 'TV Dashboards',
                'add_new' => 'Add School',
                'add_new_item' => 'Add New School',
                'edit_item' => 'Edit School Config',
            ],
            'public' => false,
            'publicly_queryable' => true,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'rewrite' => ['slug' => 'tv'],
            'menu_icon' => 'dashicons-desktop',
            'has_archive' => false,
        ]);
    }

    public function add_meta_boxes()
    {
        add_meta_box('chroma_school_config', 'School Configuration', [$this, 'render_config_box'], 'chroma_school', 'normal', 'high');
        add_meta_box('chroma_school_content', 'School Content', [$this, 'render_content_box'], 'chroma_school', 'normal', 'high');
    }

    public function render_config_box($post)
    {
        $config = get_post_meta($post->ID, '_chroma_school_config', true) ?: [];
        $director_email = $config['director_email'] ?? '';
        $timezone = $config['timezone'] ?? 'America/New_York';
        $lat = $config['lat'] ?? '';
        $lon = $config['lon'] ?? '';

        wp_nonce_field('chroma_school_save', 'chroma_school_nonce');
        ?>
        <p><strong>Config</strong></p>
        <label>Director Google Email: <input type="email" name="chroma_director_email"
                value="<?php echo esc_attr($director_email); ?>" class="widefat"></label><br><br>
        <label>Timezone: <input type="text" name="chroma_timezone" value="<?php echo esc_attr($timezone); ?>"
                class="widefat"></label><br><br>
        <label>Latitude: <input type="text" name="chroma_lat" value="<?php echo esc_attr($lat); ?>"
                class="widefat"></label><br><br>
        <label>Longitude: <input type="text" name="chroma_lon" value="<?php echo esc_attr($lon); ?>" class="widefat"></label>
        <?php
    }

    public function render_content_box($post)
    {
        $newsletter = get_post_meta($post->ID, '_chroma_school_newsletter', true) ?: ['title' => '', 'url' => '', 'body' => ''];
        $eom = get_post_meta($post->ID, '_chroma_school_eom', true) ?: ['name' => '', 'photo_url' => '', 'role' => '', 'classroom' => '', 'blurb' => ''];
        $slideshow = get_post_meta($post->ID, '_chroma_school_slideshow', true) ?: [];
        $slideshow_title = get_post_meta($post->ID, '_chroma_school_slideshow_title', true) ?: 'Highlights';
        
        // Simple textarea for JSON fallback or basic fields
        ?>
        <h3>Newsletter</h3>
        <label>Title <input type="text" name="chroma_newsletter_title" value="<?php echo esc_attr($newsletter['title']); ?>" class="widefat"></label>
        <label>URL (QR Code) <input type="text" name="chroma_newsletter_url" value="<?php echo esc_attr($newsletter['url']); ?>" class="widefat"></label>
        <label>Body <textarea name="chroma_newsletter_body" class="widefat" rows="3"><?php echo esc_textarea($newsletter['body']); ?></textarea></label>

        <h3>Star Educator</h3>
        <label>Name <input type="text" name="chroma_eom_name" value="<?php echo esc_attr($eom['name'] ?? ''); ?>" class="widefat"></label>
        <label>Photo URL <input type="text" name="chroma_eom_photo" value="<?php echo esc_attr($eom['photo_url'] ?? ''); ?>" class="widefat"></label>
        
        <h3>Slideshow</h3>
        <label>Title <input type="text" name="chroma_slideshow_title" value="<?php echo esc_attr($slideshow_title); ?>" class="widefat"></label>
        <p>Images (One URL per line)</p>
        <textarea name="chroma_slideshow_urls" class="widefat" rows="5"><?php echo esc_textarea(implode("\n", $slideshow)); ?></textarea>
        <?php
    }

    public function save_meta($post_id)
    {
        if (!isset($_POST['chroma_school_nonce']) || !wp_verify_nonce($_POST['chroma_school_nonce'], 'chroma_school_save')) {
            return;
        }

        // Config
        $config = [
            'director_email' => sanitize_email($_POST['chroma_director_email']),
            'timezone' => sanitize_text_field($_POST['chroma_timezone']),
            'lat' => sanitize_text_field($_POST['chroma_lat']),
            'lon' => sanitize_text_field($_POST['chroma_lon']),
        ];
        update_post_meta($post_id, '_chroma_school_config', $config);
        update_post_meta($post_id, '_chroma_school_director_email', $config['director_email']);

        // Content
        $newsletter = [
            'title' => sanitize_text_field($_POST['chroma_newsletter_title']),
            'url' => esc_url_raw($_POST['chroma_newsletter_url']),
            'body' => sanitize_textarea_field($_POST['chroma_newsletter_body']),
        ];
        update_post_meta($post_id, '_chroma_school_newsletter', $newsletter);

        $eom = [
            'name' => sanitize_text_field($_POST['chroma_eom_name']),
            'photo_url' => esc_url_raw($_POST['chroma_eom_photo']),
            // ... add others if needed
        ];
        update_post_meta($post_id, '_chroma_school_eom', $eom);
        
        update_post_meta($post_id, '_chroma_school_slideshow_title', sanitize_text_field($_POST['chroma_slideshow_title']));

        $slides = explode("\n", $_POST['chroma_slideshow_urls']);
        $slides = array_map('trim', $slides);
        $slides = array_filter($slides);
        update_post_meta($post_id, '_chroma_school_slideshow', $slides);
    }
}

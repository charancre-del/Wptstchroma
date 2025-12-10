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
        <p><strong>Config (Admin Only)</strong></p>
        <label>Director Google Email: <input type="email" name="chroma_director_email"
                value="<?php echo esc_attr($director_email); ?>" class="widefat"></label><br><br>
        <label>Timezone: <input type="text" name="chroma_timezone" value="<?php echo esc_attr($timezone); ?>"
                class="widefat"></label><br><br>
        <label>Latitude: <input type="text" name="chroma_lat" value="<?php echo esc_attr($lat); ?>"
                class="widefat"></label><br><br>
        <label>Longitude: <input type="text" name="chroma_lon" value="<?php echo esc_attr($lon); ?>" class="widefat"></label>
        <?php
    }

    public function save_meta($post_id)
    {
        if (!isset($_POST['chroma_school_nonce']) || !wp_verify_nonce($_POST['chroma_school_nonce'], 'chroma_school_save')) {
            return;
        }

        $config = [
            'director_email' => sanitize_email($_POST['chroma_director_email']),
            'timezone' => sanitize_text_field($_POST['chroma_timezone']),
            'lat' => sanitize_text_field($_POST['chroma_lat']),
            'lon' => sanitize_text_field($_POST['chroma_lon']),
        ];

        update_post_meta($post_id, '_chroma_school_config', $config);
    }
}

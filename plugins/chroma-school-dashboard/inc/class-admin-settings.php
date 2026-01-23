<?php

class Chroma_School_Admin_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=chroma_school',
            'Global Dashboard Settings',
            'Global Settings',
            'manage_options',
            'chroma_school_settings',
            [$this, 'settings_page_html']
        );
    }

    public function register_settings()
    {
        register_setting('chroma_school_global', 'chroma_global_cares');
        register_setting('chroma_school_global', 'chroma_global_alert');
        register_setting('chroma_school_global', 'chroma_google_client_id', 'sanitize_text_field');
    }

    public function settings_page_html()
    {
        ?>
        <div class="wrap">
            <h1>Global Dashboard Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('chroma_school_global');
                do_settings_sections('chroma_school_global');

                $cares = get_option('chroma_global_cares', []);
                $alert = get_option('chroma_global_alert', []);
                $google_client_id = get_option('chroma_google_client_id', '');
                ?>

                <h2>API Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th>Google Client ID</th>
                        <td>
                            <input type="password" name="chroma_google_client_id"
                                value="<?php echo esc_attr($google_client_id); ?>" class="large-text">
                            <p class="description">Required for Director Portal login.</p>
                        </td>
                    </tr>
                </table>

                <h2>Chroma Cares</h2>
                <table class="form-table">
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="chroma_global_cares[title]"
                                value="<?php echo esc_attr($cares['title'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Body</th>
                        <td><textarea name="chroma_global_cares[body]" rows="4"
                                cols="50"><?php echo esc_textarea($cares['body'] ?? ''); ?></textarea></td>
                    </tr>
                </table>

                <h2>Global Alert</h2>
                <label>
                    <input type="checkbox" name="chroma_global_alert[enabled]" value="1" <?php checked(1, $alert['enabled'] ?? 0); ?>> Enabled
                </label>
                <br>
                <input type="text" name="chroma_global_alert[message]" value="<?php echo esc_attr($alert['message'] ?? ''); ?>"
                    class="large-text" placeholder="Alert Message">

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

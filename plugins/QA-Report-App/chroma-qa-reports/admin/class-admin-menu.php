<?php
/**
 * Admin Menu
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Admin;

/**
 * Handles admin menu registration and page rendering.
 */
class Admin_Menu
{

    /**
     * Menu slug.
     *
     * @var string
     */
    const MENU_SLUG = 'chroma-qa-reports';

    /**
     * Register admin menu.
     */
    public function register_menu()
    {
        // Main menu
        add_menu_page(
            __('QA Reports', 'chroma-qa-reports'),
            __('QA Reports', 'chroma-qa-reports'),
            'cqa_view_own_reports',
            self::MENU_SLUG,
            [$this, 'render_dashboard'],
            'dashicons-clipboard',
            30
        );

        // Dashboard (same as main)
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'chroma-qa-reports'),
            __('Dashboard', 'chroma-qa-reports'),
            'cqa_view_own_reports',
            self::MENU_SLUG,
            [$this, 'render_dashboard']
        );

        // Schools
        add_submenu_page(
            self::MENU_SLUG,
            __('Schools', 'chroma-qa-reports'),
            __('Schools', 'chroma-qa-reports'),
            'cqa_manage_schools',
            self::MENU_SLUG . '-schools',
            [$this, 'render_schools']
        );

        // All Reports
        add_submenu_page(
            self::MENU_SLUG,
            __('All Reports', 'chroma-qa-reports'),
            __('All Reports', 'chroma-qa-reports'),
            'cqa_view_all_reports',
            self::MENU_SLUG . '-reports',
            [$this, 'render_reports']
        );

        // Create New Report
        add_submenu_page(
            self::MENU_SLUG,
            __('Create Report', 'chroma-qa-reports'),
            __('Create Report', 'chroma-qa-reports'),
            'cqa_create_reports',
            self::MENU_SLUG . '-create',
            [$this, 'render_create_report']
        );

        // Import Reports
        add_submenu_page(
            self::MENU_SLUG,
            __('Import Report', 'chroma-qa-reports'),
            __('Import Report', 'chroma-qa-reports'),
            'cqa_create_reports',
            self::MENU_SLUG . '-import',
            [$this, 'render_legacy_import']
        );

        // Settings (admin only)
        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'chroma-qa-reports'),
            __('Settings', 'chroma-qa-reports'),
            'cqa_manage_settings',
            self::MENU_SLUG . '-settings',
            [$this, 'render_settings']
        );

        // Tools & Integrity
        add_submenu_page(
            self::MENU_SLUG,
            __('System Tools', 'chroma-qa-reports'),
            __('System Tools', 'chroma-qa-reports'),
            'cqa_manage_settings',
            self::MENU_SLUG . '-tools',
            [$this, 'render_tools']
        );

        // Help & Guide
        add_submenu_page(
            self::MENU_SLUG,
            __('Help & Guide', 'chroma-qa-reports'),
            __('Help & Guide', 'chroma-qa-reports'),
            'read', // Available to all users
            self::MENU_SLUG . '-docs',
            [$this, 'render_documentation']
        );

        // Hidden pages (edit/view)
        add_submenu_page(
            null, // Hidden
            __('View Report', 'chroma-qa-reports'),
            __('View Report', 'chroma-qa-reports'),
            'cqa_view_own_reports',
            self::MENU_SLUG . '-view',
            [$this, 'render_view_report']
        );

        add_submenu_page(
            null,
            __('Edit School', 'chroma-qa-reports'),
            __('Edit School', 'chroma-qa-reports'),
            'cqa_manage_schools',
            self::MENU_SLUG . '-school-edit',
            [$this, 'render_school_edit']
        );
    }

    /**
     * Enqueue admin styles.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_styles($hook)
    {
        // Only load on our pages
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'cqa-admin-styles',
            CQA_PLUGIN_URL . 'admin/css/admin-styles.css',
            [],
            CQA_VERSION
        );

        // Mobile styles (Phase 14)
        wp_enqueue_style(
            'cqa-mobile-styles',
            CQA_PLUGIN_URL . 'admin/css/mobile-styles.css',
            ['cqa-admin-styles'],
            CQA_VERSION
        );

        // Google Fonts - REMOVED for GDPR/Performance (QAR-038)
        // Using system font stack in CSS instead.
        // wp_enqueue_style('cqa-google-fonts', ...);
    }

    /**
     * Enqueue admin scripts.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_script(
            'cqa-admin-scripts',
            CQA_PLUGIN_URL . 'admin/js/admin-scripts.js',
            ['jquery'],
            CQA_VERSION,
            true
        );

        wp_localize_script('cqa-admin-scripts', 'cqaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('cqa/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'googleClientId' => get_option('cqa_google_client_id'),
            'developerKey' => get_option('cqa_google_developer_key'),
            'debug' => defined('CQA_DEBUG') ? CQA_DEBUG : false,
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this?', 'chroma-qa-reports'),
                'saving' => __('Saving...', 'chroma-qa-reports'),
                'saved' => __('Saved!', 'chroma-qa-reports'),
                'error' => __('An error occurred.', 'chroma-qa-reports'),
            ],
        ]);

        // Report wizard script on create page
        if (strpos($hook, 'create') !== false) {
            wp_enqueue_script(
                'cqa-report-wizard',
                CQA_PLUGIN_URL . 'admin/js/report-wizard.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            // Phase 12: Duplicate report & keyboard navigation
            wp_enqueue_script(
                'cqa-duplicate-report',
                CQA_PLUGIN_URL . 'admin/js/duplicate-report.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            wp_enqueue_script(
                'cqa-keyboard-nav',
                CQA_PLUGIN_URL . 'admin/js/keyboard-nav.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            // Phase 14: Voice to text & offline
            wp_enqueue_script(
                'cqa-voice-to-text',
                CQA_PLUGIN_URL . 'admin/js/voice-to-text.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            wp_enqueue_script(
                'cqa-offline-manager',
                CQA_PLUGIN_URL . 'admin/js/offline-manager.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            // Phase 15: Photo annotator
            wp_enqueue_script(
                'cqa-photo-annotator',
                CQA_PLUGIN_URL . 'admin/js/photo-annotator.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            // Item-level photo uploader
            wp_enqueue_script(
                'cqa-item-photo-uploader',
                CQA_PLUGIN_URL . 'admin/js/item-photo-uploader.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );

            // Localize with admin URL for all scripts
            wp_localize_script('cqa-report-wizard', 'cqaAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'adminUrl' => admin_url('admin.php'),
                'restUrl' => rest_url('cqa/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'pluginUrl' => CQA_PLUGIN_URL,
                'debug' => defined('CQA_DEBUG') ? CQA_DEBUG : false,
                'strings' => [
                    'confirm_delete' => __('Are you sure you want to delete this?', 'chroma-qa-reports'),
                    'saving' => __('Saving...', 'chroma-qa-reports'),
                    'saved' => __('Saved!', 'chroma-qa-reports'),
                    'error' => __('An error occurred.', 'chroma-qa-reports'),
                    'unsaved_warning' => __('You have unsaved changes. Are you sure you want to leave?', 'chroma-qa-reports'),
                ],
            ]);
        }

        // Phase 15: School map on dashboard
        if (strpos($hook, self::MENU_SLUG) !== false && strpos($hook, 'create') === false) {
            wp_enqueue_script(
                'cqa-school-map',
                CQA_PLUGIN_URL . 'admin/js/school-map.js',
                ['jquery', 'cqa-admin-scripts'],
                CQA_VERSION,
                true
            );
        }
    }

    /**
     * Check if React UI is enabled for a specific route.
     *
     * @param string $route Route name (dashboard, schools, reports, wizard, settings).
     * @return bool
     */
    public function is_react_enabled($route = 'dashboard')
    {
        $flag_name = 'cqa_flag_react_' . $route;
        return (bool) get_option($flag_name, false);
    }

    /**
     * Enqueue React app scripts and styles.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_react_app($hook)
    {
        // Only load on main QA Reports page when React is enabled
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }

        // Check if any React feature is enabled
        if (!$this->is_react_enabled('dashboard')) {
            return;
        }

        $asset_file = CQA_PLUGIN_DIR . 'build/index.asset.php';

        if (!file_exists($asset_file)) {
            // React build not available, fall back to legacy
            return;
        }

        $assets = include $asset_file;

        // Enqueue React app styles
        wp_enqueue_style(
            'cqa-react-app',
            CQA_PLUGIN_URL . 'build/index.css',
            [],
            $assets['version']
        );

        // Enqueue React app scripts
        wp_enqueue_script(
            'cqa-runtime-guard',
            CQA_PLUGIN_URL . 'public/js/cqa-runtime-guard.js',
            [],
            CQA_VERSION,
            true
        );

        wp_enqueue_script(
            'cqa-react-app',
            CQA_PLUGIN_URL . 'build/index.js',
            array_merge(['cqa-runtime-guard'], $assets['dependencies']),
            $assets['version'],
            true
        );

        // Get current user data and capabilities
        $user = wp_get_current_user();
        $capabilities = $this->get_user_capabilities($user);

        // Localize script with data React needs
        wp_localize_script('cqa-react-app', 'cqaData', [
            'restUrl' => rest_url('cqa/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url('admin.php'),
            'pluginUrl' => CQA_PLUGIN_URL,
            'debug' => defined('CQA_DEBUG') ? CQA_DEBUG : false,
            'user' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => $this->get_user_role_label($user),
            ],
            'capabilities' => $capabilities,
            'flags' => [
                'dashboard' => $this->is_react_enabled('dashboard'),
                'schools' => $this->is_react_enabled('schools'),
                'reports' => $this->is_react_enabled('reports'),
                'wizard' => $this->is_react_enabled('wizard'),
                'settings' => $this->is_react_enabled('settings'),
            ],
            'settings' => [
                'googleConnected' => !empty(get_user_meta($user->ID, 'cqa_google_access_token', true)),
            ],
        ]);
    }

    /**
     * Get user capabilities for QA Reports.
     *
     * @param \WP_User $user User object.
     * @return array
     */
    private function get_user_capabilities($user)
    {
        $cqa_caps = [
            'cqa_view_own_reports',
            'cqa_view_all_reports',
            'cqa_create_reports',
            'cqa_edit_all_reports',
            'cqa_delete_reports',
            'cqa_approve_reports',
            'cqa_manage_schools',
            'cqa_manage_settings',
        ];

        $capabilities = [];
        foreach ($cqa_caps as $cap) {
            $capabilities[$cap] = $user->has_cap($cap);
        }

        return $capabilities;
    }

    /**
     * Get human-readable role label for user.
     *
     * @param \WP_User $user User object.
     * @return string
     */
    private function get_user_role_label($user)
    {
        $role_labels = [
            'cqa_super_admin' => 'Super Admin',
            'cqa_regional_director' => 'Regional Director',
            'cqa_qa_officer' => 'QA Officer',
            'cqa_program_management' => 'Program Management',
            'administrator' => 'Administrator',
        ];

        foreach ($user->roles as $role) {
            if (isset($role_labels[$role])) {
                return $role_labels[$role];
            }
        }

        return 'User';
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard()
    {
        // If React is enabled, render the React app container
        if ($this->is_react_enabled('dashboard')) {
            $this->render_react_app();
            return;
        }
        // Otherwise, fall back to legacy PHP view
        include CQA_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render the React application container.
     * The React app will mount to this element.
     */
    public function render_react_app()
    {
        ?>
        <div class="wrap cqa-react-wrap">
            <div id="cqa-react-app" role="application"
                aria-label="<?php esc_attr_e('QA Reports Application', 'chroma-qa-reports'); ?>">
                <div class="cqa-loading-placeholder"
                    style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
                    <div style="text-align: center;">
                        <span class="spinner is-active" style="float: none;"></span>
                        <p><?php esc_html_e('Loading QA Reports...', 'chroma-qa-reports'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render schools list page.
     */
    public function render_schools()
    {
        include CQA_PLUGIN_DIR . 'admin/views/schools-list.php';
    }

    /**
     * Render school edit page.
     */
    public function render_school_edit()
    {
        include CQA_PLUGIN_DIR . 'admin/views/school-edit.php';
    }

    /**
     * Render reports list page.
     */
    public function render_reports()
    {
        include CQA_PLUGIN_DIR . 'admin/views/reports-list.php';
    }

    /**
     * Render create report page.
     */
    public function render_create_report()
    {
        include CQA_PLUGIN_DIR . 'admin/views/report-create.php';
    }

    /**
     * Render legacy import page.
     */
    public function render_legacy_import()
    {
        include CQA_PLUGIN_DIR . 'admin/views/legacy-import.php';
    }

    /**
     * Render view report page.
     */
    public function render_view_report()
    {
        include CQA_PLUGIN_DIR . 'admin/views/report-view.php';
    }

    /**
     * Render documentation page.
     */
    public function render_documentation()
    {
        include CQA_PLUGIN_DIR . 'admin/views/documentation.php';
    }

    /**
     * Render tools page.
     */
    public function render_tools()
    {
        include CQA_PLUGIN_DIR . 'admin/views/tools.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings()
    {
        // Handle form submission
        if (isset($_POST['cqa_settings_nonce']) && wp_verify_nonce($_POST['cqa_settings_nonce'], 'cqa_save_settings')) {
            $this->save_settings();
        }

        include CQA_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Save settings.
     */
    private function save_settings()
    {
        $fields = [
            'google_client_id',
            'google_client_secret',
            'google_developer_key',
            'sso_domain',
            'sso_default_role',
            'gemini_api_key',
            'drive_root_folder',
            'company_name',
            'google_maps_api_key',
        ];

        foreach ($fields as $field) {
            if (isset($_POST["cqa_{$field}"])) {
                $value = sanitize_text_field($_POST["cqa_{$field}"]);

                // Special handling for domain
                if ($field === 'sso_domain') {
                    $value = preg_replace('#^https?://(?:www\.)?#i', '', $value);
                    $value = rtrim($value, '/');
                }

                // Special handling for Drive Folder (URL to ID)
                if ($field === 'drive_root_folder') {
                    // Extract ID from URL like https://drive.google.com/drive/folders/1abc123...
                    if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $value, $matches)) {
                        $value = $matches[1];
                    }
                }

                // Masking check: If value is all asterisks, do not update (keep existing)
                if (in_array($field, ['google_client_secret', 'gemini_api_key', 'google_developer_key', 'google_maps_api_key'])) {
                    if (preg_match('/^\*+$/', $value)) {
                        continue;
                    }
                }

                update_option("cqa_{$field}", $value);
            }
        }

        // Handle Feature Flags
        $flags = \ChromaQA\Feature_Flags::FLAGS;
        foreach ($flags as $flag => $config) {
            // Status
            $enabled = isset($_POST["cqa_flag_{$flag}"]);
            update_option("cqa_flag_{$flag}", $enabled);

            // Audience
            if (isset($_POST["cqa_flag_{$flag}_audience"])) {
                $audience = sanitize_text_field($_POST["cqa_flag_{$flag}_audience"]);
                update_option("cqa_flag_{$flag}_audience", $audience);
            }

            // Allowlist (Canary)
            if (isset($_POST["cqa_flag_{$flag}_allowlist"])) {
                $allowlist_str = sanitize_text_field($_POST["cqa_flag_{$flag}_allowlist"]);
                $allowlist_ids = array_filter(array_map('intval', explode(',', $allowlist_str)));
                update_option("cqa_flag_{$flag}_allowlist", $allowlist_ids);
            }
        }

        add_settings_error(
            'cqa_settings',
            'settings_saved',
            __('Settings saved successfully.', 'chroma-qa-reports'),
            'success'
        );
    }
}

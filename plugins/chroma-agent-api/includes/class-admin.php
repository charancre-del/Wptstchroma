<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    private const MENU_SLUG = 'chroma-agent-api';

    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);

        add_action('admin_post_caa_create_key', [__CLASS__, 'handle_create_key']);
        add_action('admin_post_caa_rotate_key', [__CLASS__, 'handle_rotate_key']);
        add_action('admin_post_caa_revoke_key', [__CLASS__, 'handle_revoke_key']);
        add_action('admin_post_caa_toggle_enabled', [__CLASS__, 'handle_toggle_enabled']);
    }

    public static function register_menu(): void
    {
        add_management_page(
            'Chroma Agent API',
            'Chroma Agent API',
            'manage_options',
            self::MENU_SLUG,
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'chroma-agent-api'));
        }

        $notice = get_transient(self::notice_key(get_current_user_id()));
        if ($notice !== false) {
            delete_transient(self::notice_key(get_current_user_id()));
        }

        $new_key_data = get_transient(self::new_key_key(get_current_user_id()));
        if ($new_key_data !== false) {
            delete_transient(self::new_key_key(get_current_user_id()));
        }

        $enabled = Utils::truthy(get_option(Utils::OPTION_ENABLED, 1));
        $keys = Key_Store::list_keys(200, 0);

        $default_scopes = [
            'read:content',
            'write:content',
            'read:theme',
            'write:theme',
            'read:seo',
            'write:seo',
            'read:media',
            'write:media',
            'admin:keys',
            'admin:audit',
        ];

        ?>
        <div class="wrap">
            <h1>Chroma Agent API</h1>
            <p>Manage machine-to-machine API keys for automation agents.</p>

            <?php if (is_array($notice) && !empty($notice['message'])): ?>
                <div class="notice <?php echo esc_attr(($notice['type'] ?? 'notice-info')); ?> is-dismissible">
                    <p><?php echo esc_html((string) $notice['message']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (is_array($new_key_data) && !empty($new_key_data['key'])): ?>
                <div class="notice notice-warning">
                    <p><strong>New API key (shown once):</strong></p>
                    <p><code style="user-select: all;"><?php echo esc_html((string) $new_key_data['key']); ?></code></p>
                    <p>Store this key now. It cannot be retrieved later.</p>
                </div>
            <?php endif; ?>

            <h2>Service Status</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('caa_toggle_enabled'); ?>
                <input type="hidden" name="action" value="caa_toggle_enabled">
                <label>
                    <input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>>
                    Enable Agent API routes
                </label>
                <?php submit_button('Save Status', 'secondary', 'submit', false); ?>
            </form>

            <hr>

            <h2>Create API Key</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('caa_create_key'); ?>
                <input type="hidden" name="action" value="caa_create_key">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="caa_label">Label</label></th>
                        <td><input type="text" class="regular-text" id="caa_label" name="label" value="IDE Agent" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="caa_scopes">Scopes</label></th>
                        <td>
                            <textarea class="large-text code" rows="5" id="caa_scopes" name="scopes" required><?php echo esc_textarea(implode(',', $default_scopes)); ?></textarea>
                            <p class="description">Comma or newline separated scopes.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="caa_rate">Rate limit / min</label></th>
                        <td><input type="number" class="small-text" id="caa_rate" name="rate_limit_per_min" min="1" max="10000" value="120"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="caa_expires">Expires at (optional)</label></th>
                        <td><input type="datetime-local" id="caa_expires" name="expires_at"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="caa_ips">IP allowlist (optional)</label></th>
                        <td>
                            <textarea class="large-text code" rows="3" id="caa_ips" name="ip_allowlist"></textarea>
                            <p class="description">One IP per line.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Create Key'); ?>
            </form>

            <hr>

            <h2>Existing Keys</h2>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Label</th>
                    <th>Prefix</th>
                    <th>Scopes</th>
                    <th>Status</th>
                    <th>Rate</th>
                    <th>Expires</th>
                    <th>Last Used</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($keys)): ?>
                    <tr><td colspan="9">No keys found.</td></tr>
                <?php else: ?>
                    <?php foreach ($keys as $row): ?>
                        <tr>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo esc_html((string) $row['label']); ?></td>
                            <td><code><?php echo esc_html((string) $row['key_prefix']); ?></code></td>
                            <td><?php echo esc_html(implode(', ', (array) ($row['scopes'] ?? []))); ?></td>
                            <td><?php echo esc_html((string) $row['status']); ?></td>
                            <td><?php echo (int) $row['rate_limit_per_min']; ?></td>
                            <td><?php echo esc_html((string) ($row['expires_at'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($row['last_used_at'] ?? '')); ?></td>
                            <td>
                                <?php if (($row['status'] ?? '') === 'active'): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:8px;">
                                        <?php wp_nonce_field('caa_rotate_key_' . (int) $row['id']); ?>
                                        <input type="hidden" name="action" value="caa_rotate_key">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button type="submit" class="button button-secondary">Rotate</button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                        <?php wp_nonce_field('caa_revoke_key_' . (int) $row['id']); ?>
                                        <input type="hidden" name="action" value="caa_revoke_key">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button type="submit" class="button button-link-delete" onclick="return confirm('Revoke this key?');">Revoke</button>
                                    </form>
                                <?php else: ?>
                                    <em>Revoked</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function handle_create_key(): void
    {
        self::require_manage_options();
        check_admin_referer('caa_create_key');

        $label = isset($_POST['label']) ? sanitize_text_field((string) wp_unslash($_POST['label'])) : '';
        $scopes_raw = isset($_POST['scopes']) ? (string) wp_unslash($_POST['scopes']) : '';
        $rate = isset($_POST['rate_limit_per_min']) ? (int) $_POST['rate_limit_per_min'] : 120;
        $expires_raw = isset($_POST['expires_at']) ? sanitize_text_field((string) wp_unslash($_POST['expires_at'])) : '';
        $ip_raw = isset($_POST['ip_allowlist']) ? (string) wp_unslash($_POST['ip_allowlist']) : '';

        $scopes = preg_split('/[\s,]+/', $scopes_raw) ?: [];
        $scopes = array_values(array_filter(array_map('trim', $scopes)));

        $ips = preg_split('/[\r\n,]+/', $ip_raw) ?: [];
        $ips = array_values(array_filter(array_map('trim', $ips), static function ($ip) {
            return filter_var($ip, FILTER_VALIDATE_IP);
        }));

        $expires = $expires_raw !== '' ? str_replace('T', ' ', $expires_raw) : null;

        $created = Key_Store::create_key($label, $scopes, $expires, $rate, get_current_user_id(), $ips);
        if (is_wp_error($created)) {
            self::set_notice('notice-error', $created->get_error_message());
            self::redirect_back();
        }

        Audit_Log::log_write([
            'actor_key_id' => 0,
            'scope' => 'admin:keys-ui',
            'method' => 'POST',
            'route' => '/wp-admin/tools.php?page=' . self::MENU_SLUG,
            'target_type' => 'api_key',
            'target_id' => (string) $created['id'],
            'dry_run' => false,
            'before' => null,
            'after' => [
                'id' => $created['id'],
                'label' => $created['label'],
                'scopes' => $created['scopes'],
                'rate_limit_per_min' => $created['rate_limit_per_min'],
                'expires_at' => $created['expires_at'],
            ],
            'diff' => ['created' => true],
            'status_code' => 201,
        ]);

        set_transient(self::new_key_key(get_current_user_id()), ['key' => (string) $created['key']], 5 * MINUTE_IN_SECONDS);
        self::set_notice('notice-success', 'API key created successfully.');
        self::redirect_back();
    }

    public static function handle_rotate_key(): void
    {
        self::require_manage_options();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            self::set_notice('notice-error', 'Invalid key id.');
            self::redirect_back();
        }

        check_admin_referer('caa_rotate_key_' . $id);

        $rotated = Key_Store::rotate_key($id);
        if (is_wp_error($rotated)) {
            self::set_notice('notice-error', $rotated->get_error_message());
            self::redirect_back();
        }

        Audit_Log::log_write([
            'actor_key_id' => 0,
            'scope' => 'admin:keys-ui',
            'method' => 'POST',
            'route' => '/wp-admin/tools.php?page=' . self::MENU_SLUG,
            'target_type' => 'api_key',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => ['rotated' => false],
            'after' => ['rotated' => true],
            'diff' => ['rotated' => ['from' => false, 'to' => true]],
            'status_code' => 200,
        ]);

        set_transient(self::new_key_key(get_current_user_id()), ['key' => (string) $rotated['key']], 5 * MINUTE_IN_SECONDS);
        self::set_notice('notice-success', 'API key rotated successfully.');
        self::redirect_back();
    }

    public static function handle_revoke_key(): void
    {
        self::require_manage_options();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            self::set_notice('notice-error', 'Invalid key id.');
            self::redirect_back();
        }

        check_admin_referer('caa_revoke_key_' . $id);

        $ok = Key_Store::revoke_key($id);
        if (!$ok) {
            self::set_notice('notice-error', 'Failed to revoke key.');
            self::redirect_back();
        }

        Audit_Log::log_write([
            'actor_key_id' => 0,
            'scope' => 'admin:keys-ui',
            'method' => 'POST',
            'route' => '/wp-admin/tools.php?page=' . self::MENU_SLUG,
            'target_type' => 'api_key',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => ['status' => 'active'],
            'after' => ['status' => 'revoked'],
            'diff' => ['status' => ['from' => 'active', 'to' => 'revoked']],
            'status_code' => 200,
        ]);

        self::set_notice('notice-success', 'API key revoked.');
        self::redirect_back();
    }

    public static function handle_toggle_enabled(): void
    {
        self::require_manage_options();
        check_admin_referer('caa_toggle_enabled');

        $enabled = isset($_POST['enabled']) && (string) $_POST['enabled'] === '1';
        $before = Utils::truthy(get_option(Utils::OPTION_ENABLED, 1));
        update_option(Utils::OPTION_ENABLED, $enabled ? 1 : 0, false);

        Audit_Log::log_write([
            'actor_key_id' => 0,
            'scope' => 'admin:keys-ui',
            'method' => 'POST',
            'route' => '/wp-admin/tools.php?page=' . self::MENU_SLUG,
            'target_type' => 'agent_api_status',
            'target_id' => 'chroma_agent_api_enabled',
            'dry_run' => false,
            'before' => ['enabled' => $before],
            'after' => ['enabled' => $enabled],
            'diff' => ['enabled' => ['from' => $before, 'to' => $enabled]],
            'status_code' => 200,
        ]);

        self::set_notice('notice-success', $enabled ? 'Agent API enabled.' : 'Agent API disabled.');
        self::redirect_back();
    }

    private static function require_manage_options(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage Agent API keys.', 'chroma-agent-api'));
        }
    }

    private static function set_notice(string $type, string $message): void
    {
        set_transient(self::notice_key(get_current_user_id()), [
            'type' => $type,
            'message' => $message,
        ], MINUTE_IN_SECONDS * 2);
    }

    private static function redirect_back(): void
    {
        $url = add_query_arg([
            'page' => self::MENU_SLUG,
        ], admin_url('tools.php'));

        wp_safe_redirect($url);
        exit;
    }

    private static function notice_key(int $user_id): string
    {
        return 'caa_admin_notice_' . $user_id;
    }

    private static function new_key_key(int $user_id): string
    {
        return 'caa_admin_key_once_' . $user_id;
    }
}

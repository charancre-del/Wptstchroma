<?php
/**
 * Settings View
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Admin;

// Get current settings
// Helper to mask secrets
$mask_secret = function($val) {
    return !empty($val) ? '********************' : '';
};

$google_client_id = get_option('cqa_google_client_id', '');
$google_client_secret = $mask_secret(get_option('cqa_google_client_secret', ''));
$gemini_api_key = $mask_secret(get_option('cqa_gemini_api_key', ''));
$drive_root_folder = get_option('cqa_drive_root_folder', '');
$company_name = get_option('cqa_company_name', 'Chroma Early Learning Academy');
$google_maps_api_key = $mask_secret(get_option('cqa_google_maps_api_key', ''));
$google_developer_key = $mask_secret(get_option('cqa_google_developer_key', ''));
?>

<div class="wrap cqa-wrap">
    <div class="cqa-header">
        <h1 class="cqa-title">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Settings', 'chroma-qa-reports'); ?>
        </h1>
    </div>

    <?php settings_errors('cqa_settings'); ?>

    <form method="post" action="" class="cqa-settings-form">
        <?php wp_nonce_field('cqa_save_settings', 'cqa_settings_nonce'); ?>

        <div class="cqa-settings-grid">
            <!-- Google OAuth -->
            <div class="cqa-card">
                <div class="cqa-card-header">
                    <h2>
                        <span class="dashicons dashicons-google"></span>
                        <?php esc_html_e('Google OAuth Settings', 'chroma-qa-reports'); ?>
                    </h2>
                </div>
                <div class="cqa-card-body">
                    <p class="description">
                        <?php esc_html_e('Configure Google OAuth 2.0 for user authentication. Create credentials at', 'chroma-qa-reports'); ?>
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud
                            Console</a>.
                    </p>
                    <p class="description">
                        <strong><?php esc_html_e('Authorized Redirect URI:', 'chroma-qa-reports'); ?></strong>
                        <code><?php echo esc_url(home_url('/qa-reports/auth/callback/')); ?></code>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_google_client_id"><?php esc_html_e('Client ID', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="cqa_google_client_id" name="cqa_google_client_id"
                                    value="<?php echo esc_attr($google_client_id); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_google_client_secret"><?php esc_html_e('Client Secret', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="cqa_google_client_secret" name="cqa_google_client_secret"
                                    value="<?php echo esc_attr($google_client_secret); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_google_developer_key"><?php esc_html_e('Developer Key (API Key)', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="cqa_google_developer_key" name="cqa_google_developer_key"
                                    value="<?php echo esc_attr(get_option('cqa_google_developer_key', '')); ?>"
                                    class="regular-text">
                                <p class="description">
                                    <?php esc_html_e('Required for Google Picker and Maps. Create an API key in Google Cloud Console.', 'chroma-qa-reports'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_sso_domain"><?php esc_html_e('Allowed Domain (SSO)', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="cqa_sso_domain" name="cqa_sso_domain"
                                    value="<?php echo esc_attr(get_option('cqa_sso_domain', 'chromaela.com')); ?>"
                                    class="regular-text" placeholder="chromaela.com">
                                <p class="description">
                                    <?php esc_html_e('Only allow Google Sign-In from this email domain (leave empty to disable auto-registration).', 'chroma-qa-reports'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_sso_default_role"><?php esc_html_e('Default Role (SSO)', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <select id="cqa_sso_default_role" name="cqa_sso_default_role">
                                    <?php
                                    $current_role = get_option('cqa_sso_default_role', 'cqa_qa_officer');
                                    $roles = \ChromaQA\Auth\User_Roles::get_roles();
                                    foreach ($roles as $key => $label):
                                        ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($current_role, $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Role assigned to new users created via Google SSO.', 'chroma-qa-reports'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <div class="cqa-oauth-status">
                        <?php if (!empty($google_client_id) && !empty($google_client_secret)): ?>
                            <span class="cqa-status-badge success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Credentials Configured', 'chroma-qa-reports'); ?>
                            </span>
                        <?php else: ?>
                            <span class="cqa-status-badge warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Not Configured', 'chroma-qa-reports'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Google Drive -->
            <div class="cqa-card">
                <div class="cqa-card-header">
                    <h2>
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php esc_html_e('Google Drive Settings', 'chroma-qa-reports'); ?>
                    </h2>
                </div>
                <div class="cqa-card-body">
                    <p class="description">
                        <?php esc_html_e('Configure the root folder for storing QA photos and documents.', 'chroma-qa-reports'); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_drive_root_folder"><?php esc_html_e('Root Folder ID', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="cqa_drive_root_folder" name="cqa_drive_root_folder"
                                    value="<?php echo esc_attr($drive_root_folder); ?>" class="regular-text">
                                <p class="description">
                                    <?php esc_html_e('The ID of the Google Drive folder where school folders will be created.', 'chroma-qa-reports'); ?>
                                </p>
                                <?php if (!empty($google_client_id) && !empty($google_client_secret)): ?>
                                    <button type="button" class="button button-secondary" id="cqa-drive-picker-btn">
                                        <span class="dashicons dashicons-google"></span>
                                        <?php esc_html_e('Select Folder', 'chroma-qa-reports'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- AI Settings -->
            <div class="cqa-card">
                <div class="cqa-card-header">
                    <h2>
                        <span class="dashicons dashicons-superhero-alt"></span>
                        <?php esc_html_e('AI Settings (Gemini)', 'chroma-qa-reports'); ?>
                    </h2>
                </div>
                <div class="cqa-card-body">
                    <p class="description">
                        <?php esc_html_e('Configure the Gemini API for AI-powered features like executive summaries and document parsing.', 'chroma-qa-reports'); ?>
                        <a href="https://makersuite.google.com/app/apikey"
                            target="_blank"><?php esc_html_e('Get API Key', 'chroma-qa-reports'); ?></a>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_gemini_api_key"><?php esc_html_e('Gemini API Key', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="cqa_gemini_api_key" name="cqa_gemini_api_key"
                                    value="<?php echo esc_attr($gemini_api_key); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>

                    <div class="cqa-oauth-status">
                        <?php if (!empty($gemini_api_key)): ?>
                            <span class="cqa-status-badge success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('API Key Configured', 'chroma-qa-reports'); ?>
                            </span>
                        <?php else: ?>
                            <span class="cqa-status-badge warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Not Configured - AI features disabled', 'chroma-qa-reports'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Feature Flags -->
            <div class="cqa-card">
                <div class="cqa-card-header">
                    <h2>
                        <span class="dashicons dashicons-flag"></span>
                        <?php esc_html_e('Feature Flags (React Migration)', 'chroma-qa-reports'); ?>
                    </h2>
                </div>
                <div class="cqa-card-body">
                    <p class="description">
                        <?php esc_html_e('Gradually rollout React-based features to specific user groups.', 'chroma-qa-reports'); ?>
                    </p>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Feature', 'chroma-qa-reports'); ?></th>
                                <th><?php esc_html_e('Status', 'chroma-qa-reports'); ?></th>
                                <th><?php esc_html_e('Audience', 'chroma-qa-reports'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $flags = \ChromaQA\Feature_Flags::FLAGS;
                            foreach ($flags as $flag => $config):
                                $enabled = get_option("cqa_flag_{$flag}", $config['default']);
                                $audience = get_option("cqa_flag_{$flag}_audience", $config['audience']);
                                $allowlist = get_option("cqa_flag_{$flag}_allowlist", []);
                                if (is_array($allowlist)) {
                                    $allowlist = implode(', ', $allowlist);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html(ucwords(str_replace(['react_', '_'], ['', ' '], $flag))); ?></strong><br>
                                        <code>cqa_flag_<?php echo esc_html($flag); ?></code>
                                    </td>
                                    <td>
                                        <label class="cqa-switch">
                                            <input type="checkbox" name="cqa_flag_<?php echo esc_html($flag); ?>" value="1"
                                                <?php checked($enabled); ?>>
                                            <span class="cqa-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <select name="cqa_flag_<?php echo esc_html($flag); ?>_audience" style="width:100%;">
                                            <option value="admins" <?php selected($audience, 'admins'); ?>>
                                                <?php esc_html_e('Admins Only', 'chroma-qa-reports'); ?></option>
                                            <option value="canary" <?php selected($audience, 'canary'); ?>>
                                                <?php esc_html_e('Canary List', 'chroma-qa-reports'); ?></option>
                                            <option value="qa_officers" <?php selected($audience, 'qa_officers'); ?>>
                                                <?php esc_html_e('QA Officers', 'chroma-qa-reports'); ?></option>
                                            <option value="all" <?php selected($audience, 'all'); ?>>
                                                <?php esc_html_e('All Users', 'chroma-qa-reports'); ?></option>
                                        </select>

                                        <div class="cqa-canary-input"
                                            style="margin-top:5px; <?php echo $audience === 'canary' ? '' : 'display:none;'; ?>">
                                            <input type="text" name="cqa_flag_<?php echo esc_html($flag); ?>_allowlist"
                                                value="<?php echo esc_attr($allowlist); ?>"
                                                placeholder="User IDs (e.g. 1, 5, 23)" class="widefat">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- General Settings -->
            <div class="cqa-card">
                <div class="cqa-card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('General Settings', 'chroma-qa-reports'); ?>
                    </h2>
                </div>
                <div class="cqa-card-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_company_name"><?php esc_html_e('Company Name', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="cqa_company_name" name="cqa_company_name"
                                    value="<?php echo esc_attr($company_name); ?>" class="regular-text">
                                <p class="description">
                                    <?php esc_html_e('This will appear on exported PDF reports.', 'chroma-qa-reports'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="cqa_google_maps_api_key"><?php esc_html_e('Google Maps API Key', 'chroma-qa-reports'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="cqa_google_maps_api_key" name="cqa_google_maps_api_key"
                                    value="<?php echo esc_attr($google_maps_api_key); ?>" class="regular-text">
                                <p class="description">
                                    <?php esc_html_e('Required for school heat map and GPS verification features.', 'chroma-qa-reports'); ?>
                                    <a href="https://console.cloud.google.com/google/maps-apis"
                                        target="_blank"><?php esc_html_e('Get API Key', 'chroma-qa-reports'); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!-- System Status -->
            <div class="cqa-card">
                <div class="cqa-card-header">
                    <h2>
                        <span class="dashicons dashicons-performance"></span>
                        <?php esc_html_e('System Status', 'chroma-qa-reports'); ?>
                    </h2>
                </div>
                <div class="cqa-card-body">
                    <p class="description">
                        <?php esc_html_e('Technical requirements and environment status.', 'chroma-qa-reports'); ?>
                    </p>
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Requirement', 'chroma-qa-reports'); ?></th>
                                <th><?php esc_html_e('Status', 'chroma-qa-reports'); ?></th>
                                <th><?php esc_html_e('Details', 'chroma-qa-reports'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong><?php esc_html_e('PDF Generation', 'chroma-qa-reports'); ?></strong></td>
                                <td>
                                    <?php
                                    $pdf = new \ChromaQA\Export\PDF_Generator();
                                    $available_libs = $pdf->get_available_libraries();
                                    if (!empty($available_libs)):
                                        ?>
                                        <span class="cqa-status-badge success">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php echo esc_html(implode(', ', $available_libs)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="cqa-status-badge error">
                                            <span class="dashicons dashicons-no-alt"></span>
                                            <?php esc_html_e('Missing', 'chroma-qa-reports'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($available_libs)): ?>
                                        <code style="font-size: 11px;">composer require dompdf/dompdf</code>
                                    <?php else: ?>
                                        <?php esc_html_e('Ready for export.', 'chroma-qa-reports'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('PHP Version', 'chroma-qa-reports'); ?></strong></td>
                                <td>
                                    <?php if (version_compare(PHP_VERSION, '7.4', '>=')): ?>
                                        <span class="cqa-status-badge success"><?php echo esc_html(PHP_VERSION); ?></span>
                                    <?php else: ?>
                                        <span class="cqa-status-badge error"><?php echo esc_html(PHP_VERSION); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php esc_html_e('Min: 7.4', 'chroma-qa-reports'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Save Settings', 'chroma-qa-reports'); ?>
            </button>
        </p>
    </form>
</div>
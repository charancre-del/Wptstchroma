<?php
/**
 * Plugin Name: Chroma Capability Fixer
 * Description: Forces addition of QA Reports capabilities to Administrator.
 * Version: 1.0
 */

add_action('init', function () {
    $role = get_role('administrator');
    if ($role) {
        $caps = [
            'cqa_manage_settings',
            'cqa_manage_users',
            'cqa_manage_schools',
            'cqa_view_all_reports',
            'cqa_create_reports',
            'cqa_edit_all_reports',
            'cqa_delete_reports',
            'cqa_export_reports',
            'cqa_use_ai_features',
            'cqa_view_own_reports',
            'cqa_approve_reports'
        ];
        foreach ($caps as $cap)
            $role->add_cap($cap);

        // Log to debug.log if enabled
        error_log('Chroma Capability Fixer: Capabilities added to Administrator.');
    }
});

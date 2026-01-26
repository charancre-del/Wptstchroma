<?php
/**
 * Admin View: Tools
 *
 * @package ChromaQAReports
 */

use ChromaQA\Utils\Integrity_Checker;
use ChromaQA\Upgrade_Manager;

// Check capabilities
if (!current_user_can('manage_options')) {
    return;
}

$message = '';
$results = null;

// Handle Actions
if (isset($_POST['cqa_action']) && check_admin_referer('cqa_tools_action', 'cqa_nonce')) {
    $action = sanitize_text_field($_POST['cqa_action']);

    if ($action === 'run_integrity_check') {
        require_once CQA_PLUGIN_DIR . 'includes/utils/class-integrity-checker.php';
        $results = Integrity_Checker::run_all();
        $message = 'Integrity Check Completed.';
    } elseif ($action === 'force_migration') {
        require_once CQA_PLUGIN_DIR . 'includes/class-upgrade-manager.php';
        // Force upgrade by calling upgrade with a low version
        $manager = new Upgrade_Manager();
        // Since the method is static private normally, we might need to expose it or just re-run Activator logic.
        // Actually Upgrade_Manager::check_and_run() checks the version option.
        // To force it, we can temporarily lower the db version option.
        update_option('cqa_db_version', '0.0.0');
        Upgrade_Manager::check_and_run();
        $message = 'Migration/Upgrade Logic Executed.';
    }
}
?>

<div class="wrap">
    <h1>QA Reports System Tools</h1>

    <?php if ($message): ?>
        <div class="updated notice is-dismissible">
            <p>
                <?php echo esc_html($message); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2>System Health & Integrity</h2>
        <p>Run these checks to verify that legacy data is compatible and database integrity is intact.</p>

        <form method="post">
            <?php wp_nonce_field('cqa_tools_action', 'cqa_nonce'); ?>
            <input type="hidden" name="cqa_action" value="run_integrity_check">
            <button type="submit" class="button button-primary">Run Integrity Check</button>
        </form>

        <?php if ($results): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border: 1px solid #ccc;">
                <h3>Results: <span style="color: <?php echo $results['status'] === 'pass' ? 'green' : 'red'; ?>">
                        <?php echo strtoupper($results['status']); ?>
                    </span></h3>
                <?php if (empty($results['issues'])): ?>
                    <p>No issues found. All records appear linked correctly.</p>
                <?php else: ?>
                    <ul style="list-style: disc; margin-left: 20px; color: #d63638;">
                        <?php foreach ($results['issues'] as $issue): ?>
                            <li>
                                <?php echo esc_html($issue); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2>Database Migrations</h2>
        <p>If you suspect the database schema is outdated (e.g. missing columns), you can force the migration runner.
        </p>

        <form method="post">
            <?php wp_nonce_field('cqa_tools_action', 'cqa_nonce'); ?>
            <input type="hidden" name="cqa_action" value="force_migration">
            <button type="submit" class="button button-secondary">Force Schema Update</button>
        </form>
    </div>
</div>
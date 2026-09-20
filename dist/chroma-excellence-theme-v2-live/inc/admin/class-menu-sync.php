<?php
/**
 * Chroma Menu Sync Utility
 * 
 * Provides an admin interface to synchronize English menus to Spanish equivalents.
 * 
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Menu_Sync {

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('admin_init', [__CLASS__, 'process_sync']);
    }

    /**
     * Register the admin page
     */
    public static function register_admin_page() {
        add_management_page(
            __('Chroma Menu Sync', 'chroma-excellence'),
            __('Chroma Menu Sync', 'chroma-excellence'),
            'manage_options',
            'chroma-menu-sync',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Render the admin page
     */
    public static function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Chroma Menu Sync Utility', 'chroma-excellence'); ?></h1>
            <p><?php _e('Use this tool to duplicate your existing English menus into Spanish menu locations. This allows you to have a starting point for translating menu items.', 'chroma-excellence'); ?></p>
            
            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Sync Menus', 'chroma-excellence'); ?></h2>
                <p><?php _e('This will performed the following actions:', 'chroma-excellence'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('Look for menus assigned to "Primary", "Footer", and "Footer Contact" locations.', 'chroma-excellence'); ?></li>
                    <li><?php _e('Create new menus with " (ES)" suffix if they don\'t exist.', 'chroma-excellence'); ?></li>
                    <li><?php _e('Copy all menu items from the English menu to the Spanish menu.', 'chroma-excellence'); ?></li>
                    <li><?php _e('Assign the new menus to the corresponding Spanish theme locations.', 'chroma-excellence'); ?></li>
                </ul>
                
                <form method="post" action="">
                    <?php wp_nonce_field('chroma_menu_sync_action', 'chroma_menu_sync_nonce'); ?>
                    <p>
                        <label>
                            <input type="checkbox" name="force_overwrite" value="1"> 
                            <strong><?php _e('Force Overwrite:', 'chroma-excellence'); ?></strong> <?php _e('If checked, existing items in Spanish menus will be deleted and replaced with English items.', 'chroma-excellence'); ?>
                        </label>
                    </p>
                    <p class="submit">
                        <input type="submit" name="chroma_sync_menus" id="submit" class="button button-primary" value="<?php esc_attr_e('Sync Menus', 'chroma-excellence'); ?>">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Process the sync action
     */
    public static function process_sync() {
        if (!isset($_POST['chroma_sync_menus'])) {
            return;
        }

        if (!check_admin_referer('chroma_menu_sync_action', 'chroma_menu_sync_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $force_overwrite = isset($_POST['force_overwrite']) ? true : false;
        $locations = get_nav_menu_locations();
        $mappings = [
            'primary' => 'primary_es',
            'footer' => 'footer_es',
            'footer_contact' => 'footer_contact_es'
        ];

        $messages = [];

        foreach ($mappings as $en_loc => $es_loc) {
            
            // Validation: Does English menu exist?
            if (!isset($locations[$en_loc]) || $locations[$en_loc] == 0) {
                $messages[] = sprintf(__('Skipped "%s": No menu assigned to English location.', 'chroma-excellence'), $en_loc);
                continue;
            }

            $en_menu_id = $locations[$en_loc];
            $en_menu_obj = wp_get_nav_menu_object($en_menu_id);
            
            if (!$en_menu_obj) {
                continue;
            }

            // Target Spanish Menu Name
            $es_menu_name = $en_menu_obj->name . ' (ES)';
            
            // Check if Spanish menu exists
            $es_menu_obj = wp_get_nav_menu_object($es_menu_name);
            
            // Create if likely using the ID or Slug lookup failed, try simpler lookup
            if (!$es_menu_obj) {
                 // Try to find by name 
                 $es_menu_obj = get_term_by('name', $es_menu_name, 'nav_menu');
            }

            if (!$es_menu_obj) {
                // Create new menu
                $es_menu_id = wp_create_nav_menu($es_menu_name);
                if (is_wp_error($es_menu_id)) {
                    $messages[] = sprintf(__('Error creating menu "%s": %s', 'chroma-excellence'), $es_menu_name, $es_menu_id->get_error_message());
                    continue;
                }
                $es_menu_obj = wp_get_nav_menu_object($es_menu_id);
                $messages[] = sprintf(__('Created new menu: "%s"', 'chroma-excellence'), $es_menu_name);
            } else {
                $es_menu_id = $es_menu_obj->term_id;
                $messages[] = sprintf(__('Found existing menu: "%s"', 'chroma-excellence'), $es_menu_name);
            }

            // Sync Items
            $en_items = wp_get_nav_menu_items($en_menu_id);
            $es_items = wp_get_nav_menu_items($es_menu_id);

            if (!empty($es_items) && !$force_overwrite) {
                $messages[] = sprintf(__('Skipped syncing items for "%s": Menu is not empty and overwrite is disabled.', 'chroma-excellence'), $es_menu_name);
            } else {
                // Clear existing items if force overwrite
                if ($force_overwrite && !empty($es_items)) {
                    foreach ($es_items as $item) {
                        wp_delete_post($item->ID, true);
                    }
                }

                // Copy items
                if ($en_items) {
                    self::copy_menu_items($en_items, $es_menu_id);
                    $messages[] = sprintf(__('Synced %d items to "%s".', 'chroma-excellence'), count($en_items), $es_menu_name);
                }
            }

            // Assign Location
            $locations[$es_loc] = $es_menu_id;
        }

        set_theme_mod('nav_menu_locations', $locations);
        
        add_action('admin_notices', function() use ($messages) {
            echo '<div class="notice notice-success is-dismissible">';
            foreach ($messages as $msg) {
                echo '<p>' . esc_html($msg) . '</p>';
            }
            echo '</div>';
        });
    }

    /**
     * Recursive function to copy menu items
     */
    private static function copy_menu_items($items, $menu_id, $parent_id = 0) {
        // Map old IDs to new IDs to handle hierarchy
        static $id_map = [];
        if ($parent_id === 0) {
            $id_map = []; // Reset on top level call
        }

        foreach ($items as $item) {
            // Only process top level items first, then children
            if ($item->menu_item_parent != $parent_id && $parent_id === 0) {
                continue;
            }
            
            // If we are looking for children, ensure this item's parent corresponds to the current parent logic 
            //(This approach is simplistic for flat array, better to build tree or multipass)
        }

        // Simpler approach: Multipass or Tree builder
        // Pass 1: Create all items with 0 parent to establish ID map
        // Pass 2: Update parents
        
        // Actually, wp_get_nav_menu_items returns items sorted by order? Not guaranteed hierarchy order.
        // Let's iterate.
        
        // Simplified Logic: 
        // 1. Create all items
        // 2. Set parents
        
        $new_ids = [];
        
        // Create all items first
        foreach ($items as $item) {
            $args = [
                'menu-item-title' => $item->title,
                'menu-item-classes' => implode(' ', $item->classes),
                'menu-item-url' => $item->url,
                'menu-item-status' => $item->post_status,
                'menu-item-object' => $item->object,
                'menu-item-object-id' => $item->object_id,
                'menu-item-type' => $item->type,
                'menu-item-target' => $item->target,
                'menu-item-attr-title' => $item->attr_title,
                'menu-item-description' => $item->description,
                'menu-item-xfn' => $item->xfn,
                'menu-item-parent-id' => 0, // Set later
            ];
            
            $new_id = wp_update_nav_menu_item($menu_id, 0, $args);
            if (!is_wp_error($new_id)) {
                $new_ids[$item->ID] = $new_id;
            }
        }
        
        // Update Hierarchy
        foreach ($items as $item) {
            if ($item->menu_item_parent > 0 && isset($new_ids[$item->menu_item_parent]) && isset($new_ids[$item->ID])) {
                $args = [
                    'menu-item-parent-id' => $new_ids[$item->menu_item_parent]
                ];
                wp_update_nav_menu_item($menu_id, $new_ids[$item->ID], $args);
            }
        }
    }
}

Chroma_Menu_Sync::init();

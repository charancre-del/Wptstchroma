<?php
/**
 * Plugin Name: Chroma Lead Log
 * Description: Lead logging system for tour and acquisition inquiries
 * Version: 1.0.0
 * Author: Chroma Development Team
 * Text Domain: chroma-lead-log
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register Lead Log CPT
 */
function chroma_register_lead_log_cpt()
{
	$labels = array(
		'name' => 'Lead Log',
		'singular_name' => 'Lead',
		'menu_name' => 'Lead Log',
		'all_items' => 'All Leads',
		'view_item' => 'View Lead',
		'search_items' => 'Search Leads',
	);

	$args = array(
		'label' => 'Lead',
		'labels' => $labels,
		'supports' => array('title'),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-list-view',
		'menu_position' => 25,
		'capability_type' => 'post',
		'capabilities' => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap' => true,
	);

	register_post_type('lead_log', $args);
}
add_action('init', 'chroma_register_lead_log_cpt', 0);

/**
 * Add Admin Columns
 */
function chroma_lead_log_columns($columns)
{
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = 'Lead';
	$new_columns['lead_type'] = 'Type';
	$new_columns['lead_name'] = 'Name';
	$new_columns['lead_email'] = 'Email';
	$new_columns['lead_phone'] = 'Phone';
	$new_columns['date'] = 'Date';

	return $new_columns;
}
add_filter('manage_lead_log_posts_columns', 'chroma_lead_log_columns');

/**
 * Populate Admin Columns
 */
function chroma_lead_log_column_content($column, $post_id)
{
	switch ($column) {
		case 'lead_type':
			echo esc_html(ucfirst(get_post_meta($post_id, 'lead_type', true)));
			break;
		case 'lead_name':
			echo esc_html(get_post_meta($post_id, 'lead_name', true));
			break;
		case 'lead_email':
			$email = get_post_meta($post_id, 'lead_email', true);
			echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
			break;
		case 'lead_phone':
			echo esc_html(get_post_meta($post_id, 'lead_phone', true) ?: '—');
			break;
	}
}
add_action('manage_lead_log_posts_custom_column', 'chroma_lead_log_column_content', 10, 2);

/**
 * Make columns sortable
 */
function chroma_lead_log_sortable_columns($columns)
{
	$columns['lead_type'] = 'lead_type';
	return $columns;
}
add_filter('manage_edit-lead_log_sortable_columns', 'chroma_lead_log_sortable_columns');

/**
 * Add Meta Box for Lead Details
 */
function chroma_lead_log_add_meta_boxes()
{
	add_meta_box(
		'chroma_lead_details',
		'Lead Details',
		'chroma_lead_log_render_details',
		'lead_log',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'chroma_lead_log_add_meta_boxes');

function chroma_lead_log_render_details($post)
{
	$payload_json = get_post_meta($post->ID, 'lead_payload', true);
	$payload = json_decode($payload_json, true);

	if (!$payload || !is_array($payload)) {
		echo '<p>No detailed data available.</p>';
		return;
	}

	echo '<table class="widefat fixed" cellspacing="0">';
	echo '<thead><tr><th style="width: 30%;">Field</th><th>Value</th></tr></thead>';
	echo '<tbody>';

	foreach ($payload as $label => $value) {
		// Handle location ID to title conversion if needed (though usually stored as title/value in payload)
		// But the tour form stores raw values, so let's check if it looks like a location ID
		$display_value = esc_html($value);

		echo '<tr>';
		echo '<td><strong>' . esc_html($label) . '</strong></td>';
		echo '<td>' . $display_value . '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
}

/**
 * Register Lead Log Settings
 */
function chroma_lead_log_register_settings()
{
	register_setting('chroma_lead_log_options', 'chroma_lead_log_webhook_url', array(
		'type' => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default' => ''
	));
}
add_action('admin_init', 'chroma_lead_log_register_settings');

/**
 * Add Settings Page to Menu
 */
function chroma_lead_log_admin_menu()
{
	add_submenu_page(
		'edit.php?post_type=lead_log',
		'Lead Log Settings',
		'Settings',
		'manage_options',
		'chroma-lead-log-settings',
		'chroma_lead_log_settings_page'
	);
}
add_action('admin_menu', 'chroma_lead_log_admin_menu');

/**
 * Render Settings Page
 */
function chroma_lead_log_settings_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Lead Log Settings</h1>
		<form action="options.php" method="post">
			<?php
			settings_fields('chroma_lead_log_options');
			do_settings_sections('chroma_lead_log_options');
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Global Webhook URL (GoHighLevel)</th>
					<td>
						<input type="url" name="chroma_lead_log_webhook_url"
							value="<?php echo esc_attr(get_option('chroma_lead_log_webhook_url')); ?>" class="regular-text"
							placeholder="https://services.leadconnectorhq.com/hooks/..." />
						<p class="description">
							Enter your GoHighLevel (or other CRM) webhook URL here. All new leads (from any form) will be
							posted to this URL as JSON.
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<div class="card" style="max-width: 600px; margin-top: 20px;">
			<h2 class="title">Webhook Payload Example</h2>
			<p>When a new lead is created, the following JSON data is sent to your webhook:</p>
			<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px; overflow-x: auto;">
	{
	  "event": "new_lead",
	  "lead_id": 123,
	  "lead_title": "Tour: John Doe",
	  "lead_type": "tour",
	  "lead_name": "John Doe",
	  "lead_email": "john@example.com",
	  "lead_phone": "555-0199",
	  "submitted_at": "2024-03-20 14:30:00",
	  "data": {
		"Parent Name": "John Doe",
		"Phone": "555-0199",
		"Email": "john@example.com",
		"Preferred Location": "Atlanta Campus"
	  }
	}
				</pre>
		</div>
	</div>
	<?php
}

/**
 * Trigger Webhook on New Lead Creation
 */
function chroma_lead_log_trigger_webhook($post_id, $post, $update)
{
	// Only run for lead_log post type
	if ($post->post_type !== 'lead_log') {
		return;
	}

	// Only run on creation (not update), or if specifically desired, logic can be adjusted.
	// However, wp_insert_post fires even on updates. We often want to avoid firing multiple times.
	// A simple check is to see if it's a revision or autosave.
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}

	// Check if this is a valid published post
	if ($post->post_status !== 'publish') {
		return;
	}

	// Prevent infinite loops if updating post meta triggers save again
	// (though we are just reading here, not saving back to post)

	// Check if webhook is configured
	$webhook_url = get_option('chroma_lead_log_webhook_url');
	if (empty($webhook_url)) {
		return;
	}

	// Delay slightly to ensure meta is saved (if this hook fires before meta save)
	// 'save_post' fires after post is saved, but meta might be added separately.
	// Using 'updated_post_meta' might be cleaner, but 'save_post' with a slight check is standard.
	// Since the form plugins use wp_insert_post with meta_input, the meta should be there.

	$lead_type = get_post_meta($post_id, 'lead_type', true);
	$lead_name = get_post_meta($post_id, 'lead_name', true);
	$lead_email = get_post_meta($post_id, 'lead_email', true);
	$lead_phone = get_post_meta($post_id, 'lead_phone', true); // Some forms might not save this top-level yet, but payload has it.
	$payload_json = get_post_meta($post_id, 'lead_payload', true);
	$payload_data = json_decode($payload_json, true) ?: array();

	// If lead type is missing, it might be an empty draft or initial save
	if (empty($lead_type) && empty($payload_data)) {
		return;
	}

	// Avoid duplicate sends? (Optional: check a 'webhook_sent' meta flag)
	if (get_post_meta($post_id, '_chroma_webhook_sent', true)) {
		return;
	}

	// Prepare Payload
	$body = array(
		'event' => 'new_lead',
		'lead_id' => $post_id,
		'lead_title' => $post->post_title,
		'lead_type' => $lead_type,
		'lead_name' => $lead_name,
		'lead_email' => $lead_email,
		'lead_phone' => $lead_phone, // Might be empty if not explicitly saved as meta key
		'submitted_at' => current_time('mysql'),
		'data' => $payload_data
	);

	// Send Request
	$response = wp_remote_post($webhook_url, array(
		'body' => wp_json_encode($body),
		'headers' => array('Content-Type' => 'application/json'),
		'timeout' => 15,
		'blocking' => false // Don't wait, just fire
	));

	// Mark as sent
	update_post_meta($post_id, '_chroma_webhook_sent', time());
}
add_action('save_post', 'chroma_lead_log_trigger_webhook', 10, 3);

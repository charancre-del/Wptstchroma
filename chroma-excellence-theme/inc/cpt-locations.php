<?php
/**
 * Custom Post Type: Locations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register Location CPT
 */
function chroma_register_location_cpt()
{
	$labels = array(
		'name' => _x('Locations', 'Post Type General Name', 'chroma-excellence'),
		'singular_name' => _x('Location', 'Post Type Singular Name', 'chroma-excellence'),
		'menu_name' => __('Locations', 'chroma-excellence'),
		'all_items' => __('All Locations', 'chroma-excellence'),
		'add_new_item' => __('Add New Location', 'chroma-excellence'),
		'edit_item' => __('Edit Location', 'chroma-excellence'),
		'view_item' => __('View Location', 'chroma-excellence'),
	);

	$args = array(
		'label' => __('Location', 'chroma-excellence'),
		'labels' => $labels,
		'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
		'public' => true,
		'menu_position' => 21,
		'menu_icon' => 'dashicons-location',
		'has_archive' => 'locations',
		'rewrite' => array('slug' => 'locations'),
		'show_in_rest' => true,
	);

	register_post_type('location', $args);
}
add_action('init', 'chroma_register_location_cpt', 0);

/**
 * Register Location taxonomy (counties/regions)
 */
function chroma_register_location_taxonomy()
{
	$labels = array(
		'name' => _x('Location Regions', 'taxonomy general name', 'chroma-excellence'),
		'singular_name' => _x('Location Region', 'taxonomy singular name', 'chroma-excellence'),
		'search_items' => __('Search Location Regions', 'chroma-excellence'),
		'all_items' => __('All Location Regions', 'chroma-excellence'),
		'parent_item' => __('Parent Region', 'chroma-excellence'),
		'parent_item_colon' => __('Parent Region:', 'chroma-excellence'),
		'edit_item' => __('Edit Region', 'chroma-excellence'),
		'update_item' => __('Update Region', 'chroma-excellence'),
		'add_new_item' => __('Add New Region', 'chroma-excellence'),
		'new_item_name' => __('New Region Name', 'chroma-excellence'),
		'menu_name' => __('Location Regions', 'chroma-excellence'),
	);

	register_taxonomy(
		'location_region',
		array('location'),
		array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_rest' => true,
			'publicly_queryable' => false,
			'query_var' => false,
			'rewrite' => false,
			'default_term' => array(
				'name' => __('Uncategorized Locations', 'chroma-excellence'),
				'slug' => 'uncategorized-locations',
			),
		)
	);
}
add_action('init', 'chroma_register_location_taxonomy', 1);

/**
 * Add admin columns
 */
function chroma_location_admin_columns($columns)
{
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = $columns['title'];
	$new_columns['city'] = __('City', 'chroma-excellence');
	$new_columns['state'] = __('State', 'chroma-excellence');
	$new_columns['phone'] = __('Phone', 'chroma-excellence');
	$new_columns['capacity'] = __('Capacity', 'chroma-excellence');
	$new_columns['date'] = $columns['date'];

	return $new_columns;
}
add_filter('manage_location_posts_columns', 'chroma_location_admin_columns');

/**
 * Populate admin columns
 */
function chroma_location_admin_column_content($column, $post_id)
{
	switch ($column) {
		case 'city':
			echo esc_html(get_post_meta($post_id, 'location_city', true) ?: '—');
			break;
		case 'state':
			echo esc_html(get_post_meta($post_id, 'location_state', true) ?: '—');
			break;
		case 'phone':
			echo esc_html(get_post_meta($post_id, 'location_phone', true) ?: '—');
			break;
		case 'capacity':
			$capacity = get_post_meta($post_id, 'location_capacity', true);
			$enrollment = get_post_meta($post_id, 'location_enrollment', true);
			if ($capacity) {
				echo esc_html($enrollment . ' / ' . $capacity);
			} else {
				echo '—';
			}
			break;
	}
}
add_action('manage_location_posts_custom_column', 'chroma_location_admin_column_content', 10, 2);

/**
 * Make columns sortable
 */
function chroma_location_sortable_columns($columns)
{
	$columns['city'] = 'city';
	$columns['state'] = 'state';
	return $columns;
}
add_filter('manage_edit-location_sortable_columns', 'chroma_location_sortable_columns');

/**
 * Custom title placeholder
 */
function chroma_location_title_placeholder($title)
{
	$screen = get_current_screen();
	if ('location' === $screen->post_type) {
		$title = __('e.g., Johns Creek Campus', 'chroma-excellence');
	}
	return $title;
}
add_filter('enter_title_here', 'chroma_location_title_placeholder');

/**
 * Add custom fields to location_region taxonomy
 */
function chroma_location_region_add_form_fields()
{
	?>
	<div class="form-field">
		<label for="region_color_bg"><?php _e('Background Color Class', 'chroma-excellence'); ?></label>
		<input type="text" name="region_color_bg" id="region_color_bg" value="chroma-greenLight"
			placeholder="e.g., chroma-greenLight">
		<p class="description">
			<?php _e('Tailwind background color class (e.g., chroma-greenLight, chroma-redLight, chroma-blueLight, chroma-yellowLight, chroma-purpleLight, chroma-orangeLight, chroma-tealLight)', 'chroma-excellence'); ?>
		</p>
	</div>
	<div class="form-field">
		<label for="region_color_text"><?php _e('Text Color Class', 'chroma-excellence'); ?></label>
		<input type="text" name="region_color_text" id="region_color_text" value="chroma-green"
			placeholder="e.g., chroma-green">
		<p class="description">
			<?php _e('Tailwind text color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow, chroma-purple, chroma-orange, chroma-teal)', 'chroma-excellence'); ?>
		</p>
	</div>
	<div class="form-field">
		<label for="region_color_border"><?php _e('Border Color Class', 'chroma-excellence'); ?></label>
		<input type="text" name="region_color_border" id="region_color_border" value="chroma-green"
			placeholder="e.g., chroma-green">
		<p class="description">
			<?php _e('Tailwind border color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow, chroma-purple, chroma-orange, chroma-teal)', 'chroma-excellence'); ?>
		</p>
	</div>
	<?php
}
add_action('location_region_add_form_fields', 'chroma_location_region_add_form_fields');

/**
 * Add custom fields to location_region taxonomy edit form
 */
function chroma_location_region_edit_form_fields($term)
{
	$color_bg = get_term_meta($term->term_id, 'region_color_bg', true);
	$color_text = get_term_meta($term->term_id, 'region_color_text', true);
	$color_border = get_term_meta($term->term_id, 'region_color_border', true);
	?>
	<tr class="form-field">
		<th scope="row"><label for="region_color_bg"><?php _e('Background Color Class', 'chroma-excellence'); ?></label>
		</th>
		<td>
			<input type="text" name="region_color_bg" id="region_color_bg"
				value="<?php echo esc_attr($color_bg ?: 'chroma-greenLight'); ?>">
			<p class="description">
				<?php _e('Tailwind background color class (e.g., chroma-greenLight, chroma-redLight, chroma-blueLight, chroma-yellowLight, chroma-purpleLight, chroma-orangeLight, chroma-tealLight)', 'chroma-excellence'); ?>
			</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="region_color_text"><?php _e('Text Color Class', 'chroma-excellence'); ?></label></th>
		<td>
			<input type="text" name="region_color_text" id="region_color_text"
				value="<?php echo esc_attr($color_text ?: 'chroma-green'); ?>">
			<p class="description">
				<?php _e('Tailwind text color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow, chroma-purple, chroma-orange, chroma-teal)', 'chroma-excellence'); ?>
			</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="region_color_border"><?php _e('Border Color Class', 'chroma-excellence'); ?></label>
		</th>
		<td>
			<input type="text" name="region_color_border" id="region_color_border"
				value="<?php echo esc_attr($color_border ?: 'chroma-green'); ?>">
			<p class="description">
				<?php _e('Tailwind border color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow, chroma-purple, chroma-orange, chroma-teal)', 'chroma-excellence'); ?>
			</p>
		</td>
	</tr>
	<?php
}
add_action('location_region_edit_form_fields', 'chroma_location_region_edit_form_fields');

/**
 * Save location_region taxonomy custom fields
 */
function chroma_save_location_region_meta($term_id)
{
	if (isset($_POST['region_color_bg'])) {
		update_term_meta($term_id, 'region_color_bg', sanitize_text_field($_POST['region_color_bg']));
	}
	if (isset($_POST['region_color_text'])) {
		update_term_meta($term_id, 'region_color_text', sanitize_text_field($_POST['region_color_text']));
	}
	if (isset($_POST['region_color_border'])) {
		update_term_meta($term_id, 'region_color_border', sanitize_text_field($_POST['region_color_border']));
	}
}
add_action('created_location_region', 'chroma_save_location_region_meta');
add_action('edited_location_region', 'chroma_save_location_region_meta');

/**
 * Add meta box for location custom fields
 */
function chroma_location_custom_fields_meta_box()
{
	add_meta_box(
		'chroma-location-custom-fields',
		__('Location Details', 'chroma-excellence'),
		'chroma_render_location_custom_fields_meta_box',
		'location',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'chroma_location_custom_fields_meta_box');

/**
 * Render location custom fields meta box
 */
function chroma_render_location_custom_fields_meta_box($post)
{
	wp_nonce_field('chroma_location_meta_nonce', 'chroma_location_meta_nonce_field');

	// Get existing values
	$hero_subtitle = get_post_meta($post->ID, 'location_hero_subtitle', true);
	$hero_review_text = get_post_meta($post->ID, 'location_hero_review_text', true);
	$hero_review_author = get_post_meta($post->ID, 'location_hero_review_author', true);
	$hero_gallery = get_post_meta($post->ID, 'location_hero_gallery', true);
	$virtual_tour_embed = get_post_meta($post->ID, 'location_virtual_tour_embed', true);
	$tagline = get_post_meta($post->ID, 'location_tagline', true);
	$description = get_post_meta($post->ID, 'location_description', true);
	$google_rating = get_post_meta($post->ID, 'location_google_rating', true);
	$hours = get_post_meta($post->ID, 'location_hours', true);
	$ages_served = get_post_meta($post->ID, 'location_ages_served', true);
	$director_name = get_post_meta($post->ID, 'location_director_name', true);
	$director_heading = get_post_meta($post->ID, 'location_director_heading', true);
	$director_bio = get_post_meta($post->ID, 'location_director_bio', true);
	$director_photo = get_post_meta($post->ID, 'location_director_photo', true);
	$director_signature = get_post_meta($post->ID, 'location_director_signature', true);
	$maps_embed = get_post_meta($post->ID, 'location_maps_embed', true);
	$tour_booking_link = get_post_meta($post->ID, 'location_tour_booking_link', true);
	$school_pickups = get_post_meta($post->ID, 'location_school_pickups', true);
	$seo_content_title = get_post_meta($post->ID, 'location_seo_content_title', true);
	$seo_content_text = get_post_meta($post->ID, 'location_seo_content_text', true);
	$address = get_post_meta($post->ID, 'location_address', true);
	$city = get_post_meta($post->ID, 'location_city', true);
	$state = get_post_meta($post->ID, 'location_state', true);
	$zip = get_post_meta($post->ID, 'location_zip', true);
	$phone = get_post_meta($post->ID, 'location_phone', true);
	$email = get_post_meta($post->ID, 'location_email', true);
	$latitude = get_post_meta($post->ID, 'location_latitude', true);
	$longitude = get_post_meta($post->ID, 'location_longitude', true);
	$service_areas = get_post_meta($post->ID, 'location_service_areas', true);
	$gmb_url = get_post_meta($post->ID, 'location_gmb_url', true);
	?>
	<style>
		.chroma-meta-field {
			margin-bottom: 20px;
		}

		.chroma-meta-field label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
		}

		.chroma-meta-field input[type="text"],
		.chroma-meta-field textarea {
			width: 100%;
		}

		.chroma-meta-field small {
			display: block;
			margin-top: 5px;
			color: #666;
			font-style: italic;
		}

		.chroma-meta-section {
			border-top: 1px solid #ddd;
			padding-top: 20px;
			margin-top: 20px;
		}

		.chroma-meta-section h4 {
			margin-top: 0;
			margin-bottom: 15px;
			font-size: 14px;
			font-weight: 600;
			text-transform: uppercase;
			color: #555;
		}

		.chroma-icon-preview {
			display: inline-flex;
			align-items: center;
			gap: 10px;
			padding: 10px 15px;
			background: #f0f0f1;
			border-radius: 4px;
			margin-top: 10px;
			font-size: 13px;
		}

		.chroma-icon-preview i {
			font-size: 16px;
			color: #2271b1;
		}

		.chroma-image-preview img {
			max-width: 200px;
			height: auto;
			margin-top: 10px;
			border: 1px solid #ddd;
			padding: 5px;
			border-radius: 4px;
		}
	</style>

	<div class="chroma-meta-section" style="border-top: none; padding-top: 0; margin-top: 0;">
		<div
			style="background: #e7f5ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #2271b1;">
			<p style="margin: 0 0 10px 0; font-weight: 600;">
				<i class="fa-solid fa-info-circle"></i> Frontend Icons Preview
			</p>
			<p style="margin: 0; font-size: 13px; color: #555;">
				The following icons will appear on your location page:
			</p>
			<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
				<div class="chroma-icon-preview">
					<i class="fa-solid fa-location-dot"></i>
					<span>Address</span>
				</div>
				<div class="chroma-icon-preview">
					<i class="fa-solid fa-phone"></i>
					<span>Phone/Email</span>
				</div>
				<div class="chroma-icon-preview">
					<i class="fa-solid fa-clock"></i>
					<span>Hours</span>
				</div>
				<div class="chroma-icon-preview">
					<i class="fa-solid fa-bus"></i>
					<span>School Pickups</span>
				</div>
			</div>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Hero Section', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_hero_subtitle"><?php _e('Hero Subtitle', 'chroma-excellence'); ?></label>
			<input type="text" id="location_hero_subtitle" name="location_hero_subtitle"
				value="<?php echo esc_attr($hero_subtitle); ?>" placeholder="e.g., Now Enrolling: Pre-K & Toddlers" />
			<small><?php _e('Small badge text shown above the location name', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_hero_review_text"><?php _e('Hero Review Text', 'chroma-excellence'); ?></label>
			<textarea id="location_hero_review_text" name="location_hero_review_text" rows="3"
				placeholder="The best decision we made for our daughter..."><?php echo esc_textarea($hero_review_text); ?></textarea>
			<small><?php _e('Review text displayed in the floating badge (leave empty to hide)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_hero_review_author"><?php _e('Hero Review Author', 'chroma-excellence'); ?></label>
			<input type="text" id="location_hero_review_author" name="location_hero_review_author"
				value="<?php echo esc_attr($hero_review_author); ?>" placeholder="Parent Review" />
			<small><?php _e('Author of the review (defaults to "Parent Review" if empty)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_hero_gallery"><?php _e('Hero Image Gallery (URLs)', 'chroma-excellence'); ?></label>
			<textarea id="location_hero_gallery" name="location_hero_gallery" rows="4"
				placeholder="Enter image URLs, one per line:&#10;https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg"><?php echo esc_textarea($hero_gallery); ?></textarea>
			<small><?php _e('Enter image URLs (one per line). If provided, these will display as a carousel in the hero section. Leave empty to use the featured image.', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_virtual_tour_embed"><?php _e('Virtual Tour Embed Code', 'chroma-excellence'); ?></label>
			<textarea id="location_virtual_tour_embed" name="location_virtual_tour_embed" rows="6"
				placeholder="Paste your virtual tour embed code (iframe, script, etc.):&#10;&lt;iframe src=&quot;https://example.com/tour&quot; width=&quot;100%&quot; height=&quot;600&quot;&gt;&lt;/iframe&gt;"><?php echo esc_textarea($virtual_tour_embed); ?></textarea>
			<small><?php _e('Paste the full embed code for your virtual tour. If provided, this will display on the location page. If empty, no space will be created on the page.', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_tagline"><?php _e('Tagline', 'chroma-excellence'); ?></label>
			<input type="text" id="location_tagline" name="location_tagline" value="<?php echo esc_attr($tagline); ?>"
				placeholder="e.g., Lawrenceville's home for brilliant beginnings." />
			<small><?php _e('Main tagline for this location (last 2 words will be italicized)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_description"><?php _e('Description', 'chroma-excellence'); ?></label>
			<textarea id="location_description" name="location_description" rows="3"
				placeholder="Short description of this location..."><?php echo esc_textarea($description); ?></textarea>
			<small><?php _e('Brief description shown in hero section', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Location Stats', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_ages_served"><?php _e('Ages Served', 'chroma-excellence'); ?></label>
			<input type="text" id="location_ages_served" name="location_ages_served"
				value="<?php echo esc_attr($ages_served); ?>" placeholder="e.g., 6w - 12y" />
			<small><?php _e('Age range served at this location', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_special_programs"><?php _e('Special Programs (Badges)', 'chroma-excellence'); ?></label>
			<textarea id="location_special_programs" name="location_special_programs" rows="2"
				placeholder="e.g., GA Pre-K, Summer Camp"><?php echo esc_textarea(get_post_meta($post->ID, 'location_special_programs', true)); ?></textarea>
			<small><?php _e('Enter programs separated by commas. These appear as badges on the location card.', 'chroma-excellence'); ?></small>
		</div>

	<div class="chroma-meta-field">
		<label for="location_quality_rated">
			<input type="checkbox" id="location_quality_rated" name="location_quality_rated" value="1"
				<?php checked(get_post_meta($post->ID, 'location_quality_rated', true), '1'); ?> />
			<?php _e('Quality Rated by Georgia DECAL', 'chroma-excellence'); ?>
		</label>
		<small><?php _e('Check if this location has achieved Georgia\'s Quality Rated status', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-meta-field">
		<label for="_chroma_license_number"><?php _e('DECAL License Number', 'chroma-excellence'); ?></label>
		<input type="text" id="_chroma_license_number" name="_chroma_license_number"
			value="<?php echo esc_attr(get_post_meta($post->ID, '_chroma_license_number', true)); ?>" placeholder="e.g., CCLC-12345" />
		<small><?php _e('Enter the official Georgia DECAL license number for this facility', 'chroma-excellence'); ?></small>
	</div>
		<div class="chroma-meta-field">
			<label for="location_google_rating"><?php _e('Google Rating', 'chroma-excellence'); ?></label>
			<input type="text" id="location_google_rating" name="location_google_rating"
				value="<?php echo esc_attr($google_rating); ?>" placeholder="e.g., 4.9" />
			<small><?php _e('Google rating for this location (e.g., 4.9)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_hours"><?php _e('Hours', 'chroma-excellence'); ?></label>
			<input type="text" id="location_hours" name="location_hours" value="<?php echo esc_attr($hours); ?>"
				placeholder="e.g., 7am - 6pm" />
			<small><?php _e('Operating hours (Mon-Fri)', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Campus Director', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_director_name"><?php _e('Director Name', 'chroma-excellence'); ?></label>
			<input type="text" id="location_director_name" name="location_director_name"
				value="<?php echo esc_attr($director_name); ?>" placeholder="e.g., Sarah Williams" />
			<small><?php _e('Name of the campus director (leave empty to hide director section)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_director_heading"><?php _e('Director Section Heading', 'chroma-excellence'); ?></label>
			<input type="text" id="location_director_heading" name="location_director_heading"
				value="<?php echo esc_attr($director_heading); ?>" placeholder="e.g., Welcome to Chroma Marietta." />
			<small><?php _e('Headline for the director section (fallback: Welcome to Chroma [City].)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_director_bio"><?php _e('Director Bio', 'chroma-excellence'); ?></label>
			<textarea id="location_director_bio" name="location_director_bio" rows="4"
				placeholder="Brief bio of the director..."><?php echo esc_textarea($director_bio); ?></textarea>
			<small><?php _e('Brief bio/welcome message from the director', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_director_photo"><?php _e('Director Photo', 'chroma-excellence'); ?></label>
			<input type="text" id="location_director_photo" name="location_director_photo" class="chroma-image-field"
				value="<?php echo esc_attr($director_photo); ?>" placeholder="https://..."
				style="width: calc(100% - 220px); display: inline-block;" />
			<button type="button" class="button chroma-upload-button" data-field="location_director_photo"
				style="margin-left: 5px;">
				<i class="fa-solid fa-upload"></i> Upload Image
			</button>
			<button type="button" class="button chroma-clear-button" data-field="location_director_photo"
				style="margin-left: 5px;">
				<i class="fa-solid fa-times"></i> Clear
			</button>
			<div class="chroma-image-preview"></div>
			<small><?php _e('Director photo (optional)', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_director_signature"><?php _e('Director Signature Image', 'chroma-excellence'); ?></label>
			<input type="text" id="location_director_signature" name="location_director_signature"
				class="chroma-image-field" value="<?php echo esc_attr($director_signature); ?>" placeholder="https://..."
				style="width: calc(100% - 220px); display: inline-block;" />
			<button type="button" class="button chroma-upload-button" data-field="location_director_signature"
				style="margin-left: 5px;">
				<i class="fa-solid fa-upload"></i> Upload Image
			</button>
			<button type="button" class="button chroma-clear-button" data-field="location_director_signature"
				style="margin-left: 5px;">
				<i class="fa-solid fa-times"></i> Clear
			</button>
			<div class="chroma-image-preview"></div>
			<small><?php _e('Director signature image (optional)', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Address & Contact', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_address"><?php _e('Street Address', 'chroma-excellence'); ?></label>
			<input type="text" id="location_address" name="location_address" value="<?php echo esc_attr($address); ?>"
				placeholder="e.g., 123 Main Street" />
		</div>

		<div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
			<div class="chroma-meta-field">
				<label for="location_city"><?php _e('City', 'chroma-excellence'); ?></label>
				<input type="text" id="location_city" name="location_city" value="<?php echo esc_attr($city); ?>"
					placeholder="e.g., Lawrenceville" />
			</div>

			<div class="chroma-meta-field">
				<label for="location_state"><?php _e('State', 'chroma-excellence'); ?></label>
				<input type="text" id="location_state" name="location_state" value="<?php echo esc_attr($state); ?>"
					placeholder="GA" />
			</div>

			<div class="chroma-meta-field">
				<label for="location_zip"><?php _e('ZIP Code', 'chroma-excellence'); ?></label>
				<input type="text" id="location_zip" name="location_zip" value="<?php echo esc_attr($zip); ?>"
					placeholder="30043" />
			</div>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
			<div class="chroma-meta-field">
				<label for="location_phone"><?php _e('Phone', 'chroma-excellence'); ?></label>
				<input type="text" id="location_phone" name="location_phone" value="<?php echo esc_attr($phone); ?>"
					placeholder="(770) 555-1234" />
			</div>

			<div class="chroma-meta-field">
				<label for="location_email"><?php _e('Email', 'chroma-excellence'); ?></label>
				<input type="email" id="location_email" name="location_email" value="<?php echo esc_attr($email); ?>"
					placeholder="info@example.com" />
			</div>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
			<div class="chroma-meta-field">
				<label for="location_latitude"><?php _e('Latitude', 'chroma-excellence'); ?></label>
				<input type="text" id="location_latitude" name="location_latitude"
					value="<?php echo esc_attr($latitude); ?>" placeholder="33.9562" />
				<small><?php _e('For map integration (optional)', 'chroma-excellence'); ?></small>
			</div>

			<div class="chroma-meta-field">
				<label for="location_longitude"><?php _e('Longitude', 'chroma-excellence'); ?></label>
				<input type="text" id="location_longitude" name="location_longitude"
					value="<?php echo esc_attr($longitude); ?>" placeholder="-83.8781" />
				<small><?php _e('For map integration (optional)', 'chroma-excellence'); ?></small>
			</div>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Google Maps & GMB', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_gmb_url"><?php _e('Google My Business URL', 'chroma-excellence'); ?></label>
			<input type="url" id="location_gmb_url" name="location_gmb_url"
				value="<?php echo esc_attr($gmb_url); ?>" placeholder="https://maps.google.com/?cid=..." />
			<small><?php _e('Direct URL to your Google My Business listing. Used by AI to enhance content generation.', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_maps_embed"><?php _e('Google Maps Embed Code', 'chroma-excellence'); ?></label>
			<textarea id="location_maps_embed" name="location_maps_embed" rows="5"
				placeholder='<iframe src="https://www.google.com/maps/embed?..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>'><?php echo esc_textarea($maps_embed); ?></textarea>
			<small><?php _e('Paste the full iframe embed code from Google Maps. Go to Google Maps → Share → Embed a map', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Tour Booking', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_tour_booking_link"><?php _e('Tour Booking Link', 'chroma-excellence'); ?></label>
			<input type="url" id="location_tour_booking_link" name="location_tour_booking_link"
				value="<?php echo esc_attr($tour_booking_link); ?>" placeholder="https://..." />
			<small><?php _e('External link for "Book a Tour Now" button (e.g., online scheduling system)', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('School Pickups', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_school_pickups"><?php _e('Elementary Schools', 'chroma-excellence'); ?></label>
			<textarea id="location_school_pickups" name="location_school_pickups" rows="5"
				placeholder="Enter one school name per line, e.g.:&#10;Lawrenceville Elementary&#10;Pleasant Hill Elementary&#10;Grace Elementary"><?php echo esc_textarea($school_pickups); ?></textarea>
			<small><?php _e('Enter one school name per line. These will be displayed on the location page.', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Location SEO Content', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_seo_content_title"><?php _e('SEO Content Title', 'chroma-excellence'); ?></label>
			<input type="text" id="location_seo_content_title" name="location_seo_content_title"
				value="<?php echo esc_attr($seo_content_title); ?>"
				placeholder="e.g., Early Education and Care in Lawrenceville, GA" />
			<small><?php _e('Title for the location-specific content section (e.g., "Early Education and Care in [City], GA")', 'chroma-excellence'); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_seo_content_text"><?php _e('SEO Content Description', 'chroma-excellence'); ?></label>
			<textarea id="location_seo_content_text" name="location_seo_content_text" rows="6"
				placeholder="Our school is more than a daycare..."><?php echo esc_textarea($seo_content_text); ?></textarea>
			<small><?php _e('Location-specific description highlighting the area, accessibility, and unique features. This content appears at the bottom of the location page.', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e('Location-Specific FAQs (LLM SEO)', 'chroma-excellence'); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_faq_items"><?php _e('FAQ Items', 'chroma-excellence'); ?></label>
			<textarea id="location_faq_items" name="location_faq_items" rows="8"
				placeholder="Format (one per line):&#10;What are your hours?|We're open Mon-Fri 6:30am-6:30pm&#10;Do you provide meals?|Yes, breakfast, lunch, and snack included"><?php echo esc_textarea(get_post_meta($post->ID, 'location_faq_items', true)); ?></textarea>
			<small><?php _e('Enter FAQ items in format: Question|Answer (one per line). These appear in JSON endpoints for LLMs.', 'chroma-excellence'); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<p><strong><?php _e('Note:', 'chroma-excellence'); ?></strong>
			<?php _e('Use the "Featured Image" box in the sidebar to set the hero image for this location. Programs available at this location can be managed from the Programs admin section.', 'chroma-excellence'); ?>
		</p>
	</div>
	<?php
}

/**
 * Save location custom fields
 */
function chroma_save_location_custom_fields($post_id)
{
	// Verify nonce
	if (!isset($_POST['chroma_location_meta_nonce_field']) || !wp_verify_nonce(wp_unslash($_POST['chroma_location_meta_nonce_field']), 'chroma_location_meta_nonce')) {
		return;
	}

	// Check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check permissions
	if (isset($_POST['post_type']) && 'location' === $_POST['post_type']) {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
	}

	// Save fields
	$fields = array(
		'location_hero_subtitle',
		'location_hero_review_text',
		'location_hero_review_author',
		'location_hero_gallery',
		'location_virtual_tour_embed',
		'location_tagline',
		'location_description',
		'location_google_rating',
		'location_hours',
		'location_ages_served',
		'location_director_name',
		'location_director_heading',
		'location_director_bio',
		'location_director_photo',
		'location_director_signature',
		'location_maps_embed',
		'location_tour_booking_link',
		'location_school_pickups',
		'location_seo_content_title',
		'location_seo_content_text',
		'location_address',
		'location_city',
		'location_state',
		'location_zip',
		'location_phone',
		'location_email',
		'location_latitude',
		'location_longitude',
		'location_service_areas',
		'location_special_programs',
		'location_faq_items',
		'location_gmb_url',
		'_chroma_license_number',
	);

	foreach ($fields as $field) {
		if (isset($_POST[$field])) {
			$value = wp_unslash($_POST[$field]);
			// Sanitize based on field type
			if (in_array($field, array('location_description', 'location_director_bio', 'location_school_pickups', 'location_seo_content_text', 'location_service_areas', 'location_hero_review_text', 'location_faq_items', 'location_hero_gallery'))) {
				$value = sanitize_textarea_field($value);
			} elseif ($field === 'location_maps_embed' || $field === 'location_virtual_tour_embed') {
				// Allow iframes and scripts for embeds
				$allowed_tags = wp_kses_allowed_html('post');
				$allowed_tags['iframe'] = array(
					'src' => true,
					'width' => true,
					'height' => true,
					'frameborder' => true,
					'allowfullscreen' => true,
					'allow' => true, 'loading' => true,
					'style' => true,
					'class' => true,
					'title' => true,
					'referrerpolicy' => true
				);
				$allowed_tags['script'] = array('src' => true, 'type' => true, 'async' => true, 'defer' => true);
				$value = wp_kses($value, $allowed_tags);
			} elseif ($field === 'location_email') {
				$value = sanitize_email($value);
			} elseif ($field === 'location_tour_booking_link' || $field === 'location_gmb_url') {
				$value = esc_url_raw($value);
			} else {
				$value = sanitize_text_field($value);
			}
			update_post_meta($post_id, $field, $value);
		}
	}

	// Save checkbox field for quality_rated
	$quality_rated = isset($_POST['location_quality_rated']) ? '1' : '';
	update_post_meta($post_id, 'location_quality_rated', $quality_rated);
}
add_action('save_post_location', 'chroma_save_location_custom_fields');

<?php
/**
 * Schema.org Meta Boxes
 * Allows customization of schema.org structured data
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Schema Meta Boxes
 */
function chroma_register_schema_meta_boxes() {
	// Homepage/Organization Schema
	add_meta_box(
		'chroma-schema-organization',
		__( 'Schema.org - Organization (ChildCare)', 'chroma-excellence' ),
		'chroma_schema_organization_meta_box',
		'page',
		'normal',
		'default'
	);

	// Location Schema
	add_meta_box(
		'chroma-schema-location',
		__( 'Schema.org - LocalBusiness (ChildCare)', 'chroma-excellence' ),
		'chroma_schema_location_meta_box',
		'location',
		'normal',
		'high'
	);

	// Program Schema
	add_meta_box(
		'chroma-schema-program',
		__( 'Schema.org - Service', 'chroma-excellence' ),
		'chroma_schema_program_meta_box',
		'program',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'chroma_register_schema_meta_boxes' );

/**
 * Organization Schema Meta Box (Homepage)
 */
function chroma_schema_organization_meta_box( $post ) {
	// Only show on homepage
	if ( get_option( 'page_on_front' ) != $post->ID ) {
		echo '<p><em>This schema only applies to the page set as homepage in Settings → Reading.</em></p>';
		return;
	}

	wp_nonce_field( 'chroma_schema_organization', 'chroma_schema_organization_nonce' );

	// Get existing values or defaults
	$name        = get_post_meta( $post->ID, 'schema_org_name', true ) ?: get_bloginfo( 'name' );
	$url         = get_post_meta( $post->ID, 'schema_org_url', true ) ?: home_url();
	$logo        = get_post_meta( $post->ID, 'schema_org_logo', true );
	$description = get_post_meta( $post->ID, 'schema_org_description', true );
	$telephone   = get_post_meta( $post->ID, 'schema_org_telephone', true );
	$email       = get_post_meta( $post->ID, 'schema_org_email', true );
	$address     = get_post_meta( $post->ID, 'schema_org_address', true );
	$area_served = get_post_meta( $post->ID, 'schema_org_area_served', true ) ?: 'Atlanta';

	?>
	<div style="margin-bottom: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
		<strong>Active Schema:</strong> ChildCare Organization<br>
		<a href="https://schema.org/ChildCare" target="_blank">View ChildCare schema documentation →</a>
	</div>

	<table class="form-table">
		<tr>
			<th><label for="schema_org_name">Organization Name</label></th>
			<td>
				<input type="text" id="schema_org_name" name="schema_org_name"
				       value="<?php echo esc_attr( $name ); ?>" class="large-text" />
				<p class="description">Default: Site name from Settings → General</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_url">URL</label></th>
			<td>
				<input type="url" id="schema_org_url" name="schema_org_url"
				       value="<?php echo esc_attr( $url ); ?>" class="large-text" />
				<p class="description">Default: Homepage URL</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_logo">Logo URL</label></th>
			<td>
				<input type="url" id="schema_org_logo" name="schema_org_logo"
				       value="<?php echo esc_attr( $logo ); ?>" class="large-text" />
				<p class="description">Full URL to organization logo image</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_description">Description</label></th>
			<td>
				<textarea id="schema_org_description" name="schema_org_description"
				          rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
				<p class="description">Brief description of your organization</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_telephone">Phone Number</label></th>
			<td>
				<input type="tel" id="schema_org_telephone" name="schema_org_telephone"
				       value="<?php echo esc_attr( $telephone ); ?>" class="regular-text" />
				<p class="description">Format: +1-555-555-5555</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_email">Email</label></th>
			<td>
				<input type="email" id="schema_org_email" name="schema_org_email"
				       value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_address">Address</label></th>
			<td>
				<textarea id="schema_org_address" name="schema_org_address"
				          rows="3" class="large-text"><?php echo esc_textarea( $address ); ?></textarea>
				<p class="description">Full mailing address (optional)</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_org_area_served">Area Served</label></th>
			<td>
				<input type="text" id="schema_org_area_served" name="schema_org_area_served"
				       value="<?php echo esc_attr( $area_served ); ?>" class="regular-text" />
				<p class="description">City or region served (e.g., "Atlanta" or "Metro Atlanta")</p>
			</td>
		</tr>
	</table>

	<p style="margin-top: 20px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
		<strong>Note:</strong> If fields are left empty, the theme will use auto-generated values from your site settings.
	</p>
	<?php
}

/**
 * Location Schema Meta Box
 */
function chroma_schema_location_meta_box( $post ) {
	wp_nonce_field( 'chroma_schema_location', 'chroma_schema_location_nonce' );

	// Get existing values
	$name           = get_post_meta( $post->ID, 'schema_loc_name', true ) ?: get_the_title( $post->ID );
	$description    = get_post_meta( $post->ID, 'schema_loc_description', true );
	$telephone      = get_post_meta( $post->ID, 'schema_loc_telephone', true );
	$email          = get_post_meta( $post->ID, 'schema_loc_email', true );
	$price_range    = get_post_meta( $post->ID, 'schema_loc_price_range', true );
	$opening_hours  = get_post_meta( $post->ID, 'schema_loc_opening_hours', true );
	$payment_accepted = get_post_meta( $post->ID, 'schema_loc_payment_accepted', true );

	?>
	<div style="margin-bottom: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
		<strong>Active Schemas:</strong> ChildCare + LocalBusiness<br>
		<a href="https://schema.org/ChildCare" target="_blank">ChildCare</a> |
		<a href="https://schema.org/LocalBusiness" target="_blank">LocalBusiness</a>
	</div>

	<table class="form-table">
		<tr>
			<th><label for="schema_loc_name">Business Name</label></th>
			<td>
				<input type="text" id="schema_loc_name" name="schema_loc_name"
				       value="<?php echo esc_attr( $name ); ?>" class="large-text" />
				<p class="description">Default: Location title</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_loc_description">Description</label></th>
			<td>
				<textarea id="schema_loc_description" name="schema_loc_description"
				          rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
				<p class="description">Default: Location excerpt or auto-generated from content</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_loc_telephone">Phone Number</label></th>
			<td>
				<input type="tel" id="schema_loc_telephone" name="schema_loc_telephone"
				       value="<?php echo esc_attr( $telephone ); ?>" class="regular-text" />
				<p class="description">Default: From location meta fields</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_loc_email">Email</label></th>
			<td>
				<input type="email" id="schema_loc_email" name="schema_loc_email"
				       value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="schema_loc_price_range">Price Range</label></th>
			<td>
				<input type="text" id="schema_loc_price_range" name="schema_loc_price_range"
				       value="<?php echo esc_attr( $price_range ); ?>" class="regular-text" />
				<p class="description">E.g., "$", "$$", "$$$", or "$50-$150"</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_loc_opening_hours">Opening Hours</label></th>
			<td>
				<textarea id="schema_loc_opening_hours" name="schema_loc_opening_hours"
				          rows="4" class="large-text"><?php echo esc_textarea( $opening_hours ); ?></textarea>
				<p class="description">Format (one per line):<br>
				Mo-Fr 07:00-18:00<br>
				Sa 08:00-12:00</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_loc_payment_accepted">Payment Methods Accepted</label></th>
			<td>
				<input type="text" id="schema_loc_payment_accepted" name="schema_loc_payment_accepted"
				       value="<?php echo esc_attr( $payment_accepted ); ?>" class="large-text" />
				<p class="description">E.g., "Cash, Credit Card, Check"</p>
			</td>
		</tr>
	</table>

	<p style="margin-top: 20px; padding: 10px; background: #d1ecf1; border-left: 4px solid #0c5460;">
		<strong>Note:</strong> Address and geo-coordinates are pulled from existing location meta fields.
	</p>
	<?php
}

/**
 * Program Schema Meta Box
 */
function chroma_schema_program_meta_box( $post ) {
	wp_nonce_field( 'chroma_schema_program', 'chroma_schema_program_nonce' );

	// Get existing values
	$name          = get_post_meta( $post->ID, 'schema_prog_name', true ) ?: get_the_title( $post->ID );
	$description   = get_post_meta( $post->ID, 'schema_prog_description', true );
	$service_type  = get_post_meta( $post->ID, 'schema_prog_service_type', true ) ?: 'Early Childhood Education';
	$provider_name = get_post_meta( $post->ID, 'schema_prog_provider_name', true ) ?: get_bloginfo( 'name' );
	$area_served   = get_post_meta( $post->ID, 'schema_prog_area_served', true ) ?: 'Metro Atlanta';
	$category      = get_post_meta( $post->ID, 'schema_prog_category', true );
	$offers        = get_post_meta( $post->ID, 'schema_prog_offers', true );

	?>
	<div style="margin-bottom: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
		<strong>Active Schema:</strong> Service<br>
		<a href="https://schema.org/Service" target="_blank">View Service schema documentation →</a>
	</div>

	<table class="form-table">
		<tr>
			<th><label for="schema_prog_name">Service Name</label></th>
			<td>
				<input type="text" id="schema_prog_name" name="schema_prog_name"
				       value="<?php echo esc_attr( $name ); ?>" class="large-text" />
				<p class="description">Default: Program title</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_prog_description">Description</label></th>
			<td>
				<textarea id="schema_prog_description" name="schema_prog_description"
				          rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
				<p class="description">Default: Program excerpt</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_prog_service_type">Service Type</label></th>
			<td>
				<input type="text" id="schema_prog_service_type" name="schema_prog_service_type"
				       value="<?php echo esc_attr( $service_type ); ?>" class="large-text" />
				<p class="description">E.g., "Early Childhood Education", "Infant Care", "Preschool"</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_prog_category">Category</label></th>
			<td>
				<input type="text" id="schema_prog_category" name="schema_prog_category"
				       value="<?php echo esc_attr( $category ); ?>" class="large-text" />
				<p class="description">Service category (optional)</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_prog_provider_name">Provider Name</label></th>
			<td>
				<input type="text" id="schema_prog_provider_name" name="schema_prog_provider_name"
				       value="<?php echo esc_attr( $provider_name ); ?>" class="large-text" />
				<p class="description">Default: Site name</p>
			</td>
		</tr>
		<tr>
			<th><label for="schema_prog_area_served">Area Served</label></th>
			<td>
				<input type="text" id="schema_prog_area_served" name="schema_prog_area_served"
				       value="<?php echo esc_attr( $area_served ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="schema_prog_offers">Offers (Price/Availability)</label></th>
			<td>
				<textarea id="schema_prog_offers" name="schema_prog_offers"
				          rows="3" class="large-text"><?php echo esc_textarea( $offers ); ?></textarea>
				<p class="description">Optional: JSON format or simple text about pricing/availability</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Organization Schema Meta
 */
function chroma_save_schema_organization_meta( $post_id ) {
	// Security checks
	if ( ! isset( $_POST['chroma_schema_organization_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['chroma_schema_organization_nonce'], 'chroma_schema_organization' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save fields
	$fields = array(
		'schema_org_name',
		'schema_org_url',
		'schema_org_logo',
		'schema_org_description',
		'schema_org_telephone',
		'schema_org_email',
		'schema_org_address',
		'schema_org_area_served',
	);

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			if ( strpos( $field, 'url' ) !== false || strpos( $field, 'logo' ) !== false ) {
				update_post_meta( $post_id, $field, esc_url_raw( $_POST[ $field ] ) );
			} elseif ( strpos( $field, 'email' ) !== false ) {
				update_post_meta( $post_id, $field, sanitize_email( $_POST[ $field ] ) );
			} else {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
add_action( 'save_post_page', 'chroma_save_schema_organization_meta' );

/**
 * Save Location Schema Meta
 */
function chroma_save_schema_location_meta( $post_id ) {
	// Security checks
	if ( ! isset( $_POST['chroma_schema_location_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['chroma_schema_location_nonce'], 'chroma_schema_location' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save fields
	$fields = array(
		'schema_loc_name',
		'schema_loc_description',
		'schema_loc_telephone',
		'schema_loc_email',
		'schema_loc_price_range',
		'schema_loc_opening_hours',
		'schema_loc_payment_accepted',
	);

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			if ( strpos( $field, 'email' ) !== false ) {
				update_post_meta( $post_id, $field, sanitize_email( $_POST[ $field ] ) );
			} elseif ( strpos( $field, 'description' ) !== false || strpos( $field, 'opening_hours' ) !== false ) {
				update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[ $field ] ) );
			} else {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
add_action( 'save_post_location', 'chroma_save_schema_location_meta' );

/**
 * Save Program Schema Meta
 */
function chroma_save_schema_program_meta( $post_id ) {
	// Security checks
	if ( ! isset( $_POST['chroma_schema_program_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['chroma_schema_program_nonce'], 'chroma_schema_program' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save fields
	$fields = array(
		'schema_prog_name',
		'schema_prog_description',
		'schema_prog_service_type',
		'schema_prog_provider_name',
		'schema_prog_area_served',
		'schema_prog_category',
		'schema_prog_offers',
	);

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			if ( strpos( $field, 'description' ) !== false || strpos( $field, 'offers' ) !== false ) {
				update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[ $field ] ) );
			} else {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
add_action( 'save_post_program', 'chroma_save_schema_program_meta' );

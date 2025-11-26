<?php
/**
 * Plugin Name: Chroma Tour Form
 * Description: Tour request form with lead logging for Chroma Early Learning Academy
 * Version: 1.0.0
 * Author: Chroma Development Team
 * Text Domain: chroma-tour-form
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Safe accessor for global settings without relying on ACF.
 */
function chroma_tour_get_global_setting( $key, $default = '' ) {
        if ( function_exists( 'chroma_get_global_setting' ) ) {
                return chroma_get_global_setting( $key, $default );
        }

        $settings = get_option( 'chroma_global_settings', array() );
        $value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

        if ( '' === $value ) {
                return $default;
        }

        return $value;
}

/**
 * Tour Form Shortcode
 * Usage: [chroma_tour_form]
 */
function chroma_tour_form_shortcode() {
        ob_start();
        ?>
        <form class="chroma-tour-form space-y-4" method="post" action="">
                <?php wp_nonce_field( 'chroma_tour_submit', 'chroma_tour_nonce' ); ?>

                <div class="grid md:grid-cols-2 gap-4">
                        <div>
                                <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Parent Name *</label>
                                <input type="text" name="parent_name" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                        </div>
                        <div>
                                <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Phone *</label>
                                <input type="tel" name="phone" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                        </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                        <div>
                                <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Email *</label>
                                <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                        </div>
                        <div>
                                <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Preferred Location</label>
                                <select name="location_id" class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none">
                                        <option value="">Select a location...</option>
                                        <?php
                                        $locations = get_posts( array( 'post_type' => 'location', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
                                        foreach ( $locations as $location ) :
                                                ?>
                                                <option value="<?php echo esc_attr( $location->ID ); ?>"><?php echo esc_html( $location->post_title ); ?></option>
                                                <?php
                                        endforeach;
                                        ?>
                                </select>
                        </div>
                </div>

                <div>
                        <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Child(ren) Age(s)</label>
                        <input type="text" name="child_ages" class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" placeholder="e.g., 10 months, 3 years" />
                </div>

                <button type="submit" name="chroma_tour_submit" class="w-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-wider py-4 rounded-full shadow-soft hover:bg-chroma-red/90 transition">
                        Request Tour
                </button>
        </form>
        <?php
        return ob_get_clean();
}
add_shortcode( 'chroma_tour_form', 'chroma_tour_form_shortcode' );

/**
 * Handle Form Submission
 */
function chroma_handle_tour_submission() {
        if ( ! isset( $_POST['chroma_tour_submit'] ) || ! wp_verify_nonce( wp_unslash( $_POST['chroma_tour_nonce'] ?? '' ), 'chroma_tour_submit' ) ) {
                return;
        }

        $parent_name = isset( $_POST['parent_name'] ) ? sanitize_text_field( wp_unslash( $_POST['parent_name'] ) ) : '';
        $phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $location_id = isset( $_POST['location_id'] ) ? intval( $_POST['location_id'] ) : 0;
        $child_ages  = isset( $_POST['child_ages'] ) ? sanitize_text_field( wp_unslash( $_POST['child_ages'] ) ) : '';

        $redirect_fallback = home_url( '/contact/' );
        $redirect_target   = wp_get_referer() ?: $redirect_fallback;
        $redirect_url      = wp_validate_redirect( $redirect_target, $redirect_fallback );

        if ( empty( $parent_name ) || empty( $phone ) || empty( $email ) || ! is_email( $email ) ) {
                wp_safe_redirect( add_query_arg( 'tour_sent', '0', $redirect_url ) );
                exit;
        }

        // Determine email recipient.
        $to_email = chroma_tour_get_global_setting( 'global_tour_email', get_option( 'admin_email' ) );
        if ( $location_id ) {
                $location_email = get_post_meta( $location_id, 'location_email', true );
                if ( $location_email ) {
                        $to_email = $location_email;
                }
        }

        // Send email.
        $subject = 'New Tour Request from ' . $parent_name;
        $message = sprintf(
                "New tour request:\n\nName: %s\nPhone: %s\nEmail: %s\nLocation: %s\nChild Ages: %s",
                $parent_name,
                $phone,
                $email,
                $location_id ? get_the_title( $location_id ) : 'Not specified',
                $child_ages ?: 'Not specified'
        );

        wp_mail( $to_email, $subject, $message );

        // Log to Lead Log CPT if it exists.
        if ( post_type_exists( 'lead_log' ) ) {
                $lead_payload = array(
                        'parent_name' => $parent_name,
                        'phone'       => $phone,
                        'email'       => $email,
                        'location_id' => $location_id,
                        'child_ages'  => $child_ages,
                        'submitted_at'=> current_time( 'mysql' ),
                );

                wp_insert_post(
                        array(
                                'post_type'   => 'lead_log',
                                'post_title'  => 'Tour: ' . $parent_name,
                                'post_status' => 'publish',
                                'meta_input'  => array(
                                        'lead_type'     => 'tour',
                                        'lead_name'     => $parent_name,
                                        'lead_email'    => $email,
                                        'lead_phone'    => $phone,
                                        'lead_location' => $location_id,
                                        'lead_payload'  => wp_json_encode( $lead_payload ),
                                ),
                        )
                );
        }

        wp_safe_redirect( add_query_arg( 'tour_sent', '1', $redirect_url ) );
        exit;
}
add_action( 'template_redirect', 'chroma_handle_tour_submission' );

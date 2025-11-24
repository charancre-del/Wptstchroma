<?php
/**
 * Plugin Name: Chroma Acquisitions Form
 * Description: Acquisitions inquiry form for potential sellers to Chroma ELA
 * Version: 1.0.0
 * Author: Chroma Development Team
 * Text Domain: chroma-acquisitions-form
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Acquisitions Form Shortcode
 * Usage: [chroma_acquisition_form]
 */
function chroma_acquisition_form_shortcode() {
        ob_start();
        ?>
        <form class="chroma-acquisition-form space-y-4" method="post" action="">
                <?php wp_nonce_field( 'chroma_acquisition_submit', 'chroma_acquisition_nonce' ); ?>

                <div class="grid md:grid-cols-2 gap-4">
                        <div>
                                <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Your Name *</label>
                                <input type="text" name="contact_name" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                        </div>
                        <div>
                                <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Phone *</label>
                                <input type="tel" name="phone" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                        </div>
                </div>

                <div>
                        <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Email *</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                </div>

                <div>
                        <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Facility Name *</label>
                        <input type="text" name="facility_name" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                </div>

                <div>
                        <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Facility Location (City, State) *</label>
                        <input type="text" name="facility_location" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none" />
                </div>

                <div>
                        <label class="block text-xs font-semibold text-brand-ink/60 uppercase mb-1.5">Additional Details</label>
                        <textarea name="details" rows="4" class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none"></textarea>
                </div>

                <button type="submit" name="chroma_acquisition_submit" class="w-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-wider py-4 rounded-full shadow-soft hover:bg-chroma-red/90 transition">
                        Submit Inquiry
                </button>
        </form>
        <?php
        return ob_get_clean();
}
add_shortcode( 'chroma_acquisition_form', 'chroma_acquisition_form_shortcode' );

/**
 * Handle Form Submission
 */
function chroma_handle_acquisition_submission() {
        if ( ! isset( $_POST['chroma_acquisition_submit'] ) || ! wp_verify_nonce( wp_unslash( $_POST['chroma_acquisition_nonce'] ?? '' ), 'chroma_acquisition_submit' ) ) {
                return;
        }

        $contact_name      = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
        $phone             = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $email             = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $facility_name     = isset( $_POST['facility_name'] ) ? sanitize_text_field( wp_unslash( $_POST['facility_name'] ) ) : '';
        $facility_location = isset( $_POST['facility_location'] ) ? sanitize_text_field( wp_unslash( $_POST['facility_location'] ) ) : '';
        $details           = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';

        $redirect_fallback = home_url( '/acquisitions/' );
        $redirect_target   = wp_get_referer() ?: $redirect_fallback;
        $redirect_url      = wp_validate_redirect( $redirect_target, $redirect_fallback );

        if ( empty( $contact_name ) || empty( $phone ) || empty( $email ) || empty( $facility_name ) || empty( $facility_location ) || ! is_email( $email ) ) {
                wp_safe_redirect( add_query_arg( 'acquisition_sent', '0', $redirect_url ) );
                exit;
        }

        // Email to acquisitions team.
        $to_email = 'acquisitions@chromaela.com';
        $subject  = 'New Acquisition Inquiry: ' . $facility_name;
        $message  = sprintf(
                "New acquisition inquiry:\n\nContact: %s\nPhone: %s\nEmail: %s\nFacility: %s\nLocation: %s\n\nDetails:\n%s",
                $contact_name,
                $phone,
                $email,
                $facility_name,
                $facility_location,
                $details
        );

        wp_mail( $to_email, $subject, $message );

        // Log to Lead Log CPT.
        if ( post_type_exists( 'lead_log' ) ) {
                $lead_payload = array(
                        'contact_name'      => $contact_name,
                        'phone'             => $phone,
                        'email'             => $email,
                        'facility_name'     => $facility_name,
                        'facility_location' => $facility_location,
                        'details'           => $details,
                        'submitted_at'      => current_time( 'mysql' ),
                );

                wp_insert_post(
                        array(
                                'post_type'   => 'lead_log',
                                'post_title'  => 'Acquisition: ' . $facility_name,
                                'post_status' => 'publish',
                                'meta_input'  => array(
                                        'lead_type'    => 'acquisition',
                                        'lead_name'    => $contact_name,
                                        'lead_email'   => $email,
                                        'lead_phone'   => $phone,
                                        'lead_payload' => wp_json_encode( $lead_payload ),
                                ),
                        )
                );
        }

        wp_safe_redirect( add_query_arg( 'acquisition_sent', '1', $redirect_url ) );
        exit;
}
add_action( 'template_redirect', 'chroma_handle_acquisition_submission' );

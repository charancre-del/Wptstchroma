<?php
/**
 * Security Headers
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add security headers to HTTP response
 * Note: CSP is DISABLED as it blocks necessary third-party scripts
 */
function chroma_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy - DISABLED
    // Reason: Blocks Clarity, Google Ads, LeadConnector, and other essential services
    // If you need CSP, configure it at the server level (Apache/Nginx) or via Cloudflare

    // HSTS - Uncomment when SSL is fully configured and tested
    // header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
}
add_action('send_headers', 'chroma_security_headers');


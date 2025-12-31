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

    // Content Security Policy - Protects against XSS attacks
    // Allow self, Google Fonts, Google APIs (Maps, Analytics), and common CDNs
    $csp_directives = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://maps.googleapis.com https://www.gstatic.com https://js.hs-scripts.com https://js.hsforms.net https://js.hscollectedforms.net https://js.usemessages.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com data:",
        "img-src 'self' data: https: blob:",
        "connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://maps.googleapis.com https://forms.hubspot.com https://api.hubspot.com https://*.hs-analytics.net https://*.hubspot.com",
        "frame-src 'self' https://www.google.com https://www.youtube.com https://player.vimeo.com https://api.leadconnectorhq.com https://*.gohighlevel.com",
        "media-src 'self' https: blob:",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self' https://forms.hubspot.com https://*.gohighlevel.com",
        "upgrade-insecure-requests"
    ];
    
    header('Content-Security-Policy: ' . implode('; ', $csp_directives));

    // HSTS - Uncomment when SSL is fully configured and tested
    // header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
}
add_action('send_headers', 'chroma_security_headers');

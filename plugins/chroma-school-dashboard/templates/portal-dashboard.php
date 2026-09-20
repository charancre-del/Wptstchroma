<?php
/**
 * Director Portal Template
 * Optimized for performance: Pre-compiled React & Tailwind, non-blocking scripts.
 */

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <style>
        /* Standalone font loading for the portal */
        @font-face {
            font-family: 'Outfit';
            src: url('/wp-content/themes/chroma-excellence-theme/assets/webfonts/Outfit-Regular.woff2') format('woff2');
            font-weight: 400;
            font-display: swap;
        }

        @font-face {
            font-family: 'Outfit';
            src: url('/wp-content/themes/chroma-excellence-theme/assets/webfonts/Outfit-Medium.woff2') format('woff2');
            font-weight: 500;
            font-display: swap;
        }

        @font-face {
            font-family: 'Outfit';
            src: url('/wp-content/themes/chroma-excellence-theme/assets/webfonts/Outfit-Bold.woff2') format('woff2');
            font-weight: 700;
            font-display: swap;
        }

        @font-face {
            font-family: 'Playfair Display';
            src: url('/wp-content/themes/chroma-excellence-theme/assets/webfonts/PlayfairDisplay-SemiBold.woff2') format('woff2');
            font-weight: 600;
            font-display: swap;
        }

        @font-face {
            font-family: 'Playfair Display';
            src: url('/wp-content/themes/chroma-excellence-theme/assets/webfonts/PlayfairDisplay-Bold.woff2') format('woff2');
            font-weight: 700;
            font-display: swap;
        }
    </style>

    <?php
    ob_start();
    wp_head();
    $portal_head = (string) ob_get_clean();
    $portal_canonical = '<link rel="canonical" href="' . esc_url(home_url('/portal/')) . '" />';

    if (preg_match('/<link\s+rel=(["\'])canonical\1[^>]*>/i', $portal_head)) {
        $portal_head = preg_replace('/<link\s+rel=(["\'])canonical\1[^>]*>/i', $portal_canonical, $portal_head);
    } else {
        $portal_head .= "\n" . $portal_canonical . "\n";
    }

    echo $portal_head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</head>

<body class="selection:bg-chroma-yellow/30">
    <div id="root"></div>
    <?php wp_footer(); ?>
</body>

</html>

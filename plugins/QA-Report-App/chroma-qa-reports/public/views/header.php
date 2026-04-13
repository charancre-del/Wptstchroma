<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_bloginfo('name')); ?> - QA Reports</title>
    <link rel="manifest" href="<?php echo esc_url(rest_url('cqa/v1/manifest')); ?>">
    <meta name="theme-color" content="#9d8253">
    <meta name="robots" content="noindex, nofollow">
    <?php
    // Intentionally avoid full wp_head() on the QA portal.
    // The portal should render only its own assets, not site-wide marketing
    // widgets, analytics, or third-party scripts injected by other plugins.
    wp_print_styles();
    wp_print_head_scripts();
    ?>
</head>

<body class="cqa-frontend">
    <main class="cqa-main">

<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_bloginfo('name')); ?> - QA Reports</title>
    <link rel="manifest" href="<?php echo esc_url(rest_url('chroma-qa/v1/manifest')); ?>">
    <meta name="theme-color" content="#9d8253">
    <?php wp_head(); ?>
</head>

<body class="cqa-frontend">
    <main class="cqa-main">
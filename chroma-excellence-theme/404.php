<?php
/**
 * 404 Error Page Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Tier 21: Traffic Recycling 404 (Smart Redirect)
if (!is_admin()) {
    $current_uri = $_SERVER['REQUEST_URI'];
    $uri_parts = explode('/', trim($current_uri, '/'));
    $potential_slug = end($uri_parts);
    
    // Clean slug
    $clean_slug = preg_replace('/[^a-z0-9]+/', ' ', strtolower($potential_slug));
    
    // 1. Direct Page Match (if exact slug exists but was accessed via weird path)
    $page = get_page_by_path($potential_slug, OBJECT, ['page', 'program', 'location']);
    if ($page && isset($page->ID)) {
        wp_redirect(get_permalink($page->ID), 301);
        exit;
    }

    // 2. Keyword Matching Strategy
    $map = [
        'career' => 'careers',
        'jobs' => 'careers',
        'hiring' => 'careers',
        'price' => 'tuition',
        'cost' => 'tuition',
        'rates' => 'tuition',
        'enroll' => 'schedule-tour',
        'tour' => 'schedule-tour',
        'visit' => 'schedule-tour',
        'start' => 'programs',
        'class' => 'programs'
    ];

    foreach ($map as $key => $target_slug) {
        if (strpos($clean_slug, $key) !== false) {
             // Find target ID
             $target_page = get_page_by_path($target_slug);
             if ($target_page) {
                 wp_redirect(get_permalink($target_page->ID), 301);
                 exit;
             }
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Outfit'], serif: ['Playfair Display'] },
          colors: { brand: { ink: '#263238', cream: '#FFFCF8' }, chroma: { blue: '#4A6C7C', yellow: '#E6BE75' } }
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Outfit', sans-serif;
    }
  </style>
  <?php wp_head(); ?>
</head>

<body class="bg-brand-cream text-brand-ink antialiased flex flex-col min-h-screen">

  <header class="p-6">
    <a href="<?php echo esc_url(home_url('/')); ?>">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/chroma-logo.png'); ?>" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/chroma-logo.png'); ?> 1x,
                   <?php echo esc_url(get_template_directory_uri() . '/assets/images/chroma-logo-highres.png'); ?> 2x"
        alt="Chroma Early Learning" class="h-10 w-auto" />
    </a>
  </header>

  <main class="flex-grow flex flex-col items-center justify-center text-center px-4">
    <div class="text-9xl font-serif font-bold text-chroma-yellow opacity-50 mb-4">404</div>
    <h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-6"><?php _e('Ruh-roh! This page is playing hide-and-seek.', 'chroma-excellence'); ?></h1>
    <p class="text-lg text-brand-ink max-w-md mb-10"><?php _e('We\'ve checked the toy bin, looked under the rugs, and even asked the goldfish, but we can\'t find this page anywhere. It must be really good at hiding!', 'chroma-excellence'); ?></p>

    <div class="flex flex-wrap justify-center gap-4">
      <a href="<?php echo esc_url(home_url('/')); ?>"
        class="px-8 py-3 bg-brand-ink text-white font-bold rounded-full uppercase tracking-widest text-xs hover:bg-chroma-blue transition-colors"><?php _e('Go Home', 'chroma-excellence'); ?></a>
      <?php $locations_url = function_exists('chroma_smart_link') ? chroma_smart_link('locations') : home_url('/locations'); ?>
      <a href="<?php echo esc_url($locations_url); ?>"
        class="px-8 py-3 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-widest text-xs hover:border-chroma-blue hover:text-chroma-blue transition-colors"><?php _e('Find a School', 'chroma-excellence'); ?></a>
    </div>
  </main>

  <footer class="p-6 text-center text-xs text-brand-ink">
    &copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?>.
  </footer>

  <?php wp_footer(); ?>
</body>

</html>
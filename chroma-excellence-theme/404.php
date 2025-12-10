<?php
/**
 * 404 Error Page Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */
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
    <h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-6">Ruh-roh! This page is playing
      hide-and-seek.</h1>
    <p class="text-lg text-brand-ink max-w-md mb-10">We've checked the toy bin, looked under the rugs, and even asked
      the goldfish, but we can't find this page anywhere. It must be really good at hiding!</p>

    <div class="flex flex-wrap justify-center gap-4">
      <a href="<?php echo esc_url(home_url('/')); ?>"
        class="px-8 py-3 bg-brand-ink text-white font-bold rounded-full uppercase tracking-widest text-xs hover:bg-chroma-blue transition-colors">Go
        Home</a>
      <?php $locations_url = chroma_smart_link('locations'); ?>
      <a href="<?php echo esc_url($locations_url); ?>"
        class="px-8 py-3 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-widest text-xs hover:border-chroma-blue hover:text-chroma-blue transition-colors">Find
        a School</a>
    </div>
  </main>

  <footer class="p-6 text-center text-xs text-brand-ink">
    &copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?>.
  </footer>

  <?php wp_footer(); ?>
</body>

</html>
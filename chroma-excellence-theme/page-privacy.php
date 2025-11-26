<?php
/**
 * Template Name: Privacy Policy
 *
 * Privacy & Families' Rights Policy page template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();

$page_id = get_the_ID();

// Get last updated date
$last_updated = get_post_meta( $page_id, 'privacy_last_updated', true ) ?: 'October 1, 2024';

// Get all 5 sections
$sections = array();
for ( $i = 1; $i <= 5; $i++ ) {
	$sections[] = array(
		'title'   => get_post_meta( $page_id, "privacy_section{$i}_title", true ),
		'content' => get_post_meta( $page_id, "privacy_section{$i}_content", true ),
	);
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>" />
  <title><?php echo esc_html( get_the_title() . ' | ' . get_bloginfo( 'name' ) ); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Outfit'], serif: ['Playfair Display'] },
          colors: { brand: { ink: '#263238', cream: '#FFFCF8' }, chroma: { blue: '#4A6C7C' } }
        }
      }
    }
  </script>
  <style>body { font-family: 'Outfit', sans-serif; }</style>
  <?php wp_head(); ?>
</head>
<body class="bg-brand-cream text-brand-ink antialiased">

  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-chroma-blue/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 h-[82px] flex items-center justify-between">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="font-bold text-lg text-brand-ink">Chroma</a>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-sm font-bold text-brand-ink/60 hover:text-chroma-blue">Back to Home</a>
    </div>
  </header>

  <main class="max-w-3xl mx-auto px-4 py-24">
    <h1 class="font-serif text-4xl md:text-5xl font-bold mb-8"><?php the_title(); ?></h1>
    <p class="text-sm text-brand-ink/50 mb-12">Last Updated: <?php echo esc_html( $last_updated ); ?></p>

    <div class="prose prose-lg text-brand-ink/80">
      <?php foreach ( $sections as $section ) : ?>
        <?php if ( ! empty( $section['title'] ) ) : ?>
          <h3 class="font-serif font-bold text-xl text-brand-ink mt-8 mb-4"><?php echo esc_html( $section['title'] ); ?></h3>
          <?php if ( ! empty( $section['content'] ) ) : ?>
            <div class="privacy-section-content">
              <?php echo wp_kses_post( $section['content'] ); ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </main>

  <footer class="bg-brand-ink text-white py-12 text-center text-sm opacity-60">
    <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.</p>
  </footer>

  <?php wp_footer(); ?>
</body>
</html>

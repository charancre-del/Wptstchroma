<?php
/**
 * Single Post Template (Stories/Blog)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Get post data
$post_id = get_the_ID();
$categories = get_the_category();
$primary_category = !empty($categories) ? $categories[0]->name : 'Stories';
$post_date = get_the_date('M j, Y');
$author_id = get_the_author_meta('ID');
$author_name = get_the_author();
$author_title = get_the_author_meta('description') ?: __('Contributor', 'chroma-excellence');
$author_avatar = get_avatar_url($author_id, array('size' => 150));
$featured_image = get_the_post_thumbnail_url($post_id, 'full');

// Get related posts (same category, exclude current)
$related_args = array(
  'post_type' => 'post',
  'posts_per_page' => 3,
  'post__not_in' => array($post_id),
  'orderby' => 'rand',
);
if (!empty($categories)) {
  $related_args['category__in'] = array($categories[0]->term_id);
}
$related_query = new WP_Query($related_args);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">

<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Outfit'], serif: ['Playfair Display'] },
          colors: { brand: { ink: '#263238', cream: '#FFFCF8' }, chroma: { blue: '#4A6C7C', yellow: '#E6BE75', red: '#D67D6B', green: '#6BBF73' } }
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Outfit', sans-serif;
    }

    .post-content h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #263238;
      margin-bottom: 1rem;
      margin-top: 3rem;
    }

    .post-content p {
      margin-bottom: 1.5rem;
    }

    .post-content p:first-of-type::first-letter {
      font-size: 3rem;
      font-family: 'Playfair Display', serif;
      color: #4A6C7C;
      float: left;
      margin-right: 0.75rem;
      margin-top: -0.375rem;
      line-height: 1;
    }

    .post-content blockquote {
      border-left: 4px solid #E6BE75;
      padding-left: 1.5rem;
      font-style: italic;
      font-size: 1.25rem;
      color: #263238;
      margin: 2.5rem 0;
    }

    .post-content ul {
      list-style: disc;
      padding-left: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .post-content ul li {
      margin-bottom: 0.5rem;
    }

    .post-content .callout-box {
      background: white;
      padding: 2rem;
      border-radius: 1.5rem;
      border: 1px solid rgba(38, 50, 56, 0.1);
      margin: 3rem 0;
    }

    .post-content .callout-box h4 {
      font-weight: 700;
      font-size: 1.125rem;
      margin-bottom: 1rem;
      margin-top: 0;
    }

    .post-content .callout-box ul {
      margin-bottom: 0;
    }
  </style>
  <?php wp_head(); ?>
</head>

<body class="bg-brand-cream text-brand-ink antialiased">

  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-chroma-blue/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 h-[82px] flex items-center justify-between">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?>"
          srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?> 1x,
                     <?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp'); ?> 2x" alt="Chroma Early Learning" class="h-12 w-auto" />
      </a>
      <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-brand-ink/80">
        <?php $stories_url = chroma_smart_link('stories'); ?>
        <a href="<?php echo esc_url($stories_url); ?>" class="hover:text-chroma-blue flex items-center gap-2"><i
            class="fa-solid fa-arrow-left"></i> <?php _e('Back to Stories', 'chroma-excellence'); ?></a>
      </nav>
      <?php $locations_url = chroma_smart_link('locations'); ?>
      <a href="<?php echo esc_url($locations_url); ?>"
        class="hidden sm:inline-flex items-center gap-2 bg-brand-ink text-white text-xs font-semibold tracking-[0.2em] px-6 py-3 rounded-full shadow-soft"><?php _e('Book Tour', 'chroma-excellence'); ?></a>
    </div>
  </header>

  <main>
    <article>
      <header class="py-20 text-center max-w-4xl mx-auto px-4">
        <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-chroma-blue mb-6">
          <span class="w-2 h-2 bg-chroma-blue rounded-full"></span> <?php echo esc_html($primary_category); ?>
          <span class="text-brand-ink/70">•</span> <?php echo esc_html($post_date); ?>
        </div>
        <h1 class="font-serif text-4xl md:text-6xl text-brand-ink mb-8 leading-tight"><?php the_title(); ?></h1>
        <div class="flex items-center justify-center gap-4">
          <img src="<?php echo esc_url($author_avatar); ?>"
            class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-md"
            alt="<?php echo esc_attr($author_name); ?>" />
          <div class="text-left">
            <p class="text-sm font-bold text-brand-ink"><?php echo esc_html($author_name); ?></p>
            <p class="text-xs text-brand-ink/90"><?php echo esc_html($author_title); ?></p>
          </div>
        </div>
      </header>

      <?php if ($featured_image): ?>
        <div class="max-w-5xl mx-auto px-4 lg:px-6 mb-12">
          <img src="<?php echo esc_url($featured_image); ?>" alt="<?php the_title_attribute(); ?>"
            class="w-full h-auto rounded-3xl shadow-lg">
        </div>
      <?php endif; ?>

      <div class="max-w-3xl mx-auto px-4 lg:px-6 pb-20">
        <div
          class="post-content prose prose-lg prose-headings:font-serif prose-headings:font-bold prose-p:text-brand-ink/90 prose-a:text-chroma-blue hover:prose-a:text-chroma-blue/80 transition-colors">
          <?php
          while (have_posts()):
            the_post();
            the_content();
          endwhile;
          ?>
        </div>
      </div>
    </article>

    <?php if ($related_query->have_posts()): ?>
      <section class="bg-white py-20 border-t border-brand-ink/5">
        <div class="max-w-6xl mx-auto px-4 lg:px-6">
          <h3 class="font-serif text-3xl font-bold mb-8 text-center"><?php _e('More from Chroma', 'chroma-excellence'); ?></h3>
          <div class="grid md:grid-cols-3 gap-8">
            <?php while ($related_query->have_posts()):
              $related_query->the_post(); ?>
              <a href="<?php the_permalink(); ?>" class="group">
                <div class="rounded-2xl overflow-hidden mb-4 h-48">
                  <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform')); ?>
                  <?php else: ?>
                    <div class="w-full h-full bg-chroma-blue/10"></div>
                  <?php endif; ?>
                </div>
                <h4 class="font-bold text-lg leading-tight group-hover:text-chroma-blue"><?php the_title(); ?></h4>
              </a>
            <?php endwhile;
            wp_reset_postdata(); ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="bg-brand-ink text-white py-12 text-center text-sm opacity-60">
    <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?>.</p>
  </footer>

  <?php wp_footer(); ?>
</body>

</html>
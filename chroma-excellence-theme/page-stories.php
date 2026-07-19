<?php
/**
 * Template Name: Stories Page (Blog)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

$page_id = get_the_ID();

// Get featured post ID
$featured_post_id = get_post_meta($page_id, 'stories_featured_post', true);

// Get selected category filter
$selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

// Query arguments
$args = array(
  'post_type' => 'post',
  'posts_per_page' => 36,
  'post_status' => 'publish',
  'orderby' => 'date',
  'order' => 'DESC',
);

// Keep the featured item and unfinished empty editorial records out of the grid.
$excluded_post_ids = function_exists('chroma_get_unfinished_empty_post_ids')
  ? chroma_get_unfinished_empty_post_ids()
  : array();
if ($featured_post_id) {
  $excluded_post_ids[] = (int) $featured_post_id;
}
$args['post__not_in'] = array_values(array_unique(array_filter(array_map('intval', $excluded_post_ids))));

// Filter by category if selected
if ($selected_category) {
  $args['category_name'] = $selected_category;
}

// WP_Query is now created after pagination is set up below

// Helper function to get category color
function chroma_get_category_color($category_slug)
{
  $colors = array(
    'parenting' => 'chroma-blue',
    'development' => 'chroma-green',
    'inside-chroma' => 'chroma-red',
  );
  return $colors[$category_slug] ?? 'chroma-blue';
}

// Get current page for pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Update query args to include paged
$args['paged'] = $paged;

$posts_query = new WP_Query($args);

// Get all categories for filter buttons
$categories = get_categories(array(
  'orderby' => 'name',
  'order' => 'ASC',
));

get_header();
?>

<main>
  <!-- Hero -->
    <section class="pageHero chroma-v2-page-hero py-20 bg-brand-cream text-center">
    <div class="max-w-4xl mx-auto px-4">
      <span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('The Blog', 'chroma-excellence'); ?></span>
      <h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6"><?php _e('Chroma Stories.', 'chroma-excellence'); ?></h1>
      <p class="text-lg text-brand-ink/90"><?php _e('Parenting tips, classroom spotlights, and insights from our educators.', 'chroma-excellence'); ?></p>

      <!-- Categories -->
      <div class="flex flex-wrap justify-center gap-2 mt-8">
        <a href="<?php echo esc_url(get_permalink()); ?>"
          class="px-4 py-2 rounded-full border border-brand-ink/10 <?php echo empty($selected_category) ? 'bg-brand-ink text-white' : 'bg-white hover:bg-brand-cream text-brand-ink/80'; ?> text-xs font-bold uppercase">
          <?php _e('All', 'chroma-excellence'); ?>
        </a>
        <?php foreach ($categories as $category): ?>
          <a href="<?php echo esc_url(add_query_arg('category', $category->slug, get_permalink())); ?>"
            class="px-4 py-2 rounded-full border border-brand-ink/10 <?php echo $selected_category === $category->slug ? 'bg-brand-ink text-white' : 'bg-white hover:bg-brand-cream text-brand-ink/80'; ?> text-xs font-bold uppercase">
            <?php echo esc_html($category->name); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ($featured_post_id):
    $featured_post = get_post($featured_post_id);
    if ($featured_post):
      setup_postdata($featured_post);
      $featured_categories = get_the_category($featured_post_id);
      $featured_image = get_the_post_thumbnail_url($featured_post_id, 'large') ?: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=1200&auto=format&fit=crop';
      ?>
      <!-- Featured Post -->
      <section class="white borderY py-12 px-4 lg:px-6 bg-white border-y border-chroma-blue/10">
        <div class="max-w-7xl mx-auto">
        <a href="<?php echo esc_url(get_permalink($featured_post_id)); ?>" class="block">
          <div class="relative rounded-[3rem] overflow-hidden shadow-soft group cursor-pointer h-[500px]">
            <img src="<?php echo esc_url($featured_image); ?>"
              class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
              alt="<?php echo esc_attr(get_the_title($featured_post_id)); ?>" />
            <div class="absolute inset-0 bg-gradient-to-t from-brand-ink/90 via-brand-ink/20 to-transparent"></div>
            <div class="absolute bottom-0 left-0 p-8 md:p-12">
              <span
                class="bg-chroma-yellow text-brand-ink text-[10px] font-bold uppercase px-3 py-1 rounded-full mb-4 inline-block"><?php _e('Featured', 'chroma-excellence'); ?></span>
              <h2 class="font-serif text-3xl md:text-4xl text-white font-bold mb-4">
                <?php echo esc_html(get_the_title($featured_post_id)); ?>
              </h2>
              <p class="text-white/80 mb-6 max-w-2xl">
                <?php echo esc_html(wp_trim_words(get_the_excerpt($featured_post_id), 25)); ?>
              </p>
              <span class="text-white text-xs font-bold uppercase tracking-widest border-b border-white/40 pb-1"><?php _e('Read Story', 'chroma-excellence'); ?></span>
            </div>
          </div>
        </a>
        </div>
      </section>
      <?php
      wp_reset_postdata();
    endif;
  endif;
  ?>

  <!-- Grid -->
  <section class="cream pb-24 pt-16 px-4 lg:px-6 bg-brand-cream">
    <div class="max-w-7xl mx-auto">
    <?php if ($posts_query->have_posts()): ?>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while ($posts_query->have_posts()):
          $posts_query->the_post();
          $post_categories = get_the_category();
          $category_name = !empty($post_categories) ? $post_categories[0]->name : 'Uncategorized';
          $category_slug = !empty($post_categories) ? $post_categories[0]->slug : 'uncategorized';
          $category_color = chroma_get_category_color($category_slug);
          $post_image = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&w=600&auto=format&fit=crop';
          ?>
          <!-- Post -->
          <article class="group cursor-pointer">
            <a href="<?php the_permalink(); ?>" class="block">
              <div class="rounded-[2rem] overflow-hidden mb-4 h-64 relative">
                <img src="<?php echo esc_url($post_image); ?>"
                  class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                  alt="<?php the_title_attribute(); ?>" />
              </div>
              <span class="text-<?php echo esc_attr($category_color); ?> font-bold text-[10px] uppercase tracking-wider">
                <?php echo esc_html($category_name); ?>
              </span>
              <h3
                class="font-serif text-xl font-bold text-brand-ink mt-2 mb-2 group-hover:text-<?php echo esc_attr($category_color); ?> transition-colors">
                <?php the_title(); ?>
              </h3>
              <p class="text-sm text-brand-ink/90">
                <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?>
              </p>
            </a>
          </article>
        <?php endwhile; ?>
      </div>

      <!-- Pagination: expose every archive page so all stories remain within three clicks. -->
      <?php if ($posts_query->max_num_pages > 1): ?>
        <nav class="mt-12 flex flex-wrap justify-center gap-2" aria-label="<?php esc_attr_e('Stories pagination', 'chroma-excellence'); ?>">
          <?php
          echo wp_kses_post(
            paginate_links(array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'current' => max(1, $paged),
              'total' => (int) $posts_query->max_num_pages,
              'show_all' => true,
              'prev_text' => __('&larr; Previous', 'chroma-excellence'),
              'next_text' => __('Next &rarr;', 'chroma-excellence'),
              'type' => 'plain',
              'before_page_number' => '<span class="inline-flex min-w-11 h-11 items-center justify-center rounded-full border border-brand-ink/10 bg-white px-4 text-sm font-bold text-brand-ink hover:bg-brand-ink hover:text-white transition-colors">',
              'after_page_number' => '</span>',
            ))
          );
          ?>
        </nav>
      <?php endif; ?>

    <?php else: ?>
      <div class="text-center py-16">
        <p class="text-brand-ink/90 text-lg"><?php _e('No stories found. Check back soon!', 'chroma-excellence'); ?></p>
      </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

    <div class="mt-16 rounded-[2.5rem] bg-white border border-chroma-blue/10 shadow-soft p-6 md:p-8">
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        <a href="<?php echo esc_url(home_url('/programs/preschool/')); ?>" class="rounded-3xl bg-brand-cream border border-brand-ink/5 p-6 hover:-translate-y-1 transition-transform">
          <h3 class="font-serif text-xl font-bold text-brand-ink"><?php esc_html_e('How Preschool Prepares Children for Kindergarten Success', 'chroma-excellence'); ?></h3>
          <p class="mt-3 text-sm leading-relaxed text-brand-ink/70"><?php esc_html_e('A family-friendly look at routines, confidence, social skills, and early academics.', 'chroma-excellence'); ?></p>
        </a>
        <a href="<?php echo esc_url(home_url('/about/')); ?>" class="rounded-3xl bg-brand-cream border border-brand-ink/5 p-6 hover:-translate-y-1 transition-transform">
          <h3 class="font-serif text-xl font-bold text-brand-ink"><?php esc_html_e("Early Education Expansion: Chroma Academy's 19 New Locations", 'chroma-excellence'); ?></h3>
          <p class="mt-3 text-sm leading-relaxed text-brand-ink/70"><?php esc_html_e('How Chroma is growing its Metro Atlanta campus community while keeping care personal.', 'chroma-excellence'); ?></p>
        </a>
        <a href="<?php echo esc_url(home_url('/locations/ellenwood-campus/')); ?>" class="rounded-3xl bg-brand-cream border border-brand-ink/5 p-6 hover:-translate-y-1 transition-transform">
          <h3 class="font-serif text-xl font-bold text-brand-ink"><?php esc_html_e('Childcare & Preschool in Ellenwood, GA', 'chroma-excellence'); ?></h3>
          <p class="mt-3 text-sm leading-relaxed text-brand-ink/70"><?php esc_html_e('What families can expect from Chroma care in the Ellenwood community.', 'chroma-excellence'); ?></p>
        </a>
        <a href="<?php echo esc_url(home_url('/locations/south-cobb-campus-austell/')); ?>" class="rounded-3xl bg-brand-cream border border-brand-ink/5 p-6 hover:-translate-y-1 transition-transform">
          <h3 class="font-serif text-xl font-bold text-brand-ink"><?php esc_html_e('Childcare & Preschool in Austell, GA — South Cobb Campus', 'chroma-excellence'); ?></h3>
          <p class="mt-3 text-sm leading-relaxed text-brand-ink/70"><?php esc_html_e('A guide for families considering Chroma’s South Cobb campus in Austell.', 'chroma-excellence'); ?></p>
        </a>
        <a href="<?php echo esc_url(home_url('/locations/midway-campus/')); ?>" class="rounded-3xl bg-brand-cream border border-brand-ink/5 p-6 hover:-translate-y-1 transition-transform md:col-span-2 lg:col-span-1">
          <h3 class="font-serif text-xl font-bold text-brand-ink"><?php esc_html_e('Secure the Best Childcare in Alpharetta, GA — Midway Campus', 'chroma-excellence'); ?></h3>
          <p class="mt-3 text-sm leading-relaxed text-brand-ink/70"><?php esc_html_e('What to know about care, curriculum, and campus fit near Midway and Alpharetta.', 'chroma-excellence'); ?></p>
        </a>
      </div>
    </div>
    </div>
  </section>

  <section class="white borderY py-20 px-4 lg:px-6 bg-white border-y border-chroma-blue/10">
    <div class="max-w-4xl mx-auto text-center">
      <span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Newsletter', 'chroma-excellence'); ?></span>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-5">
        <?php esc_html_e('Get the next guide in your inbox.', 'chroma-excellence'); ?>
      </h2>
      <p class="text-brand-ink/70 text-lg leading-relaxed mb-8">
        <?php esc_html_e('Parenting tips, Chroma stories, and early learning resources delivered occasionally.', 'chroma-excellence'); ?>
      </p>
      <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-brand-ink text-white text-xs font-bold uppercase tracking-[0.18em] shadow-soft">
        <?php esc_html_e('Connect With Us', 'chroma-excellence'); ?>
      </a>
    </div>
  </section>
</main>

<?php get_footer(); ?>

<?php
/**
 * Template Part: Parent Reviews Carousel
 *
 * @package Chroma_Excellence
 */

$reviews = chroma_home_parent_reviews();
$reviews_content = chroma_home_parent_reviews_section();

if (empty($reviews)) {
        return;
}
?>

<section id="reviews" class="reviews white borderY py-20 lg:py-24 bg-white border-y border-chroma-blue/10" data-section="reviews">
        <div class="max-w-7xl mx-auto px-4 lg:px-6">
                <div class="chroma-reviews-grid reveal" data-reviews-carousel>
                        <aside class="reviewSide">
                                <div>
                                        <div class="kicker font-bold tracking-[0.2em] text-xs uppercase mb-3">
                                                <?php echo esc_html($reviews_content['eyebrow']); ?>
                                        </div>
                                        <div class="text-chroma-yellow tracking-[0.2em] text-lg mb-4" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                                        <h2 class="font-serif text-4xl md:text-5xl leading-tight mb-4">
                                                <?php echo esc_html($reviews_content['heading']); ?>
                                        </h2>
                                </div>
                                <p class="text-white/75 leading-relaxed">
                                        <?php echo esc_html($reviews_content['subheading']); ?>
                                </p>
                        </aside>

                        <div class="relative min-w-0">
                                <div class="chroma-review-viewport rounded-[3rem]">
                                        <div class="flex transition-transform duration-500 ease-in-out" data-reviews-track>
                                                <?php foreach ($reviews as $index => $review): ?>
                                                        <?php
                                                        $initials = '';
                                                        $name_parts = preg_split('/\s+/', trim((string) $review['name']));
                                                        foreach (array_slice(array_filter($name_parts), 0, 2) as $part) {
                                                                $initials .= strtoupper(substr($part, 0, 1));
                                                        }
                                                        $initials = $initials ?: 'CP';
                                                        ?>
                                                        <article class="w-full flex-shrink-0 chroma-review-card" data-review-slide="<?php echo esc_attr($index); ?>">
                                                                <blockquote>
                                                                        <?php echo esc_html(wp_trim_words((string) $review['review'], 22, '…')); ?>
                                                                </blockquote>
                                                                <div class="flex items-center gap-4">
                                                                        <div class="chroma-review-avatar"><?php echo esc_html($initials); ?></div>
                                                                        <div>
                                                                                <strong class="text-brand-ink"><?php echo esc_html($review['name']); ?></strong><br>
                                                                                <span class="text-brand-ink/65"><?php echo esc_html($review['location'] ?: __('Chroma family', 'chroma-excellence')); ?></span>
                                                                        </div>
                                                                </div>
                                                        </article>
                                                <?php endforeach; ?>
                                        </div>
                                </div>

                                <?php if (count($reviews) > 1): ?>
                                        <div class="flex items-center justify-between gap-4 mt-6">
                                                <button class="w-12 h-12 inline-flex items-center justify-center bg-white rounded-full shadow-lg text-brand-ink hover:bg-chroma-blue hover:text-white transition"
                                                        data-review-prev
                                                        aria-label="<?php echo esc_attr($reviews_content['prev_label']); ?>">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                        </svg>
                                                </button>
                                                <div class="flex justify-center gap-2" data-reviews-dots>
                                                        <?php foreach ($reviews as $index => $review): ?>
                                                                <button class="w-3 h-3 rounded-full transition-all duration-300 <?php echo 0 === $index ? 'bg-chroma-red w-8' : 'bg-chroma-blue/30 hover:bg-chroma-blue/50'; ?>"
                                                                        data-review-dot="<?php echo esc_attr($index); ?>"
                                                                        aria-label="<?php echo esc_attr(sprintf($reviews_content['dot_aria_label_format'], $index + 1)); ?>"></button>
                                                        <?php endforeach; ?>
                                                </div>
                                                <button class="w-12 h-12 inline-flex items-center justify-center bg-white rounded-full shadow-lg text-brand-ink hover:bg-chroma-blue hover:text-white transition"
                                                        data-review-next
                                                        aria-label="<?php echo esc_attr($reviews_content['next_label']); ?>">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                </button>
                                        </div>
                                <?php endif; ?>
                        </div>
                </div>
        </div>
</section>

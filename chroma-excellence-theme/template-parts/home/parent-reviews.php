<?php
/**
 * Template Part: Parent Reviews Carousel
 * Testimonials from Chroma parents
 *
 * @package Chroma_Excellence
 */

$reviews = chroma_home_parent_reviews();

if (empty($reviews)) {
        return;
}
?>

<section id="reviews" class="py-20 bg-white border-y border-chroma-blue/10" data-section="reviews">
        <div class="max-w-6xl mx-auto px-4 lg:px-6">
                <div class="text-center mb-12">
                        <span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-4 block"><?php _e('What Parents Say', 'chroma-excellence'); ?></span>
                        <h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3"><?php _e('Trusted by thousands of Atlanta families', 'chroma-excellence'); ?></h2>
                        <p class="text-brand-ink max-w-2xl mx-auto"><?php _e('Don\'t just take our word for it. Here\'s what parents have to say about their experience with Chroma Early Learning.', 'chroma-excellence'); ?></p>
                </div>

                <div class="relative" data-reviews-carousel>
                        <!-- Reviews Container -->
                        <div class="overflow-hidden">
                                <div class="flex transition-transform duration-500 ease-in-out" data-reviews-track>
                                        <?php foreach ($reviews as $index => $review): ?>
                                                <div class="w-full flex-shrink-0 px-4"
                                                        data-review-slide="<?php echo esc_attr($index); ?>">
                                                        <div
                                                                class="bg-brand-cream rounded-3xl p-8 md:p-12 max-w-4xl mx-auto border border-chroma-blue/10 shadow-soft">
                                                                <!-- Star Rating -->
                                                                <div class="flex justify-center gap-1 mb-6">
                                                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                                                                <?php if ($i < $review['rating']): ?>
                                                                                        <svg class="w-6 h-6 text-chroma-yellow fill-current"
                                                                                                viewBox="0 0 24 24">
                                                                                                <path
                                                                                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                                                        </svg>
                                                                                <?php else: ?>
                                                                                        <svg class="w-6 h-6 text-chroma-blue/20 fill-current"
                                                                                                viewBox="0 0 24 24">
                                                                                                <path
                                                                                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                                                        </svg>
                                                                                <?php endif; ?>
                                                                        <?php endfor; ?>
                                                                </div>

                                                                <!-- Review Text -->
                                                                <blockquote
                                                                        class="text-brand-ink text-lg md:text-xl leading-relaxed mb-6 text-center font-serif italic">
                                                                        "<?php echo esc_html($review['review']); ?>"
                                                                </blockquote>

                                                                <!-- Author Info -->
                                                                <div class="text-center">
                                                                        <p class="font-bold text-brand-ink">
                                                                                <?php echo esc_html($review['name']); ?></p>
                                                                        <?php if (!empty($review['location'])): ?>
                                                                                <p class="text-sm text-brand-ink">
                                                                                        <?php echo esc_html($review['location']); ?>
                                                                                </p>
                                                                        <?php endif; ?>
                                                                </div>
                                                        </div>
                                                </div>
                                        <?php endforeach; ?>
                                </div>
                        </div>

                        <!-- Navigation Dots -->
                        <?php if (count($reviews) > 1): ?>
                                <div class="flex justify-center gap-2 mt-8" data-reviews-dots>
                                        <?php foreach ($reviews as $index => $review): ?>
                                                <button class="w-3 h-3 rounded-full transition-all duration-300 <?php echo 0 === $index ? 'bg-chroma-red w-8' : 'bg-chroma-blue/30 hover:bg-chroma-blue/50'; ?>"
                                                        data-review-dot="<?php echo esc_attr($index); ?>"
                                                        aria-label="<?php echo esc_attr(sprintf('Go to review %d', $index + 1)); ?>"></button>
                                        <?php endforeach; ?>
                                </div>
                        <?php endif; ?>

                        <!-- Navigation Arrows (optional for larger screens) -->
                        <?php if (count($reviews) > 1): ?>
                                <button class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 w-12 h-12 items-center justify-center bg-white rounded-full shadow-lg text-brand-ink hover:bg-chroma-blue hover:text-white transition"
                                        data-review-prev aria-label="Previous review">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 19l-7-7 7-7" />
                                        </svg>
                                </button>
                                <button class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 w-12 h-12 items-center justify-center bg-white rounded-full shadow-lg text-brand-ink hover:bg-chroma-blue hover:text-white transition"
                                        data-review-next aria-label="Next review">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5l7 7-7 7" />
                                        </svg>
                                </button>
                        <?php endif; ?>
                </div>
        </div>
</section>
<?php
/**
 * Template Part: Stats Strip
 * Displays 4 key stats from hardcoded helpers (no ACF)
 *
 * @package Chroma_Excellence
 */

$stats = chroma_home_stats();
if (!$stats) {
        return;
}
?>

<section class="bg-white py-12 border-y border-chroma-blue/10" data-section="stats">
        <div class="max-w-6xl mx-auto px-4 lg:px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <?php foreach ($stats as $stat): ?>
                        <?php
                        $color_class = !empty($stat['color']) ? 'text-' . $stat['color'] : 'text-chroma-red';
                        ?>
                        <div class="group">
                                <div
                                        class="font-serif text-3xl font-bold <?php echo esc_attr($color_class); ?> group-hover:scale-110 transition-transform duration-300">
                                        <?php echo esc_html($stat['value']); ?></div>
                                <div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink">
                                        <?php echo esc_html($stat['label']); ?></div>
                        </div>
                <?php endforeach; ?>
        </div>
</section>

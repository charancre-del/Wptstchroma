<?php
/**
 * Daily Schedule Tabs
 * Template Part: Schedule Tabs
 *
 * @package Chroma_Excellence
 */

$tracks = chroma_home_schedule_tracks();
$schedule_content = chroma_home_schedule_content();

if (empty($tracks)) {
        return;
}
?>

<section id="schedule" class="cream py-20 lg:py-24 bg-brand-cream relative" data-section="schedule">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-chroma-red via-chroma-yellow to-chroma-blue opacity-40"></div>
        <div class="max-w-6xl mx-auto px-4 lg:px-6" data-schedule>
                <script type="application/json" data-schedule-tracks>
                        <?php echo wp_json_encode($tracks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                </script>
                <div class="head reveal text-center max-w-3xl mx-auto mb-12">
                        <span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-4 block">
                                <?php echo esc_html($schedule_content['eyebrow']); ?>
                        </span>
                        <h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3">
                                <?php echo esc_html($schedule_content['heading']); ?>
                        </h2>
                        <p class="text-brand-ink max-w-2xl mx-auto">
                                <?php echo esc_html($schedule_content['subheading']); ?>
                        </p>
                </div>

                <div class="chroma-schedule-tabs-wrap mb-12">
                        <div class="chroma-schedule-tabs bg-white border border-chroma-blue/15 p-1 rounded-2xl flex gap-2"
                                data-schedule-tabs>
                                <?php foreach ($tracks as $index => $track): ?>
                                        <?php
                                        $is_active = 0 === $index;
                                        $tab_classes = $is_active
                                                ? 'bg-chroma-red text-white shadow-soft'
                                                : 'text-gray-900 hover:text-chroma-red';
                                        ?>
                                        <button
                                                class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 <?php echo esc_attr($tab_classes); ?>"
                                                data-schedule-tab="<?php echo esc_attr($track['key']); ?>"
                                                aria-pressed="<?php echo esc_attr($is_active ? 'true' : 'false'); ?>">
                                                <?php echo esc_html($track['label'] ?? ucfirst($track['key'])); ?>
                                        </button>
                                <?php endforeach; ?>
                        </div>
                </div>

                <p class="scheduleHint text-center -mt-7 mb-8 text-sm text-brand-ink/75">
                        <?php esc_html_e('Choose a program above, then slide through a sample Chroma day.', 'chroma-excellence'); ?>
                </p>

                <?php foreach ($tracks as $index => $track): ?>
                        <?php
                        $is_active = 0 === $index;
                        $panel_classes = $is_active ? 'tab-content active' : 'tab-content hidden';
                        $background_tint = !empty($track['background']) ? $track['background'] : 'bg-brand-cream';
                        ?>
                        <div class="<?php echo esc_attr($panel_classes); ?>" data-schedule-panel="<?php echo esc_attr($track['key']); ?>">
                                <?php
                                $steps = !empty($track['steps']) && is_array($track['steps']) ? array_values($track['steps']) : array();
                                $first_step = $steps[0] ?? array('time' => '', 'title' => $track['title'], 'copy' => $track['description'] ?? '');
                                ?>
                                <div class="day reveal" data-sun-schedule>
                                        <script type="application/json" data-sun-steps>
                                                <?php echo wp_json_encode($steps, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                                        </script>
                                        <div class="sky" aria-hidden="true">
                                                <div class="sun" data-sun-orb></div>
                                                <div class="cloud c1"></div>
                                                <div class="cloud c2"></div>
                                        </div>
                                        <div class="panel">
                                                <div class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3">
                                                        <?php echo esc_html($track['title']); ?>
                                                </div>
                                                <div class="time font-serif" data-sun-time><?php echo esc_html($first_step['time']); ?></div>
                                                <h3 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-4" data-sun-title>
                                                        <?php echo esc_html($first_step['title']); ?>
                                                </h3>
                                                <p class="text-brand-ink/75 text-lg leading-relaxed min-h-[7rem]" data-sun-copy>
                                                        <?php echo esc_html($first_step['copy']); ?>
                                                </p>
                                                <div class="track mt-8"><div class="progress" data-sun-progress></div></div>
                                                <input
                                                        class="mt-5 w-full accent-chroma-yellow"
                                                        data-sun-range
                                                        type="range"
                                                        min="0"
                                                        max="<?php echo esc_attr(max(0, count($steps) - 1)); ?>"
                                                        value="0"
                                                        step="1"
                                                        aria-label="<?php echo esc_attr($track['title']); ?>"
                                                />
                                        </div>
                                </div>
                        </div>
                <?php endforeach; ?>
        </div>
</section>

<?php
/**
 * Template Part: Locations Preview
 * Interactive map + scrollable campus cards
 *
 * @package Chroma_Excellence
 */

$locations_data = chroma_home_locations_preview();
if (!$locations_data) {
    return;
}

$map_json = $locations_data['map_points'] ?? array();
$featured = $locations_data['featured'] ?? array();
$grouped = $locations_data['grouped'] ?? array();
$explorer_id = 'chroma-locations-map';
$hide_heading = !empty($args['hide_heading']);
$map_only = !empty($args['map_only']);
$stacked = !empty($args['stacked']);
?>

<section id="locations" class="chroma-locations-showcase py-20 md:py-24 bg-white <?php echo $map_only ? 'chroma-map-only' : ''; ?> <?php echo $stacked ? 'chroma-locations-stacked' : ''; ?>" data-section="locations">
    <div class="max-w-[112rem] mx-auto px-4 sm:px-6 lg:px-8">

        <?php if (!$hide_heading): ?>
            <div class="text-center mb-12">
                <h2 class="font-serif text-4xl md:text-6xl font-semibold tracking-[-0.035em] text-brand-ink mb-4">
                    <?php echo esc_html($locations_data['heading'] ?: __('Our Locations', 'chroma-excellence')); ?>
                </h2>
                <?php if (!empty($locations_data['subheading'])): ?>
                    <p class="text-brand-ink/70 text-base md:text-lg leading-relaxed max-w-3xl mx-auto">
                        <?php echo esc_html($locations_data['subheading']); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($map_json) && !empty($featured)): ?>
            <div class="chroma-location-explorer grid <?php echo $stacked ? 'grid-cols-1' : 'lg:grid-cols-[minmax(0,0.98fr)_minmax(0,0.92fr)]'; ?> gap-7 xl:gap-11 items-stretch"
                data-location-explorer
                data-map-target="<?php echo esc_attr($explorer_id); ?>">
                <div class="chroma-location-map-panel relative rounded-[2rem] overflow-hidden border border-chroma-blue/10 shadow-soft bg-chroma-blueLight/30 min-h-[32rem]">
                    <div id="<?php echo esc_attr($explorer_id); ?>"
                        data-chroma-map
                        data-location-map
                        data-chroma-locations='<?php echo esc_attr(wp_json_encode($map_json)); ?>'
                        class="absolute inset-0"></div>
                </div>

                <div class="chroma-location-list-panel rounded-[2rem] border border-chroma-blue/10 shadow-soft bg-white p-5 md:p-7 flex flex-col min-h-[32rem]">
                    <div class="flex gap-3 flex-wrap mb-5" data-location-filters>
                        <button type="button"
                            class="chroma-location-filter"
                            data-location-filter="closest"
                            aria-pressed="false">
                            <?php esc_html_e('Use My Location', 'chroma-excellence'); ?>
                        </button>
                        <?php foreach ($grouped as $group):
                            $slug = sanitize_title($group['slug'] ?? $group['label'] ?? '');
                            if (!$slug) {
                                continue;
                            }
                            ?>
                            <button type="button"
                                class="chroma-location-filter"
                                data-location-filter="<?php echo esc_attr($slug); ?>"
                                aria-pressed="false">
                                <?php echo esc_html($group['label'] ?? $slug); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <p class="chroma-location-status rounded-[1.25rem] bg-chroma-cream px-5 py-4 text-sm md:text-base text-brand-ink/75 mb-5"
                        data-location-status>
                        <?php esc_html_e('Share your location to sort campuses by distance, or choose a region to zoom the map.', 'chroma-excellence'); ?>
                    </p>

                    <div class="chroma-location-card-scroll pr-2 -mr-2" data-location-list>
                        <?php foreach ($featured as $location):
                            $location_id = (int) ($location['id'] ?? 0);
                            $location_map = null;
                            foreach ($map_json as $point) {
                                if ((int) ($point['id'] ?? 0) === $location_id) {
                                    $location_map = $point;
                                    break;
                                }
                            }

                            $region_slugs = array_map('sanitize_title', $location['region_slugs'] ?? array());
                            $region_attr = implode(' ', array_filter($region_slugs));
                            $city_state = trim(($location['city'] ?? '') . (!empty($location['state']) ? ', ' . strtoupper((string) $location['state']) : ''));
                            ?>
                            <article class="chroma-location-card rounded-[1.35rem] border border-chroma-blue/10 bg-white mb-3 transition"
                                data-location-card-wrap
                                data-location-id="<?php echo esc_attr($location_id); ?>"
                                data-location-regions="<?php echo esc_attr($region_attr); ?>"
                                data-location-lat="<?php echo esc_attr($location_map['lat'] ?? ''); ?>"
                                data-location-lng="<?php echo esc_attr($location_map['lng'] ?? ''); ?>">
                                <button type="button"
                                    class="w-full text-left p-5"
                                    data-location-card="<?php echo esc_attr($location_id); ?>">
                                    <span class="block text-xl md:text-2xl font-bold text-brand-ink leading-tight mb-1">
                                        <?php echo esc_html($location['title'] ?? ''); ?>
                                    </span>
                                    <?php if ($city_state): ?>
                                        <span class="block text-brand-ink/75 font-semibold">
                                            <?php echo esc_html($city_state); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($location['address'])): ?>
                                        <span class="block text-brand-ink/60 text-sm mt-3">
                                            <?php echo esc_html($location['address']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="block text-brand-ink/70 text-xs uppercase tracking-[0.16em] mt-2">
                                        <?php esc_html_e('Click card to zoom map', 'chroma-excellence'); ?>
                                    </span>
                                    <span class="block text-chroma-red font-bold text-sm mt-2 hidden" data-location-distance></span>
                                </button>

                                <div class="px-5 pb-5 flex flex-wrap gap-2 text-sm">
                                    <?php if (!empty($location['phone'])): ?>
                                        <a class="min-h-11 inline-flex items-center px-3 text-brand-ink/75 hover:text-chroma-red transition"
                                            href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', (string) $location['phone'])); ?>">
                                            <?php echo esc_html($location['phone']); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($location['url'])): ?>
                                        <a class="campus-card-link min-h-11 inline-flex items-center px-3 font-bold text-chroma-red hover:text-brand-ink transition"
                                            href="<?php echo esc_url($location['url']); ?>">
                                            <?php esc_html_e('View campus', 'chroma-excellence'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($locations_data['cta_link'])): ?>
                        <div class="pt-4">
                            <a href="<?php echo esc_url($locations_data['cta_link']); ?>"
                                class="inline-flex items-center justify-center px-7 py-4 rounded-full border border-chroma-blue/20 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.18em] hover:border-chroma-blue hover:text-chroma-blue transition">
                                <?php echo esc_html($locations_data['cta_label'] ?: get_theme_mod('chroma_locations_label', __('View All Locations', 'chroma-excellence'))); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

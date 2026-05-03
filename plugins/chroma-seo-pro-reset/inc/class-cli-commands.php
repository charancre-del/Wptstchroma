<?php
/**
 * Chroma WP-CLI Commands
 * Adds translation management commands to WP-CLI.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_CLI')) {
    return;
}

class Chroma_CLI_Commands
{
    /**
     * Translate a single post to Spanish.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : The ID of the post to translate.
     *
     * ## EXAMPLES
     *
     *     wp chroma translate 123
     *
     * @when after_wp_load
     */
    public function translate($args, $assoc_args)
    {
        $post_id = intval($args[0]);
        $post = get_post($post_id);

        if (!$post) {
            WP_CLI::error("Post $post_id not found.");
            return;
        }

        WP_CLI::log("Translating post $post_id: {$post->post_title}...");

        $fields = [
            '_chroma_es_title' => $post->post_title,
            '_chroma_es_content' => $post->post_content,
            '_chroma_es_excerpt' => $post->post_excerpt,
        ];

        $translated = Chroma_Translation_Engine::translate_bulk($fields, 'es', 'Translate for a childcare website.');

        if (isset($translated['_error'])) {
            WP_CLI::error("Translation failed: " . $translated['_error']);
            return;
        }

        foreach ($translated as $key => $value) {
            if (strpos($key, '_chroma_es_') === 0 && !empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }

        WP_CLI::success("Post $post_id translated successfully.");
    }

    /**
     * Translate all posts of a given type.
     *
     * ## OPTIONS
     *
     * [--post-type=<type>]
     * : The post type to translate. Default: page
     *
     * [--force]
     * : Retranslate even if Spanish content exists.
     *
     * ## EXAMPLES
     *
     *     wp chroma translate-all --post-type=location
     *
     * @when after_wp_load
     */
    public function translate_all($args, $assoc_args)
    {
        $post_type = isset($assoc_args['post-type']) ? $assoc_args['post-type'] : 'page';
        $force = isset($assoc_args['force']);

        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $count = 0;
        $total = count($posts);

        WP_CLI::log("Found $total {$post_type}s to process.");

        foreach ($posts as $post) {
            $has_translation = get_post_meta($post->ID, '_chroma_es_content', true);
            
            if ($has_translation && !$force) {
                WP_CLI::log("Skipping {$post->ID} (already translated)");
                continue;
            }

            WP_CLI::log("Translating {$post->ID}: {$post->post_title}...");
            
            $fields = [
                '_chroma_es_title' => $post->post_title,
                '_chroma_es_content' => $post->post_content,
                '_chroma_es_excerpt' => $post->post_excerpt,
            ];

            $translated = Chroma_Translation_Engine::translate_bulk($fields, 'es', 'Translate for a childcare website.');

            if (!isset($translated['_error'])) {
                foreach ($translated as $key => $value) {
                    if (strpos($key, '_chroma_es_') === 0 && !empty($value)) {
                        update_post_meta($post->ID, $key, $value);
                    }
                }
                $count++;
            }
        }

        WP_CLI::success("Translated $count of $total {$post_type}s.");
    }

    /**
     * Flush translation memory cache.
     *
     * ## EXAMPLES
     *
     *     wp chroma flush-cache
     *
     * @when after_wp_load
     */
    public function flush_cache($args, $assoc_args)
    {
        global $wpdb;
        
        // Use prepared statements with esc_like for security
        $like_pattern = $wpdb->esc_like('_transient_chroma_trans_') . '%';
        $timeout_pattern = $wpdb->esc_like('_transient_timeout_chroma_trans_') . '%';
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_pattern,
                $timeout_pattern
            )
        );

        WP_CLI::success("Flushed translation memory cache. Removed $deleted entries.");
    }

    /**
     * Show translation statistics.
     *
     * ## EXAMPLES
     *
     *     wp chroma stats
     *
     * @when after_wp_load
     */
    public function stats($args, $assoc_args)
    {
        $post_types = ['page', 'location', 'program'];
        $stats = [];

        foreach ($post_types as $type) {
            $total = wp_count_posts($type)->publish;
            
            $translated = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$GLOBALS['wpdb']->postmeta} 
                 WHERE meta_key = '_chroma_es_content' AND meta_value != ''
                 AND post_id IN (SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_type = %s AND post_status = 'publish')",
                $type
            ));

            $stats[$type] = [
                'total' => $total,
                'translated' => $translated,
                'percent' => $total > 0 ? round(($translated / $total) * 100) : 0
            ];
        }

        WP_CLI::log("\n📊 Translation Statistics\n");
        
        foreach ($stats as $type => $data) {
            $bar = str_repeat('█', (int)($data['percent'] / 5)) . str_repeat('░', 20 - (int)($data['percent'] / 5));
            WP_CLI::log(sprintf(
                "%s: %s %d%% (%d/%d)",
                str_pad(ucfirst($type), 12),
                $bar,
                $data['percent'],
                $data['translated'],
                $data['total']
            ));
        }

        WP_CLI::log("");
    }

    /**
     * Audit sitemap coverage and EN/ES SEO source duplication.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Options: summary, json. Default: summary
     *
     * [--limit=<number>]
     * : Number of example rows to print in summary output. Default: 20
     *
     * ## EXAMPLES
     *
     *     wp chroma seo-audit
     *     wp chroma seo-audit --format=json
     *
     * @when after_wp_load
     */
    public function seo_audit($args, $assoc_args)
    {
        $format = isset($assoc_args['format']) ? strtolower((string) $assoc_args['format']) : 'summary';
        $limit = isset($assoc_args['limit']) ? max(1, (int) $assoc_args['limit']) : 20;

        $report = [
            'sitemap' => $this->build_sitemap_audit_report(),
            'title_duplicates' => $this->build_uniqueness_report('title'),
            'meta_description_duplicates' => $this->build_uniqueness_report('meta_description'),
            'city_aliases' => $this->build_city_alias_report(),
        ];

        if ($format === 'json') {
            WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return;
        }

        WP_CLI::log('');
        WP_CLI::log('Chroma SEO Audit');
        WP_CLI::log('');

        $sitemap = $report['sitemap'];
        WP_CLI::log(sprintf('Sitemap URLs generated: %d', $sitemap['generated_count']));
        WP_CLI::log(sprintf('Missing from generated sitemap: %d', count($sitemap['missing'])));
        WP_CLI::log(sprintf('Spanish home in sitemap: %s', $sitemap['has_es_home'] ? 'yes' : 'no'));

        $title_report = $report['title_duplicates'];
        $meta_report = $report['meta_description_duplicates'];
        WP_CLI::log(sprintf('EN/ES duplicate titles: %d', count($title_report['duplicates'])));
        WP_CLI::log(sprintf('EN/ES duplicate meta descriptions: %d', count($meta_report['duplicates'])));
        WP_CLI::log(sprintf('Canonical city aliases tracked: %d', count($report['city_aliases'])));

        $this->output_example_rows('Missing sitemap URLs', $sitemap['missing'], $limit);
        $this->output_example_rows('Duplicate titles', $title_report['duplicates'], $limit);
        $this->output_example_rows('Duplicate meta descriptions', $meta_report['duplicates'], $limit);
        $this->output_example_rows('City aliases', $report['city_aliases'], $limit);
    }

    /**
     * Build a local sitemap coverage report from the current generation sources.
     *
     * @return array
     */
    private function build_sitemap_audit_report()
    {
        $generated = $this->get_generated_sitemap_urls();
        $expected = [];

        foreach ((array) chroma_get_standard_sitemap_urls() as $url_data) {
            $loc = (string) ($url_data['loc'] ?? '');
            if ($loc !== '') {
                $expected[$loc] = true;
            }
        }

        if (class_exists('Chroma_Combo_Page_Generator')) {
            foreach ((array) Chroma_Combo_Page_Generator::get_all_combos() as $combo) {
                $url = (string) ($combo['url'] ?? '');
                if ($url === '') {
                    continue;
                }

                $expected[$url] = true;
                $expected[$this->to_spanish_url($url)] = true;
            }
        }

        if (class_exists('Chroma_Near_Me_Pages')) {
            foreach ((array) Chroma_Near_Me_Pages::get_sitemap_urls() as $url) {
                $url = (string) $url;
                if ($url === '') {
                    continue;
                }

                $expected[$url] = true;
                $expected[$this->to_spanish_url($url)] = true;
            }
        }

        $missing = [];
        foreach (array_keys($expected) as $url) {
            if (!isset($generated[$url])) {
                $missing[] = [
                    'url' => $url,
                    'type' => $this->classify_route_type($url),
                ];
            }
        }

        return [
            'generated_count' => count($generated),
            'has_es_home' => isset($generated[home_url('/es/')]),
            'missing' => $missing,
        ];
    }

    /**
     * Build a duplication report for EN/ES title or meta-description pairs.
     *
     * @param string $field
     * @return array
     */
    private function build_uniqueness_report($field)
    {
        $pairs = array_merge(
            $this->collect_static_route_pairs(),
            $this->collect_archive_pairs(),
            $this->collect_singular_pairs(),
            $this->collect_combo_pairs(),
            $this->collect_near_me_pairs()
        );

        $duplicates = [];
        foreach ($pairs as $pair) {
            $en_value = $this->normalize_audit_value($pair['en'][$field] ?? '');
            $es_value = $this->normalize_audit_value($pair['es'][$field] ?? '');

            if ($en_value === '' || $es_value === '') {
                continue;
            }

            if ($en_value === $es_value) {
                $duplicates[] = [
                    'route' => $pair['route'],
                    'type' => $pair['type'],
                    'en' => $pair['en'][$field] ?? '',
                    'es' => $pair['es'][$field] ?? '',
                ];
            }
        }

        return [
            'checked' => count($pairs),
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Collect canonical city aliases that should normalize to a canonical slug.
     *
     * @return array
     */
    private function build_city_alias_report()
    {
        if (!function_exists('chroma_seo_get_virtual_city_records')) {
            return [];
        }

        $aliases = [];
        foreach ((array) chroma_seo_get_virtual_city_records() as $record) {
            $canonical_slug = sanitize_title((string) ($record['canonical_slug'] ?? ''));
            $state = strtolower((string) ($record['state'] ?? 'ga'));
            $city_name = (string) ($record['city_name'] ?? '');

            foreach ((array) ($record['aliases'] ?? []) as $alias) {
                $alias = sanitize_title((string) $alias);
                if ($alias === '' || $alias === $canonical_slug) {
                    continue;
                }

                $aliases[] = [
                    'city' => $city_name,
                    'state' => strtoupper($state),
                    'alias' => $alias,
                    'canonical_slug' => $canonical_slug,
                    'example_combo' => home_url('/preschool-in-' . $alias . '-' . $state . '/'),
                    'expected_combo' => home_url('/preschool-in-' . $canonical_slug . '-' . $state . '/'),
                ];
            }
        }

        return $aliases;
    }

    /**
     * Collect static page EN/ES SEO pairs.
     *
     * @return array
     */
    private function collect_static_route_pairs()
    {
        if (!function_exists('chroma_seo_get_static_route_defaults') || !function_exists('chroma_seo_get_static_profile')) {
            return [];
        }

        $pairs = [];
        foreach (array_keys((array) chroma_seo_get_static_route_defaults()) as $route_key) {
            $en_path = $route_key === 'home' ? '/' : '/' . $route_key . '/';
            $es_path = $route_key === 'home' ? '/es/' : '/es/' . $route_key . '/';

            $pairs[] = [
                'type' => 'static',
                'route' => home_url($en_path),
                'en' => chroma_seo_get_static_profile($route_key, 'en'),
                'es' => chroma_seo_get_static_profile($route_key, 'es'),
            ];
        }

        return $pairs;
    }

    /**
     * Collect archive EN/ES SEO pairs.
     *
     * @return array
     */
    private function collect_archive_pairs()
    {
        if (!function_exists('chroma_seo_get_archive_profile')) {
            return [];
        }

        $pairs = [];
        $archives = [
            'location' => '/locations/',
            'program' => '/programs/',
            'city' => '/communities/',
        ];

        foreach ($archives as $post_type => $path) {
            $pairs[] = [
                'type' => 'archive',
                'route' => home_url($path),
                'en' => chroma_seo_get_archive_profile($post_type, 'en'),
                'es' => chroma_seo_get_archive_profile($post_type, 'es'),
            ];
        }

        return $pairs;
    }

    /**
     * Collect singular EN/ES SEO pairs.
     *
     * @return array
     */
    private function collect_singular_pairs()
    {
        if (!function_exists('chroma_seo_build_singular_profile')) {
            return [];
        }

        $pairs = [];
        $posts = get_posts([
            'post_type' => ['page', 'post', 'program', 'location', 'city'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        foreach ($posts as $post) {
            if ($post->post_type === 'page' && function_exists('chroma_seo_get_static_route_key')) {
                $route_key = chroma_seo_get_static_route_key('/' . $post->post_name . '/');
                if ($route_key !== '') {
                    continue;
                }
            }

            $en = $this->build_default_english_singular_profile($post);
            $es = chroma_seo_build_singular_profile($post, 'es');

            if (empty($es['title'])) {
                $es['title'] = $en['title'];
            }

            if (empty($es['meta_description'])) {
                $es['meta_description'] = $en['meta_description'];
            }

            $pairs[] = [
                'type' => 'singular:' . $post->post_type,
                'route' => get_permalink($post),
                'en' => $en,
                'es' => $es,
            ];
        }

        return $pairs;
    }

    /**
     * Collect combo EN/ES SEO pairs.
     *
     * @return array
     */
    private function collect_combo_pairs()
    {
        if (!class_exists('Chroma_Combo_Page_Generator') || !function_exists('chroma_seo_build_combo_profile') || !function_exists('chroma_seo_resolve_virtual_city_context')) {
            return [];
        }

        $pairs = [];
        foreach ((array) Chroma_Combo_Page_Generator::get_all_combos() as $combo) {
            $program = $combo['program'] ?? null;
            $city_context = chroma_seo_resolve_virtual_city_context((string) ($combo['city_slug'] ?? ''), (string) ($combo['state'] ?? ''));
            if (!$program instanceof WP_Post || !is_array($city_context)) {
                continue;
            }

            $pairs[] = [
                'type' => 'combo',
                'route' => (string) ($combo['url'] ?? ''),
                'en' => chroma_seo_build_combo_profile($program, $city_context, 'en'),
                'es' => chroma_seo_build_combo_profile($program, $city_context, 'es'),
            ];
        }

        return $pairs;
    }

    /**
     * Collect near-me EN/ES SEO pairs.
     *
     * @return array
     */
    private function collect_near_me_pairs()
    {
        if (!function_exists('chroma_seo_build_near_me_profile')) {
            return [];
        }

        $pairs = [];
        $keywords = ['daycare', 'preschool', 'childcare', 'pre-k', 'infant-care'];

        foreach ($keywords as $keyword) {
            $pairs[] = [
                'type' => 'near-me',
                'route' => home_url('/' . $keyword . '-near-me/'),
                'en' => chroma_seo_build_near_me_profile($keyword, null, 'en'),
                'es' => chroma_seo_build_near_me_profile($keyword, null, 'es'),
            ];
        }

        if (function_exists('chroma_seo_get_virtual_city_records')) {
            foreach ((array) chroma_seo_get_virtual_city_records() as $city_context) {
                foreach ($keywords as $keyword) {
                    $pairs[] = [
                        'type' => 'near-me-city',
                        'route' => home_url('/' . $keyword . '-near-' . $city_context['canonical_slug'] . '-' . strtolower((string) $city_context['state']) . '/'),
                        'en' => chroma_seo_build_near_me_profile($keyword, $city_context, 'en'),
                        'es' => chroma_seo_build_near_me_profile($keyword, $city_context, 'es'),
                    ];
                }
            }
        }

        return $pairs;
    }

    /**
     * Build a fallback English singular profile for auditing comparisons.
     *
     * @param WP_Post $post
     * @return array
     */
    private function build_default_english_singular_profile($post)
    {
        $title = (string) get_the_title($post);
        $meta_description = '';

        if ($post->post_type === 'program') {
            $meta_description = trim((string) get_post_meta($post->ID, 'program_meta_description', true));
        } elseif ($post->post_type === 'page' && $post->post_name === 'about') {
            $meta_description = trim((string) get_post_meta($post->ID, 'about_meta_description', true));
        } else {
            $meta_description = trim((string) get_post_meta($post->ID, 'meta_description', true));
        }

        if ($meta_description === '') {
            $excerpt = chroma_seo_clean_text((string) $post->post_excerpt);
            if ($excerpt !== '') {
                $meta_description = $excerpt;
            } else {
                $meta_description = wp_trim_words(chroma_seo_clean_text((string) $post->post_content), 28, '…');
            }
        }

        return [
            'title' => $title,
            'meta_description' => $meta_description,
        ];
    }

    /**
     * Get a deduplicated set of generated sitemap URLs.
     *
     * @return array
     */
    private function get_generated_sitemap_urls()
    {
        $urls = array_merge(
            (array) apply_filters('chroma_sitemap_urls', []),
            (array) chroma_get_standard_sitemap_urls()
        );

        $generated = [];
        foreach ($urls as $url_data) {
            $loc = (string) ($url_data['loc'] ?? '');
            if ($loc !== '') {
                $generated[$loc] = true;
            }
        }

        return $generated;
    }

    /**
     * Convert an English URL to its Spanish path variant.
     *
     * @param string $url
     * @return string
     */
    private function to_spanish_url($url)
    {
        $path = (string) wp_parse_url((string) $url, PHP_URL_PATH);
        if ($path === '') {
            return home_url('/es/');
        }

        if ($path === '/') {
            return home_url('/es/');
        }

        return home_url('/es' . user_trailingslashit('/' . ltrim($path, '/')));
    }

    /**
     * Normalize values for EN/ES duplication checks.
     *
     * @param string $value
     * @return string
     */
    private function normalize_audit_value($value)
    {
        $value = chroma_seo_clean_text((string) $value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }

        return strtolower($value);
    }

    /**
     * Classify a route family for summary output.
     *
     * @param string $url
     * @return string
     */
    private function classify_route_type($url)
    {
        $path = (string) wp_parse_url((string) $url, PHP_URL_PATH);

        if (preg_match('#/(es/)?[a-z0-9-]+-in-[a-z-]+-[a-z]{2}/?$#i', $path)) {
            return 'combo';
        }

        if (preg_match('#/(es/)?[a-z0-9-]+-near(?:-me|-[a-z-]+-[a-z]{2})/?$#i', $path)) {
            return 'near-me';
        }

        if (preg_match('#^/(es/)?(locations|programs|communities)/?$#i', $path)) {
            return 'archive';
        }

        if ($path === '/es/' || $path === '/') {
            return 'home';
        }

        return 'standard';
    }

    /**
     * Print example rows in summary mode.
     *
     * @param string $heading
     * @param array  $rows
     * @param int    $limit
     * @return void
     */
    private function output_example_rows($heading, array $rows, $limit)
    {
        if (empty($rows)) {
            return;
        }

        WP_CLI::log('');
        WP_CLI::log($heading . ':');

        $count = 0;
        foreach ($rows as $row) {
            if ($count >= $limit) {
                break;
            }

            $parts = [];
            foreach ($row as $key => $value) {
                $parts[] = $key . '=' . (is_scalar($value) ? (string) $value : wp_json_encode($value));
            }

            WP_CLI::log(' - ' . implode(' | ', $parts));
            $count++;
        }
    }
}

WP_CLI::add_command('chroma', 'Chroma_CLI_Commands');



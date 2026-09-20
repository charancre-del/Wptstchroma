<?php
/**
 * WordPress Cleanup Functions
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
        exit;
}

/**
 * Disable comments on attachments
 */
function chroma_disable_attachment_comments($open, $post_id)
{
        $post = get_post($post_id);
        if ($post && $post->post_type === 'attachment') {
                return false;
        }
        return $open;
}
add_filter('comments_open', 'chroma_disable_attachment_comments', 10, 2);

/**
 * Redirect attachment pages to parent or home
 */
function chroma_redirect_attachment_pages()
{
        if (is_attachment()) {
                global $post;
                if ($post && $post->post_parent) {
                        wp_safe_redirect(get_permalink($post->post_parent), 301);
                } else {
                        chroma_send_gone_response(
                                __('Attachment Unavailable', 'chroma-excellence'),
                                __('This attachment URL is no longer available.', 'chroma-excellence')
                        );
                }
                exit;
        }
}
add_action('template_redirect', 'chroma_redirect_attachment_pages');

/**
 * Disable author archives
 */
function chroma_disable_author_archives()
{
        if (is_author()) {
                chroma_send_gone_response(
                        __('Archive Unavailable', 'chroma-excellence'),
                        __('Author archive pages are not available on this site.', 'chroma-excellence')
                );
                exit;
        }
}
add_action('template_redirect', 'chroma_disable_author_archives');

/**
 * Redirect tracked marketing 404 URLs to homepage.
 *
 * Some ad platforms append tracking parameters (or malformed tracking suffixes)
 * to landing URLs. If that results in a 404, recover to homepage while preserving
 * attribution parameters where possible.
 */
function chroma_redirect_tracked_404_to_home()
{
        if (is_admin() || wp_doing_ajax() || !is_404()) {
                return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        // Skip internal/system endpoints.
        $skip_prefixes = array(
                '/wp-admin',
                '/wp-json',
                '/wp-content',
                '/wp-includes',
        );

        foreach ($skip_prefixes as $prefix) {
                if (strpos($path, $prefix) === 0) {
                        return;
                }
        }

        $skip_exact = array('/robots.txt', '/favicon.ico', '/sitemap.xml');
        if (in_array($path, $skip_exact, true)) {
                return;
        }

        $tracking_keys = array(
                'fbclid',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'utm_id',
                'gclid',
                'msclkid',
                'ttclid',
                'twclid',
                'wbraid',
                'gbraid',
                'li_fat_id',
                'mc_cid',
                'mc_eid',
                'yclid',
        );

        $has_tracking_marker = false;
        foreach ($tracking_keys as $key) {
                if (isset($_GET[$key])) {
                        $has_tracking_marker = true;
                        break;
                }
        }

        // Fallback detection for malformed paths that include fbclid-like suffixes.
        if (
                !$has_tracking_marker
                && is_string($request_uri)
                && preg_match('/(?:fbclid|utm_|gclid|msclkid|ttclid|wbraid|gbraid|twclid)=/i', $request_uri)
        ) {
                $has_tracking_marker = true;
        }

        if (!$has_tracking_marker) {
                return;
        }

        $query_forward = array();
        foreach ($tracking_keys as $key) {
                if (!isset($_GET[$key])) {
                        continue;
                }

                $value = wp_unslash($_GET[$key]);
                if (is_array($value)) {
                        continue;
                }

                $query_forward[$key] = sanitize_text_field((string) $value);
        }

        $target = home_url('/');
        if (!empty($query_forward)) {
                $target = add_query_arg($query_forward, $target);
        }

        wp_safe_redirect($target, 302);
        exit;
}
add_action('template_redirect', 'chroma_redirect_tracked_404_to_home', 0);

/**
 * Redirect retired public aliases to their current canonical destinations.
 */
function chroma_redirect_legacy_public_aliases()
{
        if (is_admin() || wp_doing_ajax()) {
                return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $path = is_string($path) ? '/' . trim($path, '/') . '/' : '';
        if ($path === '') {
                return;
        }

        $is_spanish = strpos($path, '/es/') === 0;
        $base_path = $is_spanish ? substr($path, 3) : $path;
	$aliases = [
		'/blog/' => '/stories/',
		'/contact/' => '/contact-us/',
		'/programs/curriculum/' => '/curriculum/',
		'/programs/developmental-approach/' => '/curriculum/',
		'/locations/midway-alpharetta/' => '/locations/midway-campus/',
		'/locations/pleasant-hill-campus-duluth/' => '/locations/pleasanthill-campus-duluth/',
		'/post/what-to-do-if-kids-get-physical-during-a-meltdown/' => '/2025/12/02/the-psychology-behind-toy-throwing-understanding-your-childs-behavior/',
		'/post/6d9f3d13-a22b-41e9-9a4a-88af74b35004-why-you-should-not-over-react-when-your-child-gets-hurt-calm-parenting-and-emotional-support-strategies/' => '/2025/12/02/why-you-should-not-over-react-when-your-child-gets-hurt-calm-parenting-and-emotional-support-strategies/',
		'/post/all-about-why-language-and-tone-matter-with-your-preschooler/' => '/2025/12/26/supporting-toddler-language-development-communication/',
		'/post/how-to-master-communicating-with-your-child-in-30-days/' => '/2025/12/27/tips-for-effective-communication-with-toddlers/',
		'/post/understanding-child-behavior-psychology-how-to-nurture-emotional-intelligence-and-positive-growth/' => '/2025/12/02/the-psychology-behind-toy-throwing-understanding-your-childs-behavior/',
		'/post/teaching-by-example-how-parents-shape-their-childrens-behavior/' => '/2025/12/26/effective-positive-discipline-techniques-for-young-children/',
		'/post/handling-bad-behavior-without-yelling-positive-discipline/' => '/2025/12/26/effective-positive-discipline-techniques-for-young-children/',
	];

        if (!isset($aliases[$base_path])) {
                return;
        }

        $target_path = ($is_spanish ? '/es' : '') . $aliases[$base_path];
        wp_safe_redirect(home_url($target_path), 301);
        exit;
}
add_action('template_redirect', 'chroma_redirect_legacy_public_aliases', -60);

/**
 * Repair migrated content links at render time while database cleanup is reviewed.
 * Clear aliases are redirected to canonical pages, including migrated Chroma
 * articles and official Georgia early-learning resources.
 *
 * @param string $content Post content.
 * @return string
 */
function chroma_normalize_legacy_content_links($content)
{
        if (!is_string($content) || $content === '') {
                return $content;
        }

	$aliases = [
		'/contact/' => '/contact-us/',
		'/programs/curriculum/' => '/curriculum/',
		'/programs/developmental-approach/' => '/curriculum/',
		'/locations/midway-alpharetta/' => '/locations/midway-campus/',
		'/locations/pleasant-hill-campus-duluth/' => '/locations/pleasanthill-campus-duluth/',
		'/post/what-to-do-if-kids-get-physical-during-a-meltdown/' => '/2025/12/02/the-psychology-behind-toy-throwing-understanding-your-childs-behavior/',
		'/post/6d9f3d13-a22b-41e9-9a4a-88af74b35004-why-you-should-not-over-react-when-your-child-gets-hurt-calm-parenting-and-emotional-support-strategies/' => '/2025/12/02/why-you-should-not-over-react-when-your-child-gets-hurt-calm-parenting-and-emotional-support-strategies/',
		'/post/all-about-why-language-and-tone-matter-with-your-preschooler/' => '/2025/12/26/supporting-toddler-language-development-communication/',
		'/post/how-to-master-communicating-with-your-child-in-30-days/' => '/2025/12/27/tips-for-effective-communication-with-toddlers/',
		'/post/understanding-child-behavior-psychology-how-to-nurture-emotional-intelligence-and-positive-growth/' => '/2025/12/02/the-psychology-behind-toy-throwing-understanding-your-childs-behavior/',
		'/post/teaching-by-example-how-parents-shape-their-childrens-behavior/' => '/2025/12/26/effective-positive-discipline-techniques-for-young-children/',
		'/post/handling-bad-behavior-without-yelling-positive-discipline/' => '/2025/12/26/effective-positive-discipline-techniques-for-young-children/',
	];
	$external_aliases = [
		'https://www.gelds.decal.ga.gov/' => 'https://www.decal.ga.gov/Prek/GELDS.aspx',
		'https://gelds.decal.ga.gov/' => 'https://www.decal.ga.gov/Prek/GELDS.aspx',
		'https://decal.ga.gov/' => 'https://georgia.gov/organization/department-early-care-and-learning',
		'https://www.decal.ga.gov/' => 'https://georgia.gov/organization/department-early-care-and-learning',
	];

        $normalized_content = preg_replace_callback(
                '/href=(["\'])([^"\']+)\1/i',
                static function ($matches) use ($aliases, $external_aliases) {
                        $href = html_entity_decode((string) $matches[2], ENT_QUOTES, 'UTF-8');
			$normalized_href = trailingslashit(strtok($href, '?#'));
			if (isset($external_aliases[$normalized_href])) {
				return 'href=' . $matches[1] . esc_url($external_aliases[$normalized_href]) . $matches[1];
			}
                        $path = wp_parse_url($href, PHP_URL_PATH);
                        if (!is_string($path) || $path === '') {
                                return $matches[0];
                        }

                        $normalized_path = '/' . trim($path, '/') . '/';
                        $is_spanish = strpos($normalized_path, '/es/') === 0;
                        $base_path = $is_spanish ? substr($normalized_path, 3) : $normalized_path;
                        if (!isset($aliases[$base_path])) {
                                return $matches[0];
                        }

                        $target_path = ($is_spanish ? '/es' : '') . $aliases[$base_path];
                        return 'href=' . $matches[1] . esc_url(home_url($target_path)) . $matches[1];
                },
                $content
        );
        if (is_string($normalized_content)) {
                $content = $normalized_content;
        }

	return $content;
}
add_filter('the_content', 'chroma_normalize_legacy_content_links', 35);

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove WordPress version from head
 */
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

/**
 * Disable RSS feeds
 */
function chroma_send_gone_response($title, $message)
{
        nocache_headers();
        status_header(410);
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        header('X-Robots-Tag: noindex, follow', true);

        echo '<!doctype html><html><head><meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="robots" content="noindex,follow">';
        echo '<title>' . esc_html($title) . '</title></head><body>';
        echo '<h1>' . esc_html($title) . '</h1>';
        echo '<p>' . wp_kses_post($message) . '</p>';
        echo '</body></html>';
        exit;
}

function chroma_disable_feeds()
{
        chroma_send_gone_response(
                __('Feed Unavailable', 'chroma-excellence'),
                sprintf(
                        __('No feed is available for this site. Please visit the <a href="%s">homepage</a>.', 'chroma-excellence'),
                        esc_url(home_url('/'))
                )
        );
}
add_action('do_feed', 'chroma_disable_feeds', 1);
add_action('do_feed_rdf', 'chroma_disable_feeds', 1);
add_action('do_feed_rss', 'chroma_disable_feeds', 1);
add_action('do_feed_rss2', 'chroma_disable_feeds', 1);
add_action('do_feed_atom', 'chroma_disable_feeds', 1);
add_action('do_feed_rss2_comments', 'chroma_disable_feeds', 1);
add_action('do_feed_atom_comments', 'chroma_disable_feeds', 1);

/**
 * Disable emojis to reduce extraneous HTTP requests and inline scripts.
 */
function chroma_disable_emojis()
{
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', 'chroma_disable_emojis_tinymce');
        add_filter('wp_resource_hints', 'chroma_disable_emojis_dns_prefetch', 10, 2);
}
add_action('init', 'chroma_disable_emojis');

/**
 * Filter out the emoji plugin from TinyMCE.
 */
function chroma_disable_emojis_tinymce($plugins)
{
        if (is_array($plugins)) {
                return array_diff($plugins, array('wpemoji'));
        }

        return array();
}

/**
 * Remove emoji CDN DNS prefetch.
 */
function chroma_disable_emojis_dns_prefetch($urls, $relation_type)
{
        if ('dns-prefetch' !== $relation_type) {
                return $urls;
        }

        return array_filter(
                $urls,
                function ($url) {
                        return false === strpos($url, 's.w.org/images/core/emoji/');
                }
        );
}
/**
 * Retire legacy team-member query URLs so they cannot resolve as homepage content.
 */
function chroma_block_legacy_team_member_queries()
{
        if (is_admin() || wp_doing_ajax()) {
                return;
        }

        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
        $post_id = isset($_GET['p']) ? absint(wp_unslash($_GET['p'])) : 0;

        if ($post_type !== 'team_member' || $post_id < 1) {
                return;
        }

        chroma_send_gone_response(
                __('Page Removed', 'chroma-excellence'),
                __('This legacy team-member URL is no longer available.', 'chroma-excellence')
        );
}
add_action('template_redirect', 'chroma_block_legacy_team_member_queries', -50);

/**
 * Keep career detail pages out of the index while preserving crawl paths.
 *
 * @param array $robots
 * @return array
 */
function chroma_noindex_career_robots($robots)
{
        if (!is_singular('career')) {
                return $robots;
        }

        if (!is_array($robots)) {
                $robots = [];
        }

        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow'] = true;

        return $robots;
}
add_filter('wp_robots', 'chroma_noindex_career_robots', 30);

/**
 * Mirror career noindex directives when a plugin owns robots output.
 *
 * @param string $robots
 * @return string
 */
function chroma_noindex_career_wpseo_robots($robots)
{
        if (!is_singular('career')) {
                return $robots;
        }

        return 'noindex,follow';
}
add_filter('wpseo_robots', 'chroma_noindex_career_wpseo_robots', 30);

/**
 * Add an X-Robots-Tag header for career detail pages so server-side noindex wins.
 */
function chroma_send_career_x_robots_header()
{
        if (headers_sent() || !is_singular('career')) {
                return;
        }

        header('X-Robots-Tag: noindex, follow', true);
}
add_action('send_headers', 'chroma_send_career_x_robots_header', 20);

/**
 * Determine whether a city landing page has explicit search approval.
 *
 * @param int $post_id City post ID.
 * @return bool
 */
function chroma_city_is_search_approved($post_id)
{
        $post_id = absint($post_id);
        if ($post_id < 1 || get_post_type($post_id) !== 'city') {
                return false;
        }

        return get_post_meta($post_id, 'city_search_approved', true) === '1';
}

/**
 * Redirect the inaccurate LaFayette community page to the verified directory.
 */
function chroma_redirect_unverified_city_pages()
{
        if (!is_singular('city')) {
                return;
        }

        $post = get_queried_object();
        if ($post instanceof WP_Post && sanitize_title((string) $post->post_name) === 'lafayette') {
                wp_safe_redirect(home_url('/locations/'), 301);
                exit;
        }
}
add_action('template_redirect', 'chroma_redirect_unverified_city_pages', -40);

/**
 * Keep generic city pages and virtual keyword routes out of search indexes.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function chroma_noindex_unapproved_local_routes($robots)
{
        $is_unapproved_city = is_singular('city') && !chroma_city_is_search_approved(get_queried_object_id());
        $is_virtual_route = (string) get_query_var('chroma_combo') !== ''
                || (string) get_query_var('chroma_near_me') !== ''
                || (string) get_query_var('chroma_service_area') !== '';

        if (!$is_unapproved_city && !$is_virtual_route) {
                return $robots;
        }

        if (!is_array($robots)) {
                $robots = [];
        }

        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow'] = true;

        return $robots;
}
add_filter('wp_robots', 'chroma_noindex_unapproved_local_routes', 40);

/**
 * Mirror noindex directives when an SEO plugin owns robots output.
 *
 * @param string $robots Robots directive string.
 * @return string
 */
function chroma_noindex_unapproved_local_routes_wpseo($robots)
{
        $is_unapproved_city = is_singular('city') && !chroma_city_is_search_approved(get_queried_object_id());
        $is_virtual_route = (string) get_query_var('chroma_combo') !== ''
                || (string) get_query_var('chroma_near_me') !== ''
                || (string) get_query_var('chroma_service_area') !== '';

        return ($is_unapproved_city || $is_virtual_route) ? 'noindex,follow' : $robots;
}
add_filter('wpseo_robots', 'chroma_noindex_unapproved_local_routes_wpseo', 40);

/**
 * Send a definitive noindex header for unapproved local landing routes.
 */
function chroma_send_local_route_x_robots_header()
{
        if (headers_sent()) {
                return;
        }

        $is_unapproved_city = is_singular('city') && !chroma_city_is_search_approved(get_queried_object_id());
        $is_virtual_route = (string) get_query_var('chroma_combo') !== ''
                || (string) get_query_var('chroma_near_me') !== ''
                || (string) get_query_var('chroma_service_area') !== '';

        if ($is_unapproved_city || $is_virtual_route) {
                header('X-Robots-Tag: noindex, follow', true);
        }
}
add_action('send_headers', 'chroma_send_local_route_x_robots_header', 30);

/**
 * Identify legacy city-keyword articles that duplicate campus and directory intent.
 *
 * These posts remain accessible for historical reference, but are not suitable
 * organic landing pages because the location directory and campus pages provide
 * the maintained, verified local experience.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function chroma_is_legacy_local_doorway_post($post_id = 0)
{
        $post_id = $post_id ? absint($post_id) : get_queried_object_id();
        if ($post_id < 1 || get_post_type($post_id) !== 'post') {
                return false;
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
                return false;
        }

        $slug = sanitize_title((string) $post->post_name);
        $patterns = [
                '/childcare-preschool-(?:in|on|services-in)-/',
                '/secure-the-best-childcare-in-/',
                '/trusted-childcare-preschool-in-/',
                '/quality-childcare-preschool-in-/',
                '/nurturing-infant-care-programs-in-/',
                '/engaging-toddler-care-programs-in-/',
                '/premier-choice-for-daycare-and-preschool-in-/',
        ];

        foreach ($patterns as $pattern) {
                if (preg_match($pattern, $slug)) {
                        return true;
                }
        }

        return false;
}

/**
 * Keep legacy local doorway articles out of search while preserving links.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function chroma_noindex_legacy_local_doorway_posts($robots)
{
        if (!is_singular('post') || !chroma_is_legacy_local_doorway_post()) {
                return $robots;
        }

        if (!is_array($robots)) {
                $robots = [];
        }

        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow'] = true;

        return $robots;
}
add_filter('wp_robots', 'chroma_noindex_legacy_local_doorway_posts', 45);

function chroma_noindex_legacy_local_doorway_posts_wpseo($robots)
{
        return is_singular('post') && chroma_is_legacy_local_doorway_post()
                ? 'noindex,follow'
                : $robots;
}
add_filter('wpseo_robots', 'chroma_noindex_legacy_local_doorway_posts_wpseo', 45);

function chroma_send_legacy_local_doorway_x_robots_header()
{
        if (!headers_sent() && is_singular('post') && chroma_is_legacy_local_doorway_post()) {
                header('X-Robots-Tag: noindex, follow', true);
        }
}
add_action('send_headers', 'chroma_send_legacy_local_doorway_x_robots_header', 35);

/**
 * Identify published editorial posts that do not yet contain an article body.
 *
 * These records remain available to editors, but they should not be promoted
 * to search engines until substantive content has been added.
 *
 * @param int $post_id Optional post ID.
 * @return bool
 */
function chroma_is_unfinished_empty_post($post_id = 0)
{
        $post = get_post($post_id ?: get_queried_object_id());
        if (!$post instanceof WP_Post || $post->post_type !== 'post' || $post->post_status !== 'publish') {
                return false;
        }

        return trim(wp_strip_all_tags(strip_shortcodes((string) $post->post_content))) === '';
}

/**
 * Return published editorial post IDs that do not yet contain an article body.
 *
 * @return int[]
 */
function chroma_get_unfinished_empty_post_ids()
{
        static $ids = null;
        if ($ids !== null) {
                return $ids;
        }

        global $wpdb;
        $ids = array_map(
                'intval',
                (array) $wpdb->get_col(
                        $wpdb->prepare(
                                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND TRIM(post_content) = %s",
                                'post',
                                'publish',
                                ''
                        )
                )
        );

        return $ids;
}

/**
 * Keep unfinished empty editorial records out of search while preserving URLs.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function chroma_noindex_unfinished_empty_posts($robots)
{
        if (!is_singular('post') || !chroma_is_unfinished_empty_post()) {
                return $robots;
        }

        if (!is_array($robots)) {
                $robots = [];
        }

        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow'] = true;

        return $robots;
}
add_filter('wp_robots', 'chroma_noindex_unfinished_empty_posts', 46);

function chroma_noindex_unfinished_empty_posts_wpseo($robots)
{
        return is_singular('post') && chroma_is_unfinished_empty_post()
                ? 'noindex,follow'
                : $robots;
}
add_filter('wpseo_robots', 'chroma_noindex_unfinished_empty_posts_wpseo', 46);

function chroma_send_unfinished_empty_posts_x_robots_header()
{
        if (!headers_sent() && is_singular('post') && chroma_is_unfinished_empty_post()) {
                header('X-Robots-Tag: noindex, follow', true);
        }
}
add_action('send_headers', 'chroma_send_unfinished_empty_posts_x_robots_header', 36);

/**
 * Keep incomplete Spanish singular variants out of search until translated.
 */
function chroma_is_unverified_spanish_singular()
{
        if (!is_singular() || !isset($_SERVER['REQUEST_URI'])) {
                return false;
        }

        $path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        if (strpos($path, '/es/') !== 0) {
                return false;
        }

        $post_id = get_queried_object_id();
        return $post_id > 0
                && function_exists('chroma_post_has_verified_spanish_variant')
                && !chroma_post_has_verified_spanish_variant($post_id);
}

function chroma_noindex_unverified_spanish_robots($robots)
{
        if (!chroma_is_unverified_spanish_singular()) {
                return $robots;
        }

        if (!is_array($robots)) {
                $robots = [];
        }
        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow'] = true;
        return $robots;
}
add_filter('wp_robots', 'chroma_noindex_unverified_spanish_robots', 50);

function chroma_noindex_unverified_spanish_wpseo($robots)
{
        return chroma_is_unverified_spanish_singular() ? 'noindex,follow' : $robots;
}
add_filter('wpseo_robots', 'chroma_noindex_unverified_spanish_wpseo', 50);

function chroma_send_unverified_spanish_x_robots_header()
{
        if (!headers_sent() && chroma_is_unverified_spanish_singular()) {
                header('X-Robots-Tag: noindex, follow', true);
        }
}
add_action('send_headers', 'chroma_send_unverified_spanish_x_robots_header', 37);

/**
 * Keep non-indexable or unsupported post types out of the unified sitemap.
 *
 * @param array $post_types
 * @return array
 */
function chroma_filter_sitemap_post_types($post_types)
{
        return array_values(array_diff((array) $post_types, ['career', 'team_member', 'attachment']));
}
add_filter('chroma_sitemap_post_types', 'chroma_filter_sitemap_post_types', 20);

/**
 * Normalize robots.txt output so crawl directives and sitemap ownership stay consistent.
 */
function chroma_normalize_robots_txt($output, $public)
{
        $existing_lines = preg_split('/\R/', (string) $output) ?: [];
        $clean_lines = [];
        $line_index = [];

        foreach ($existing_lines as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                        continue;
                }

                if (preg_match('/^sitemap\s*:/i', $line)) {
                        continue;
                }

                if (preg_match('#^disallow\s*:\s*/(?:items|feed|comments/feed)/?$#i', $line)) {
                        continue;
                }

                $normalized_key = strtolower(preg_replace('/\s+/', ' ', $line));
                if (isset($line_index[$normalized_key])) {
                        continue;
                }

                $clean_lines[] = $line;
                $line_index[$normalized_key] = true;
        }

        $required_lines = [
                'User-agent: *',
                'Disallow: /wp-admin/',
                'Allow: /wp-admin/admin-ajax.php',
                'Allow: /wp-json/chroma-agent/v1/geo-feed',
                'Disallow: /items/',
                'Disallow: /feed/',
                'Disallow: /comments/feed/',
                'Sitemap: ' . esc_url_raw(home_url('/sitemap.xml')),
        ];

        foreach ($required_lines as $line) {
                $normalized_key = strtolower(preg_replace('/\s+/', ' ', $line));
                if (isset($line_index[$normalized_key])) {
                        continue;
                }

                $clean_lines[] = $line;
                $line_index[$normalized_key] = true;
        }

        return implode("\n", $clean_lines) . "\n";
}
add_filter('robots_txt', 'chroma_normalize_robots_txt', 999, 2);

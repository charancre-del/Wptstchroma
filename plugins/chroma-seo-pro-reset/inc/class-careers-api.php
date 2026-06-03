<?php
/**
 * Careers API Handler
 *
 * Fetches and parses job listings from external sources.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Careers_API
{
    /**
     * Get careers data
     *
     * @param bool $force_refresh Whether to bypass cache
     * @return array Array of job data
     */
    public static function get_careers($force_refresh = false)
    {
        // Check for cached data
        $cached_jobs = get_transient('chroma_careers_data');
        if (false !== $cached_jobs && !$force_refresh) {
            return $cached_jobs;
        }

        // Use option for feed URL to avoid hardcoding in plugin.
        $primary_url = esc_url_raw(get_option('chroma_careers_feed_url', self::default_feed_url()));
        if ($primary_url === '') {
            $primary_url = self::default_feed_url();
        }

        $allowed_hosts = apply_filters('chroma_careers_allowed_feed_hosts', ['app.acquire4hire.com']);
        $allowed_hosts = array_map('strtolower', array_filter((array) $allowed_hosts));

        foreach (self::candidate_feed_urls($primary_url) as $url) {
            $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
            if (!wp_http_validate_url($url) || !in_array($host, $allowed_hosts, true)) {
                chroma_debug_log(' Careers API blocked invalid feed URL.');
                continue;
            }

            // Fetch data with timeout.
            $response = wp_safe_remote_get($url, array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/xml,application/json,text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ),
            ));

            if (is_wp_error($response)) {
                chroma_debug_log(' Careers API Error: ' . $response->get_error_message());
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                continue;
            }

            $jobs = self::parse_feed_body($body, $url);
            if (empty($jobs)) {
                continue;
            }

            // Cache for 1 hour.
            set_transient('chroma_careers_data', $jobs, HOUR_IN_SECONDS);
            return $jobs;
        }

        return array();
    }

    /**
     * Default to the structured feed. The old list.json endpoint now returns
     * HTML and remains supported as a fallback for saved options.
     *
     * @return string
     */
    private static function default_feed_url()
    {
        return 'https://app.acquire4hire.com/feed/indeed.xml?id=4668';
    }

    /**
     * Return feed candidates in preferred order.
     *
     * @param string $primary_url Configured feed URL.
     * @return array
     */
    private static function candidate_feed_urls($primary_url)
    {
        $urls = array();
        $primary_url = esc_url_raw((string) $primary_url);
        if ($primary_url === '') {
            return array(self::default_feed_url());
        }

        $host = strtolower((string) wp_parse_url($primary_url, PHP_URL_HOST));
        $path = (string) wp_parse_url($primary_url, PHP_URL_PATH);
        $query = (string) wp_parse_url($primary_url, PHP_URL_QUERY);

        if ($host === 'app.acquire4hire.com' && stripos($path, '/careers/list.json') !== false) {
            parse_str($query, $params);
            if (!empty($params['id'])) {
                $urls[] = 'https://app.acquire4hire.com/feed/indeed.xml?id=' . rawurlencode((string) $params['id']);
            }
        }

        $urls[] = $primary_url;
        $urls[] = self::default_feed_url();
        return array_values(array_unique($urls));
    }

    /**
     * Parse any supported feed body.
     *
     * @param string $body       Response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_feed_body($body, $source_url)
    {
        $jobs = self::parse_json_feed($body, $source_url);
        if (!empty($jobs)) {
            return $jobs;
        }

        $jobs = self::parse_xml_feed($body, $source_url);
        if (!empty($jobs)) {
            return $jobs;
        }

        return self::parse_html_feed($body, $source_url);
    }

    /**
     * Parse JSON feed variants from Acquire4Hire or similar providers.
     *
     * @param string $body      Response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_json_feed($body, $source_url)
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return array();
        }

        $rows = array();
        if (isset($decoded['jobs']) && is_array($decoded['jobs'])) {
            $rows = $decoded['jobs'];
        } elseif (isset($decoded['data']['jobs']) && is_array($decoded['data']['jobs'])) {
            $rows = $decoded['data']['jobs'];
        } elseif (isset($decoded[0]) && is_array($decoded[0])) {
            $rows = $decoded;
        } elseif (isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data'][0])) {
            $rows = $decoded['data'];
        }

        if (empty($rows)) {
            return array();
        }

        $jobs = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = self::first_non_empty($row, array('title', 'job_title', 'jobTitle', 'name', 'position'));
            $url = self::first_non_empty($row, array('url', 'job_url', 'jobUrl', 'apply_url', 'applyUrl', 'link', 'absolute_url'));
            if ($title === '' || $url === '') {
                continue;
            }

            $location = self::first_non_empty($row, array('location', 'job_location', 'city_state', 'city'));
            $state = self::first_non_empty($row, array('state', 'region'));
            if ($location !== '' && $state !== '' && stripos($location, $state) === false) {
                $location .= ', ' . $state;
            }

            $jobs[] = array(
                'title' => sanitize_text_field($title),
                'location' => sanitize_text_field($location),
                'type' => sanitize_text_field(self::normalize_feed_job_type(self::first_non_empty($row, array('type', 'employment_type', 'employmentType', 'job_type')))),
                'url' => self::normalize_url($url, $source_url),
                'description' => self::first_non_empty($row, array('description', 'summary', 'excerpt')),
                'date_posted' => self::first_non_empty($row, array('date_posted', 'posted_at', 'postedDate', 'published_at', 'date')),
            );
        }

        return array_values(array_filter($jobs, function ($job) {
            return !empty($job['title']) && !empty($job['url']);
        }));
    }

    /**
     * Parse structured Acquire4Hire/Indeed XML feeds.
     *
     * @param string $body       Response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_xml_feed($body, $source_url)
    {
        if (!is_string($body) || stripos(ltrim($body), '<') !== 0) {
            return array();
        }

        if (!class_exists('DOMDocument')) {
            return self::parse_xml_feed_fallback($body, $source_url);
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($body, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();

        if (!$loaded) {
            return self::parse_xml_feed_fallback($body, $source_url);
        }

        $xpath = new DOMXPath($dom);
        $job_nodes = $xpath->query('//job');
        if (!$job_nodes || $job_nodes->length === 0) {
            return self::parse_xml_feed_fallback($body, $source_url);
        }

        $jobs = array();
        foreach ($job_nodes as $node) {
            $title = self::xpath_text($xpath, './title', $node);
            $url = self::xpath_text($xpath, './url', $node);
            if ($title === '' || $url === '') {
                continue;
            }

            $city = self::xpath_text($xpath, './city', $node);
            $state = self::xpath_text($xpath, './state', $node);
            $location = trim(implode(', ', array_filter(array($city, $state))));

            $jobs[] = array(
                'title' => sanitize_text_field($title),
                'location' => sanitize_text_field($location),
                'type' => sanitize_text_field(self::normalize_feed_job_type(self::xpath_text($xpath, './jobtype', $node))),
                'url' => self::normalize_url($url, $source_url),
                'description' => wp_kses_post(self::xpath_text($xpath, './description', $node)),
                'date_posted' => sanitize_text_field(self::xpath_text($xpath, './date', $node)),
                'salary' => sanitize_text_field(self::xpath_text($xpath, './salary', $node)),
                'city' => sanitize_text_field($city),
                'state' => sanitize_text_field($state),
                'postal_code' => sanitize_text_field(self::xpath_text($xpath, './postalcode', $node)),
                'country' => sanitize_text_field(self::xpath_text($xpath, './country', $node)),
                'reference' => sanitize_text_field(self::xpath_text($xpath, './referencenumber', $node)),
                'category' => sanitize_text_field(self::xpath_text($xpath, './category', $node)),
                'education' => sanitize_text_field(self::xpath_text($xpath, './education', $node)),
                'experience' => sanitize_text_field(self::xpath_text($xpath, './experience', $node)),
            );
        }

        return array_values(array_filter($jobs, function ($job) {
            return !empty($job['title']) && !empty($job['url']);
        }));
    }

    /**
     * Lightweight XML feed fallback for hosts without DOMDocument.
     *
     * @param string $body       XML response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_xml_feed_fallback($body, $source_url)
    {
        $jobs = array();
        if (!preg_match_all('/<job\b[^>]*>(.*?)<\/job>/is', $body, $matches)) {
            return $jobs;
        }

        foreach ($matches[1] as $fragment) {
            $title = self::xml_fragment_text($fragment, 'title');
            $url = self::xml_fragment_text($fragment, 'url');
            if ($title === '' || $url === '') {
                continue;
            }

            $city = self::xml_fragment_text($fragment, 'city');
            $state = self::xml_fragment_text($fragment, 'state');
            $location = trim(implode(', ', array_filter(array($city, $state))));

            $jobs[] = array(
                'title' => sanitize_text_field($title),
                'location' => sanitize_text_field($location),
                'type' => sanitize_text_field(self::normalize_feed_job_type(self::xml_fragment_text($fragment, 'jobtype'))),
                'url' => self::normalize_url($url, $source_url),
                'description' => wp_kses_post(self::xml_fragment_text($fragment, 'description')),
                'date_posted' => sanitize_text_field(self::xml_fragment_text($fragment, 'date')),
                'salary' => sanitize_text_field(self::xml_fragment_text($fragment, 'salary')),
                'city' => sanitize_text_field($city),
                'state' => sanitize_text_field($state),
                'postal_code' => sanitize_text_field(self::xml_fragment_text($fragment, 'postalcode')),
                'country' => sanitize_text_field(self::xml_fragment_text($fragment, 'country')),
                'reference' => sanitize_text_field(self::xml_fragment_text($fragment, 'referencenumber')),
                'category' => sanitize_text_field(self::xml_fragment_text($fragment, 'category')),
                'education' => sanitize_text_field(self::xml_fragment_text($fragment, 'education')),
                'experience' => sanitize_text_field(self::xml_fragment_text($fragment, 'experience')),
            );
        }

        return array_values(array_filter($jobs, function ($job) {
            return !empty($job['title']) && !empty($job['url']);
        }));
    }

    /**
     * Extract and decode a simple child element from a feed job fragment.
     *
     * @param string $fragment XML fragment inside one <job> node.
     * @param string $tag      Child tag name.
     * @return string
     */
    private static function xml_fragment_text($fragment, $tag)
    {
        $tag = preg_quote((string) $tag, '/');
        if (!preg_match('/<' . $tag . '\b[^>]*>(.*?)<\/' . $tag . '>/is', $fragment, $match)) {
            return '';
        }

        $value = preg_replace('/^\s*<!\[CDATA\[(.*)\]\]>\s*$/is', '$1', (string) $match[1]);
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim($value);
    }

    /**
     * Parse legacy HTML feed format.
     *
     * @param string $body       Response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_html_feed($body, $source_url)
    {
        $jobs = array();

        if (!class_exists('DOMDocument')) {
            return self::parse_job_cards_from_html($body, $source_url);
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($body);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $html_base_url = self::html_base_url($xpath, $source_url);

        $card_nodes = $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' job-card ')]");
        foreach ($card_nodes as $node) {
            $title_node = $xpath->query(".//h3", $node)->item(0);
            $location_node = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' loc ')]", $node)->item(0);
            $pay_node = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' pay ')]", $node)->item(0);
            $perk_nodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' perk ')]", $node);

            if (!$title_node) {
                continue;
            }

            $perks = array();
            foreach ($perk_nodes as $perk_node) {
                $perk = trim($perk_node->textContent);
                if ($perk !== '') {
                    $perks[] = $perk;
                }
            }

            $title = trim($title_node->textContent);
            $job_url = self::normalize_url($node->getAttribute('href'), $html_base_url);
            $location = $location_node ? trim($location_node->textContent) : '';
            $pay = $pay_node ? trim($pay_node->textContent) : '';
            $type = self::first_matching_perk($perks, array('Full Time', 'Part Time', 'Contract', 'Temporary', 'Intern', 'Volunteer'));

            if ($title === '' || $job_url === '') {
                continue;
            }

            $jobs[] = array(
                'title' => sanitize_text_field($title),
                'location' => sanitize_text_field(trim($location, " \t\n\r\0\x0B,")),
                'type' => sanitize_text_field($type ?: 'FULL_TIME'),
                'url' => $job_url,
                'description' => sanitize_text_field(trim(implode(' ', array_filter(array($title, $location, $pay, implode(', ', $perks)))))),
            );
        }

        if (!empty($jobs)) {
            return array_values(array_filter($jobs, function ($job) {
                return !empty($job['title']) && !empty($job['url']);
            }));
        }

        $job_nodes = $xpath->query("//div[contains(@class, 'job')]");

        foreach ($job_nodes as $node) {
            $title_node = $xpath->query(".//div[contains(@class, 'job1')]//a//h2", $node)->item(0);
            $link_node = $xpath->query(".//div[contains(@class, 'job1')]//a", $node)->item(0);
            $location_node = $xpath->query(".//div[contains(@class, 'job2')]//div", $node)->item(0);

            if (!$title_node || !$link_node) {
                continue;
            }

            $title = trim($title_node->textContent);
            $job_url = self::normalize_url($link_node->getAttribute('href'), $source_url);
            $location = $location_node ? trim($location_node->textContent) : 'Alpharetta, GA';

            $jobs[] = array(
                'title' => sanitize_text_field($title),
                'location' => sanitize_text_field(trim($location, " \t\n\r\0\x0B,")),
                'type' => 'FULL_TIME',
                'url' => $job_url,
            );
        }

        return array_values(array_filter($jobs, function ($job) {
            return !empty($job['title']) && !empty($job['url']);
        }));
    }

    /**
     * Determine the base URL used by relative links in an HTML feed.
     *
     * @param DOMXPath $xpath      Parsed feed XPath.
     * @param string   $source_url Feed endpoint URL.
     * @return string
     */
    private static function html_base_url($xpath, $source_url)
    {
        $canonical = $xpath->query("//link[translate(@rel, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'canonical']/@href")->item(0);
        if ($canonical && trim($canonical->nodeValue) !== '') {
            return trim($canonical->nodeValue);
        }

        $og_url = $xpath->query("//meta[@property = 'og:url']/@content")->item(0);
        if ($og_url && trim($og_url->nodeValue) !== '') {
            return trim($og_url->nodeValue);
        }

        return $source_url;
    }

    /**
     * Pick a recognizable employment type from the feed's perk tags.
     *
     * @param array $perks      Perk strings.
     * @param array $candidates Accepted labels.
     * @return string
     */
    private static function first_matching_perk($perks, $candidates)
    {
        foreach ((array) $perks as $perk) {
            foreach ($candidates as $candidate) {
                if (strcasecmp((string) $perk, (string) $candidate) === 0) {
                    return (string) $perk;
                }
            }
        }

        return '';
    }

    /**
     * Read a text value from an XPath query.
     *
     * @param DOMXPath $xpath   XPath instance.
     * @param string   $query   Relative or absolute XPath query.
     * @param DOMNode  $context Optional context node.
     * @return string
     */
    private static function xpath_text($xpath, $query, $context = null)
    {
        $nodes = $context ? $xpath->query($query, $context) : $xpath->query($query);
        if (!$nodes || $nodes->length === 0) {
            return '';
        }

        return trim((string) $nodes->item(0)->textContent);
    }

    /**
     * Normalize provider employment type labels for display.
     *
     * @param string $raw_type Provider type label.
     * @return string
     */
    private static function normalize_feed_job_type($raw_type)
    {
        $raw_type = trim((string) $raw_type);
        if ($raw_type === '') {
            return 'Full Time';
        }

        $compact = strtolower(preg_replace('/[^a-z]/i', '', $raw_type));
        $map = array(
            'fulltime' => 'Full Time',
            'parttime' => 'Part Time',
            'contract' => 'Contract',
            'contractor' => 'Contract',
            'temporary' => 'Temporary',
            'temp' => 'Temporary',
            'intern' => 'Intern',
            'internship' => 'Intern',
            'volunteer' => 'Volunteer',
        );

        return $map[$compact] ?? $raw_type;
    }

    /**
     * Lightweight fallback for hosts without DOMDocument.
     *
     * @param string $body       Response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_job_cards_from_html($body, $source_url)
    {
        $jobs = array();
        if (!is_string($body) || $body === '') {
            return $jobs;
        }

        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $body, $base_match)) {
            $source_url = html_entity_decode($base_match[1], ENT_QUOTES);
        }

        if (!preg_match_all('/<a\b([^>]*\bclass=["\'][^"\']*\bjob-card\b[^"\']*["\'][^>]*)>(.*?)<\/a>/is', $body, $matches, PREG_SET_ORDER)) {
            return $jobs;
        }

        foreach ($matches as $match) {
            if (!preg_match('/\bhref=["\']([^"\']+)["\']/i', $match[1], $href_match)) {
                continue;
            }

            $card = $match[2];
            $title = preg_match('/<h3[^>]*>(.*?)<\/h3>/is', $card, $title_match) ? trim(wp_strip_all_tags($title_match[1])) : '';
            $location = preg_match('/<[^>]+class=["\'][^"\']*\bloc\b[^"\']*["\'][^>]*>(.*?)<\/[^>]+>/is', $card, $loc_match) ? trim(wp_strip_all_tags($loc_match[1])) : '';
            $pay = preg_match('/<[^>]+class=["\'][^"\']*\bpay\b[^"\']*["\'][^>]*>(.*?)<\/[^>]+>/is', $card, $pay_match) ? trim(wp_strip_all_tags($pay_match[1])) : '';

            preg_match_all('/<span[^>]+class=["\'][^"\']*\bperk\b[^"\']*["\'][^>]*>(.*?)<\/span>/is', $card, $perk_matches);
            $perks = array_map('trim', array_map('wp_strip_all_tags', $perk_matches[1] ?? array()));
            $type = self::first_matching_perk($perks, array('Full Time', 'Part Time', 'Contract', 'Temporary', 'Intern', 'Volunteer'));

            if ($title === '') {
                continue;
            }

            $jobs[] = array(
                'title' => sanitize_text_field($title),
                'location' => sanitize_text_field(trim($location, " \t\n\r\0\x0B,")),
                'type' => sanitize_text_field($type ?: 'FULL_TIME'),
                'url' => self::normalize_url(html_entity_decode($href_match[1], ENT_QUOTES), $source_url),
                'description' => sanitize_text_field(trim(implode(' ', array_filter(array($title, $location, $pay, implode(', ', $perks)))))),
            );
        }

        return array_values(array_filter($jobs, function ($job) {
            return !empty($job['title']) && !empty($job['url']);
        }));
    }

    /**
     * Return first non-empty key from an array row.
     *
     * @param array $row  Source row.
     * @param array $keys Candidate keys.
     * @return string
     */
    private static function first_non_empty($row, $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && !is_array($row[$key])) {
                $value = trim((string) $row[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return '';
    }

    /**
     * Normalize feed URL to absolute URL.
     *
     * @param string $value      URL from feed.
     * @param string $source_url Feed endpoint URL.
     * @return string
     */
    private static function normalize_url($value, $source_url)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return esc_url_raw($value);
        }

        $parts = wp_parse_url($source_url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $base = $parts['scheme'] . '://' . $parts['host'];
        if (strpos($value, '//') === 0) {
            return esc_url_raw($parts['scheme'] . ':' . $value);
        }

        if (strpos($value, '/') === 0) {
            return esc_url_raw($base . $value);
        }

        return esc_url_raw($base . '/' . ltrim($value, '/'));
    }
}

/**
 * Compatibility wrapper for theme
 */
if (!function_exists('chroma_get_careers')) {
    function chroma_get_careers($force_refresh = false) {
        return Chroma_Careers_API::get_careers($force_refresh);
    }
}



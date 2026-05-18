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

        // Use option for feed URL to avoid hardcoding in plugin
        $url = esc_url_raw(get_option('chroma_careers_feed_url', 'https://app.acquire4hire.com/careers/list.json?id=4668'));
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $allowed_hosts = apply_filters('chroma_careers_allowed_feed_hosts', ['app.acquire4hire.com']);
        $allowed_hosts = array_map('strtolower', array_filter((array) $allowed_hosts));

        if (!wp_http_validate_url($url) || !in_array($host, $allowed_hosts, true)) {
            chroma_debug_log(' Careers API blocked invalid feed URL.');
            return array();
        }

        // Fetch data with timeout
        $response = wp_safe_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json,text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ),
        ));

        if (is_wp_error($response)) {
            chroma_debug_log(' Careers API Error: ' . $response->get_error_message());
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array();
        }

        $jobs = self::parse_json_feed($body, $url);
        if (empty($jobs)) {
            $jobs = self::parse_html_feed($body, $url);
        }

        // Cache for 1 hour
        if (!empty($jobs)) {
            set_transient('chroma_careers_data', $jobs, HOUR_IN_SECONDS);
        }

        return $jobs;
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
                'type' => sanitize_text_field(self::first_non_empty($row, array('type', 'employment_type', 'employmentType', 'job_type')) ?: 'FULL_TIME'),
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
     * Parse legacy HTML feed format.
     *
     * @param string $body       Response body.
     * @param string $source_url Feed URL.
     * @return array
     */
    private static function parse_html_feed($body, $source_url)
    {
        if (!class_exists('DOMDocument')) {
            return array();
        }

        $jobs = array();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($body);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
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



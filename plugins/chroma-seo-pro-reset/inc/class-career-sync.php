<?php
/**
 * Career Feed Sync
 *
 * Synchronizes jobs from external feed into 'career' CPT.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Career_Sync
{
    /**
     * Register weekly schedule.
     *
     * @param array $schedules Cron schedules.
     * @return array
     */
    public static function register_cron_schedules($schedules)
    {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => WEEK_IN_SECONDS,
                'display'  => __('Once Weekly', 'chroma-excellence'),
            ];
        }
        return $schedules;
    }

    /**
     * Init
     */
    public static function init()
    {
        add_filter('cron_schedules', [__CLASS__, 'register_cron_schedules']);
        add_action('chroma_career_sync_event', [__CLASS__, 'run_sync']);
        add_action('wp_ajax_chroma_sync_careers', [__CLASS__, 'ajax_run_sync']);

        self::ensure_cron_schedule();
    }

    /**
     * Ensure sync event exists and is weekly.
     */
    private static function ensure_cron_schedule()
    {
        $event = function_exists('wp_get_scheduled_event')
            ? wp_get_scheduled_event('chroma_career_sync_event')
            : null;

        if ($event && isset($event->schedule) && $event->schedule !== 'weekly') {
            wp_clear_scheduled_hook('chroma_career_sync_event');
            $event = null;
        }

        if (!$event && !wp_next_scheduled('chroma_career_sync_event')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'weekly', 'chroma_career_sync_event');
        }
    }

    /**
     * AJAX Trigger for UI
     */
    public static function ajax_run_sync()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $result = self::run_sync();
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    /**
     * Run the Sync Process
     */
    public static function run_sync()
    {
        if (!class_exists('Chroma_Careers_API')) {
            return ['error' => 'Careers API is unavailable.'];
        }

        $jobs = Chroma_Careers_API::get_careers(true);
        if (!is_array($jobs)) {
            return ['error' => 'Invalid careers feed response.'];
        }

        $created = 0;
        $updated = 0;
        $trashed = 0;
        $errors = [];
        $active_urls = [];

        foreach ($jobs as $job) {
            if (!is_array($job)) {
                continue;
            }

            $title = isset($job['title']) ? sanitize_text_field((string) $job['title']) : '';
            $job_url = isset($job['url']) ? esc_url_raw((string) $job['url']) : '';
            if ($title === '' || $job_url === '') {
                continue;
            }

            $active_urls[] = $job_url;

            $description = '';
            if (!empty($job['description'])) {
                $description = wp_kses_post((string) $job['description']);
            }
            if ($description === '') {
                $location_str = !empty($job['location']) ? sanitize_text_field((string) $job['location']) : 'our center';
                $description = sprintf(
                    __('We are hiring for %1$s at %2$s. Click apply to view role requirements and submit your application.', 'chroma-excellence'),
                    $title,
                    $location_str
                );
            }

            $post_data = [
                'post_type'    => 'career',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_content' => $description,
                'post_excerpt' => wp_trim_words(wp_strip_all_tags($description), 40),
            ];

            $existing = self::get_post_by_job_url($job_url);
            if ($existing) {
                $post_data['ID'] = (int) $existing->ID;
                $result = wp_update_post($post_data, true);
                if (is_wp_error($result)) {
                    $errors[] = sprintf('%s: %s', $title, $result->get_error_message());
                    continue;
                }
                $post_id = (int) $existing->ID;
                $updated++;
            } else {
                $result = wp_insert_post($post_data, true);
                if (is_wp_error($result)) {
                    $errors[] = sprintf('%s: %s', $title, $result->get_error_message());
                    continue;
                }
                $post_id = (int) $result;
                $created++;
            }

            update_post_meta($post_id, '_career_external_url', $job_url);
            update_post_meta($post_id, '_career_location', sanitize_text_field((string) ($job['location'] ?? '')));
            update_post_meta($post_id, '_career_type', self::normalize_employment_type((string) ($job['type'] ?? 'FULL_TIME')));
            update_post_meta($post_id, '_career_date_posted', self::normalize_date_posted((string) ($job['date_posted'] ?? '')));

            foreach ([
                '_career_city'        => $job['city'] ?? '',
                '_career_state'       => $job['state'] ?? '',
                '_career_postal_code' => $job['postal_code'] ?? '',
                '_career_country'     => $job['country'] ?? '',
            ] as $meta_key => $meta_value) {
                $meta_value = sanitize_text_field((string) $meta_value);
                if ($meta_value !== '') {
                    update_post_meta($post_id, $meta_key, $meta_value);
                }
            }

            $salary = self::parse_salary((string) ($job['salary'] ?? ''));
            if (!empty($salary)) {
                update_post_meta($post_id, '_career_salary', $salary['amount']);
                update_post_meta($post_id, '_career_salary_currency', $salary['currency']);
                update_post_meta($post_id, '_career_salary_unit', $salary['unit']);
            }
        }

        if (!empty($active_urls)) {
            $trashed = self::prune_old_jobs(array_values(array_unique($active_urls)));
        }

        $timestamp = current_time('mysql');
        $total = count(array_values(array_unique($active_urls)));
        update_option('chroma_last_career_sync', $timestamp);
        update_option('chroma_last_career_sync_count', $total);
        delete_transient('chroma_careers_data');

        $result = [
            'timestamp' => $timestamp,
            'total'     => $total,
            'created'   => $created,
            'updated'   => $updated,
            'trashed'   => $trashed,
        ];

        if (!empty($errors)) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    /**
     * Normalize employment type value to Schema.org supported enums.
     *
     * @param string $raw_type Input type.
     * @return string
     */
    private static function normalize_employment_type($raw_type)
    {
        $type = strtoupper(trim($raw_type));
        $map = [
            'FULL_TIME' => 'FULL_TIME',
            'FULL TIME' => 'FULL_TIME',
            'PART_TIME' => 'PART_TIME',
            'PART TIME' => 'PART_TIME',
            'CONTRACT' => 'CONTRACTOR',
            'CONTRACTOR' => 'CONTRACTOR',
            'TEMP' => 'TEMPORARY',
            'TEMPORARY' => 'TEMPORARY',
            'INTERN' => 'INTERN',
            'INTERNSHIP' => 'INTERN',
            'VOLUNTEER' => 'VOLUNTEER',
        ];

        return isset($map[$type]) ? $map[$type] : 'FULL_TIME';
    }

    /**
     * Normalize date posted to Y-m-d.
     *
     * @param string $raw_date Raw date from feed.
     * @return string
     */
    private static function normalize_date_posted($raw_date)
    {
        $raw_date = trim($raw_date);
        if ($raw_date === '') {
            return current_time('Y-m-d');
        }

        $timestamp = strtotime($raw_date);
        if ($timestamp === false) {
            return current_time('Y-m-d');
        }

        return gmdate('Y-m-d', $timestamp);
    }

    /**
     * Parse a provider salary string into existing career salary meta fields.
     *
     * @param string $raw_salary Raw salary string.
     * @return array
     */
    private static function parse_salary($raw_salary)
    {
        $raw_salary = trim($raw_salary);
        if ($raw_salary === '') {
            return [];
        }

        if (!preg_match_all('/(?:\$|USD)?\s*([0-9]+(?:\.[0-9]+)?)/i', $raw_salary, $matches) || empty($matches[1])) {
            return [];
        }

        $amounts = array_map('floatval', $matches[1]);
        $amount = min($amounts);
        if ($amount <= 0) {
            return [];
        }

        $lower = strtolower($raw_salary);
        $unit = 'YEAR';
        if (preg_match('/\b(hour|hourly|hr)\b/', $lower)) {
            $unit = 'HOUR';
        } elseif (preg_match('/\b(day|daily)\b/', $lower)) {
            $unit = 'DAY';
        } elseif (preg_match('/\b(week|weekly)\b/', $lower)) {
            $unit = 'WEEK';
        } elseif (preg_match('/\b(month|monthly)\b/', $lower)) {
            $unit = 'MONTH';
        }

        return [
            'amount'   => $amount,
            'currency' => 'USD',
            'unit'     => $unit,
        ];
    }

    /**
     * Find post by external URL
     */
    private static function get_post_by_job_url($url)
    {
        $args = [
            'post_type' => 'career',
            'meta_key' => '_career_external_url',
            'meta_value' => $url,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'no_found_rows' => true
        ];
        $query = new WP_Query($args);
        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Trash jobs not in the current list
     */
    private static function prune_old_jobs($active_urls)
    {
        $all_jobs = get_posts([
            'post_type' => 'career',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ]);

        $trashed = 0;
        foreach ($all_jobs as $job_id) {
            $job_url = get_post_meta($job_id, '_career_external_url', true);
            if ($job_url && !in_array($job_url, $active_urls)) {
                wp_trash_post($job_id);
                $trashed++;
            }
        }
        return $trashed;
    }
}



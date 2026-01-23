<?php
/**
 * Event Schema Builder
 * Generates JSON-LD for Event Schema
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Event_Schema_Builder
{
    /**
     * Build Event schema for a location
     *
     * @param int $post_id Location ID
     * @return array|null Schema array or null
     */
    public static function build($post_id)
    {
        $events = get_post_meta($post_id, 'location_events', true);
        if (empty($events) || !is_array($events)) {
            return null;
        }

        $schema_events = [];
        $location_data = [
            '@type' => 'Place',
            'name' => get_the_title($post_id),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => get_post_meta($post_id, 'location_address', true),
                'addressLocality' => get_post_meta($post_id, 'location_city', true),
                'addressRegion' => get_post_meta($post_id, 'location_state', true),
                'postalCode' => get_post_meta($post_id, 'location_zip', true),
            ],
        ];

        foreach ($events as $event) {
            // Skip past events
            if (strtotime($event['start']) < time()) {
                continue;
            }

            $schema_events[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => $event['name'],
                'startDate' => date('c', strtotime($event['start'])),
                'endDate' => date('c', strtotime($event['start']) + 7200), // Default 2 hours
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'location' => $location_data,
                'description' => $event['description'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                ],
            ];
        }

        return $schema_events;
    }

    /**
     * Output schema to head
     */
    public static function output()
    {
        if (!is_singular('location')) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $schemas = self::build(get_the_ID());
        if ($schemas) {
            foreach ($schemas as $schema) {
                Chroma_Schema_Registry::register($schema, ['source' => 'event-builder']);
            }
        }
    }
}



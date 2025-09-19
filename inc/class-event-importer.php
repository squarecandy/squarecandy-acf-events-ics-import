<?php

/**
 * Event Importer Class
 *
 * Converts ICS events to WordPress event posts with ACF fields
 */
class SQCDY_Event_Importer {

    /**
     * Import events from ICS feed
     *
     * @param string $feed_url ICS feed URL
     * @param array $options Import options
     * @return array Results array with counts and messages
     */
    public static function import_from_feed($feed_url, $options = []) {
        $defaults = [
            'update_existing' => false,
            'default_category' => '',
            'dry_run' => false,
            'limit' => 0
        ];

        $options = wp_parse_args($options, $defaults);

        $results = [
            'success' => false,
            'total_events' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'messages' => []
        ];

        // Parse the ICS feed
        $events = SQCDY_ICS_Parser::parse_feed($feed_url);

        if ($events === false) {
            $results['errors'][] = 'Failed to fetch or parse ICS feed';
            return $results;
        }

        $results['total_events'] = count($events);
        $results['messages'][] = sprintf('Found %d events in ICS feed', count($events));

        // Limit events if specified
        if ($options['limit'] > 0) {
            $events = array_slice($events, 0, $options['limit']);
            $results['messages'][] = sprintf('Limited to %d events', $options['limit']);
        }

        // Import each event
        foreach ($events as $event) {
            $import_result = self::import_single_event($event, $options);

            if ($import_result['success']) {
                if ($import_result['action'] === 'created') {
                    $results['imported']++;
                } elseif ($import_result['action'] === 'updated') {
                    $results['updated']++;
                }

                if (!empty($import_result['message'])) {
                    $results['messages'][] = $import_result['message'];
                }
            } else {
                $results['skipped']++;
                if (!empty($import_result['error'])) {
                    $results['errors'][] = $import_result['error'];
                }
            }
        }

        $results['success'] = true;

        return $results;
    }

    /**
     * Import single ICS event
     *
     * @param array $ics_event Parsed ICS event data
     * @param array $options Import options
     * @return array Result array
     */
    private static function import_single_event($ics_event, $options) {
        $result = [
            'success' => false,
            'action' => '',
            'post_id' => 0,
            'message' => '',
            'error' => ''
        ];

        // Validate required fields
        if (empty($ics_event['summary']) || empty($ics_event['dtstart'])) {
            $result['error'] = 'Event missing required fields (summary or start date)';
            return $result;
        }

        // Check if event already exists by UID
        $existing_post = self::find_existing_event($ics_event['uid']);

        if ($existing_post && !$options['update_existing']) {
            $result['error'] = sprintf('Event "%s" already exists (UID: %s)', $ics_event['summary'], $ics_event['uid']);
            return $result;
        }

        if ($options['dry_run']) {
            $result['success'] = true;
            $result['action'] = $existing_post ? 'would_update' : 'would_create';
            $result['message'] = sprintf('Would %s event: %s', $result['action'], $ics_event['summary']);
            return $result;
        }

        // Prepare event data
        $event_data = self::prepare_event_data($ics_event, $options);

        if ($existing_post) {
            // Update existing event
            $event_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($event_data, true);
            $result['action'] = 'updated';
        } else {
            // Create new event
            $post_id = wp_insert_post($event_data, true);
            $result['action'] = 'created';
        }

        if (is_wp_error($post_id)) {
            $result['error'] = $post_id->get_error_message();
            return $result;
        }

        // Set ACF fields
        $acf_result = self::set_acf_fields($post_id, $ics_event, $options);

        if (!$acf_result) {
            $result['error'] = 'Failed to set ACF fields';
            return $result;
        }

        // Store the UID for future reference
        update_post_meta($post_id, '_ics_uid', $ics_event['uid']);
        update_post_meta($post_id, '_ics_last_import', current_time('mysql'));

        $result['success'] = true;
        $result['post_id'] = $post_id;
        $result['message'] = sprintf('%s event: %s (ID: %d)',
            ucfirst($result['action']), $ics_event['summary'], $post_id);

        return $result;
    }

    /**
     * Find existing event by UID
     *
     * @param string $uid ICS UID
     * @return WP_Post|null Existing post or null
     */
    private static function find_existing_event($uid) {
        if (empty($uid)) {
            return null;
        }

        $posts = get_posts([
            'post_type' => 'event',
            'meta_query' => [
                [
                    'key' => '_ics_uid',
                    'value' => $uid,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);

        return !empty($posts) ? $posts[0] : null;
    }

    /**
     * Prepare WordPress post data from ICS event
     *
     * @param array $ics_event Parsed ICS event data
     * @param array $options Import options
     * @return array WordPress post data
     */
    private static function prepare_event_data($ics_event, $options) {
        $post_data = [
            'post_type' => 'event',
            'post_title' => $ics_event['summary'],
            'post_content' => isset($ics_event['description']) ? $ics_event['description'] : '',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ];

        // Set category if specified
        if (!empty($options['default_category'])) {
            $category_term = get_term_by('name', $options['default_category'], 'events-category');
            if ($category_term) {
                $post_data['tax_input'] = [
                    'events-category' => [$category_term->term_id]
                ];
            }
        }

        return $post_data;
    }

    /**
     * Set ACF fields for event
     *
     * @param int $post_id WordPress post ID
     * @param array $ics_event Parsed ICS event data
     * @param array $options Import options
     * @return bool Success status
     */
    private static function set_acf_fields($post_id, $ics_event, $options) {
        // Determine event type based on requirements
        $is_all_day = SQCDY_ICS_Parser::is_all_day_event($ics_event);
        $is_multi_day = SQCDY_ICS_Parser::is_multi_day_event($ics_event);

        // Set basic fields
        update_field('all_day', $is_all_day, $post_id);

        // Handle start date and time
        if (isset($ics_event['dtstart']['timestamp'])) {
            $start_timestamp = $ics_event['dtstart']['timestamp'];

            // Set start date (required field)
            $start_date = date('F j, Y', $start_timestamp);
            update_field('start_date', $start_date, $post_id);

            // For single-day events with times: import only start date and start time, leave end blank
            if (!$is_all_day && !$is_multi_day) {
                $start_time = date('g:i a', $start_timestamp);
                update_field('start_time', $start_time, $post_id);

                // Leave end date and end time blank as requested
                update_field('multi_day', false, $post_id);
                update_field('end_date', '', $post_id);
                update_field('end_time', '', $post_id);

            } elseif ($is_all_day && $is_multi_day) {
                // All-day multi-day event
                update_field('multi_day', true, $post_id);
                update_field('start_time', '', $post_id);

                if (isset($ics_event['dtend']['timestamp'])) {
                    // For all-day events, end date is often the day after the last day
                    // Subtract one day to get the actual end date
                    $end_timestamp = $ics_event['dtend']['timestamp'] - DAY_IN_SECONDS;
                    $end_date = date('F j, Y', $end_timestamp);
                    update_field('end_date', $end_date, $post_id);
                }

                update_field('end_time', '', $post_id);

            } elseif ($is_all_day && !$is_multi_day) {
                // Single day all-day event
                update_field('multi_day', false, $post_id);
                update_field('start_time', '', $post_id);
                update_field('end_date', '', $post_id);
                update_field('end_time', '', $post_id);

            } else {
                // Multi-day event with times - this is less common but we should handle it
                update_field('multi_day', true, $post_id);
                $start_time = date('g:i a', $start_timestamp);
                update_field('start_time', $start_time, $post_id);

                if (isset($ics_event['dtend']['timestamp'])) {
                    $end_timestamp = $ics_event['dtend']['timestamp'];
                    $end_date = date('F j, Y', $end_timestamp);
                    $end_time = date('g:i a', $end_timestamp);

                    update_field('end_date', $end_date, $post_id);
                    update_field('end_time', $end_time, $post_id);
                }
            }
        }

        // Handle location data
        if (!empty($ics_event['location'])) {
            $location_parts = self::parse_location($ics_event['location']);

            update_field('venue', $location_parts['venue'], $post_id);
            update_field('address', $location_parts['address'], $post_id);
            update_field('city', $location_parts['city'], $post_id);
            update_field('state', $location_parts['state'], $post_id);
            update_field('zip', $location_parts['zip'], $post_id);
            update_field('country', $location_parts['country'], $post_id);
        }

        return true;
    }

    /**
     * Parse location string into components
     *
     * @param string $location_string Full location string
     * @return array Location components
     */
    private static function parse_location($location_string) {
        $parts = [
            'venue' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => ''
        ];

        // Basic parsing - this can be enhanced based on common patterns
        $location_parts = array_map('trim', explode(',', $location_string));

        if (count($location_parts) >= 1) {
            $parts['venue'] = $location_parts[0];
        }

        if (count($location_parts) >= 2) {
            $parts['address'] = $location_parts[1];
        }

        if (count($location_parts) >= 3) {
            $parts['city'] = $location_parts[2];
        }

        if (count($location_parts) >= 4) {
            // Try to parse "State ZIP" format
            $state_zip = $location_parts[3];
            if (preg_match('/^(.+?)\s+(\d{5}(-\d{4})?)$/', $state_zip, $matches)) {
                $parts['state'] = $matches[1];
                $parts['zip'] = $matches[2];
            } else {
                $parts['state'] = $state_zip;
            }
        }

        if (count($location_parts) >= 5) {
            $parts['country'] = $location_parts[4];
        }

        return $parts;
    }
}

<?php

/**
 * ICS Parser Class
 *
 * Parses ICS calendar feeds and extracts event data
 */
class SQCDY_ICS_Parser {

    /**
     * Parse ICS feed from URL
     *
     * @param string $url The ICS feed URL
     * @return array Array of parsed events or false on failure
     */
    public static function parse_feed($url) {
        $ics_content = wp_remote_get($url);

        if (is_wp_error($ics_content)) {
            return false;
        }

        $body = wp_remote_retrieve_body($ics_content);

        if (empty($body)) {
            return false;
        }

        return self::parse_ics_content($body);
    }

    /**
     * Parse ICS content string
     *
     * @param string $ics_content Raw ICS content
     * @return array Array of parsed events
     */
    public static function parse_ics_content($ics_content) {
        $events = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $ics_content));
        $current_event = null;
        $in_event = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Handle line continuations (lines starting with space or tab)
            if (isset($previous_line) && (strpos($line, ' ') === 0 || strpos($line, "\t") === 0)) {
                $previous_line .= ltrim($line);
                continue;
            }

            if (isset($previous_line)) {
                self::parse_line($previous_line, $current_event, $in_event, $events);
            }

            $previous_line = $line;
        }

        // Process the last line
        if (isset($previous_line)) {
            self::parse_line($previous_line, $current_event, $in_event, $events);
        }

        return $events;
    }

    /**
     * Parse individual ICS line
     *
     * @param string $line ICS line
     * @param array &$current_event Current event being parsed
     * @param bool &$in_event Whether we're currently parsing an event
     * @param array &$events Array of completed events
     */
    private static function parse_line($line, &$current_event, &$in_event, &$events) {
        if ($line === 'BEGIN:VEVENT') {
            $in_event = true;
            $current_event = [];
            return;
        }

        if ($line === 'END:VEVENT') {
            if ($in_event && $current_event) {
                $events[] = $current_event;
            }
            $in_event = false;
            $current_event = null;
            return;
        }

        if (!$in_event) {
            return;
        }

        // Parse property:value pairs
        $colon_pos = strpos($line, ':');
        if ($colon_pos === false) {
            return;
        }

        $property = substr($line, 0, $colon_pos);
        $value = substr($line, $colon_pos + 1);

        // Handle parameters (e.g., DTSTART;VALUE=DATE:20150220)
        $semicolon_pos = strpos($property, ';');
        if ($semicolon_pos !== false) {
            $params = substr($property, $semicolon_pos + 1);
            $property = substr($property, 0, $semicolon_pos);
        } else {
            $params = '';
        }

        // Store relevant properties
        switch ($property) {
            case 'DTSTART':
                $current_event['dtstart'] = self::parse_datetime($value, $params);
                $current_event['dtstart_raw'] = $value;
                $current_event['dtstart_params'] = $params;
                break;

            case 'DTEND':
                $current_event['dtend'] = self::parse_datetime($value, $params);
                $current_event['dtend_raw'] = $value;
                $current_event['dtend_params'] = $params;
                break;

            case 'SUMMARY':
                $current_event['summary'] = self::decode_ics_text($value);
                break;

            case 'DESCRIPTION':
                $current_event['description'] = self::decode_ics_text($value);
                break;

            case 'LOCATION':
                $current_event['location'] = self::decode_ics_text($value);
                break;

            case 'UID':
                $current_event['uid'] = $value;
                break;

            case 'CREATED':
                $current_event['created'] = self::parse_datetime($value, $params);
                break;

            case 'LAST-MODIFIED':
                $current_event['last_modified'] = self::parse_datetime($value, $params);
                break;
        }
    }

    /**
     * Parse ICS datetime
     *
     * @param string $value DateTime value
     * @param string $params Parameters (e.g., VALUE=DATE)
     * @return array Parsed datetime info
     */
    private static function parse_datetime($value, $params = '') {
        $is_date_only = strpos($params, 'VALUE=DATE') !== false;
        $timezone = null;

        if (preg_match('/TZID=([^;]+)/', $params, $matches)) {
            $timezone = $matches[1];
        }

        $result = [
            'is_date_only' => $is_date_only,
            'timezone' => $timezone,
            'raw' => $value
        ];

        if ($is_date_only) {
            // Date only format: YYYYMMDD
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $matches)) {
                $result['year'] = $matches[1];
                $result['month'] = $matches[2];
                $result['day'] = $matches[3];
                $result['timestamp'] = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
            }
        } else {
            // DateTime format: YYYYMMDDTHHMMSSZ or YYYYMMDDTHHMMSS
            if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})(Z?)$/', $value, $matches)) {
                $result['year'] = $matches[1];
                $result['month'] = $matches[2];
                $result['day'] = $matches[3];
                $result['hour'] = $matches[4];
                $result['minute'] = $matches[5];
                $result['second'] = $matches[6];
                $result['is_utc'] = !empty($matches[7]);

                $timestamp = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);

                // Convert from UTC if needed
                if ($result['is_utc']) {
                    $timestamp = $timestamp - get_option('gmt_offset') * HOUR_IN_SECONDS;
                }

                $result['timestamp'] = $timestamp;
            }
        }

        return $result;
    }

    /**
     * Decode ICS text (handle escape sequences)
     *
     * @param string $text Encoded ICS text
     * @return string Decoded text
     */
    private static function decode_ics_text($text) {
        // Decode common ICS escape sequences
        $text = str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $text);

        // Remove HTML tags from description
        $text = strip_tags($text);

        return trim($text);
    }

    /**
     * Check if event is all day
     *
     * @param array $event Parsed event data
     * @return bool True if all day event
     */
    public static function is_all_day_event($event) {
        return isset($event['dtstart']['is_date_only']) && $event['dtstart']['is_date_only'];
    }

    /**
     * Check if event spans multiple days
     *
     * @param array $event Parsed event data
     * @return bool True if multi-day event
     */
    public static function is_multi_day_event($event) {
        if (!isset($event['dtstart']) || !isset($event['dtend'])) {
            return false;
        }

        $start_date = date('Y-m-d', $event['dtstart']['timestamp']);
        $end_date = date('Y-m-d', $event['dtend']['timestamp']);

        return $start_date !== $end_date;
    }
}

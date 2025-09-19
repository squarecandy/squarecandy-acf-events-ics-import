<?php
/**
 * Test file for ICS Import functionality
 *
 * This file can be used to test the ICS parsing functionality
 * independently of WordPress. Uncomment the lines at the bottom
 * to run tests.
 */

// Uncomment to test independently (make sure to include the parser file)
/*
require_once 'includes/class-ics-parser.php';

// Test with the sample Google Calendar URL
$test_url = 'https://calendar.google.com/calendar/ical/494h1li4bohjc380cl8bntq94k%40group.calendar.google.com/public/basic.ics';

echo "Testing ICS Parser with URL: " . $test_url . "\n\n";

$events = SQCDY_ICS_Parser::parse_feed($test_url);

if ($events === false) {
    echo "Failed to parse ICS feed\n";
    exit;
}

echo "Found " . count($events) . " events:\n\n";

foreach (array_slice($events, 0, 5) as $i => $event) {
    echo "Event " . ($i + 1) . ":\n";
    echo "  Title: " . ($event['summary'] ?? 'No title') . "\n";
    echo "  Start: " . ($event['dtstart']['raw'] ?? 'No start') . "\n";
    echo "  End: " . ($event['dtend']['raw'] ?? 'No end') . "\n";
    echo "  All Day: " . (SQCDY_ICS_Parser::is_all_day_event($event) ? 'Yes' : 'No') . "\n";
    echo "  Multi Day: " . (SQCDY_ICS_Parser::is_multi_day_event($event) ? 'Yes' : 'No') . "\n";
    echo "  Location: " . ($event['location'] ?? 'No location') . "\n";
    echo "  Description: " . substr($event['description'] ?? '', 0, 100) . "...\n";
    echo "\n";
}
*/

/**
 * WordPress function to test ICS import from admin
 * Add this as an AJAX action if needed for debugging
 */
function sqcdy_ics_import_test() {
    // Only allow for administrators
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $test_url = 'https://calendar.google.com/calendar/ical/494h1li4bohjc380cl8bntq94k%40group.calendar.google.com/public/basic.ics';

    echo '<h2>ICS Import Test</h2>';
    echo '<p>Testing with URL: <code>' . esc_html($test_url) . '</code></p>';

    $events = SQCDY_ICS_Parser::parse_feed($test_url);

    if ($events === false) {
        echo '<div class="notice notice-error"><p>Failed to parse ICS feed</p></div>';
        return;
    }

    echo '<div class="notice notice-success"><p>Found ' . count($events) . ' events</p></div>';

    echo '<h3>Sample Events:</h3>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Title</th><th>Start</th><th>Type</th><th>Location</th></tr></thead>';
    echo '<tbody>';

    foreach (array_slice($events, 0, 10) as $event) {
        $is_all_day = SQCDY_ICS_Parser::is_all_day_event($event);
        $is_multi_day = SQCDY_ICS_Parser::is_multi_day_event($event);

        $type = [];
        if ($is_all_day) $type[] = 'All Day';
        if ($is_multi_day) $type[] = 'Multi Day';
        if (empty($type)) $type[] = 'Single Day with Time';

        echo '<tr>';
        echo '<td>' . esc_html($event['summary'] ?? 'No title') . '</td>';
        echo '<td>' . esc_html($event['dtstart']['raw'] ?? 'No start') . '</td>';
        echo '<td>' . esc_html(implode(', ', $type)) . '</td>';
        echo '<td>' . esc_html(substr($event['location'] ?? 'No location', 0, 50)) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

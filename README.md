# Square Candy ACF Events ICS Import

A WordPress plugin that imports events from ICS calendar feeds and creates events using the Square Candy ACF Events plugin.

## Description

This plugin extends the Square Candy ACF Events plugin by adding the ability to import events from external ICS (iCalendar) feeds, such as Google Calendar public feeds. It automatically creates WordPress event posts with properly mapped ACF fields.

## Features

- **ICS Feed Parsing**: Robust parsing of ICS calendar format
- **Smart Date/Time Handling**: Proper handling of different event types:
  - Single-day events with times: Import start date and start time only (end date/time left blank)
  - All-day single events: Import start date only
  - Multi-day events: Import appropriate start and end dates with times
- **Location Parsing**: Automatic parsing of location data into venue, address, city, state, zip, and country fields
- **Duplicate Prevention**: Uses ICS UID to prevent duplicate imports
- **Update Existing**: Option to update existing events when re-importing
- **Preview Mode**: Dry-run import to see what would be imported
- **Admin Interface**: Easy-to-use settings and import interface

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Square Candy ACF Events plugin
- Advanced Custom Fields (ACF) plugin

## Installation

1. Upload the plugin files to `/wp-content/plugins/squarecandy-acf-events-ics-import/`
2. Activate the plugin through the WordPress admin
3. Make sure you have the Square Candy ACF Events plugin and ACF activated
4. Go to Events > ICS Import to configure and run imports

## Usage

### Basic Setup

1. **Configure Feed URL**: Go to Events > ICS Import and enter your ICS feed URL
2. **Set Options**: Choose default category, timezone, and update preferences
3. **Preview Import**: Click "Preview Import" to see what events would be imported
4. **Run Import**: Click "Run Import" to actually create the events

### Event Type Handling

The plugin follows specific rules for different event types:

#### Single-Day Events with Times
- **Input**: Event on June 15, 2024 from 3:00 PM to 5:00 PM
- **Output**: Start Date = June 15, 2024, Start Time = 3:00 pm, End Date = blank, End Time = blank

#### All-Day Single Events  
- **Input**: All-day event on June 15, 2024
- **Output**: Start Date = June 15, 2024, All Day = checked, other time fields = blank

#### Multi-Day Events
- **Input**: Event from June 15, 2024 to June 17, 2024
- **Output**: Start Date = June 15, 2024, End Date = June 17, 2024, Multi-Day = checked

### Settings

- **ICS Feed URL**: The URL of your ICS calendar feed
- **Default Category**: Default events category for imported events
- **Update Existing Events**: Whether to update events that already exist (based on UID)
- **Timezone**: Timezone for interpreting event times

### Location Parsing

The plugin automatically parses location strings like:
```
"Venue Name, 123 Main St, City, State ZIP, Country"
```

Into separate fields:
- Venue: "Venue Name"
- Address: "123 Main St"
- City: "City"
- State: "State"
- Zip: "ZIP"
- Country: "Country"

## Hooks and Filters

### Actions
- `sqcdy_ics_import_before_event_create` - Fired before creating an event
- `sqcdy_ics_import_after_event_create` - Fired after creating an event

### Filters
- `sqcdy_ics_import_event_data` - Filter event data before creating post
- `sqcdy_ics_import_location_parsing` - Filter location parsing results

## Troubleshooting

### Common Issues

**"Failed to fetch or parse ICS feed"**
- Check that the ICS URL is accessible
- Verify the URL returns valid ICS content
- Check for SSL certificate issues

**"Event missing required fields"**
- The ICS event must have at minimum a SUMMARY and DTSTART field
- Check the source calendar for incomplete events

**Events not showing correct times**
- Verify the timezone setting matches your calendar's timezone
- Check if the ICS feed includes timezone information

### Debug Mode

To enable debug logging, add this to your wp-config.php:
```php
define('SQCDY_ICS_IMPORT_DEBUG', true);
```

## Example ICS Feed URLs

### Google Calendar
```
https://calendar.google.com/calendar/ical/[calendar-id]/public/basic.ics
```

### Outlook/Office 365
```
https://outlook.live.com/owa/calendar/[calendar-id]/reachcalendar/calendar.ics
```

## Development

### File Structure
```
squarecandy-acf-events-ics-import/
├── squarecandy-acf-events-ics-import.php  # Main plugin file
├── includes/
│   ├── class-ics-parser.php               # ICS parsing functionality
│   ├── class-event-importer.php           # Event creation logic
│   └── admin-pages.php                    # Admin interface
└── README.md
```

### Classes

- **SQCDY_ICS_Parser**: Handles parsing of ICS feed content
- **SQCDY_Event_Importer**: Manages creating WordPress events from ICS data

## Changelog

### 1.0.0
- Initial release
- ICS feed parsing and import functionality
- Admin interface for configuration
- Support for single-day, multi-day, and all-day events
- Location parsing and field mapping

## Support

For support and bug reports, please open an issue on the GitHub repository or contact Square Candy.

## License

This plugin is licensed under the GPL v2 or later.

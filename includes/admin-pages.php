<?php

/**
 * Admin Pages for ICS Import
 */

/**
 * Main admin page
 */
function sqcdy_ics_import_admin_page() {
    $feed_url = get_option('sqcdy_ics_import_feed_url', '');
    $default_category = get_option('sqcdy_ics_import_default_category', '');
    $update_existing = get_option('sqcdy_ics_import_update_existing', false);
    $timezone = get_option('sqcdy_ics_import_timezone', get_option('timezone_string', 'America/New_York'));

    // Handle form submissions
    if (isset($_POST['action'])) {
        check_admin_referer('sqcdy_ics_import_nonce');

        if ($_POST['action'] === 'save_settings') {
            sqcdy_ics_import_save_settings();
        } elseif ($_POST['action'] === 'import_preview') {
            $preview_results = sqcdy_ics_import_preview();
        } elseif ($_POST['action'] === 'import_run') {
            $import_results = sqcdy_ics_import_run();
        }
    }

    ?>
    <div class="wrap sqcdy-ics-import-page">
        <h1>ICS Import for Events</h1>

        <?php if (isset($import_results)): ?>
            <div class="notice notice-<?php echo $import_results['success'] ? 'success' : 'error'; ?> is-dismissible">
                <h3>Import Results</h3>
                <p><strong>Total Events:</strong> <?php echo $import_results['total_events']; ?></p>
                <p><strong>Imported:</strong> <?php echo $import_results['imported']; ?></p>
                <p><strong>Updated:</strong> <?php echo $import_results['updated']; ?></p>
                <p><strong>Skipped:</strong> <?php echo $import_results['skipped']; ?></p>

                <?php if (!empty($import_results['messages'])): ?>
                    <h4>Messages:</h4>
                    <ul>
                        <?php foreach ($import_results['messages'] as $message): ?>
                            <li><?php echo esc_html($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($import_results['errors'])): ?>
                    <h4>Errors:</h4>
                    <ul>
                        <?php foreach ($import_results['errors'] as $error): ?>
                            <li style="color: #d63384;"><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($preview_results)): ?>
            <div class="notice notice-info is-dismissible">
                <h3>Preview Results</h3>
                <p><strong>Total Events Found:</strong> <?php echo $preview_results['total_events']; ?></p>
                <p><strong>Would Import:</strong> <?php echo $preview_results['imported']; ?></p>
                <p><strong>Would Update:</strong> <?php echo $preview_results['updated']; ?></p>
                <p><strong>Would Skip:</strong> <?php echo $preview_results['skipped']; ?></p>

                <?php if (!empty($preview_results['messages'])): ?>
                    <h4>Sample Events:</h4>
                    <ul>
                        <?php foreach (array_slice($preview_results['messages'], 0, 10) as $message): ?>
                            <li><?php echo esc_html($message); ?></li>
                        <?php endforeach; ?>
                        <?php if (count($preview_results['messages']) > 10): ?>
                            <li><em>... and <?php echo count($preview_results['messages']) - 10; ?> more</em></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">

                    <!-- Settings Form -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Import Settings</span></h2>
                        <div class="inside">
                            <form method="post" action="">
                                <?php wp_nonce_field('sqcdy_ics_import_nonce'); ?>
                                <input type="hidden" name="action" value="save_settings">

                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="feed_url">ICS Feed URL</label>
                                        </th>
                                        <td>
                                            <input type="url" id="feed_url" name="feed_url" value="<?php echo esc_attr($feed_url); ?>" class="regular-text" required>
                                            <p class="description">Enter the URL of the ICS calendar feed to import</p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="default_category">Default Category</label>
                                        </th>
                                        <td>
                                            <?php
                                            $categories = get_terms([
                                                'taxonomy' => 'events-category',
                                                'hide_empty' => false
                                            ]);
                                            ?>
                                            <select id="default_category" name="default_category">
                                                <option value="">No Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo esc_attr($category->name); ?>" <?php selected($default_category, $category->name); ?>>
                                                        <?php echo esc_html($category->name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description">Choose a default category for imported events</p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="update_existing">Update Existing Events</label>
                                        </th>
                                        <td>
                                            <input type="checkbox" id="update_existing" name="update_existing" value="1" <?php checked($update_existing); ?>>
                                            <label for="update_existing">Update existing events if they already exist (based on UID)</label>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="timezone">Timezone</label>
                                        </th>
                                        <td>
                                            <select id="timezone" name="timezone">
                                                <?php
                                                $timezones = [
                                                    'America/New_York' => 'Eastern Time',
                                                    'America/Chicago' => 'Central Time',
                                                    'America/Denver' => 'Mountain Time',
                                                    'America/Los_Angeles' => 'Pacific Time',
                                                    'UTC' => 'UTC'
                                                ];
                                                foreach ($timezones as $tz => $label): ?>
                                                    <option value="<?php echo esc_attr($tz); ?>" <?php selected($timezone, $tz); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description">Timezone for interpreting event times</p>
                                        </td>
                                    </tr>
                                </table>

                                <?php submit_button('Save Settings'); ?>
                            </form>
                        </div>
                    </div>

                    <!-- Import Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Import Actions</span></h2>
                        <div class="inside">

                            <?php if (empty($feed_url)): ?>
                                <p><em>Please configure the ICS Feed URL above before importing.</em></p>
                            <?php else: ?>

                                <form method="post" action="" style="display: inline-block; margin-right: 10px;">
                                    <?php wp_nonce_field('sqcdy_ics_import_nonce'); ?>
                                    <input type="hidden" name="action" value="import_preview">
                                    <?php submit_button('Preview Import', 'secondary', 'submit', false); ?>
                                </form>

                                <form method="post" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to import these events? This action cannot be easily undone.');">
                                    <?php wp_nonce_field('sqcdy_ics_import_nonce'); ?>
                                    <input type="hidden" name="action" value="import_run">
                                    <?php submit_button('Run Import', 'primary', 'submit', false); ?>
                                </form>

                                <p class="description" style="margin-top: 10px;">
                                    <strong>Preview Import</strong> will show you what events would be imported without actually creating them.<br>
                                    <strong>Run Import</strong> will actually create the event posts in WordPress.
                                </p>

                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">

                    <div class="postbox">
                        <h2 class="hndle"><span>About</span></h2>
                        <div class="inside">
                            <p>This plugin imports events from ICS calendar feeds and creates WordPress events using the Square Candy ACF Events plugin.</p>

                            <h4>Event Handling:</h4>
                            <ul>
                                <li><strong>Single-day events:</strong> Import start date and start time only (end date/time left blank)</li>
                                <li><strong>All-day single events:</strong> Import start date only</li>
                                <li><strong>Multi-day events:</strong> Import start and end dates with appropriate times</li>
                            </ul>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span>Recent Imports</span></h2>
                        <div class="inside">
                            <?php
                            $recent_imports = get_posts([
                                'post_type' => 'event',
                                'meta_query' => [
                                    [
                                        'key' => '_ics_last_import',
                                        'compare' => 'EXISTS'
                                    ]
                                ],
                                'orderby' => 'meta_value',
                                'order' => 'DESC',
                                'posts_per_page' => 10
                            ]);

                            if (empty($recent_imports)): ?>
                                <p><em>No imported events yet.</em></p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($recent_imports as $post): ?>
                                        <li>
                                            <a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                                            <br><small><?php echo get_post_meta($post->ID, '_ics_last_import', true); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <style>
        .form-table th {
            width: 200px;
        }

        .postbox .inside ul {
            list-style-type: disc;
            margin-left: 20px;
        }

        .postbox .inside ul li {
            margin-bottom: 5px;
        }
    </style>
    <?php
}

/**
 * Save settings
 */
function sqcdy_ics_import_save_settings() {
    if (isset($_POST['feed_url'])) {
        update_option('sqcdy_ics_import_feed_url', sanitize_url($_POST['feed_url']));
    }

    if (isset($_POST['default_category'])) {
        update_option('sqcdy_ics_import_default_category', sanitize_text_field($_POST['default_category']));
    }

    update_option('sqcdy_ics_import_update_existing', isset($_POST['update_existing']));

    if (isset($_POST['timezone'])) {
        update_option('sqcdy_ics_import_timezone', sanitize_text_field($_POST['timezone']));
    }

    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    });
}

/**
 * Preview import
 */
function sqcdy_ics_import_preview() {
    $feed_url = get_option('sqcdy_ics_import_feed_url');

    if (empty($feed_url)) {
        return ['success' => false, 'errors' => ['No feed URL configured']];
    }

    $options = [
        'update_existing' => get_option('sqcdy_ics_import_update_existing', false),
        'default_category' => get_option('sqcdy_ics_import_default_category', ''),
        'dry_run' => true,
        'limit' => 50 // Limit preview to 50 events
    ];

    return SQCDY_Event_Importer::import_from_feed($feed_url, $options);
}

/**
 * Run import
 */
function sqcdy_ics_import_run() {
    $feed_url = get_option('sqcdy_ics_import_feed_url');

    if (empty($feed_url)) {
        return ['success' => false, 'errors' => ['No feed URL configured']];
    }

    $options = [
        'update_existing' => get_option('sqcdy_ics_import_update_existing', false),
        'default_category' => get_option('sqcdy_ics_import_default_category', ''),
        'dry_run' => false
    ];

    return SQCDY_Event_Importer::import_from_feed($feed_url, $options);
}

<?php

/**
 * Admin Pages for ICS Import
 */

/**
 * Main admin page
 */
function sqcdy_ics_import_admin_page() {
	$feed_url        = get_option( 'sqcdy_ics_import_feed_url', '' );
	$update_existing = get_option( 'sqcdy_ics_import_update_existing', false );
	$timezone        = get_option( 'sqcdy_ics_import_timezone', get_option( 'timezone_string', 'America/New_York' ) );

	// Handle form submissions
	if ( isset( $_POST['action'] ) && check_admin_referer( 'sqcdy_ics_import_nonce', '_wpnonce', false ) ) {
		if ( sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'save_settings' ) {
			sqcdy_ics_import_save_settings();
		} elseif ( 'import_preview' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
			$preview_results = sqcdy_ics_import_preview();
		} elseif ( 'import_run' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
			$import_results = sqcdy_ics_import_run();
		} elseif ( 'test_feed' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
			$test_results = sqcdy_ics_import_test_feed();
		}
	}

	?>
	<div class="wrap sqcdy-ics-import-page">
		<h1><?php esc_html_e( 'ICS Import for Events', 'squarecandy-acf-events-ics-import' ); ?></h1>

		<?php if ( isset( $test_results ) ) : ?>
			<div class="notice notice-info is-dismissible">
				<h3><?php esc_html_e( 'Feed Test Results', 'squarecandy-acf-events-ics-import' ); ?></h3>
				<?php foreach ( $test_results as $key => $value ) : ?>
					<p><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?>:</strong> <?php echo esc_html( $value ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( isset( $import_results ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $import_results['success'] ? 'success' : 'error' ); ?> is-dismissible">
				<h3><?php esc_html_e( 'Import Results', 'squarecandy-acf-events-ics-import' ); ?></h3>
				<p><strong><?php esc_html_e( 'Total Events:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $import_results['total_events'] ); ?></p>
				<p><strong><?php esc_html_e( 'Imported:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $import_results['imported'] ); ?></p>
				<p><strong><?php esc_html_e( 'Updated:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $import_results['updated'] ); ?></p>
				<p><strong><?php esc_html_e( 'Skipped:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $import_results['skipped'] ); ?></p>

				<?php if ( ! empty( $import_results['messages'] ) ) : ?>
					<h4><?php esc_html_e( 'Messages:', 'squarecandy-acf-events-ics-import' ); ?></h4>
					<ul>
						<?php foreach ( $import_results['messages'] as $message ) : ?>
							<li><?php echo esc_html( $message ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $import_results['errors'] ) ) : ?>
					<h4><?php esc_html_e( 'Errors:', 'squarecandy-acf-events-ics-import' ); ?></h4>
					<ul>
						<?php foreach ( $import_results['errors'] as $error ) : ?>
							<li style="color: #d63384;"><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( isset( $preview_results ) ) : ?>
			<div class="notice notice-info is-dismissible">
				<h3><?php esc_html_e( 'Preview Results', 'squarecandy-acf-events-ics-import' ); ?></h3>
				<p><strong><?php esc_html_e( 'Total Events Found:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $preview_results['total_events'] ); ?></p>
				<p><strong><?php esc_html_e( 'Would Import:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $preview_results['imported'] ); ?></p>
				<p><strong><?php esc_html_e( 'Would Update:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $preview_results['updated'] ); ?></p>
				<p><strong><?php esc_html_e( 'Would Skip:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php echo esc_html( $preview_results['skipped'] ); ?></p>

				<?php if ( ! empty( $preview_results['messages'] ) ) : ?>
					<h4><?php esc_html_e( 'Sample Events:', 'squarecandy-acf-events-ics-import' ); ?></h4>
					<ul>
						<?php foreach ( array_slice( $preview_results['messages'], 0, 10 ) as $message ) : ?>
							<li><?php echo esc_html( $message ); ?></li>
						<?php endforeach; ?>
						<?php if ( count( $preview_results['messages'] ) > 10 ) : ?>
							<li><em>... and <?php echo count( $preview_results['messages'] ) - 10; ?> more</em></li>
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
						<h2 class="hndle"><span><?php esc_html_e( 'Import Settings', 'squarecandy-acf-events-ics-import' ); ?></span></h2>
						<div class="inside">
							<form method="post" action="">
								<?php wp_nonce_field( 'sqcdy_ics_import_nonce' ); ?>
								<input type="hidden" name="action" value="save_settings">

								<table class="form-table">
									<tr>
										<th scope="row">
											<label for="feed_url"><?php esc_html_e( 'ICS Feed URL', 'squarecandy-acf-events-ics-import' ); ?></label>
										</th>
										<td>
											<input type="url" id="feed_url" name="feed_url" value="<?php echo esc_attr( $feed_url ); ?>" class="regular-text" required>
											<p class="description"><?php esc_html_e( 'Enter the URL of the ICS calendar feed to import', 'squarecandy-acf-events-ics-import' ); ?></p>
										</td>
									</tr>

									<tr>
										<th scope="row">
											<label for="update_existing"><?php esc_html_e( 'Update Existing Events', 'squarecandy-acf-events-ics-import' ); ?></label>
										</th>
										<td>
											<input type="checkbox" id="update_existing" name="update_existing" value="1" <?php checked( $update_existing ); ?>>
											<label for="update_existing"><?php esc_html_e( 'Update existing events if they already exist (based on UID)', 'squarecandy-acf-events-ics-import' ); ?></label>
										</td>
									</tr>

									<tr>
										<th scope="row">
											<label for="timezone"><?php esc_html_e( 'Timezone', 'squarecandy-acf-events-ics-import' ); ?></label>
										</th>
										<td>
											<select id="timezone" name="timezone">
												<?php
												$timezones = array(
													'America/New_York' => __( 'Eastern Time', 'squarecandy-acf-events-ics-import' ),
													'America/Chicago' => __( 'Central Time', 'squarecandy-acf-events-ics-import' ),
													'America/Denver' => __( 'Mountain Time', 'squarecandy-acf-events-ics-import' ),
													'America/Los_Angeles' => __( 'Pacific Time', 'squarecandy-acf-events-ics-import' ),
													'UTC' => __( 'UTC', 'squarecandy-acf-events-ics-import' ),
												);
												foreach ( $timezones as $tz => $label ) :
													?>
													<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $timezone, $tz ); ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description"><?php esc_html_e( 'Timezone for interpreting event times', 'squarecandy-acf-events-ics-import' ); ?></p>
										</td>
									</tr>
								</table>

								<?php submit_button( __( 'Save Settings', 'squarecandy-acf-events-ics-import' ) ); ?>
							</form>
						</div>
					</div>

					<!-- Import Actions -->
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Import Actions', 'squarecandy-acf-events-ics-import' ); ?></span></h2>
						<div class="inside">

							<?php if ( empty( $feed_url ) ) : ?>
								<p><em><?php esc_html_e( 'Please configure the ICS Feed URL above before importing.', 'squarecandy-acf-events-ics-import' ); ?></em></p>
							<?php else : ?>

								<form method="post" action="" style="display: inline-block; margin-right: 10px;">
									<?php wp_nonce_field( 'sqcdy_ics_import_nonce' ); ?>
									<input type="hidden" name="action" value="test_feed">
									<?php submit_button( __( 'Test Feed', 'squarecandy-acf-events-ics-import' ), 'secondary', 'submit', false ); ?>
								</form>

								<form method="post" action="" style="display: inline-block; margin-right: 10px;">
									<?php wp_nonce_field( 'sqcdy_ics_import_nonce' ); ?>
									<input type="hidden" name="action" value="import_preview">
									<?php submit_button( __( 'Preview Import', 'squarecandy-acf-events-ics-import' ), 'secondary', 'submit', false ); ?>
								</form>

								<form method="post" action="" style="display: inline-block;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to import these events? This action cannot be easily undone.', 'squarecandy-acf-events-ics-import' ) ); ?>');">
									<?php wp_nonce_field( 'sqcdy_ics_import_nonce' ); ?>
									<input type="hidden" name="action" value="import_run">
									<?php submit_button( __( 'Run Import', 'squarecandy-acf-events-ics-import' ), 'primary', 'submit', false ); ?>
								</form>

								<p class="description" style="margin-top: 10px;">
									<?php
									printf(
										/* translators: %1$s: Test Feed, %2$s: Preview Import, %3$s: Run Import */
										esc_html__( '%1$s will test the feed URL and show diagnostic information. %2$s will show you what events would be imported without actually creating them. %3$s will actually create the event posts in WordPress.', 'squarecandy-acf-events-ics-import' ),
										'<strong>' . esc_html__( 'Test Feed', 'squarecandy-acf-events-ics-import' ) . '</strong>',
										'<strong>' . esc_html__( 'Preview Import', 'squarecandy-acf-events-ics-import' ) . '</strong>',
										'<strong>' . esc_html__( 'Run Import', 'squarecandy-acf-events-ics-import' ) . '</strong>'
									);
									?>
								</p>

							<?php endif; ?>
						</div>
					</div>

				</div>

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">

					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'About', 'squarecandy-acf-events-ics-import' ); ?></span></h2>
						<div class="inside">
							<p><?php esc_html_e( 'This plugin imports events from ICS calendar feeds and creates WordPress events using the Square Candy ACF Events plugin.', 'squarecandy-acf-events-ics-import' ); ?></p>

							<h4><?php esc_html_e( 'Event Handling:', 'squarecandy-acf-events-ics-import' ); ?></h4>
							<ul>
								<li><strong><?php esc_html_e( 'Single-day events:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php esc_html_e( 'Import start date and start time only (end date/time left blank)', 'squarecandy-acf-events-ics-import' ); ?></li>
								<li><strong><?php esc_html_e( 'All-day single events:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php esc_html_e( 'Import start date only', 'squarecandy-acf-events-ics-import' ); ?></li>
								<li><strong><?php esc_html_e( 'Multi-day events:', 'squarecandy-acf-events-ics-import' ); ?></strong> <?php esc_html_e( 'Import start and end dates with appropriate times', 'squarecandy-acf-events-ics-import' ); ?></li>
							</ul>
						</div>
					</div>

					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Recent Imports', 'squarecandy-acf-events-ics-import' ); ?></span></h2>
						<div class="inside">
							<?php
							$recent_imports = get_posts(
								array(
									'post_type'      => 'event',
									'meta_query'     => array(
										array(
											'key'     => '_ics_last_import',
											'compare' => 'EXISTS',
										),
									),
									'orderby'        => 'meta_value',
									'order'          => 'DESC',
									'posts_per_page' => 10,
								)
							);

							if ( empty( $recent_imports ) ) :
								?>
								<p><em><?php esc_html_e( 'No imported events yet.', 'squarecandy-acf-events-ics-import' ); ?></em></p>
							<?php else : ?>
								<ul>
									<?php foreach ( $recent_imports as $post ) : ?>
										<li>
											<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
											<br><small><?php echo esc_html( get_post_meta( $post->ID, '_ics_last_import', true ) ); ?></small>
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


	<?php
}

/**
 * Save settings
 */
function sqcdy_ics_import_save_settings() {
	// Verify nonce
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'sqcdy_ics_import_nonce' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'squarecandy-acf-events-ics-import' ) );
	}

	if ( isset( $_POST['feed_url'] ) ) {
		update_option( 'sqcdy_ics_import_feed_url', esc_url_raw( wp_unslash( $_POST['feed_url'] ) ) );
	}

	update_option( 'sqcdy_ics_import_update_existing', isset( $_POST['update_existing'] ) );

	if ( isset( $_POST['timezone'] ) ) {
		update_option( 'sqcdy_ics_import_timezone', sanitize_text_field( wp_unslash( $_POST['timezone'] ) ) );
	}

	add_action(
		'admin_notices',
		function() {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'squarecandy-acf-events-ics-import' ) . '</p></div>';
		}
	);
}

/**
 * Preview import
 */
function sqcdy_ics_import_preview() {
	$feed_url = get_option( 'sqcdy_ics_import_feed_url' );

	if ( empty( $feed_url ) ) {
		return array(
			'success' => false,
			'errors'  => array( 'No feed URL configured' ),
		);
	}

	$options = array(
		'update_existing' => get_option( 'sqcdy_ics_import_update_existing', false ),
		'dry_run'         => true,
		'limit'           => 50, // Limit preview to 50 events
	);

	return SQCDY_Event_Importer::import_from_feed( $feed_url, $options );
}

/**
 * Run import
 */
function sqcdy_ics_import_run() {
	$feed_url = get_option( 'sqcdy_ics_import_feed_url' );

	if ( empty( $feed_url ) ) {
		return array(
			'success' => false,
			'errors'  => array( 'No feed URL configured' ),
		);
	}

	$options = array(
		'update_existing' => get_option( 'sqcdy_ics_import_update_existing', false ),
		'dry_run'         => false,
	);

	return SQCDY_Event_Importer::import_from_feed( $feed_url, $options );
}

/**
 * Test ICS feed for debugging
 */
function sqcdy_ics_import_test_feed() {
	$feed_url = get_option( 'sqcdy_ics_import_feed_url' );

	if ( empty( $feed_url ) ) {
		return array(
			'error' => 'No feed URL configured',
		);
	}

	$results = array(
		'feed_url' => $feed_url,
	);

	// Test the raw feed fetch
	$response = wp_remote_get( $feed_url, array( 'timeout' => 30 ) );

	if ( is_wp_error( $response ) ) {
		$results['fetch_error'] = $response->get_error_message();
		return $results;
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	$results['http_status'] = $response_code;

	if ( 200 !== $response_code ) {
		$results['error'] = 'HTTP error ' . $response_code;
		return $results;
	}

	$body = wp_remote_retrieve_body( $response );
	$results['content_length'] = strlen( $body );

	if ( empty( $body ) ) {
		$results['error'] = 'Empty response body';
		return $results;
	}

	// Count VEVENT blocks
	$vevent_count = substr_count( $body, 'BEGIN:VEVENT' );
	$results['vevent_blocks_found'] = $vevent_count;

	// Test parsing
	$events = SQCDY_ICS_Parser::parse_ics_content( $body );
	$results['events_parsed'] = is_array( $events ) ? count( $events ) : 'Parse failed';

	// Show first few lines of content
	$lines = explode( "\n", $body );
	$results['first_5_lines'] = implode( ' | ', array_slice( $lines, 0, 5 ) );

	return $results;
}

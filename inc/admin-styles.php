<?php

/**
 * Plugin stylesheet enqueue
 */
add_action( 'admin_enqueue_scripts', 'sqcdy_ics_import_admin_styles' );

function sqcdy_ics_import_admin_styles( $hook ) {
	// Only load on our admin page
	if ( 'event_page_sqcdy-ics-import' !== $hook ) {
		return;
	}

	// Inline CSS for admin page
	wp_add_inline_style(
		'wp-admin',
		'
        .sqcdy-ics-import-page .form-table th {
            width: 200px;
        }

        .sqcdy-ics-import-page .postbox .inside ul {
            list-style-type: disc;
            margin-left: 20px;
        }

        .sqcdy-ics-import-page .postbox .inside ul li {
            margin-bottom: 5px;
        }

        .sqcdy-ics-import-page .import-actions {
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            padding: 15px;
            margin: 10px 0;
        }

        .sqcdy-ics-import-page .import-results {
            margin: 20px 0;
        }

        .sqcdy-ics-import-page .import-results h4 {
            margin-top: 15px;
            margin-bottom: 5px;
        }

        .sqcdy-ics-import-page .import-results ul {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            padding: 10px;
            margin: 5px 0;
        }
    '
	);
}

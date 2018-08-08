<?php
/**
 * Our actions functions.
 *
 * @package DebugQuickLook
 */

// Call our namepsace.
namespace DebugQuickLook\Actions;

// Set our alias items.
use DebugQuickLook as Core;
use DebugQuickLook\Helpers as Helpers;
use DebugQuickLook\Parser as Parser;
use DebugQuickLook\Formatting as Formatting;

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\run_quicklook_action' );
add_filter( 'removable_query_args', __NAMESPACE__ . '\add_removable_arg' );
add_action( 'admin_notices', __NAMESPACE__ . '\display_purge_result' );

/**
 * Run the quicklook action if we've requested it.
 *
 * @return void
 */
function run_quicklook_action() {

	// Bail if current user doesnt have cap.
	if ( ! current_user_can( 'manage_options' ) ) {
		return; // @@todo convert to filtered.
	}

	// Bail without the query strings or not on admin.
	if ( ! is_admin() || empty( $_GET['quicklook'] ) || empty( $_GET['debug'] ) ) {
		return;
	}

	// Check to see if our nonce was provided.
	if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'debug_quicklook_' . sanitize_text_field( $_GET['debug'] ) . '_action' ) ) {
		return;
	}

	// Switch through and return the item.
	switch ( sanitize_text_field( $_GET['debug'] ) ) {

		case 'view' :
			Parser\run_parse();
			break;

		case 'purge' :
			Helpers\purge_log_file();
			break;

		// End all case breaks.
	}

	// @@todo see if another action is needed.

	// And just be finished.
	return;
}

/**
 * Add our custom strings to the vars.
 *
 * @param array $args  The existing array of args.
 */
function add_removable_arg( $args ) {

    // Include my new arg.
	array_push( $args, 'quicklook' );

	// And return the args.
	return $args;
}

/**
 * Echo out the notification.
 *
 * @return HTML
 */
function display_purge_result() {

	// Bail without the query strings or not on admin.
	if ( ! is_admin() || empty( $_GET['quicklook'] ) || empty( $_GET['quickpurge'] ) ) {
		return;
	}

	// Show the message.
	echo '<div class="notice notice-success is-dismissible">';
		echo '<p>' . esc_html__( 'Success! Your debug file has been purged.', 'debug-quick-look' ) . '</p>';
	echo '</div>';
}

<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package DebugQuickLook
 */

// Call our namepsace.
namespace DebugQuickLook\Helpers;

// Set our alias items.
use DebugQuickLook as Core;

/**
 * Build and return the data for our admin nodes.
 *
 * @return array
 */
function get_admin_bar_nodes() {

	// Set the view args.
	$view_args  = array(
		'id'        => 'quick-look-view',
		'title'     => __( 'View Log', 'debug-quick-look' ),
		'href'      => esc_url( build_quicklook_url( 'view' ) ),
		'position'  => 0,
		'parent'    => 'debug-quick-look',
		'meta'      => array(
			'title'     => __( 'View Log', 'debug-quick-look' ),
			'target'    => '_blank',
		),
	);

	// Set the purge args.
	$purge_args = array(
		'id'        => 'quick-look-purge',
		'title'     => __( 'Purge Log', 'debug-quick-look' ),
		'href'      => esc_url( build_quicklook_url( 'purge' ) ),
		'position'  => 0,
		'parent'    => 'debug-quick-look',
		'meta'      => array(
			'title'     => __( 'Purge Log', 'debug-quick-look' ),
		),
	);

	// Return the array of data.
	return array( 'view' => $view_args, 'purge' => $purge_args );
}

/**
 * Build and return the single URL for a quick action.
 *
 * @param  string $action  The action being added.
 *
 * @return string
 */
function build_quicklook_url( $action = '' ) {

	// Set my nonce name and key.
	$nonce  = 'debug_quicklook_' . sanitize_text_field( $action ) . '_action';

	// Set up my args.
	$setup  = array(
		'quicklook' => 1,
		'debug'     => sanitize_text_field( $action ),
		'nonce'     => wp_create_nonce( $nonce ),
	);

	// And return the URL.
	return add_query_arg( $setup, admin_url( '/' ) );
}

/**
 * Run a quick check to see if the debug log file is empty.
 *
 * @return boolean
 */
function check_file_data() {

	// If no file exists at all, create an empty one.
	if ( false === file_exists( Core\DEBUG_FILE ) ) {
		file_put_contents( Core\DEBUG_FILE, '' );
	}

	// If the file is empty, return that.
	return 0 === filesize( Core\DEBUG_FILE ) ? false : true;
}

/**
 * Purge the existing log file.
 *
 * @return boolean
 */
function purge_log_file() {

	// Purge the data file.
	file_put_contents( Core\DEBUG_FILE, '' );

	// And redirect with a query string.
	$direct = add_query_arg( array( 'quicklook' => 1, 'quickpurge' => 1 ), admin_url( '/' ) );

	// Then redirect.
	wp_redirect( $direct );
	exit;
}

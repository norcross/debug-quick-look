<?php
/**
 * Handle the admin bar setup.
 *
 * @package DebugQuickLook
 */

// Call our namepsace.
namespace DebugQuickLook\AdminBar;

// Set our alias items.
use DebugQuickLook as Core;
use DebugQuickLook\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'wp_head', __NAMESPACE__ . '\add_warning_css' );
add_action( 'admin_head', __NAMESPACE__ . '\add_warning_css' );
add_action( 'admin_bar_menu', __NAMESPACE__ . '\admin_bar_links', 9999 );

/**
 * Add the CSS to flag our warning message.
 */
function add_warning_css() {

	// Bail if current user doesnt have cap or the constant is set.
	if ( ! current_user_can( 'manage_options' ) ) {
		return; // @@todo convert to filtered.
	}

	// Open the style tag.
	echo '<style>';

	// Output the actual CSS item.
	echo 'li#wp-admin-bar-debug-quick-look li.debug-quick-look-missing .ab-item span {';
		echo 'color: #ff0000;';
		echo 'font-weight: bold;';
		echo 'font-family: Consolas, Monaco, monospace;';
	echo '}';

	// Close the style tag.
	echo '</style>';
}

/**
 * Add the links for the debug log file.
 *
 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
 *
 * @return void.
 */
function admin_bar_links( $wp_admin_bar ) {

	// Bail if current user doesnt have cap.
	if ( ! current_user_can( 'manage_options' ) ) {
		return; // @@todo convert to filtered.
	}

	// Fetch my nodes.
	$nodes  = Helpers\get_admin_bar_nodes();

	// Bail without nodes.
	if ( ! $nodes ) {
		return;
	}

	// Add a parent item.
	$wp_admin_bar->add_node(
		array(
			'id'    => 'debug-quick-look',
			'title' => __( 'Debug Quick Look', 'debug-quick-look' ),
		)
	);

	// Loop my node data.
	foreach ( $nodes as $node_name => $node_data ) {
		$wp_admin_bar->add_node( $node_data );
	}

	// And be done.
	return;
}

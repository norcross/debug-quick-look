<?php
/**
 * Our custom wp_die handler.
 *
 * @package DebugQuickLook
 */

// Call our namepsace.
namespace DebugQuickLook\Handler;

// Set our alias items.
use DebugQuickLook as Core;
use DebugQuickLook\Helpers as Helpers;
use DebugQuickLook\Formatting as Formatting;

/**
 * Output the custom wp_die display we built.
 *
 * @param  mixed  $message  The data to display
 * @param  string $title    Our file title.
 * @param  array  $args     Any additional args passed.
 *
 * @return void
 */
function build_handler( $message, $title = '', $args = array() ) {

	// Set an empty.
	$build  = '';

	// Set the doctype.
	$build .= '<!DOCTYPE html>';

	// Set the opening HTML tag.
	$build .= '<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">';

	// Output the head tag.
	$build .= handler_head_tag( $title );

	// Output the body.
	$build .= handler_body_tag( $message, $args );

	// Close out the final HTML tag.
	$build .= '</html>';

	// Echo out the display.
	echo $build;

	// Then a regular die() to finish.
	die();
}

/**
 * Set up the <head> tag.
 *
 * @param  string  $title  The title to output.
 * @param  boolean $echo   Whether to echo or return.
 *
 * @return mixed
 */
function handler_head_tag( $title = '', $echo = false ) {

	// Determine the page title.
	$title  = ! empty( $title ) ? sanitize_text_field( $title ) : __( 'View Your File', 'debug-quick-look' );

	// Set an empty.
	$build  = '';

	// Set the opening head tag.
	$build .= '<head>' . "\n";

		// Include the basic meta tags.
		$build .= '<meta charset="utf-8">' . "\n";
		$build .= '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' . "\n";
		$build .= '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>' . "\n";

		// Check the 'no robots', but output ourselves since the function only echos.
		if ( function_exists( 'wp_no_robots' ) ) {
			$build .= '<meta name="robots" content="noindex,follow" />' . "\n";
		}

		// Load our CSS.
		$build .= load_handler_css();

		// Output the title tag.
		$build .= '<title>' . esc_html( $title ) . '</title>' . "\n";

	// Close out the head tag.
	$build .= '</head>';

	// Echo if requested.
	if ( $echo ) {
		echo $build;
	}

	// Just return the build.
	return $build;
}

/**
 * Set up the <body> tag.
 *
 * @param  string  $message  The total message output.
 * @param  array   $args     The optional args that were passed.
 * @param  boolean $echo     Whether to echo or return.
 *
 * @return mixed
 */
function handler_body_tag( $message = '', $args = array(), $echo = false ) {

	// Set an empty.
	$build  = '';

	// Set the opening body tag.
	$build .= '<body class="debug-quick-look">' . "\n";

		// Set the intro
		$build .= load_handler_intro( $args );

		// Output the message.
		$build .= load_handler_message( $message );

	// Close out the body tag.
	$build .= '</body>';

	// Echo if requested.
	if ( $echo ) {
		echo $build;
	}

	// Just return the build.
	return $build;
}

/**
 * Load our CSS to display.
 *
 * @return mixed
 */
function load_handler_css() {

	// Set my stylesheet URL.
	$stylesheet = Core\ASSETS_URL . '/css/debug-quick-look.css';

	// If we haven't already run the admin_head function, output the file.
	if ( ! did_action( 'admin_head' ) ) {

		// Set my stylesheet URL.
		$stylesheet = add_query_arg( array( 'ver' => time() ), $stylesheet );

		// And just return
		return '<link href="' . esc_url( $stylesheet ) . '" rel="stylesheet" type="text/css">' . "\n";
	}

	// Get my raw CSS.
	$style  = @file_get_contents( $stylesheet );

	// Wrap it in a style tag and return it.
	return '<style type="text/css">' . $style . '</style>' . "\n";
}

/**
 * Load our introduction to display.
 *
 * @param  array $args  The optional args that were passed.
 *
 * @return mixed
 */
function load_handler_intro( $args = array() ) {

	// Bail if we said to skip the intro.
	if ( ! empty( $args['skip-intro'] ) ) {
		return;
	}

	// Set the totals variable.
	$ttlnum = ! empty( $args['totals'] ) ? $args['totals'] : 0;

	// Set the totals display string.
	$totals = sprintf( __( 'Total log entries: %s', 'debug-quick-look' ), '<strong>' . absint( $ttlnum ) . '</strong>' );

	// Set an empty.
	$build  = '';

	// Set the opening div.
	$build .= '<div class="debug-quick-look-intro">' . "\n";

		// Output the paragraph wrapper.
		$build .= '<p>';

			// Handle the link output.
			$build .= '<a href="' . admin_url( '/' ) . '">&laquo; ' . esc_html__( 'Return To Admin Dashboard', 'debug-quick-look' ) . '</a>';

			// Output the entry count.
			$build .= '<span class="debug-quick-look-intro-entry-count">' . $totals . '</span>';

		// Close the paragraph
		$build .= '</p>' . "\n";

	// Close out the div tag.
	$build .= '</div>' . "\n";

	// Just return the build.
	return $build;
}

/**
 * Load our message to display.
 *
 * @param  string  $message  The total message output.
 *
 * @return mixed
 */
function load_handler_message( $message ) {

	// Set an empty.
	$build  = '';

	// Set the opening div.
	$build .= '<div class="debug-quick-look-block-list">' . "\n";

		// Output the actual link.
		$build .= $message . "\n";

	// Close out the div tag.
	$build .= '</div>' . "\n";

	// Just return the build.
	return $build;
}

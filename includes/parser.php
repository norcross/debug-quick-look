<?php
/**
 * Our various parser functions.
 *
 * @package DebugQuickLook
 */

// Call our namepsace.
namespace DebugQuickLook\Parser;

// Set our alias items.
use DebugQuickLook as Core;
use DebugQuickLook\Helpers as Helpers;
use DebugQuickLook\Formatting as Formatting;

/**
 * Kick off our parsing action.
 *
 * @return void
 */
function run_parse() {

	// Add the new die handler.
	add_filter( 'wp_die_handler', __NAMESPACE__ . '\die_handler' );

	// Parse it.
	$parsed = parse_debug_log( Core\DEBUG_FILE );

	// And show the world.
	wp_die( $parsed['display'], __( 'View Your File', 'debug-quick-look' ), array( 'totals' => absint( $parsed['totals'] ) ) );
}

/**
 * Handle parsing our logfile.
 *
 * @param  string $logfile  Which log file we want to debug.
 * @param  string $order    What order to display the entries.
 *
 * @return mixed
 */
function parse_debug_log( $logfile = '', $order = 'desc' ) {

	// Fetch the full lines.
	$lines  = file( $logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	//preprint( $lines, true );

	// Run a quick right trim on each line.
	$lines  = array_map( 'rtrim', $lines );

	// Set our empty.
	$setup  = array();

	// Set a marker for the proper lines.
	$index  = 0;

	// Loop the lines.
	foreach ( $lines as $nm => $linestring ) {

		// Get our first character, which we test with.
		$first  = substr( $linestring, 0, 1 );

		// Starting with the date bracket.
		if ( '[' === esc_attr( $first ) ) {

			// Set the line index, in case we need to append it.
			$index = absint( $nm );

			// Set the line as a new array element.
			$setup[ $nm ] = $linestring;
		}

		// Now handle the non-bracket lines.
		if ( '[' !== esc_attr( $first ) ) {

			// Get our current line data.
			$start  = $setup[ $index ];

			// Merge our current string with whatever we already had.
			$merge  = $start . PHP_EOL . $linestring;

			// Add it to the merged string.
			$setup[ $index ] = $merge;
		}

		// Should be done here.
	}

	// Reset the array keys.
	$setup  = array_values( $setup );

	// Wrap the divs.
	$setup  = Formatting\format_parsed_lines( $setup );

	// If we wanted descending, swap.
	if ( 'desc' === sanitize_text_field( $order ) ) {
		$setup  = array_reverse( $setup );
	}

	// Dump it.
	//preprint( $setup, true );

	// Return an array of the markup and count.
	return array(
		'totals' => count( $setup ),
		'display' => implode( "\n", $setup ),
	);
}

/**
 * Return our custom wp_die handler.
 *
 * @param  string $die_handler  The current handler.
 *
 * @return string
 */
function die_handler( $die_handler ) {
	return '\DebugQuickLook\Handler\build_handler';
}

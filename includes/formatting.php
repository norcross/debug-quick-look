<?php
/**
 * Our various formatting functions.
 *
 * @package DebugQuickLook
 */

// Call our namepsace.
namespace DebugQuickLook\Formatting;

// Set our alias items.
use DebugQuickLook as Core;
use DebugQuickLook\Helpers as Helpers;

/**
 * Format each line of our log data array.
 *
 * @param  array $lines  The log file lines.
 *
 * @return array
 */
function format_parsed_lines( $lines ) {
	return array_map( __NAMESPACE__ . '\format_single_line', $lines );
}

/**
 * Format the single parsed line.
 *
 * @param  string $single  The single line.
 *
 * @return string          The formatted line.
 */
function format_single_line( $single ) {

	// Set our block class before we start manupulating.
	$class  = set_parse_block_class( $single );

	// Parse my dateblock.
	$single = wrap_dateblock( $single );

	// Check for the stack trace.
	$single = wrap_stacktrace( $single );

	// And the warning types.
	$single = wrap_warning_types( $single );

	// Format any JSON we may have.
	$single = wrap_json_bits( $single );

	// Now set our display.
	$build  = '';

	// Set the div wrapper.
	$build .= '<div class="' . esc_attr( $class ) . '">';

		// Add a second div wrapper to mimic the <pre> tag stuff.
		$build .= '<div class="log-entry-block-pre-wrap">';

			// Output to handle the text remaining.
			$build .= wpautop( $single, false ) . "\n";

		// Close the div wrapper.
		$build .= '</div>';

	// Close the div wrapper.
	$build .= '</div>';

	// Now return the whole thing.
	return $build;
}

/**
 * Create a class for log file block.
 *
 * @param  string $single  The single line from the log file.
 *
 * @return string $class   The resulting class.
 */
function set_parse_block_class( $single ) {

	// Set the notice types we want.
	$types  = array(
		'notice'       => 'PHP Notice:',
		'warning'      => 'PHP Warning:',
		'fatal'        => 'PHP Fatal error:',
		'wordpress-db' => 'WordPress database error',
		'stack-trace'  => 'Stack trace:',
		'wp-community' => 'WP_Community_Events',
	);

	// Set our default class.
	$data[] = 'log-entry-block';

	// Now loop them and check each one.
	foreach ( $types as $key => $text ) {

		// Bail if we don't have it.
		if ( strpos( $single, $text ) === false ) {
			continue;
		}

		// Add the key to our class data array.
		$data[] = 'log-entry-block-' . esc_attr( $key );
	}

	// @@todo add a filter here.

	// Make sure each one is sanitized.
	$setup  = array_map( 'sanitize_html_class', $data );

	// Return the whole thing.
	return implode( ' ', $setup );
}

/**
 * Set up the date block.
 *
 * @param  string $single  The single line.
 *
 * @return string          The formatted line.
 */
function wrap_dateblock( $single ) {

	// Set up our formatting rules.
	$format = "~
		\[(              # open outer square brackets and capturing group
		(?:              # open subpattern for optional inner square brackets
		    [^[\]]*      # non-square-bracket characters
		    \[           # open inner square bracket
		    [^[\]]*      # non-square-bracket characters
		    ]            # close inner square bracket
		)*               # end subpattern and repeat it 0 or more times
		[^[\]]*          # non-square-bracket characters
		)]               # end capturing group and outer square brackets
		(?:              # open subpattern for optional parentheses
		    \((          # open parentheses and capturing group
		    [a-z]+       # letters
		    )\)          # close capturing group and parentheses
		)?               # end subpattern and make it optional
		~isx";

	// Run the big match for the date bracket.
	preg_match( $format, $single, $matches );

	// If we don't have the dateblock, return what we had.
	if ( empty( $matches[0] ) ) {
		return $single;
	}

	// Format our date.
	$fdate  = date( 'c', strtotime( $matches[1] ) );

	// Set the markup.
	$markup = '<span class="log-entry-date"><time datetime="' . esc_attr( $fdate ) . '">' . $matches[0] . '</time></span>';

	// @@todo add a filter here.

	// Wrap the dateblock itself in a time.
	return str_replace( $matches[0], $markup, $single );
}

/**
 * Set up the stack trace list.
 *
 * @param  string $single  The single line.
 *
 * @return string          The formatted line.
 */
function wrap_stacktrace( $single ) {

	// Set up our formatting rules.
	$format = "/Stack trace:\n((?:#\d*.+\n*)+)/m";

	// Run the match check.
	preg_match( $format, $single, $matches );

	// If we don't have the list, return what we had.
	if ( empty( $matches[1] ) ) {
		return $single;
	}

	// Create an array of the stack data.
	$array  = explode( PHP_EOL, $matches[1] );

	// Wrap each one with a list tag.
	$mapped = array_map( __NAMESPACE__ . '\wrap_stack_list', $array );

	// Now pull it back in.
	$ltags  = implode( '', $mapped );

	// Wrap the list with the ul tag.
	$ulwrap = '<ul class="log-entry-stack-trace-list-wrap">' . $ltags . '</ul>';

	// Now merge in the list.
	$merged = str_replace( $matches[1], $ulwrap, $single );

	// Set my title.
	$twrap  = '<p class="log-entry-stack-trace-title">Stack trace:</p>';

	// @@todo add a filter here.

	// And return it, wrapping the word in a paragraph.
	return str_replace( 'Stack trace:', $twrap, $merged );
}

/**
 * Wrap any warning types with markup.
 *
 * @param  string $single  The single line from the log file.
 *
 * @return string $single  The formatted line from the log file.
 */
function wrap_warning_types( $single ) {

	// Set the notice types we want.
	$types  = array(
		'notice'       => 'PHP Notice:  ',
		'warning'      => 'PHP Warning:  ',
		'fatal'        => 'PHP Fatal error:  ',
		'wordpress-db' => 'WordPress database error ',
		'wp-community' => 'WP_Community_Events::maybe_log_events_response: ',
	);

	// @@todo add a filter here.

	// Now loop them and check each one.
	foreach ( $types as $key => $text ) {

		// Bail if we don't have it.
		if ( strpos( $single, $text ) === false ) {
			continue;
		}

		// Set the notice class.
		$nclass = 'log-entry-error-label log-entry-error-' . esc_attr( $key ) . '-label';

		// Set up the wrapped item.
		$markup = '<span class="' . esc_attr( $nclass ) . '">' . esc_html( rtrim( $text, ': ' ) ) . '</span>' . PHP_EOL;

		// Now wrap it in some markup.
		$single = str_replace( $text, $markup, $single );
	}

	// @@todo add a filter here.

	// And return the trimmed single.
	return $single;
}

/**
 * Wrap any JSON that may exist.
 *
 * @param  string $single  The single line from the log file.
 *
 * @return string $single  The formatted line from the log file.
 */
function wrap_json_bits( $single ) {

	// Set my format for finding JSON.
	$format = '/\{(?:[^{}]|(?R))*\}/x';

	// Attempt the preg_match.
	preg_match_all( $format, $single, $matches );

	// Bail if we have none.
	if ( empty( $matches[0] ) ) {
		return $single;
	}

	// Loop each bit of JSON and attempt to format it.
	foreach ( $matches[0] as $found_json ) {

		// Now attempt to decode it.
		$maybe_json = json_decode( $found_json, true );

		// If we threw an error, return the single line.
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			continue;
		}

		// Get my markup and wrap it in a div.
		$markup = '<div class="log-entry-json-array-section">' . format_json_array( $maybe_json ) . '</div>';

		// Now wrap it in some markup.
		$single = str_replace( $found_json, $markup, $single );
	}

	// @@todo add a filter here.

	// And return the trimmed single.
	return $single;
}

/**************************************
  Set the various callback formatting.
***************************************/

/**
 * Wrap any stack trace list with list markup.
 *
 * @param  string $single  The single line from the log file.
 *
 * @return string $single  The formatted line from the log file.
 */
function wrap_stack_list( $single ) {

	// @@todo add a filter here.

	// And return the trimmed single.
	return '<li class="log-entry-stack-trace-list-item">' . trim( $single ) . '</li>';
}

/**
 * Take the JSON array and make it fancy.
 *
 * @param  array $maybe_json  The array parsed from the JSON.
 *
 * @return HTML
 */
function format_json_array( $maybe_json ) {

	// Set my empty build.
	$build  = '';

	// Wrap the whole thing in a list.
	$build .= '<ul class="log-entry-json-array-wrap">';

	// Loop my array and start checking.
	foreach ( $maybe_json as $key => $value ) {

		// Open it as a list.
		$build .= '<li class="log-entry-json-array-item">';

			// Set the key as our first label.
			$build .= '<span class="log-entry-json-array-piece">' . esc_html( $key ) . '</span>';

			// Add our splitter.
			$build .= '<span class="log-entry-json-array-piece log-entry-json-array-splitter">&nbsp;&equals;&gt;&nbsp;</span>';

			// If the value isn't an array, make a basic list item.
			if ( ! is_array( $value ) ) {

				// Make boolean a string for display.
				$value = is_bool( $value ) ? var_export( $value, true ) : $value;

				// Just wrap the piece as per usual.
				$build .= '<span class="log-entry-json-array-piece">' . esc_html( $value ) . '</span>';

			} else {

				// Set the "array" holder text.
				$build .= '<span class="log-entry-json-array-piece"><em>' . esc_html__( '(array)', 'debug-quick-look' ) . '</em></span>';

				// And get recursive with it.
				$build .= format_json_array( $value );
			}

		// Close the item inside.
		$build .= '</li>';
	}

	// Close my list.
	$build .= '</ul>';

	// Return the entire thing.
	return $build;
}

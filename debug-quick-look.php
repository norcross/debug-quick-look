<?php
/**
 * Plugin Name: Debug Quick Look
 * Plugin URI: https://github.com/norcross/debug-quick-look
 * Description: Creates an admin bar link to view or purge the debug log file
 * Author: Andrew Norcross
 * Author URI: http://andrewnorcross.com/
 * Version: 0.0.1
 * Text Domain: debug-quick-look
 * Requires WP: 4.4
 * Domain Path: languages
 * GitHub Plugin URI: https://github.com/norcross/debug-quick-look
 * @package debug-quick-look
 */

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Andrew Norcross
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Call our class.
 */
class DebugQuickLook {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_head',                      array( $this, 'add_warning_css'     )           );
		add_action( 'admin_head',                   array( $this, 'add_warning_css'     )           );
		add_action( 'admin_init',                   array( $this, 'process_debug_type'  )           );
		add_action( 'admin_bar_menu',               array( $this, 'admin_bar_links'     ),  9999    );
	}

	/**
	 * Add the CSS to flag our warning message.
	 */
	public function add_warning_css() {

		// Bail if current user doesnt have cap or the constant is set.
		if ( ! current_user_can( 'manage_options' ) || false !== $constant = $this->check_debug_constant() ) {
			return;
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
	 * Run a quick check to see if the debug log constant is set.
	 *
	 * @return boolean
	 */
	public function check_debug_constant() {
		return defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? true : false;
	}

	/**
	 * Run a quick check to see if the debug log file is empty.
	 *
	 * @return boolean
	 */
	public function check_file_data() {

		// If the constant isn't set, return false right away.
		if ( false === $constant = $this->check_debug_constant() ) {
			return false;
		}

		// Set my path file.
		$pfile  = WP_CONTENT_DIR . '/debug.log';

		// If no file exists at all, create an empty one.
		if ( false === file_exists( $pfile ) ) {
			file_put_contents( $pfile, '' );
		}

		// If the file is empty, return that.
		return 0 === filesize( $pfile ) ? false : true;
	}

	/**
	 * Handle our debug file actions based on query strings.
	 *
	 * @return HTML
	 */
	public function process_debug_type() {

		// Bail if current user doesnt have cap.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Bail without the query strings or not on admin.
		if ( ! is_admin() || empty( $_GET['quicklook'] ) || empty( $_GET['quickaction'] ) ) {
			return;
		}

		// Bail if we didn't pass the correct action type.
		if ( ! in_array( sanitize_key( $_GET['quickaction'] ), array( 'view', 'purge' ) ) ) {
			return;
		}

		// Create some basic CSS.
		$style  = '
		p.returnlink { text-align: center; font-size: 14px; line-height: 22px; }
		p.nofile { text-align: center; font-size: 14px; line-height: 22px; font-style: italic; }
		p.codeblock { background-color: #fff; color: #000; font-size: 14px; line-height: 22px; padding: 5px 15px; }
		p.codeblock br { height: 5px; display: block; margin: 0; padding: 0; }
		p strong { font-weight: bold; }	p em { font-style: italic; }
		code, pre { white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word; }
		code pre, span.prewrap { color: #ff0000; }
		';

		// Filter it.
		$style  = apply_filters( 'debug_quick_look_css', $style );

		// Set my empty.
		$build  = '';

		// Include a "back to admin" link.
		$build .= '<p class="returnlink"><a href="' . admin_url( '/' ) . '">' . esc_html__( 'Return To Admin Dashboard', 'debug-quick-look' ) . '</a></p>';

		// Check to make sure we have a file.
		if ( false === $exists = $this->check_file_data() ) {
			$build .= '<p class="nofile">' . esc_html__( 'Your debug file is empty.', 'debug-quick-look' ) . '</p>';
		}

		// We have a file. So start the additional checks.
		if ( false !== $exists = $this->check_file_data() ) {

			// We requested a viewing.
			if ( 'view' === sanitize_key( $_GET['quickaction'] ) ) {

				// Parse out the data.
				$data   = file_get_contents( WP_CONTENT_DIR . '/debug.log' );

				// Trim and break it up.
				$data   = nl2br( trim( $data ) );

				// Convert my line breaks.
				$data   = str_replace( array( '<pre>', '</pre>' ), array( '<span class="prewrap">', '</span>' ), $data );

				// Generate the actual output.
				$build .= '<p class="codeblock"><code>' . $data . '</code></p>';
			}

			// We requested a purging.
			if ( 'purge' === sanitize_key( $_GET['quickaction'] ) ) {

				// Clear out the data.
				$purge  = file_put_contents( WP_CONTENT_DIR . '/debug.log', '' );

				// Show a message.
				$build .= '<p class="nofile">' . esc_html__( 'The log file has been purged.', 'debug-quick-look' ) . '</p>';
			}
		}

		// If we have CSS values, echo them.
		if ( ! empty( $style ) ) {
			echo '<style>' . esc_attr( $style ) . '</style>';
		}

		// Echo out the build.
		echo wp_kses_post( $build );

		// And die.
		die();
	}

	/**
	 * Add the links for the debug log file.
	 *
	 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
	 *
	 * @return void.
	 */
	public function admin_bar_links( WP_Admin_Bar $wp_admin_bar ) {

		// Bail if current user doesnt have cap.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add a parent item.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'debug-quick-look',
				'title' => __( 'Debug Quick Look', 'debug-quick-look' ),
			)
		);

		// Load the two links if we have the logging constant defined.
		if ( false !== $constant = $this->check_debug_constant() ) {

			// Make my links.
			$view   = add_query_arg( array( 'quicklook' => 1, 'quickaction' => 'view' ), admin_url( '/' ) );
			$purge  = add_query_arg( array( 'quicklook' => 1, 'quickaction' => 'purge' ), admin_url( '/' ) );

			// Add the "view" link.
			$wp_admin_bar->add_node(
				array(
					'id'        => 'quick-look-view',
					'title'     => __( 'View Log', 'debug-quick-look' ),
					'href'      => esc_url( $view ),
					'position'  => 0,
					'parent'    => 'debug-quick-look',
					'meta'      => array(
						'title'     => __( 'View Log', 'debug-quick-look' ),
						'target'    => '_blank',
					),
				)
			);

			// Add the "purge" link.
			$wp_admin_bar->add_node(
				array(
					'id'        => 'quick-look-purge',
					'title'     => __( 'Purge Log', 'debug-quick-look' ),
					'href'      => esc_url( $purge ),
					'position'  => 0,
					'parent'    => 'debug-quick-look',
					'meta'      => array(
						'title'     => __( 'Purge Log', 'debug-quick-look' ),
						'target'    => '_blank',
					),
				)
			);
		}

		// Load a warning message if we haven't defined it.
		if ( false === $constant = $this->check_debug_constant() ) {

			// Add the text node with our warning.
			$wp_admin_bar->add_node(
				array(
					'id'        => 'quick-look-error',
					'title'     => __( 'The <span>WP_DEBUG_LOG</span> constant is not defined!', 'debug-quick-look' ),
					'position'  => 0,
					'parent'    => 'debug-quick-look',
					'meta'      => array(
						'class' => 'debug-quick-look-missing',
					),
				)
			);
		}
	}

	// End our class.
}

// Call our class.
$DebugQuickLook = new DebugQuickLook();
$DebugQuickLook->init();

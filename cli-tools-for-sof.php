<?php
/**
 * Plugin Name: Command Line Tools for Spirit of Football
 * Plugin URI: https://github.com/spiritoffootball/cli-tools-for-sof
 * GitHub Plugin URI: https://github.com/spiritoffootball/cli-tools-for-sof
 * Description: Manage aspects of the Spirit of Football website through the command line.
 * Author: Christian Wach
 * Version: 1.0.0
 * Author URI: https://haystack.co.uk
 *
 * @package Command_Line_Tools_For_SOF
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'COMMAND_LINE_SOF_VERSION', '1.0.0' );

// Store reference to this file.
if ( ! defined( 'COMMAND_LINE_SOF_FILE' ) ) {
	define( 'COMMAND_LINE_SOF_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'COMMAND_LINE_SOF_URL' ) ) {
	define( 'COMMAND_LINE_SOF_URL', plugin_dir_url( COMMAND_LINE_SOF_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'COMMAND_LINE_SOF_PATH' ) ) {
	define( 'COMMAND_LINE_SOF_PATH', plugin_dir_path( COMMAND_LINE_SOF_FILE ) );
}

/**
 * Command Line Tools for SOF Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 1.0.0
 */
class Command_Line_Tools_For_SOF {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load wp-cli tools.
		$this->include_files();

	}

	/**
	 * Loads the wp-cli tools.
	 *
	 * @since 1.0.0
	 */
	public function include_files() {

		// Bail if not wp-cli context.
		if ( ! defined( 'WP_CLI' ) ) {
			return;
		}

		// Bail if not PHP 5.6+.
		if ( ! version_compare( phpversion(), '5.6.0', '>=' ) ) {
			return;
		}

		// Load our wp-cli tools.
		require COMMAND_LINE_SOF_PATH . 'includes/wp-cli-sof.php';

	}

}

/**
 * Bootstrap plugin if not yet loaded and returns reference.
 *
 * @since 1.0.0
 *
 * @return Command_Line_Tools_for_SOF $plugin The plugin reference.
 */
function command_line_sof() {

	// Maybe bootstrap plugin.
	static $plugin;
	if ( ! isset( $plugin ) ) {
		$plugin = new Command_Line_Tools_For_SOF();
	}

	// Return reference.
	return $plugin;

}

// Bootstrap immediately.
command_line_sof();

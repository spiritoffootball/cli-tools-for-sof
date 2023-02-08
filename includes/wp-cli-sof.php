<?php
/**
 * WP-CLI tools for the Spirit of Football website.
 *
 * @package Command_Line_Tools_for_SOF
 */

// Bail if WP-CLI is not present.
if (!class_exists('WP_CLI')) {
  return;
}

// Set up commands.
WP_CLI::add_hook('before_wp_load', function() {

  // Include files.
  require_once __DIR__ . '/commands/command-base.php';
  require_once __DIR__ . '/commands/command-sof.php';
  require_once __DIR__ . '/commands/command-network.php';

  // ----------------------------------------------------------------------------
  // Add commands.
  // ----------------------------------------------------------------------------
  WP_CLI::add_command('sof', 'CLI_Tools_SOF_Command');
  WP_CLI::add_command('sof network', 'CLI_Tools_SOF_Command_Network');

});

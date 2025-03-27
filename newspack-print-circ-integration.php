<?php
/**
 * Plugin Name: Newspack Print Circulation Integration
 * Description: Plugin to integrate Newspack with Print Circulation Systems.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://newspack.com/
 * License: GPL2
 * Text Domain: newspack-print-circulation
 *
 * @package Newspack\PrintCirculation
 */

defined( 'ABSPATH' ) || exit;

define( 'NEWSPACK_PRINT_CIRC_INTEGRATION_VERSION', '1.0.0' );

// Define NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_FILE.
if ( ! defined( 'NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_FILE' ) ) {
	define( 'NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_FILE', __FILE__ );
}

// Define NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_DIR.
if ( ! defined( 'NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_DIR' ) ) {
	define( 'NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_DIR', dirname( plugin_basename( NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_FILE ) ) );
}

require_once 'vendor/autoload.php';

new Newspack\PrintCirculationIntegration\Initializer();

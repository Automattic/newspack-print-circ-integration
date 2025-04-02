<?php
/**
 * WP-CLI Command for Import Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration\CLI;

use WP_CLI;
use Newspack\PrintCirculationIntegration\Import as Import_Module;
use Newspack\PrintCirculationIntegration\Logger;

class Import {

	/**
	 * Trigger the import process manually.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newspack-print import-users --batch-size=50
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function import_users( $args, $assoc_args ) {
		$batch_size = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 20;

		WP_CLI::log( 'Starting the import process...' );

		// Initialize the import module.
		$import_module = new Import_Module();

		// Fetch the CSV file.
		$fetch_csv_status = $import_module->fetch_csv_file();

		if ( is_wp_error( $fetch_csv_status ) ) {
			WP_CLI::error( 'Error fetching CSV file: ' . $fetch_csv_status->get_error_message() );
			return;
		}

		WP_CLI::log( 'CSV file fetched successfully.' );

		// Run the import process.
		$import_result = $import_module->import_users( $batch_size );

		if ( is_wp_error( $import_result ) ) {
			WP_CLI::error( 'Error importing users: ' . $import_result->get_error_message() );
			return;
		}

		WP_CLI::success( 'Import process completed successfully.' );

		// Display logs.
		self::display_logs();
	}

	/**
	 * Display logs in the CLI terminal.
	 */
	private static function display_logs() {
		WP_CLI::log( 'Displaying the latest logs:' );

		$logs = Logger::get_logs();

		if ( empty( $logs ) ) {
			WP_CLI::log( 'No logs available.' );
			return;
		}

		foreach ( $logs as $log ) {
			WP_CLI::log( sprintf( '[%s] %s', $log['time'], $log['message'] ) );
		}
	}
}

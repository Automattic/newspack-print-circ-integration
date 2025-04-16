<?php
/**
 * WP-CLI Command for Import Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration\CLI;

use WP_CLI;
use Newspack\PrintCirculationIntegration\Import as Import_Module;
use Newspack\PrintCirculationIntegration\Newspack_Fields;
use Newspack\PrintCirculationIntegration\Logger;
use Newspack\PrintCirculationIntegration\Settings;

class Import {

	/**
	 * Trigger the import process manually.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * [--csv-path=<csv-path>]
	 * : The path to the CSV file. Default is the what is in Settings.
	 *
	 * [--dry-run]
	 * : If set, This will only test the mapping and output the processed rows. No changes will be made to the database.
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
		$is_dry_run = isset( $assoc_args['dry-run'] );
		$csv_path   = isset( $assoc_args['csv-path'] ) ? $assoc_args['csv-path'] : '';
		$csv_path   = ! empty( $csv_path ) ? $csv_path : Settings::get_setting( Settings::CSV_IMPORT_PATH_OPTION );

		WP_CLI::log( 'Starting the import process...' );

		// Initialize the import module.
		$import_module = new Import_Module();

		// Fetch the CSV file.
		$import_module->set_csv_path( $csv_path );

		WP_CLI::log( 'Starting Processing the users...' );

		$job_id = 'CLI-import-' . gmdate( 'Y-m-d-H:i:s' );
		WP_CLI::log( 'Job ID: ' . $job_id );
		Logger::set_job_id( $job_id );

		// Initialize flags and variables.
		$offset          = 0;
		$count           = 0;
		$total_processed = 0;

		if ( $is_dry_run ) {
			$result = $import_module->test_import_users();
			$fields = array_keys( $result[0] );
			WP_CLI\Utils\format_items(
				'table',
				$result,
				$fields
			);
			WP_CLI::log( 'Dry run completed. No changes made to the database.' );
			return;
		}

		while ( true ) {
			WP_CLI::log( sprintf( 'Processing batch with offset %d and batch size %d...', $offset, $batch_size ) );

			$result = $import_module->import_users( $batch_size, $offset );

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
				break;
			}

			if ( $result === true ) {
				$total_processed += $batch_size;
				$offset += $batch_size;
			} else {
				WP_CLI::log( 'No more users to process. Stopping...' );
				break;
			}

			$count++;
		}

		Logger::add_log( sprintf( 'Processed a total of %d batches of users via CLI.', $count ) );
	}
}

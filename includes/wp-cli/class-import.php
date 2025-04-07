<?php
/**
 * WP-CLI Command for Import Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration\CLI;

use WP_CLI;
use Newspack\PrintCirculationIntegration\Import as Import_Module;
use Newspack\PrintCirculationIntegration\Import_Parser;
use Newspack\PrintCirculationIntegration\Newspack_Fields;
use Newspack\PrintCirculationIntegration\Logger;
use WP;

class Import {

	/**
	 * Trigger the import process manually.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * * [--csv-path=<csv-path>]
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
		$offset        = 0;
		$count         = 0;
		$skipped_users = [];

		if ( $is_dry_run ) {
			$fields = array_keys( Newspack_Fields::get_fields() );
			$result = $import_module->test_import_users();
			WP_CLI\Utils\format_items(
				'table',
				$result,
				$fields
			);
		} else {
			$import_module->import_users( $batch_size );
		}

		// WP_CLI::success( sprintf( 'Processed a total of %d batches of users.', $count ) );

		// // Log the skipped users.
		// if ( ! empty( $skipped_users ) ) {
		// WP_CLI::line( WP_CLI::colorize( '%Y-------Skipped users with missing data -------%n' ) );

		// foreach ( $skipped_users as $skipped_user ) {
		// WP_CLI::log( sprintf( 'Skipped user: %s', json_encode( $skipped_user ) ) );
		// }
		// }

		// Logger::add_log( sprintf( 'Processed a total of %d batches of users via CLI.', $count ) );
	}
}

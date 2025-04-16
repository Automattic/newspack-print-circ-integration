<?php
/**
 * WP-CLI Command for Export Process.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration\CLI;

use WP_CLI;
use Newspack\PrintCirculationIntegration\Export as Export_Module;
use Newspack\PrintCirculationIntegration\Logger;

/**
 * Handles CSV export operations.
 */
class Export {

	/**
	 * Export users to CSV.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * [--dry-run]
	 * : If set, this will only test the export and output the processed rows. No changes will be made to the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newspack-print export-users --batch-size=50
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function export_users( $args, $assoc_args ) {
		$is_dry_run = isset( $assoc_args['dry-run'] );
		if ( $is_dry_run ) {
			self::test_export_users( $args, $assoc_args );
			return;
		}

		$batch_size = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 20;

		$export_module = new Export_Module();

		$job_id = 'CLI-export-' . gmdate( 'Y-m-d-H:i:s' );
		WP_CLI::log( 'Job ID: ' . $job_id );
		Logger::set_job_id( $job_id );

		Logger::add_log( 'Starting the export process...' );

		$offset         = 0;
		$total_exported = 0;

		while ( true ) {
			WP_CLI::log( sprintf( 'Processing batch with offset %d and batch size %d...', $offset, $batch_size ) );

			$result = $export_module->export_users( $batch_size, $offset );

			if ( is_wp_error( $result ) ) {
				Logger::add_log( sprintf( 'Error exporting users: %s', $result->get_error_message() ) );
				break;
			}

			if ( false === $result ) {
				Logger::add_log( 'No more users to export.' );
				break;
			}

			$total_exported += $batch_size;
			$offset         += $batch_size;
		}

		Logger::add_log( sprintf( 'Exported a total of %d users via CLI.', $total_exported ) );
	}

	/**
	 * Test export users.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<batch-size>]
	 * : The size of each batch. Default is 20.
	 *
	 * ## EXAMPLES
	 *
	 *     wp newspack-print test-export-users --batch-size=50
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function test_export_users( $args, $assoc_args ) {
		$batch_size = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 20;

		$export_module = new Export_Module();

		$job_id = 'CLI-test-export-' . gmdate( 'Y-m-d-H:i:s' );
		WP_CLI::log( 'Job ID: ' . $job_id );
		Logger::set_job_id( $job_id );

		Logger::add_log( 'Starting the test export process...' );

		$offset         = 0;
		$total_exported = 0;
		$exported_users = [];

		while ( true ) {
			WP_CLI::log( sprintf( 'Processing batch with offset %d and batch size %d...', $offset, $batch_size ) );

			$result = $export_module->test_export_users( $batch_size, $offset );

			if ( false === $result ) {
				Logger::add_log( 'No more users to export.' );
				break;
			}

			$exported_users[] = $result;
			$total_exported  += $batch_size;
			$offset          += $batch_size;
		}

		if ( empty( $exported_users ) ) {
			Logger::add_log( 'No users to export.' );
			return;
		}

		$exported_users = array_merge( ...$exported_users );
		$header         = array_keys( $exported_users[0] );

		WP_CLI\Utils\format_items(
			'table',
			$exported_users,
			$header
		);
		Logger::add_log( 'Test export completed. No changes made to the database.' );

		Logger::add_log( sprintf( 'Test exported a total of %d users via CLI.', $total_exported ) );
	}
}

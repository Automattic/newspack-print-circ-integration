<?php
/**
 * Newspack Print Integration Exporter.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use WP_Error;

/**
 * Exporter class to handle the CSV export.
 */
class Export {

	/**
	 * CSV file handle.
	 *
	 * @var string
	 */
	private $csv_handle = 'newspack-print-circ-export.csv';

	/**
	 * CSV file path.
	 *
	 * @var string
	 */
	private $csv_path = '';

	/**
	 * Export users to CSV.
	 *
	 * @param int $batch_size Batch size.
	 * @param int $offset Offset.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function export_users( $batch_size = 20, $offset = 0 ) {
		// Initialize or get CSV file.
		if ( empty( $this->csv_path ) ) {
			$this->csv_path = WP_CONTENT_DIR . '/uploads/' . $this->csv_handle;
		}

		// Get users to export.
		$users = get_users( [
			'number'   => $batch_size,
			'offset'   => $offset,
			'meta_key' => Newspack_Fields::get_field( Newspack_Fields::CIRCULATION_ID_FIELD )['db_field'],
		] );

		if ( empty( $users ) ) {
			return false;
		}

		// Create CSV if it doesn't exist.
		if ( 0 === $offset ) {
			$this->initialize_csv();
		}

		$csv_file = fopen( $this->csv_path, 'a' ); // WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $csv_file ) {
			Logger::add_log( 'Error opening CSV file for writing.' );
			return new WP_Error( 'csv_error', __( 'Could not open CSV file for writing.', 'newspack-print' ) );
		}

		// Check if the file is empty to write headers.
		$add_headers = false;
		if ( 0 === fstat( $csv_file )['size'] ) {
			$add_headers = true;
		}

		foreach ( $users as $user ) {
			$user_data = Newspack_Fields::get_user_as_array( $user->ID );

			// Update Status.
			$user_data[ Newspack_Fields::STATUS ] = Access_Manager::get_user_export_status( $user->ID );

			$export_line = Export_Parser::parse_line( $user_data );

			Logger::add_log( sprintf( 'Exporting user %d: %s', $user->ID, print_r( $export_line, true ) ) );

			if ( $add_headers ) {
				// Add headers only once.
				$headers = array_keys( $export_line );
				fputcsv( $csv_file, $headers ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv
				$add_headers = false;
			}

			if ( ! empty( $export_line ) ) {
				fputcsv( $csv_file, $export_line ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv
			}
		}

		fclose( $csv_file );
		Logger::add_log( sprintf( 'Exported %d users to CSV.', count( $users ) ) );

		return true;
	}

	/**
	 * Test export users.
	 *
	 * @param int $batch_size Batch size.
	 * @param int $offset Offset.
	 * @return bool|array False if no users, array of user data if successful.
	 */
	public function test_export_users( $batch_size = 20, $offset = 0 ) {
		$users = get_users( [
			'number'   => $batch_size,
			'offset'   => $offset,
			'meta_key' => Newspack_Fields::get_field( Newspack_Fields::CIRCULATION_ID_FIELD )['db_field'],
		] );

		if ( empty( $users ) ) {
			return false;
		}

		$export_data = [];

		foreach ( $users as $user ) {
			$user_data = Newspack_Fields::get_user_as_array( $user->ID );

			// Update Status.
			$user_data[ Newspack_Fields::STATUS ] = Access_Manager::get_user_export_status( $user->ID );

			$export_line = Export_Parser::parse_line( $user_data );

			Logger::add_log( sprintf( 'Exporting user %d: %s', $user->ID, print_r( $export_line, true ) ) );

			if ( ! empty( $export_line ) ) {
				$export_data[] = $export_line;
			}
		}

		if ( empty( $export_data ) ) {
			return false;
		}

		return $export_data;
	}

	/**
	 * Initialize CSV file with headers.
	 */
	private function initialize_csv() {
		$csv_file = fopen( $this->csv_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $csv_file ) {
			return false;
		}

		return true;
	}
}

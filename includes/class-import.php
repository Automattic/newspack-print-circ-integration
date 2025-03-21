<?php
/**
 * Newspack Print Integration Importer.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

use Newspack\PrintCirculationIntegration\Settings;

/**
 * Importer class to handle the CSV import.
 */
class Import {

	/**
	 * Settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * CSV file handle.
	 *
	 * @var resource
	 */
	private $csv_file;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = get_option( Settings::SETTINGS_OPTION, [] );
		add_action( 'init', [ $this, 'process_csv' ] );
	}

	/**
	 * Set the CSV file handle.
	 *
	 * @param resource $csv_file CSV file handle.
	 */
	public function set_csv_file( $csv_file ) {
		$this->csv_file = $csv_file;
	}

	/**
	 * Fetch the CSV file from the URL.
	 */
	public function fetch_csv_file() {
		// $csv_url = $this->settings['csv_import_path'] ?? '';
		// if ( ! $csv_url ) {
		// 	error_log( 'CSV URL not set in settings.' );
		// 	return;
		// }

		// $response = wp_remote_get( $csv_url );
		// if ( is_wp_error( $response ) ) {
		// 	error_log( 'Failed to fetch CSV file: ' . $response->get_error_message() );
		// 	return;
		// }

		$csv_path = WP_PLUGIN_DIR . '/' . NEWSPACK_PRINT_CIRC_INTEGRATION_PLUGIN_DIR . '/temp/Subscriber_Export_Report_2025_02_17.csv';
		if ( ! file_exists( $csv_path ) ) {
			error_log( 'Failed to open CSV file for writing: ' . $csv_path );
			return;
		}

		// Open the file in read mode.
		$csv_file = fopen( $csv_path, 'r' );
		if ( ! $csv_file ) {
			error_log( 'Failed to open CSV file for reading: ' . $csv_path );
			return;
		}

		$this->set_csv_file( $csv_file );
	}

	/**
	 * Validate and parse the CSV file.
	 */
	public function process_csv() {
		$header = fgetcsv( $this->csv_file );
		if ( ! $header ) {
			error_log( 'Failed to read CSV header.' );
			fclose( $this->csv_file );
			return;
		}
		while ( $row = fgetcsv( $this->csv_file ) ) {
			$row_data = array_combine( $header, $row );
			if ( ! $row_data ) {
				error_log( 'Invalid CSV row: ' . json_encode( $row ) );
				continue;
			}

			$mapped_data = $this->map_csv_row( $row_data );
			$this->create_or_update_user( $mapped_data );
		}

		fclose( $this->csv_file );
	}

	/**
	 * Map a CSV row to WordPress user fields.
	 */
	private function map_csv_row( $row ) {
		$mapping = json_decode( $this->settings['csv_mapping'] ?? '{}', true );
		$mapped_data = [];

		foreach ( $mapping as $csv_field => $wp_field ) {
			$mapped_data[ $wp_field ] = $row[ $csv_field ] ?? null;
		}

		// Fill in default values for missing fields.
		$mapped_data['circulation_id'] = $mapped_data['circulation_id'] ?? uniqid();
		$mapped_data['user_email']     = $mapped_data['user_email'] ?? $this->generate_placeholder_email( $mapped_data );
		$mapped_data['user_login']     = $mapped_data['user_login'] ?? $this->generate_placeholder_login_username( $mapped_data );

		return $mapped_data;
	}

	/**
	 * Generate a placeholder email if the email is missing.
	 */
	private function generate_placeholder_email( $row ) {
		$account_id = $row['circulation_id'] ?? uniqid();
		return 'newspack.print.circ_' . $account_id . '@unknown.com';
	}

	/**
	 * Generate a placeholder login username if the username is missing.
	 */
	private function generate_placeholder_login_username( $row ) {
		$account_id = $row['circulation_id'] ?? uniqid();
		return 'newspack_print_circ_username_' . $account_id;
	}

	/**
	 * Create or update a user based on the mapped data.
	 */
	private function create_or_update_user( $data ) {
		$user_fields = [
			'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url',
			'user_registered', 'display_name', 'nickname', 'first_name', 'last_name',
			'description', 'rich_editing', 'comment_shortcuts', 'admin_color',
			'use_ssl', 'user_registered', 'show_admin_bar_front', 'role'
		];

		$user_data = [];
		$circulation_id = $data['circulation_id'];
	
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $user_fields, true ) ) {
				$user_data[ $key ] = $value;
			} else {
				$user_data['meta_input'][ $key ] = $value;
			}
		}
	
		// Check if the user already exists by circulation ID.
		$user = $this->identify_user_by_circulation_id( $circulation_id );
		if ( $user ) {
			// Update existing user.
			$user_data['ID'] = $user->ID;
			$user_id = wp_update_user( $user_data );
			if ( is_wp_error( $user_id ) ) {
				error_log( 'Failed to update user: ' . $user_id->get_error_message() );
				return;
			}
		} else {
			// Create a new user.
			$user_id = wp_insert_user( $user_data );
			if ( is_wp_error( $user_id ) ) {
				error_log( 'Failed to create user: ' . $user_id->get_error_message() );
				return;
			}
		}
	}

	/**
	 * Identify a user by circulation ID.
	 *
	 * @param string $circulation_id Circulation ID.
	 *
	 * @return WP_User|null User object if found, null otherwise.
	 */
	private function identify_user_by_circulation_id( $circulation_id ) {
		$user = get_users( [
			'meta_key'   => 'circulation_id',
			'meta_value' => $circulation_id,
		] );
		if ( ! $user ) {
			return null;
		}

		return $user[0];
	}
}

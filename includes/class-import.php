<?php
/**
 * Newspack Print Integration Importer.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Importer class to handle the CSV import.
 */
class Import {
	/**
	 * CSV file handle.
	 *
	 * @var resource
	 */
	private $csv_file;

	/**
	 * Set the CSV file handle.
	 *
	 * @param resource $csv_file CSV file handle.
	 */
	private function set_csv_file( $csv_file ) {
		$this->csv_file = $csv_file;
	}

	/**
	 * Fetch the CSV file from the URL.
	 * TODO: This is temporary till the remote file fetching logic is implemented.
	 */
	public function fetch_csv_file() {
		// Fetch the CSV file from the URL.
		$csv_path = Settings::get_setting( Settings::CSV_IMPORT_PATH_OPTION );

		// Open the file in read mode.
		$csv_file = fopen( $csv_path, 'r' ); // phpcs:ignore
		if ( ! $csv_file ) {
			error_log( 'Failed to open CSV file for reading: ' . $csv_path ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$this->set_csv_file( $csv_file );
	}

	/**
	 * Import users from the CSV file.
	 *
	 * @param int $limit  Number of users to import.
	 * @param int $offset Offset.
	 */
	public function import_users( $limit = 100, $offset = 0 ) {

		$users = self::get_users_to_import( $limit, $offset );

		foreach ( $users as $user ) {
			$user = Import_Parser::parse_line( $user );
			self::process_user( $user );
		}
	}

	/**
	 * Get users to import from the CSV file.
	 *
	 * @param int $limit  Number of users to import.
	 * @param int $offset Offset.
	 */
	public function get_users_to_import( $limit = 100, $offset = 0 ) {

		$users = [];
		$csv_file = $this->csv_file;
		if ( ! $csv_file ) {
			error_log( 'Failed to open CSV file for reading.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return $users;
		}

		// Get the header.
		$header = fgetcsv( $csv_file );

		// Read the CSV file.
		$line = 0;
		while ( ( $row = fgetcsv( $csv_file ) ) !== false ) {
			$line++;
			if ( $line < $offset ) {
				continue;
			}

			// Create an associative array.
			$row = array_combine( $header, $row );

			$users[] = $row;
			if ( count( $users ) >= $limit ) {
				break;
			}
		}

		fclose( $csv_file );

		return $users;
	}

	/**
	 * Get a user if it exists or create a new user.
	 *
	 * @param array $user User data.
	 */
	public static function get_or_create_user( $user ) {
		/**
		 * Check if the user already exists by email.
		 */
		if ( ! empty( $user[ Newspack_Fields::EMAIL_FIELD ] ) ) {
			$existing_user = self::get_user_by_email( $user[ Newspack_Fields::EMAIL_FIELD ] );
			if ( $existing_user ) {
				return $existing_user->ID;
			}
		}

		/**
		 * Check if the user already exists by circulation_id.
		 */
		$existing_user = self::get_user_by_circulation_id( $user[ Newspack_Fields::CIRCULATION_ID_FIELD ] );

		if ( $existing_user ) {
			return $existing_user->ID;
		} else {
			/**
			 * User does not exist, create a new user.
			 */
			$user_id = self::create_user( $user );
		}

		return $user_id;
	}

	/**
	 * Check if the user should be updated.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if the user should be updated, false otherwise.
	 */
	public static function should_update_user( $user_id ) {
		// Only users that have the circulation_id should be updated.
		return (bool) Newspack_Fields::get_field_value( Newspack_Fields::CIRCULATION_ID_FIELD, $user_id );
	}

	/**
	 * Create a new user.
	 *
	 * @param array $user User data.
	 * @return int User ID.
	 */
	public static function create_user( $user ) {

		// If email is empty, generate a dummy email.
		if ( empty( $user[ Newspack_Fields::EMAIL_FIELD ] ) ) {
			$user[ Newspack_Fields::EMAIL_FIELD ] = 'no-email-' . uniqid() . '@newspackprintcirc.com';
		}

		$user_id = wp_create_user(
			$user[ Newspack_Fields::EMAIL_FIELD ],
			wp_generate_password(),
			$user[ Newspack_Fields::EMAIL_FIELD ]
		);

		// Populate the user fields.
		foreach ( $user as $key => $value ) {
			Newspack_Fields::set_field_value( $key, $user_id, $value );
		}

		return $user_id;
	}

	/**
	 * Update a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $user User data.
	 */
	public static function update_user( $user_id, $user ) {
		if ( empty( $user_id ) || empty( $user ) || ! is_array( $user ) ) {
			return;
		}

		foreach ( $user as $key => $value ) {
			Newspack_Fields::set_field_value( $key, $user_id, $value );
		}
	}

	/**
	 * Process a user.
	 *
	 * @param array $user User data.
	 */
	public static function process_user( $user ) {
		// Get or create the user.
		$user_id = self::get_or_create_user( $user );

		// Update the user if required.
		if ( self::should_update_user( $user_id ) ) {
			self::update_user( $user_id, $user );
		}
	}

	/**
	 * Get a user by email.
	 *
	 * @param string $email User email.
	 * @return WP_User|false User object if found, false otherwise.
	 */
	public static function get_user_by_email( $email ) {
		$user = \get_user_by( 'email', $email );
		return $user;
	}

	/**
	 * Get a user by circulation_id.
	 *
	 * @param string $circulation_id User circulation_id.
	 * @return WP_User|false User object if found, false otherwise.
	 */
	public static function get_user_by_circulation_id( $circulation_id ) {
		$circulation_field = Newspack_Fields::get_field( Newspack_Fields::CIRCULATION_ID_FIELD );
		$user = get_users(
			[
				'meta_key'   => $circulation_field['db_field'],
				'meta_value' => $circulation_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			]
		);

		return empty( $user ) ? false : $user[0];
	}
}

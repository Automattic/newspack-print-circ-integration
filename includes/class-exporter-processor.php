<?php
/**
 * Export Processor.
 *
 * @package Newspack_Print_Circ
 */

namespace Newspack_Print_Circ;

/**
 * Class to handle processing of Export files.
 */
class Export_Processor {

	public static function export_users( $limit = 100, $offset = 0 ) {

		$users = self::get_users_to_export( $limit, $offset );

		foreach ( $users as $user ) {
			$line = self::process_user( $user );
			$lines[] = $line;
		}

		self::write_lines_to_file( $lines );
	}

	public static function get_users_to_export( $limit = 100, $offset = 0 ) {

		$membership_plan_ids = Settings::get_settings( 'membership_plan_ids' );

		// Logic to get users who have one of the membership plan IDs in any status.
		$users = wcm_get_users_with_subscription( $membership_plan_ids ); // this function does not exist, we need to implement it.

		return $users;
	}

	public static function process_user( $user_id) {

		$user_array = Newspack_Fields::get_user_as_array( $user->ID );
		$user_array['status'] = self::user_has_active_membership( $user_id ) ? Settings::get_settings( 'active_status_value' ) : Settings::get_settings( 'inactive_status_value' );

		$line = Export_Parser::parse_line( $user_array );

		return $line;

	}

	/**
	 * Method that only tests how the export will look like.
	 *
	 * We can use this method in a CLI command, to check if the mapping is correct before actually exporting anything to a file.
	 *
	 * In the future we'll also have an UI for this, where users will be able to check if the Mapping settings are producing the expected output.
	 *
	 * @return array
	 */
	public static function test() {

		$users = self::get_users_to_export( 20 );

		foreach ( $users as $user ) {
			$line = self::process_user( $user );
			$lines[] = $line;
		}

		return $lines;

	}

	public static function user_has_active_membership( $user_id ) {
		// TODO
	}

}

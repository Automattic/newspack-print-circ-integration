<?php
/**
 * Newspack Print Integration Fields.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Class to handle field mapping and transformations.
 */
class Newspack_Fields {

	/**
	 * Field slugs.
	 */
	const CIRCULATION_ID_FIELD = 'circ_id';
	const FIRST_NAME_FIELD     = 'first_name';
	const LAST_NAME_FIELD      = 'last_name';
	const DISPLAY_NAME_FIELD   = 'display_name';
	const EMAIL_FIELD          = 'email';
	const ADDRESS_FIELD        = 'address';
	const CITY_FIELD           = 'city';
	const STATE_FIELD          = 'state';
	const ZIP_FIELD            = 'zip';
	const PHONE_FIELD          = 'phone';
	const STATUS               = 'status';
	const EXTRA_FIELD          = 'newspack_circ_extra';

	/**
	 * Get the fields to be mapped.
	 *
	 * @return array Fields to be mapped.
	 */
	public static function get_fields() {
		return [
			self::CIRCULATION_ID_FIELD => [
				'label'    => 'Circulation ID',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_circ_id',
			],
			self::FIRST_NAME_FIELD     => [
				'label'    => 'First Name',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_first_name',
			],
			self::LAST_NAME_FIELD      => [
				'label'    => 'Last Name',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_last_name',
			],
			self::DISPLAY_NAME_FIELD   => [
				'label'    => 'Display Name',
				'type'     => 'user_prop',
				'db_field' => 'display_name',
			],
			self::EMAIL_FIELD          => [
				'label'    => 'Email',
				'type'     => 'user_prop',
				'db_field' => 'user_email',
			],
			self::ADDRESS_FIELD        => [
				'label'    => 'Address',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_address_1',
			],
			self::CITY_FIELD           => [
				'label'    => 'City',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_city',
			],
			self::STATE_FIELD          => [
				'label'    => 'State',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_state',
			],
			self::ZIP_FIELD            => [
				'label'    => 'Zip',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_postcode',
			],
			self::PHONE_FIELD          => [
				'label'    => 'Phone',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_billing_phone',
			],
			self::STATUS               => [
				'label'    => 'Status',
				'type'     => 'user_meta',
				'db_field' => 'newspack_print_circ_status',
			],
			self::EXTRA_FIELD          => [
				'label'    => 'Extra Data',
				'type'     => 'extra_data',
				'db_field' => 'newspack_circ_extra_data',
			],
		];
	}

	/**
	 * Get a specific field.
	 *
	 * @param string $slug Field slug.
	 * @return array Field data.
	 */
	public static function get_field( $slug ) {
		$fields = self::get_fields();
		
		if ( isset( $fields[ $slug ] ) ) {
			return $fields[ $slug ];
		}

		return [];
	}

	/**
	 * Get the user's fields.
	 *
	 * @param int $user_id User ID.
	 * @return array User fields.
	 */
	public static function get_user_as_array( $user_id ) {
		if ( empty( $user_id ) ) {
			return [];
		}

		$fields = self::get_fields();
		$user_array = [];
		foreach ( $fields as $slug => $field ) {
			$user_array[ $slug ] = self::get_field_value( $slug, $user_id );
		}
		return $user_array;
	}

	/**
	 * Get the value of a field.
	 *
	 * @param string $slug Field slug.
	 * @param int    $user_id User ID.
	 * @return mixed   Field value.
	 */
	public static function get_field_value( $slug, $user_id ) {
		if ( empty( $slug ) || empty( $user_id ) ) {
			return null;
		}

		$field = self::get_field( $slug );

		// Get the field value based on the field type.
		if ( ! empty( $field['type'] ) && 'user_meta' === $field['type'] ) {
			// If its a user meta field.
			return get_user_meta( $user_id, $field['db_field'], true );
		} elseif ( ! empty( $field['type'] ) && 'user_prop' === $field['type'] ) {
			// If its a user property.
			$user = get_user_by( 'id', $user_id );
			return $user->{$field['db_field']};
		} else {
			// Retrieve from the user's extra data.
			$extra_field = self::get_field( self::EXTRA_FIELD );
			$extra_data = get_user_meta( $user_id, $extra_field['db_field'], true );
			if ( ! is_array( $extra_data ) ) {
				$extra_data = [];
			}
			return $extra_data[ $slug ] ?? null;
		}
	}

	/**
	 * Set the value of a field.
	 *
	 * @param string $slug Field slug.
	 * @param int    $user_id User ID.
	 * @param mixed  $value Field value.
	 */
	public static function set_field_value( $slug, $user_id, $value ) {
		if ( empty( $user_id ) || empty( $slug ) ) {
			return;
		}

		$field = self::get_field( $slug );

		// Set the field value based on the field type.
		if ( ! empty( $field['type'] ) && 'user_meta' === $field['type'] ) {
			// Store as a user meta field.
			update_user_meta( $user_id, $field['db_field'], $value );
		} elseif ( ! empty( $field['type'] ) && 'user_prop' === $field['type'] ) {
			// Store as a user property.
			$user = get_user_by( 'id', $user_id );
			$user->{$field['db_field']} = $value;
			wp_update_user( $user );
		} else {
			$extra_field = self::get_field( self::EXTRA_FIELD );
			// Store as a part of the user's extra data.
			$extra_data = get_user_meta( $user_id, $extra_field['db_field'], true );
			if ( ! is_array( $extra_data ) ) {
				$extra_data = [];
			}
			$extra_data[ $slug ] = $value;
			update_user_meta( $user_id, $extra_field['db_field'], $extra_data );
		}
	}
}

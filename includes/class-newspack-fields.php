
<?php
/**
 * Fields.
 *
 * @package Newspack_Print_Circ
 */

namespace Newspack_Print_Circ;

/**
 * Class to handle field mapping and transformations.
 */
class Newspack_Fields {

	public function get_fields() {

		return [
			'circ_id' => [
				'label' => 'Circulation ID',
				'type' => 'user_meta',
				'db_field' => 'newspack_print_circ_circ_id',
			],
			'first_name' => [
				'label' => 'First Name',
				'type' => 'user_meta',
				'db_field' => 'billing_first_name',
			],
			'last_name' => [
				'label' => 'Last Name',
				'type' => 'user_meta',
				'db_field' => 'billing_last_name',
			],
			'display_name' => [
				'label' => 'Display Name',
				'type' => 'user_prop',
				'db_field' => 'display_name',
			],
			'email' => [
				'label' => 'Email',
				'type' => 'user_prop',
				'db_field' => 'user_email',
			],
			'address' => [
				'label' => 'Address',
				'type' => 'user_meta',
				'db_field' => 'billing_address_1',
			],
			'city' => [
				'label' => 'City',
				'type' => 'user_meta',
				'db_field' => 'billing_city',
			],
			'state' => [
				'label' => 'State',
				'type' => 'user_meta',
				'db_field' => 'billing_state',
			],
			'zip' => [
				'label' => 'Zip',
				'type' => 'user_meta',
				'db_field' => 'billing_postcode',
			],
			'phone' => [
				'label' => 'Phone',
				'type' => 'user_meta',
				'db_field' => 'billing_phone',
			],
		];
	}

	public static function get_field( $slug ) {
		$fields = self::get_fields();
		return $fields[ $slug ];
	}

	public static function get_user_as_array( $user_id ) {
		$fields = self::get_fields();
		$user_array = [];
		foreach ( $fields as $slug => $field ) {
			$user_array[ $slug ] = self::get_field_value( $slug, $user_id );
		}
		return $user_array;
	}

	public static function get_field_value( $slug, $user_id ) {
		$field = self::get_field( $slug );
		if ( $field['type'] === 'user_meta' ) {
			return get_user_meta( $user_id, $field['db_field'], true );
		}
		if ( $field['type'] === 'user_prop' ) {
			$user = get_user_by( 'id', $user_id );
			return $user->{$field['db_field']};
		}
	}

	public static function set_field_value( $slug, $user_id, $value ) {
		$field = self::get_field( $slug );
		if ( $field['type'] === 'user_meta' ) {
			update_user_meta( $user_id, $field['db_field'], $value );
		}
		if ( $field['type'] === 'user_prop' ) {
			$user = get_user_by( 'id', $user_id );
			$user->{$field['db_field']} = $value;
			wp_update_user( $user );
		}
	}

}

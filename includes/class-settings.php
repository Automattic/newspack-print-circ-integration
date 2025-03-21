<?php
/**
 * Settings.
 *
 * @package Newspack_Print_Circ
 */

namespace Newspack_Print_Circ;

class Settings {

	/**
	 * Return hardcoded settings.
	 *
	 * @param string|null $key The key of the setting to return. Return all settings if null.
	 * @return array
	 */
	public function get_settings( $key = null) {

        $settings = [
            /**
             * The membership plan IDs that give readers access to the print edition.
             * Users with these plans will be exported to the Print Circulation System.
             * Imported active subscribers will be added to these plans.
             */
            'membership_plan_ids' => [10,20],

            /**
             * The membership plan ID that will be granted to new active users.
             */
            'new_users_granted_membership_plan_id' => 10,

            /**
             * The value of the status field that indicates a user is active.
             */
            'active_status_value' => 'Active',

            /**
             * The value of the status field that indicates a user is inactive.
             */
            'inactive_status_value' => 'Inactive',

            /**
             * The mapping of Newspack fields to Print Circulation System fields.
             *
             * Newspack fields on the left, Print Circulation System fields on the right.
             */
            'mapping' => [
                'circ_id' => 'Account',
                'status' => 'Status',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'display_name' => 'display_name',
                'email' => 'E-mail',
                'phone' => 'Phone',
                'address' => 'address',
				'city' => 'City',
				'state' => 'State',
				'zip' => 'Zip',
            ],
            /**
             * Transformations to apply to the date on importing and exporting
             *
             * In the future, the contents of each of these functions will be set via the Settings page, on a textarea, so we'll need to eval them.
             *
             * For now, we can hardcode them for the first publisher we are working with.
             */
            'import_transformations' => function( $line ) {
                // Join the address street name and st number into a single address field.
                $line['address'] = $line['Street Name'] . ' ' . $line['St Num'];
                unset( $line['Street Name'] );
				unset( $line['St Num'] );

                // Set display name;
                $line['display_name'] = $line['First Name'] . ' ' . $line['Last Name'];

                return $line;
            },
            'export_transformations' => function( $line ) {
                // Break address into street name and st number using a regex to extract the numeric part.
                $line['Street Name'] = preg_replace('/(\d+)$/', '', $line['address']);
                $line['St Num'] = preg_replace('/^[^0-9]*(\d+)$/', '$1', $line['address']);

                return $line;
            },
        ];

		if ( $key ) {
			return $settings[ $key ];
		}

		return $settings;
	}
}
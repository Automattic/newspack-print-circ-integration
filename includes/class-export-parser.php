<?php
/**
 * Export Parser.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Class to handle parsing of export data.
 */
class Export_Parser {

	/**
	 * Parse user data for export.
	 *
	 * @param array $user_data User data from Newspack fields.
	 * @return array Parsed line for export.
	 */
	public static function parse_line( $user_data ) {
		if ( empty( $user_data ) ) {
			return [];
		}

		// Get field mapping and reverse it for export.
		$mapping = json_decode( Settings::get_setting( Settings::CSV_MAPPING_OPTION ), true );

		// Map fields to CSV format.
		$export_line = [];
		foreach ( $user_data as $key => $value ) {
			if ( isset( $mapping[ $key ] ) ) {
				$export_line[ $mapping[ $key ] ] = $value;
			}
		}

		// Apply export transformations after mapping.
		$export_transformations = Settings::get_setting( Settings::EXPORT_TRANSFORMATIONS_OPTION );
		if ( ! empty( $export_transformations ) ) {
			$export_transformations = eval( 'return ' . $export_transformations . ';' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
			if ( is_callable( $export_transformations ) ) {
				$export_line = $export_transformations( $export_line );
			}
		}

		// Reorder the export_line to match the CSV fields order.
		$csv_fields_order = Settings::get_setting( Settings::CSV_FIELDS );

		if ( ! empty( $csv_fields_order ) ) {
			$ordered_line = array_fill_keys( $csv_fields_order, '' );
			foreach ( $export_line as $key => $value ) {
				if ( in_array( $key, $csv_fields_order ) ) {
					$ordered_line[ $key ] = $value;
				}
			}
			$export_line = $ordered_line;
		}

		return $export_line;
	}
}

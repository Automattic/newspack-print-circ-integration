<?php
/**
 * Import Parser.
 *
 * @package Newspack\PrintCirculationIntegration
 */

namespace Newspack\PrintCirculationIntegration;

/**
 * Class to handle parsing of import files.
 */
class Import_Parser {

	/**
	 * Parse a line from the CSV file.
	 *
	 * @param array $line Line from the CSV file.
	 * @return array Parsed line.
	 */
	public static function parse_line( $line ) {
		$line = array_map( 'trim', $line );

		$import_transformations = Settings::get_setting( Settings::IMPORT_TRANSFORMATIONS_OPTION );
		if ( ! empty( $import_transformations ) ) {
			/**
			 * TODO: eval is a huge security risk! need to find a better way to do this.
			 */
			$import_transformations = eval( 'return ' . $import_transformations . ';' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
			if ( is_callable( $import_transformations ) ) {
				$line = $import_transformations( $line );
			}
		}

		$mapping = json_decode( Settings::get_setting( Settings::CSV_MAPPING_OPTION ), true );
		$newspack_fields = Newspack_Fields::get_fields();

		$parsed_line = [];
		foreach ( $newspack_fields as $key => $field ) {
			if ( isset( $mapping[ $key ] ) && isset( $line[ $mapping[ $key ] ] ) ) {
				$parsed_line[ $key ] = $line[ $mapping[ $key ] ];
			}
		}

		foreach ( $line as $header => $value ) {
			if ( ! in_array( $header, $mapping ) ) {
				$extra_fields[ $header ] = $value;
			}
		}

		if ( ! empty( $extra_fields ) ) {
			$parsed_line[ Newspack_Fields::EXTRA_FIELD ] = $extra_fields;
		}

		return $parsed_line;
	}
}

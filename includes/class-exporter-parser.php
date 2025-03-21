<?php
/**
 * Export Parser.
 *
 * @package Newspack_Print_Circ
 */

namespace Newspack_Print_Circ;

/**
 * Class to handle parsing of Export files.
 */
class Export_Parser {

	public static function parse_line( $line ) {

		$mapping = Settings::get_settings( 'mapping' );
		$newspack_fields = Newspack_Fields::get_fields();

		foreach ( $newspack_fields as $key => $field ) {
			$line[ $mapping[ $key ] ] = $line[ $key ];
		}

		$export_transformations = Settings::get_settings( 'export_transformations' );
		$line = $export_transformations( $line );

		return $line;
	}
}

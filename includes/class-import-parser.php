<?php
/**
 * Import Parser.
 *
 * @package Newspack_Print_Circ
 */

namespace Newspack_Print_Circ;

/**
 * Class to handle parsing of import files.
 */
class Import_Parser {

	public static function parse_line( $line ) {
		$line = array_map( 'trim', $line );

		$import_transformations = Settings::get_settings( 'import_transformations' );
		$line = $import_transformations( $line );

		$mapping = Settings::get_settings( 'mapping' );
		$newspack_fields = Newspack_Fields::get_fields();

		foreach ( $newspack_fields as $key => $field ) {
			$line[ $key ] = $line[ $mapping[ $key ] ];
		}

		return $line;
	}
}

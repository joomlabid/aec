<?php
/**
 * @version $Id: php4.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage PHP4 Compatibility Layer
 * @copyright 2006-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

// If we haven't got native JSON, we must include it
if ( !function_exists( 'json_decode' ) ) {
	// Make sure no other service has loaded this library somewhere else
	if ( !class_exists( "Services_JSON" ) ) {
		require_once( $mosConfig_absolute_path . '/components/com_acctexp/lib/php4/json/json.php' );
	}

	// Create dummy encoding function
	function json_encode( $value )
	{
		$JSONenc = new Services_JSON();
		return $JSONenc->encode( $value );
	}

	// Create dummy decoding function
	function json_decode( $value )
	{
		$JSONdec = new Services_JSON();
		return $JSONdec->decode( $value );
	}

}

if ( !function_exists( 'str_split' ) ) {
	function str_split( $text, $split = 1 ) {
		// place each character of the string into an array
		$array = array();
		for ( $i = 0; $i < strlen( $text ); ){
			$key = NULL;
			for ( $j = 0; $j < $split; $j++, $i++ ) {
				if ( isset( $text[$i] ) ) {
					$key .= $text[$i];
				}
			}
			array_push( $array, $key );
		}
		return $array;
	}
}

?>

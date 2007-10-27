<?php
/**
 * @version $Id: eucalib.common.php
 * @package Eucalib: Component library for the Joomla! CMS
 * @subpackage Eucalib Common Files
 * @copyright Copyright (C) 2007 David Deutsch, All Rights Reserved
 * @author David Deutsch <skore@skore.de>
 * @license GNU/GPL v.2 or later http://www.gnu.org/copyleft/gpl.html
 *
 *                         _ _ _
 *                        | (_) |
 *     ___ _   _  ___ __ _| |_| |__
 *    / _ \ | | |/ __/ _` | | | '_ \
 *   |  __/ |_| | (_| (_| | | | |_) |
 *    \___|\__,_|\___\__,_|_|_|_.__/  v1.0
 *
 * The Extremely Useful Component LIBrary will rock your socks. Seriously. Reuse it!
 */

defined( '_VALID_MOS' ) or die( 'Restricted access' );

/**
* parameterized Database Table entry
*
* For use with as an abstract class that adds onto table entries
*/
class paramDBTable extends mosDBTable
{
	/**
	 * Dummy function to be overridden by calling class
	 * @return array
	 */
	function paramTypeList()
	{
		return array();
	}

	/**
	 * Receive Parameters and decode them into an array
	 * @return array
	 */
	function getParams( $field = 'params' )
	{
		if ( empty( $this->$field ) ) {
			return false;
		}

		$params = explode( "\n", $this->$field );

		$array = array();
		foreach ( $params as $chunk ) {
			$k = explode( '=', $chunk, 2 );
			if ( !empty( $k[0] ) ) {
				// Strip slashes, but preserve special characters
				$array[$k[0]] = stripslashes( str_replace( array( '\n', '\t', '\r' ), array( "\n", "\t", "\r" ), $k[1] ) );
			}
			unset( $k );
		}
		return $array;
	}

	/**
	 * Encode array and set Parameter field
	 */
	function setParams( $array, $field = 'params' )
	{
		$params = array();

		foreach ( $array as $key => $value ) {
			if ( !empty( $key ) ) {
				if ( get_magic_quotes_gpc() ) {
					$value = stripslashes($value);
				}
				$value = $this->_db->getEscaped( $value );

				$params[] = $key . '=' . $value;
			}
		}

		$this->$field = implode( "\n", $params );
	}

	/**
	 * Add an array of Parameters to an existing parameter field
	 */
	function addParams( $array, $field = 'params', $overwrite = true )
	{
		$params = $this->getParams( $field );
		foreach ( $array as $key => $value ) {
			if ( $overwrite ) {
				$params[$key] = $value;
			} else {
				if ( !isset( $params[$key] ) ) {
					$params[$key] = $value;
				}
			}
		}
		$this->setParams ($params, $field);
	}

	/**
	 * Delete a set of Parameters providing an array of key names
	 */
	function delParams( $array, $field = 'params' )
	{
		$params = $this->getParams( $field );
		foreach ( $array as $key ) {
			if ( isset( $params[$key] ) ) {
				unset( $params[$key] );
			}
		}
		$this->setParams( $params, $field );
	}

	/**
	 * Return the differences between a new set of Parameters and the existing one
	 */
	function diffParams( $array, $field = 'params' )
	{
		$diff = array();

		$params = $this->getParams( $field );
		foreach ( $array as $key => $value ) {
			if ( isset( $params[$key] ) ) {
				if( !( ( $value === $params[$key] ) || ( addslashes( $value ) === $params[$key] ) ) ) {
					$diff[$key] = array( $params[$key], $value );
				}
			}
		}

		if ( count( $diff ) ) {
			return $diff;
		} else {
			return false;
		}
	}

	/**
	 * Return a compilation of all field values, provide a list of parameter fields
	 * @return array
	 */
	function fullparamsValuesArray()
	{
		$params = $this->paramTypeList();

		$vars = get_object_vars( $this );

		$paramsvalues = array();
		foreach ( $vars as $var => $content ) {
			if ( ( strpos( $var, '_' ) !== 0 ) || ( strpos( $var, '_' ) === false ) ) {
				if ( isset( $params[$var] ) ) {
					if ( is_array( $params[$var] ) ) {
						$paramsvalues[$var] = $this->getParams( $var );
					} else {
						$paramsvalues[$var] = $this->$var;
					}
				}
			}
		}

		return $paramsvalues;
	}

	/**
	 * Automatically save a full object by referring to its paramTypeList
	 */
	function fullSave( $post=null )
	{
		$params = $this->paramTypeList();

		if ( is_null( $post ) ) {
			$post = $_POST;
		}

		$this->load( $post['id'] );

		// Travel through preset parameters
		foreach ( $params as $param => $ptype ) {
			// If the entry has child entries, we need to parse them here
			if ( is_array( $ptype ) ) {
				$paramarray = array();
				foreach ( $ptype as $paramitem => $pitype ) {
					// The name is a composition between the item and child item name
					$supposedfield = $param . '_' . $paramitem;

					// See whether we have such an entry
					if ( isset( $post[$supposedfield] ) ) {
						if ( is_array( $post[$supposedfield] ) ) {
							$paramarray[$paramitem] = implode( ';', $post[$supposedfield] );
						} else {
							$paramarray[$paramitem] = $post[$supposedfield];
						}
					// Or maybe its within an array?
					} elseif ( is_array( $post[$param] ) ) {
						if ( isset( $post[$param][$paramitem] ) ) {
							if ( is_array( $post[$param][$paramitem] ) ) {
								$paramarray[$paramitem] = implode( ';', $post[$param][$paramitem]);
							} else {
								$paramarray[$paramitem] = $post[$param][$paramitem];
							}
						}
					}
				}

				$this->setParams( $paramarray, $param );
			// For regular entries, its rather simple
			} else {
				if ( isset( $post[$param] ) ) {
					if ( is_array( $post[$param] ) ) {
						if ( get_magic_quotes_gpc() ) {
							$value = stripslashes( implode( ';', $post[$param] ) );
						} else {
							$value = implode( ';', $post[$param] );
						}
					} else {
						if ( get_magic_quotes_gpc() ) {
							$value = stripslashes( $post[$param] );
						} else {
							$value = $post[$param];
						}
					}
					$this->$param = $this->_db->getEscaped( $value );
				}
			}
		}

		$this->check();
		$this->store();
	}

}

class languageFileHandler
{
	function languageFileHandler( $filepath ) {
		$this->filepath = $filepath;
	}

	function getConstantsArray() {

		$file = fopen( $this->filepath, "r" );

		$array = array();
		while ( !feof( $file ) ) {
			$buffer = fgets($file, 4096);
			if ( strpos( $buffer, 'define') !== false ) {
				$linearray = explode( '\'', $buffer );
				if ( count( $linearray ) === 5 ) {
					$array[$linearray[1]] = $linearray[3];
				}
			}
    	}

		return $array;
	}

	function getHTML() {

		$file = fopen( $this->filepath, "r" );

		$array = array();
		while ( !feof( $file ) ) {
			$buffer = fgets($file, 4096);
			if ( strpos( $buffer, 'define') !== false ) {
				$linearray = explode( '\'', $buffer );
				if ( count( $linearray ) === 5 ) {
					$array[$linearray[1]] = $linearray[3];
				}
			}
    	}

		return $array;
	}
}

function resolveProxy ( $task, $returntask=null, $admin=false )
{
	if ( empty( $task ) ) {
		$task = 'self_notask';
	}

	// Explode task
	$atask = explode( '_', $task, 4 );

	$requires = array();

	// Load eucalib for this section
	$requires[] = _EUCA_BASEDIR.'/eucalib.' . $atask[0] . '.php';

	// Load class
	$requires[] = _EUCA_APP_COMPDIR .'/' . _EUCA_APP_SHORTNAME . '.class.php';

	if ( $admin ) {
		// Load admin
		$requires[] = _EUCA_BASEDIR.'/eucalib.admin.' . $atask[0] . '.php';

		// Load admin common
		$requires[] = _EUCA_BASEDIR.'/eucalib.admin.common.php';

		// Load admin class
		$requires[] = _EUCA_APP_ADMINDIR .'/admin.' . _EUCA_APP_SHORTNAME . '.class.php';

		// Load admin file
		$requires[] = _EUCA_APP_ADMINDIR .'/admin.' . _EUCA_APP_SHORTNAME . '.' . $atask[0] . '.php';
	} else {
		// Load regular file
		$requires[] = _EUCA_APP_COMPDIR .'/' . _EUCA_APP_SHORTNAME . '.' . $atask[0] . '.php';
	}

	foreach ( $requires as $require ) {
		if( file_exists( $require ) ) {
			include_once( $require );
		}
	}

	$subtask = '';

	if ( isset( $atask[1] ) ) {
		if ( $atask[1] ) {
			$subtask = $atask[1];
			if ( isset( $atask[2] ) ) {
				$action = $atask[2];
			} else {
				$action = 'init';
			}
		}
	}

	if ( class_exists ( $subtask ) ) {
		$class = new $subtask();
		if ( method_exists( $class, $action ) ) {
			if ( isset( $atask[3] ) ) {
				$class->$action( $atask[3] );
			} else {
				$class->$action();
			}
		}
	}

	if ( !empty( $returntask ) ) {
		$append = '';
		foreach ( $_REQUEST as $name => $value ) {
			if ( !( strlen( $name ) >= 32 ) && ( $name != "option" ) && ( $name != "task" ) && ( $name != "returntask" ) ) {
				$append .= '&amp;' . $name . '=' . $value;
			}
		}

		if ( $admin ) {
			mosRedirect( '/administrator/index2.php?option=com_' . _EUCA_APP_SHORTNAME . '&task='  . $returntask . $append );
		} else {
			mosRedirect( 'index.php?option=com_' . _EUCA_APP_SHORTNAME . '&task='  . $returntask . $append );
		}
	}
}

class eucaToolbox
{
	function eucaToolbox()
	{

	}

	function makeIcon( $name, $alt=false )
	{
		if ( !$alt ) {
			$alt = $name;
		}

		return '<img src="'. eucaToolbox::IconSrc( $name ) .'" border="0" alt="' . $alt . '" title="' . $alt . '" class="euca_icon" />';
	}

	function IconSrc( $name )
	{
		return _EUCA_APP_ICONSDIR . '/' . $name . '.png';
	}

	function natSortKey(&$arrIn, $case=0 )
	{
		$key_array = array();
		$arrOut = array();

		foreach ( $arrIn as $key => $value ) {
			$key_array[]=$key;
		}

		if ( $case ) {
			natcasesort( $key_array );
		} else {
			natsort( $key_array );
		}

		foreach ( $key_array as $key => $value ) {
			$arrOut[$value] = $arrIn[$value];
		}

		$arrIn=$arrOut;
	}
}

class eucaDebug
{
	function eucaDebug()
	{
		if ( phpversion() >= "5.0.0") {
			include_once( _EUCA_APP_PEAR );
			include_once( _EUCA_APP_LIBDIR . '/php_debug/php_debug_2/PHP/Debug.php' );

			// Options array for Debug object
			$options = array(
				'HTML_DIV_images_path' => 'images',
				'HTML_DIV_css_path' => 'css',
				'HTML_DIV_js_path' => 'js',
			);

			$this->Dbgv = 2;
			$this->Dbg = new PHP_Debug( $options );
		} else {
			include_once( _EUCA_APP_LIBDIR . '/php_debug/php_debug_1/sources/debug.php' );

			$this->Dbgv = 1;
			$this->Dbg = new Debug();
		}

		include_once( _EUCA_APP_LIBDIR . '/krumo/class.krumo.php' );
	}

	function displayDebug()
	{
		if ( $this->Dbgv == 2 ) {
			$this->Dbg->display();
		} else {
			$this->Dbg->DebugDisplay();
		}
	}
}

?>
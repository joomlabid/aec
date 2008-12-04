<?php
/**
 * @version $Id: aecaccess.php
 * @package AEC - Account Control Expiration - Joomla 1.5 Plugins
 * @subpackage Access
 * @copyright 2006-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

if ( !class_exists( 'mosDBTable' ) ) {
	// We have a problem - the legacy bot is not published (yet).
	// Issue error end do not load anything.

	$db =& JFactory::getDBO();

	$date	= date( 'Y-m-d H:i:s' );
	$short	= 'Plugin could not be loaded';
	$tags	= 'system,plugins,fatal';
	$event	= 'One of the AEC Plugins could not be loaded because the Legacy Plugin not published or published after AEC plugins. MUST be published before AEC plugins!';
	$level	= 128;
	$notify	= 1;

	$query = 'INSERT INTO #__acctexp_eventlog'
			. ' (`datetime`, `short`, `tags`, `event`, `level`, `notify` )'
			. ' VALUES (\'' . $date . '\', \'' . $short . '\', \'' . $tags . '\', \'' . $event . '\', \'' . $level . '\', \'' . $notify . '\')';

	$db->setQuery( $query );
	$db->query();
} else {

jimport('joomla.event.plugin');

/**
 * AEC Authentication plugin
 *
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @package AEC Component
 */
class plgAuthenticationAECaccess extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgAuthenticationAECaccess( &$subject, $config ) {
		parent::__construct( $subject, $config );
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param   array 	$credentials Array holding the user credentials
	 * @param 	array   $options     Array of extra options
	 * @param	object	$response	 Authentication response object
	 * @return	boolean
	 * @since 1.5
	 */
	function onAuthenticate( $credentials, $options, &$response )
	{
		if ( file_exists( JPATH_ROOT.DS."components".DS."com_acctexp".DS."acctexp.class.php" ) ) {
			include_once( JPATH_ROOT.DS."components".DS."com_acctexp".DS."acctexp.class.php" );

			global $aecConfig;

			$autholist = $aecConfig->cfg['authorization_list'];

			if ( !empty( $autholist ) ) {
				foreach( $autholist as $auth ) {
					if ( !empty( $auth ) ) {
						$className = 'plgAuthentication'.$auth;
						$plugin = PluginHandler::getPlugin( 'authentication', $auth );

						JLoader::import( 'authentication.'.$auth, JPATH_ROOT.DS.'plugins', 'plugins' );
						if ( class_exists( $className ) ) {
							$plugin = new $className( JAuthentication::getInstance(), (array)$plugin );
						}

						// Try to authenticate
						if ( method_exists( $plugin, 'onAuthenticate' ) ) {
							$plugin->onAuthenticate($credentials, $options, $response);
						}

						// If authentication is unsuccessfull break whole authorization
						if ( $response->status !== JAUTHENTICATE_STATUS_SUCCESS ) {
							return;
						}
					}
				}
			}

			$authlist = $aecConfig->cfg['authlist'];

			if ( empty( $authlist ) ) {
				$authlist = array( 'joomla' );
			}

			if ( empty( $authlist[0] ) ) {
				$authlist = array( 'joomla' );
			}

			foreach( $authlist as $auth ) {
				if ( !empty( $auth ) ) {
					$className = 'plgAuthentication'.$auth;
					$plugin = PluginHandler::getPlugin( 'authentication', $auth );

					JLoader::import( 'authentication.'.$auth, JPATH_ROOT.DS.'plugins', 'plugins' );
					if ( class_exists( $className ) ) {
						$plugin = new $className( JAuthentication::getInstance(), (array)$plugin );
					}

					// Try to authenticate
					$plugin->onAuthenticate($credentials, $options, $response);

					// If authentication is successfull break the loop
					if ( $response->status === JAUTHENTICATE_STATUS_SUCCESS ) {
						break;
					}
				}
			}

			// process AEC verifications
			if ( $response->status === JAUTHENTICATE_STATUS_SUCCESS ) {
				$this->_AECVerification( $credentials, $response );
				return;
			}
		} else {
			$auth = 'joomla';

			$className = 'plgAuthentication'.$auth;
			$plugin = JPluginHelper::getPlugin( 'authentication', $auth );

			JLoader::import( 'authentication.'.$auth, JPATH_ROOT.DS.'plugins', 'plugins' );
			if ( class_exists( $className ) ) {
				$plugin = new $className( JAuthentication::getInstance(), (array)$plugin );
			}

			// Try to authenticate
			if ( method_exists( $plugin, 'onAuthenticate' ) ) {
				$plugin->onAuthenticate($credentials, $options, $response);
			}

			return;
		}
	}

	function onLoginFailure( &$response )
	{
		$db =& JFactory::getDBO();

		$query = 'SELECT id'
		. ' FROM #__users'
		. ' WHERE username = \'' . AEC_AUTH_ERROR_UNAME . '\''
		;
		$db->setQuery( $query );
		$id = $db->loadResult();

		$redirect = false;

		switch ( AEC_AUTH_ERROR_MSG ) {
			case 'pending':
			case 'open_invoice':
				$redirect = JURI::base( true ) . '/index.php?option=com_acctexp&task=pending&userid=' . $id;
				break;
			case 'expired':
				$redirect = JURI::base( true ) . '/index.php?option=com_acctexp&task=expired&userid=' . $id ;
				break;
			case 'hold':
				$redirect = JURI::base( true ) . '/index.php?option=com_acctexp&task=hold&userid=' . $id ;
				break;
			case 'subscribe':
				$redirect = JURI::base( true ) . '/index.php?option=com_acctexp&task=subscribe&userid=' . $id ;
				break;
		}

		if ( $redirect ) {
			$app =& JFactory::getApplication();
			$app->redirect( $redirect );
		}
	}

	function _AECVerification( $credentials, &$response )
	{
		if ( strpos( JPATH_BASE, '/administrator' ) ) {
			$response->status = JAUTHENTICATE_STATUS_SUCCESS;
			return true;
		}

		$savetask = '';
		if ( isset( $_REQUEST['task'] ) ) {
			$_REQUEST['task'] = '';
			$savetask = $_REQUEST['task'];
		}

		include_once( JPATH_ROOT .DS.'components'.DS.'com_acctexp'.DS.'acctexp.php' );

		$_REQUEST['task'] = $savetask;

		$verification = AECToolbox::VerifyUser( $credentials['username'] );

		if ( $verification === true ) {
			$response->status = JAUTHENTICATE_STATUS_SUCCESS;
		} else {
			$this->_error = $verification;
			define( 'AEC_AUTH_ERROR_MSG', $this->_error );
			define( 'AEC_AUTH_ERROR_UNAME', $credentials['username'] );
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
		}
	}
}

}
?>

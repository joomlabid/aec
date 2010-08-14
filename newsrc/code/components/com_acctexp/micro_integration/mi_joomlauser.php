<?php
/**
 * @version $Id: mi_joomlauser.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - Joomla User
 * @copyright 2006-2010 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_joomlauser
{
	function Info()
	{
		$info = array();
		$info['name'] = _AEC_MI_NAME_JOOMLAUSER;
		$info['desc'] = _AEC_MI_DESC_JOOMLAUSER;

		return $info;
	}

	function Settings()
	{
		$settings = array();
		$settings['activate']		= array( 'list_yesno' );
		$settings['block']			= array( 'list_yesno' );
		$settings['username']		= array( 'inputD' );
		$settings['username_rand']	= array( 'inputC' );
		$settings['password']		= array( 'inputD' );

		$rewriteswitches			= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );
		$settings['rewriteInfo']	= array( 'fieldset', _AEC_MI_SET4_MYSQL, AECToolbox::rewriteEngineInfo( $rewriteswitches ) );

		return $settings;
	}

	function action( $request )
	{
		$database = &JFactory::getDBO();

		$set = array();

		if ( $this->settings['activate'] ) {
			$set[] = '`block` = \'0\'';
			$set[] = '`activation` = \'\'';
		}

		$username = $this->getUsername( $request );

		if ( !empty( $username ) ) {
			$set[] = '`username` = \'' . $username . '\'';
		}

		if ( !empty( $this->settings['password'] ) ) {
			$pw = AECToolbox::rewriteEngineRQ( $this->settings['password'], $request );

			if ( aecJoomla15check() ) {
				jimport('joomla.user.helper');

				$salt  = JUserHelper::genRandomPassword( 32 );
				$crypt = JUserHelper::getCryptedPassword( $pw, $salt );
				$password = $crypt.':'.$salt;
			} else {
				$password = md5( $pw );
			}

			$set[] = '`password` = \'' . $password . '\'';
		}

		if ( !empty( $set ) ) {
			$query = 'UPDATE #__users';
			$query .= ' SET ' . implode( ', ', $set );
			$query .= ' WHERE `id` = \'' . (int) $request->metaUser->userid . '\'';

			$database->setQuery( $query );
			$database->query() or die( $database->stderr() );

			$userid = $request->metaUser->userid;

			// Reloading metaUser object for other MIs
			$request->metaUser = new metaUser( $userid );
		}
	}

	function getUsername( $request )
	{
		if ( !empty( $this->settings['username_rand'] ) ) {
			$database = &JFactory::getDBO();

			$numberofrows	= 1;
			while ( $numberofrows ) {
				$uname =	strtolower( substr( base64_encode( md5( rand() ) ), 0, $this->settings['username_rand'] ) );
				// Check if already exists
				$query = 'SELECT count(*)'
						. ' FROM #__users'
						. ' WHERE `username` = \'' . $uname . '\''
						;
				$database->setQuery( $query );
				$numberofrows = $database->loadResult();
			}

			return $uname;
		} elseif ( !empty( $this->settings['username'] ) ) {
			return AECToolbox::rewriteEngineRQ( $this->settings['username'], $request );
		}
	}

	function expiration_action( $request )
	{
		if ( $this->settings['block'] ) {
			$database = &JFactory::getDBO();

			$query = 'UPDATE #__users'
				. ' SET `block` = \'1\''
				. ' WHERE `id` = \'' . (int) $request->metaUser->userid . '\''
				;

			$database->setQuery( $query );
			$database->query() or die( $database->stderr() );
		}

	}
}

?>
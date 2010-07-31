<?php
/**
 * @version $Id: tool_supporthours.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Toolbox - Support Hours
 * @copyright 2010 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class tool_supporthours
{
	function Info()
	{
		$info = array();
		$info['name'] = "Support Hours";
		$info['desc'] = "Gets rid of subscriptions for users that don't exist anymore.";

		return $info;
	}

	function Settings()
	{
		return array();
	}

	function Action( $request )
	{
		// Find all memberships without a user account
		// Delete them
	}

}
?>

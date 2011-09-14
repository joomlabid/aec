<?php
/**
 * @version $Id: upgrade_0_14_4.inc.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Install Includes
 * @copyright 2011 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

$db->setQuery("ALTER TABLE #__acctexp_plans CHANGE `name` `name` varchar(255)");
if ( !$db->query() ) {
	$errors[] = array( $db->getErrorMsg(), $query );
}

if ( is_dir( JPATH_SITE . '/components/com_acctexp/lang' ) ) {
	eucaInstall::rrmdir( JPATH_SITE . '/components/com_acctexp/lang' );
}

if ( is_dir( JPATH_SITE . '/components/com_acctexp/micro_integration/lang' ) ) {
	eucaInstall::rrmdir( JPATH_SITE . '/components/com_acctexp/micro_integration/lang' );
}

if ( is_dir( JPATH_SITE . '/components/com_acctexp/processors/lang' ) ) {
	eucaInstall::rrmdir( JPATH_SITE . '/components/com_acctexp/processors/lang' );
}

if ( is_dir( JPATH_SITE . '/administrator/components/com_acctexp/lang' ) ) {
	eucaInstall::rrmdir( JPATH_SITE . '/administrator/components/com_acctexp/lang' );
}

?>
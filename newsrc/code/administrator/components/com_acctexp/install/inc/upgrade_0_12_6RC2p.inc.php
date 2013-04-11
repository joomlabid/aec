<?php
/**
 * @version $Id: upgrade_0_12_6_RC2p.inc.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Install Includes
 * @copyright 2006-2013 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

$db->setQuery("ALTER TABLE #__acctexp_invoices CHANGE `coupons` `coupons` text NULL");
if ( !$db->query() ) {
	$errors[] = array( $db->getErrorMsg(), $query );
}

?>
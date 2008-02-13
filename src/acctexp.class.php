<?php
/**
 * @version $Id: acctexp.class.php 16 2007-06-27 09:04:04Z mic $
 * @package AEC - Account Control Expiration - Subscription component for Joomla! OS CMS
 * @subpackage Core Class
 * @copyright Copyright (C) 2004-2007, All Rights Reserved, Helder Garcia, David Deutsch
 * @author Helder Garcia <helder.garcia@gmail.com>, David Deutsch <skore@skore.de> & Team AEC - http://www.gobalnerd.org
 * @license GNU/GPL v.2 http://www.gnu.org/copyleft/gpl.html
 */

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// Please note that the GPL states that any headers in files and
// Copyright notices as well as credits in headers, source files
// and output (screens, prints, etc.) can not be removed.
// You can extend them with your own credits, though...
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

global $mosConfig_absolute_path, $mosConfig_offset_user, $aecConfig;

if ( !defined ( 'AEC_FRONTEND' ) && !defined( '_AEC_LANG' ) ) {
	// mic: call only if called from the backend
	$langPath = $mosConfig_absolute_path . '/administrator/components/com_acctexp/com_acctexp_language_backend/';
	if ( file_exists( $langPath . $GLOBALS['mosConfig_lang'] . '.php' )) {
			include_once( $langPath . $GLOBALS['mosConfig_lang'] . '.php' );
	} else {
			include_once( $langPath. 'english.php' );
	}
	include_once( $langPath . 'general.php' );
}

if ( !defined( '_AEC_LANG' ) ) {
	$langPath = $mosConfig_absolute_path . '/components/com_acctexp/com_acctexp_language/';
	if ( file_exists( $langPath . $GLOBALS['mosConfig_lang'] . '.php' ) ) {
		include_once( $langPath . $GLOBALS['mosConfig_lang'] . '.php' );
	} else {
		include_once( $langPath . 'english.php' );
	}
	define( '_AEC_LANG', 1 );
}

if ( !class_exists( 'paramDBTable' ) ) {
	include_once( $mosConfig_absolute_path . '/components/com_acctexp/lib/eucalib/eucalib.common.php' );
}

// compatibility w/ Mambo
if ( empty( $mosConfig_offset_user ) ) {
	global $mosConfig_offset;
	$mosConfig_offset_user = $mosConfig_offset;
}

// Catch all debug function
function aecDebug( $text )
{
	global $database;

	$eventlog = new eventLog( $database );
	$eventlog->issue( 'debug', 'debug', 'debug entry: '.$text, 128 );
}

class metaUser
{
	/** @var int */
	var $userid				= null;
	/** @var object */
	var $cmsUser			= null;
	/** @var object */
	var $objSubscription	= null;
	/** @var int */
	var $hasSubscription	= null;

	function metaUser( $userid )
	{
		global $database;

		$this->cmsUser = false;
		$this->userid = 0;

		$this->hasSubscription = 0;
		$this->objSubscription = null;
		$this->focusSubscription = null;

		if ( $userid ) {
			$this->userid = $userid;

			$this->cmsUser = new mosUser( $database );
			$this->cmsUser->load( $userid );

			$aecid = AECfetchfromDB::SubscriptionIDfromUserID( $userid );
			if ( $aecid ) {
				$this->objSubscription = new Subscription( $database );
				$this->objSubscription->load( $aecid );
				$this->focusSubscription = new Subscription( $database );
				$this->focusSubscription->load( $aecid );
				$this->hasSubscription = 1;
			}
		}
	}

	function getTempAuth()
	{
		$return = false;
		$params = array();

		// Get params either from the subscription or from the _user entry
		if ( $this->hasSubscription ) {
			$params = $this->objSubscription->getParams();
		} else {
			if ( is_object( $this->cmsUser ) ) {
				$par = explode( "\n", $this->cmsUser->params );

				foreach ( $par as $chunk ) {
					$k = explode( '=', $chunk, 2 );
					$params[$k[0]] = isset( $k[1] ) ? trim( $k[1] ) : '';
				}
			}
		}

		// Only authorize if user IP is matching and the grant is not expired
		if ( isset( $params['tempauth_exptime'] ) && isset( $params['tempauth_ip'] ) ) {
			if ( ( $params['tempauth_ip'] == $_SERVER['REMOTE_ADDR'] ) && ( $params['tempauth_exptime'] >= time() ) ) {
				return true;
			}
		}

		return false;
	}

	function setTempAuth( $password=false )
	{
		global $aecConfig;

		// Make sure we catch traditional and new joomla passwords
		if ( $password !== false ) {
			if ( strpos( $this->cmsUser->password, ':') === false ) {
				if ( $this->cmsUser->password != md5( $password ) ) {
					return false;
				}
			} else {
				list( $hash, $salt ) = explode(':', $this->cmsUser->password);
				$cryptpass = md5( $password . $salt );
				if ( $hash != $cryptpass ) {
					return false;
				}
			}
		}

		// Set params
		$params = array();
		$params['tempauth_ip'] = $_SERVER['REMOTE_ADDR'];
		$params['tempauth_exptime'] = strtotime( '+' . max( 10, $aecConfig->cfg['temp_auth_exp'] ) . ' minutes', time() );

		// Save params either to subscription or to _user entry
		if ( $this->hasSubscription ) {
			$this->objSubscription->addParams( $params );
			$this->objSubscription->check();
			$this->objSubscription->store();
		} else {
			if ( is_object( $this->cmsUser ) ) {
				$add = strpos( $this->cmsUser->params, "\n" ) ? "\n" : '';

				$array = array();
				foreach ( $params as $name => $value ) {
					$array[] = $name . "=" . $value;
				}

				$this->cmsUser->params .= $add . implode( "\n", $array );
				$this->cmsUser->check();
				$this->cmsUser->store();
			}
		}
		return true;
	}

	function getSecondarySubscriptions()
	{
		global $database;

		$query = 'SELECT `id`, `plan`, `type`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `userid` = \'' . (int) $this->userid . '\''
				. ' AND `primary` = \'0\''
				. ' ORDER BY `lastpay_date` DESC'
				;
		$database->setQuery( $query );
		return $database->loadObjectList();
	}

	function procTriggerCreate( $user, $payment, $usage )
	{
		global $database, $aecConfig;

		// Create a new cmsUser from user details - only allowing basic details so far
		// Try different types of usernames to make sure we have a unique one
		$usernames = array( $user['username'],
							$user['username'] . substr( md5( $user['name'] ), 0, 3 ),
							$user['username'] . substr( md5( ( $user['name'] . time() ) ), 0, 3 )
							);

		// Iterate through semi-random and pseudo-random usernames until a non-existing is found
		$id = 1;
		$k = 0;
		while ( $id ) {
			$username = $usernames[min( $k, ( count( $usernames ) - 1 ) )];

			$query = 'SELECT `id`'
					. ' FROM #__users'
					. ' WHERE `username` = \'' . $username . '\''
					;
			$database->setQuery( $query );

			$id = $database->loadResult();
			$k++;
		}

		$var['id'] 			= 0;
		$var['gid'] 		= 0;
		$var['username']	= $username;
		$var['name']		= $user['name'];
		$var['email']		= $user['email'];
		$var['password']	= $user['password'];

		$userid = AECToolbox::saveUserRegistration( 'com_acctexp', $var, true );

		// Create a new invoice with $invoiceid as secondary ident
		$invoice = new Invoice( $database );
		$invoice->create( $userid, $usage, $payment['processor'], $payment['secondary_ident'] );

		// return nothing, the invoice will be handled by the second ident anyways
		return;
	}

	function establishFocus( $payment_plan, $processor='none' )
	{
		global $database;

		$plan_params = $payment_plan->getParams();

		// Check whether a record exists
		if ( $this->hasSubscription ) {
			$existing_record = $this->focusSubscription->getSubscriptionID( $this->userid, $payment_plan->id, null );
		} else {
			$existing_record = 0;
		}

		if ( !isset( $plan_params['make_primary'] ) ) {
			$plan_params['make_primary'] = 1;
		}

		// To be failsafe, a new subscription may have to be added in here
		if ( !$this->hasSubscription || !$plan_params['make_primary'] ) {
			if ( $existing_record && ( $plan_params['update_existing'] || $plan_params['make_primary'] ) ) {
				// Update existing non-primary subscription
				$this->focusSubscription = new Subscription( $database );
				$this->focusSubscription->load( $existing_record );
			} else {
				// Create new subscription
				$this->focusSubscription = new Subscription( $database );
				$this->focusSubscription->load( 0 );
				$this->focusSubscription->createNew( $this->userid, $processor, 0, $plan_params['make_primary'] );
				$this->hasSubscription = 1;
			}
		}
	}

	function moveFocus( $subscrid )
	{
		global $database;

		$subscription = new Subscription( $database );
		$subscription->load( $subscrid );

		// If Subscription exists, move the focus to that one
		if ( $subscription->id ) {
			if ( $subscription->userid == $this->userid ) {
				$this->focusSubscription = $subscription;
				return true;
			} else {
				// This subscription does not belong to the user!
				return false;
			}
		} else {
			// This subscription does not exist
			return false;
		}
	}

	function loadSubscriptions()
	{
		global $database;

		// Get all the users subscriptions
		$query = 'SELECT id'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `userid` = \'' . (int) $this->userid . '\''
				;
		$database->setQuery( $query );
		$subscrids = $database->loadResultArray();

		if ( count( $subscrids ) > 1 ) {
			$this->allSubscriptions = array();

			foreach ( $subscrids as $subscrid ) {
			$subscription = new Subscription( $database );
			$subscription->load( $subscrid );

			$this->allSubscriptions[] = $subscription;
			}

			return true;
		} else {
			// There is only the one that is probably already loaded
			$this->allSubscriptions = false;
			return false;
		}
	}

	function instantGIDchange( $gid )
	{
		global $database, $acl;

		// Always protect last administrator
		if ( $this->cmsUser->gid >= 24 ) {
			$query = 'SELECT count(*)'
					. ' FROM #__core_acl_groups_aro_map'
					. ' WHERE `group_id` = \'25\''
					;
			$database->setQuery( $query );
			if ( $database->loadResult() <= 1) {
				return false;
			}

			$query = 'SELECT count(*)'
					. ' FROM #__core_acl_groups_aro_map'
					. ' WHERE `group_id` = \'24\''
					;
			$database->setQuery( $query );
			if ( $database->loadResult() <= 1) {
				return false;
			}
		}

		// Get ARO ID for user
		$query = 'SELECT `aro_id`'
				. ' FROM #__core_acl_aro'
				. ' WHERE `value` = \'' . (int) $this->userid . '\''
				;
		$database->setQuery( $query );
		$aro_id = $database->loadResult();

		// Carry out ARO ID -> ACL group mapping
		$query = 'UPDATE #__core_acl_groups_aro_map'
				. ' SET `group_id` = \'' . (int) $gid . '\''
				. ' WHERE `aro_id` = \'' . $aro_id . '\''
				;
		$database->setQuery( $query );
		$database->query() or die( $database->stderr() );

		// Moxie Mod - updated to add usertype to users table and update session table for immediate access to usertype features
		$gid_name = $acl->get_group_name( $gid, 'ARO' );

		// Set GID and usertype
		$query = 'UPDATE #__users'
				. ' SET `gid` = \'' .  (int) $gid . '\', `usertype` = \'' . $gid_name . '\''
				. ' WHERE `id` = \''  . (int) $this->userid . '\''
				;
		$database->setQuery( $query );
		$database->query() or die( $database->stderr() );

		// Update Session
		$query = 'UPDATE #__session'
				. ' SET `usertype` = \'' . $gid_name . '\''
				. ' WHERE `userid` = \'' . (int) $this->userid . '\''
				;
		$database->setQuery( $query );
		$database->query() or die( $database->stderr() );
	}

	function loadCBuser()
	{
		global $database;

		$query = 'SELECT `name`'
				. ' FROM #__comprofiler_fields'
				. ' WHERE `table` != \'#__users\''
				. ' AND `name` != \'NA\'';
		$database->setQuery( $query );
		$fields = $database->loadResultArray();

		$query = 'SELECT cbactivation' . ( !empty( $fields ) ? ', ' . implode( ', ', $fields ) : '')
				. ' FROM #__comprofiler'
				. ' WHERE `user_id` = \'' . (int) $this->userid . '\'';
		$database->setQuery( $query );
		$database->loadObject( $this->cbUser );

		if ( is_object( $this->cbUser ) ) {
			$this->hasCBprofile = true;
		} else {
			$this->hasCBprofile = false;
		}
	}

	function CustomRestrictionResponse( $restrictions )
	{
		$s = array();
		$n = 0;
		foreach ( $restrictions as $restriction ) {
			$check1 = AECToolbox::rewriteEngine( $restriction[0], $this );
			$check2 = AECToolbox::rewriteEngine( $restriction[2], $this );

			switch ( $restriction[1] ) {
				case '=':
					$status = (bool) ( $check1 == $check2 );
					break;
				case '<>':
					$status = (bool) ( $check1 != $check2 );
					break;
				case '<=':
					$status = (bool) ( $check1 <= $check2 );
					break;
				case '>=':
					$status = (bool) ( $check1 >= $check2 );
					break;
				case '>':
					$status = (bool) ( $check1 > $check2 );
					break;
				case '<':
					$status = (bool) ( $check1 < $check2 );
					break;
			}

			$s['customchecker'.$n] = $status;
			$n++;
		}

		return $s;
	}

	function permissionResponse( $restrictions )
	{
		if ( is_array( $restrictions ) ) {
			$return = array();
			foreach ( $restrictions as $name => $value ) {
				// TODO: Tautological && ?
				if ( !is_null( $value ) && !( $value === "" ) ) {
					// Switch flag for inverted call
					if ( strpos( $name, '_excluded' ) !== false ) {
						$invert = true;
						$name = str_replace( '_excluded', '', $name );
					} else {
						$invert = false;
					}

					// Convert values to array or explode to array if none
					if ( !is_array( $value ) ) {
						if ( strpos( $value, ';' ) !== false ) {
							$check = explode( ';', $value );
						} else {
							$check = array( (int) $value );
						}
					} else {
						$check = $value;
					}

					$status = false;

					switch ( $name ) {
						// Check for set userid
						case 'userid':
							if ( is_object( $this->cmsUser ) ) {
								if ( $this->cmsUser->id === $value ) {
									$status = true;
								}
							}
							break;
						// Check for a certain GID
						case 'fixgid':
							if ( is_object( $this->cmsUser ) ) {
								if ( (int) $value === (int) $this->cmsUser->gid ) {
									$status = true;
								}
							}
							break;
						// Check for Minimum GID
						case 'mingid':
							if ( is_object( $this->cmsUser ) ) {
								$groups = GeneralInfoRequester::getLowerACLGroup( (int) $this->cmsUser->gid );
								if ( in_array( (int) $value, (array) $groups ) ) {
									$status = true;
								}
							}
							break;
						// Check for Maximum GID
						case 'maxgid':
							if ( is_object( $this->cmsUser ) ) {
								$groups = GeneralInfoRequester::getLowerACLGroup( $value );
								if ( in_array( (int) $this->cmsUser->gid, (array) $groups) ) {
									$status = true;
								}
							} else {
								// New user, so will always pass a max GID test
								$status = true;
							}
							break;
						// Check whether the user is currently in the right plan
						case 'plan_present':
							if ( $this->hasSubscription ) {
								if ( in_array( (int) $this->focusSubscription->plan, $check ) ) {
									$status = true;
								}
							} else {
								if ( in_array( 0, $check ) ) {
									// "None" chosen, so will always pass if new user
									$status = true;
								}
							}
							break;
						// Check whether the user was in the correct plan before
						case 'plan_previous':
							if ( $this->hasSubscription ) {
								if (
									( in_array( (int) $this->focusSubscription->previous_plan, $check ) )
									|| ( ( in_array( 0, $check ) ) && is_null( $this->focusSubscription->previous_plan ) )
									) {
									$status = true;
								}
							} else {
								if ( in_array( 0, $check ) ) {
									// "None" chosen, so will always pass if new user
									$status = true;
								}
							}
							break;
						// Check whether the user has used the right plan before
						case 'plan_overall':
							if ( $this->hasSubscription ) {
								$array = $this->focusSubscription->getUsedPlans();
								foreach ( $check as $v ) {
									if ( ( !empty( $array[(int) $v] ) || ( $this->focusSubscription->plan == $v ) ) ) {
										$status = true;
									}
								}
							} else {
								if ( in_array( 0, $check ) ) {
									// "None" chosen, so will always pass if new user
									$status = true;
								}
							}
							break;
						// Check whether the user has used the plan at least a certain number of times
						case 'plan_amount_min':
							if ( $this->hasSubscription ) {
								$usage = $this->focusSubscription->getUsedPlans();
								$check = explode( ',', $value );
								if ( isset( $usage[(int) $check[0]] ) ) {
									// We have to add one here if the user is currently in the plan
									if ( (int) $this->focusSubscription->plan === (int) $check[0] ) {
										$used_times = (int) $check[1] + 1;
									} else {
										$used_times = (int) $check[1];
									}

									if ( $usage[(int) $check[0]] >= (int) $used_times ) {
										$status = true;
									}
								}
							}
							break;
						// Check whether the user has used the plan at max a certain number of times
						case 'plan_amount_max':
							if ( $this->hasSubscription ) {
								$usage = $this->focusSubscription->getUsedPlans();
								$check = explode( ',', $value );
								if ( isset( $usage[(int) $check[0]] ) ) {
									// We have to add one here if the user is currently in the plan
									if ( (int) $this->focusSubscription->plan === (int) $check[0] ) {
										$used_times = (int) $check[1] + 1;
									} else {
										$used_times = (int) $check[1];
									}

									if ( $usage[(int) $check[0]] <= (int) $used_times ) {
										$status = true;
									}
								} else {
									$status = true;
								}
							} else {
								// New user will always pass max plan amount test
								$status = true;
							}
							break;
					}
				}

				// Swap if inverted and reestablish name
				if ( $invert ) {
					$name .= '_excluded';
					$return[$name] = !$status;
				} else {
					$return[$name] = $status;
				}
			}
			return $return;
		} else {
			return false;
		}
	}

	function usedCoupon ( $couponid, $type )
	{
		global $database;

		$query = 'SELECT `usecount`'
				. ' FROM #__acctexp_couponsxuser'
				. ' WHERE `userid` = \'' . $this->userid . '\''
				. ' AND `coupon_id` = \'' . $couponid . '\''
				. ' AND `type` = \'' . $type . '\''
				;
		$database->setQuery( $query );
		$usecount = $database->loadResult();

		if ( $usecount ) {
			return $usecount;
		} else {
			return false;
		}
	}
}

class Config_General extends paramDBTable
{
	/** @var int Primary key */
	var $id 				= null;
	/** @var text */
	var $settings 			= null;

	function Config_General( &$db )
	{
		$this->mosDBTable( '#__acctexp_config', 'id', $db );

		$this->load(1);

		// If we have no settings, init them
		if ( empty( $this->settings ) ) {
			$this->initParams();
			$this->cfg = $this->getParams( 'settings' );
		} else {
			$this->cfg = $this->getParams( 'settings' );
		}
	}

	function initParams()
	{
		$def = array();
		$def['require_subscription']				= 0;
		$def['alertlevel2']							= 7;
		$def['alertlevel1']							= 3;
		$def['expiration_cushion']					= 12;
		$def['heartbeat_cycle']						= 24;
		$def['heartbeat_cycle_backend']				= 1;
		$def['plans_first']							= 0;
		$def['simpleurls']							= 0;
		$def['display_date_frontend']				= "%a, %d %b %Y %T %Z";
		$def['display_date_backend']				= "%a, %d %b %Y %T %Z";
		$def['enable_mimeta']						= 0;
		$def['enable_coupons']						= 0;
		$def['gwlist']								= '';
		$def['milist']								= "mi_email;mi_htaccess;mi_mysql_query;mi_email;mi_virtuemart";
		$def['displayccinfo']						= 1;
		$def['customtext_confirm_keeporiginal']		= 1;
		$def['customtext_checkout_keeporiginal']	= 1;
		$def['customtext_notallowed_keeporiginal']	= 1;
		$def['customtext_pending_keeporiginal']		= 1;
		$def['customtext_expired_keeporiginal']		= 1;
		// new 0.12.4
		$def['bypassintegration']					= '';
		$def['customintro']							= '';
		$def['customthanks']						= '';
		$def['customcancel']						= '';
		$def['customnotallowed']					= '';
		$def['tos']									= '';
		$def['customtext_plans']					= '';
		$def['customtext_confirm']					= '';
		$def['customtext_checkout']					= '';
		$def['customtext_notallowed']				= '';
		$def['customtext_pending']					= '';
		$def['customtext_expired']					= '';
		// new 0.12.4.2
		$def['adminaccess']							= 1;
		$def['noemails']							= 0;
		$def['nojoomlaregemails']					= 0;
		// new 0.12.4.10
		$def['debugmode']							= 0;
		// new 0.12.4.12
		$def['override_reqssl']						= 0;
		// new 0.12.4.16
		$def['invoicenum_display_id']				= 0;
		$def['invoicenum_display_idinflate']		= '';
		$def['invoicenum_display_case']				= 0;
		$def['invoicenum_display_chunking']			= 4;
		$def['invoicenum_display_separator']		= '-';
		$def['use_recaptcha']						= 0;
		$def['recaptcha_privatekey']				= '';
		$def['recaptcha_publickey']					= '';
		$def['ssl_signup']							= 0;
		$def['error_notification_level']			= 32;
		$def['email_notification_level']			= 512;
		$def['temp_auth_exp']						= 60;
		$def['skip_confirmation']					= 0;
		$def['show_fixeddecision']					= 0;

		// Insert a new entry if there is none yet
		if ( empty( $this->settings ) ) {
			global $database;

			$query = 'SELECT * FROM #__acctexp_config'
			. ' WHERE `id` = \'1\''
			;
			$database->setQuery( $query );

			if ( !$database->loadResult() ) {
				$query = 'INSERT INTO #__acctexp_config'
				. ' VALUES( \'1\', \'\' )'
				;
				$database->setQuery( $query );
				$database->query() or die( $database->stderr() );
			}
		}

		// Write to Params, do not overwrite existing data
		$this->addParams( $def, 'settings', false );

		// Temporarily unset this array as there is no database field called cfg
		unset( $this->cfg );

		$this->check();
		$this->store();

		// Reload Settings
		$this->cfg = $this->getParams( 'settings' );

		return true;
	}

	function saveSettings()
	{
		// Extra check for duplicated rows
		// TODO: Sometime in the future, this can be abandoned
		if ( $this->RowDuplicationCheck() ) {
			$this->CleanDuplicatedRows();
			$this->load(1);
		}

		$this->setParams( $this->cfg, 'settings' );

		unset( $this->cfg );

		$this->check();
		$this->store();

		// Reload Settings
		$this->cfg = $this->getParams( 'settings' );
	}

	function RowDuplicationCheck()
	{
		global $database;

		$query = 'SELECT count(*)'
				. ' FROM #__acctexp_config'
				;
		$database->setQuery( $query );
		$rows = $database->loadResult();

		if ( $rows > 1 ) {
			return true;
		} else {
			return false;
		}
	}

	function CleanDuplicatedRows()
	{
		global $database;

		$query = 'SELECT max(id)'
				. ' FROM #__acctexp_config'
				;
		$database->setQuery( $query );
		$database->query();
		$max = $database->loadResult();

		$query = 'DELETE'
				. ' FROM #__acctexp_config'
				. ' WHERE `id` != \'' . $max . '\''
				;
		$database->setQuery( $query );
		$database->query();

		if ( !( $max == 1 ) ) {
			$query = 'UPDATE #__acctexp_config'
					. ' SET `id` = \'1\''
					. ' WHERE `id` =\'' . $max . '\''
					;
			$database->setQuery( $query );
			$database->query();
		}
	}
}

if ( !is_object( $aecConfig ) ) {
	global $database;

	$aecConfig = new Config_General( $database );
}

class aecHeartbeat extends mosDBTable
{
 	/** @var int Primary key */
	var $id				= null;
 	/** @var datetime */
	var $last_beat 		= null;

	/**
	 * @param database A database connector object
	 */
	function aecHeartbeat( &$db )
	{
	 	$this->mosDBTable( '#__acctexp_heartbeat', 'id', $db );
	 	$this->load(1);
	}

	function frontendping()
	{
		global $database, $aecConfig;

		if ( !is_null( $aecConfig->cfg['heartbeat_cycle'] ) || ($aecConfig->cfg['heartbeat_cycle'] == 0) ) {
			$this->ping( $aecConfig->cfg['heartbeat_cycle'] );
		}
	}

	function backendping()
	{
		global $database, $aecConfig;

		if ( !is_null( $aecConfig->cfg['heartbeat_cycle_backend'] ) || !($aecConfig->cfg['heartbeat_cycle_backend'] == 0) ) {
			$this->ping( $aecConfig->cfg['heartbeat_cycle_backend'] );
		}
	}

	function ping( $configCycle )
	{
		global $mainframe, $mosConfig_offset_user;

		if ( $this->last_beat ) {
			$ping	= strtotime( $this->last_beat ) + $configCycle*3600;
		} else {
			$ping = 0;
		}

		if ( ( $ping - (time() + $mosConfig_offset_user*3600) ) <= 0 ) {
			$this->last_beat = date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );
			$this->check();
			$this->store();

			$this->beat();
		} else {
			// sleep, mechanical Hound, but do not sleep / kept awake with wolves teeth
		}
	}

	function beat()
	{
		global $database, $aecConfig;
		// Other ideas: Clean out old Coupons

		// TODO: function to clean up database before doing the checks - could improve performance
		// maybe just set a database flag for this, so that database cleanup is done only every X days

		// Receive maximum pre expiration time
		$query = 'SELECT MAX(pre_exp_check)'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `active` = \'1\''
				;
		$database->setQuery( $query );
		$pre_expiration = $database->loadResult();

		if ( $pre_expiration ) {
			// pre-expiration found, search limit set to the maximum pre-expiration time
			$expiration_limit = AECToolbox::computeExpiration( ( $pre_expiration + 1 ), 'D', time() );
		} else {
			// No pre-expiration actions found, limiting search to all users who expire until tomorrow (just to be safe)
			$pre_expiration		= false;
			$expiration_limit	= AECToolbox::computeExpiration( 1, 'D', time() );
		}

		// Select all the users that are Active and have an expiration date
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `expiration` <= \'' . $expiration_limit . '\''
				. ' AND `status` != \'Expired\''
				. ' AND `status` != \'Closed\''
				. ' AND `status` != \'Excluded\''
				. ' ORDER BY `expiration`'
				;
		$database->setQuery( $query );
		$subscription_list = $database->loadResultArray();

		$expired_users		= array();
		$pre_expired_users	= array();
		$found_expired		= 1;
		$e					= 0;
		$pe					= 0;
		$exp_actions		= 0;
		$exp_users			= 0;
		$pps				= array();

		// Efficient way to check for expired users without checking on each one
		if ( !empty( $subscription_list ) ) {
			foreach ( $subscription_list as $sub_id ) {
				$subscription = new Subscription($database);
				$subscription->load( $sub_id );

				if ( $found_expired ) {
					// Check whether this user really is expired
					// If this check fails, this user and all following users will be put into pre expiration check
					$found_expired = $subscription->is_expired();

					if ( $found_expired && !in_array( $subscription->userid, $expired_users ) ) {
						// We may need to carry out processor functions
						if ( !isset( $pps[$subscription->type] ) ) {
							// Load payment processor into overall array
							$pps[$subscription->type] = new PaymentProcessor();
							if ( $pps[$subscription->type]->loadName( $subscription->type ) ) {
								$pps[$subscription->type]->init();

								// Load prepare validation function
								$prepval = $pps[$subscription->type]->prepareValidation( $subscription_list );
								if ( $prepval === null ) {
									// This Processor has no such function, set to false to ignore later calls
									$pps[$subscription->type] = false;
								} elseif ( $prepval === false ) {
									// Break - we have a problem with one processor
									$eventlog = new eventLog( $database );
									$eventlog->issue( 'heartbeat failed - processor', 'heartbeat, failure,'.$subscription->type, 'The payment processor failed to respond to validation request - waiting for next turn', 128 );
									return;
								}
							} else {
								// Processor does not exist
								$pps[$subscription->type] = false;
							}
						}

						// Carry out validation if possible
						if ( !empty( $pps[$subscription->type] ) ) {
							$validation = $pps[$subscription->type]->validateSubscription( $sub_id, $subscription_list );
						} else {
							$validation = false;
						}

						// Validation failed or was not possible for this processor - expire
						if ( empty( $validation ) ) {
							if ( $subscription->expire() ) {
								$e++;
							}
						} else {

						}
					}
				}

				// If we have found all expired users, put all others into pre expiration
				if ( !$found_expired && !in_array( $subscription->userid, $pre_expired_users ) ) {
					if ( $pre_expiration ) {
						$pre_expired_users[] = $subscription->userid;
					}
				}
			}

			// Only go for pre expiration action if we have at least one user for it
			if ( $pre_expiration && !empty( $pre_expired_users ) ) {
				// Get all the MIs which have a pre expiration check
				$query = 'SELECT `id`'
						. ' FROM #__acctexp_microintegrations'
						. ' WHERE `pre_exp_check` > \'0\''
						;
				$database->setQuery( $query );
				$mi_pexp = $database->loadResultArray();

				// Get all the plans which have MIs
				$query = 'SELECT `id`'
						. ' FROM #__acctexp_plans'
						. ' WHERE `micro_integrations` != \'\''
						;
				$database->setQuery( $query );
				$plans_mi = $database->loadResultArray();

				// Filter out plans which have not got the right MIs applied
				$expmi_plans = array();
				foreach ( $plans_mi as $plan_id ) {
					$query = 'SELECT `micro_integrations`'
							. ' FROM #__acctexp_plans'
							. ' WHERE `id` = \'' . $plan_id . '\''
							;
					$database->setQuery( $query );
					$plan_mis = explode( ';', $database->loadResult() );
					$pexp_mis = array_intersect( $plan_mis, $mi_pexp );

					if ( count( $pexp_mis ) ) {
						$expmi_plans[] = $plan_id;
					}
				}

				// Filter out the users which dont have the correct plan
				$query = 'SELECT `id`, `userid`'
						. ' FROM #__acctexp_subscr'
						. ' WHERE `userid` IN (' . implode( ',', $pre_expired_users ) . ')'
						. ' AND `plan` IN (' . implode( ',', $expmi_plans) . ')'
						;
				$database->setQuery( $query );
				$user_list = $database->loadObjectList();

				foreach ( $user_list as $usid ) {
					$metaUser = new metaUser( $usid->userid );
					$metaUser->moveFocus( $usid->id );

					// Two double checks here, just to be sure
					if ( !( strcmp( $metaUser->focusSubscription->status, 'Expired' ) === 0 ) && !$metaUser->focusSubscription->recurring ) {
						if ( in_array( $metaUser->focusSubscription->plan, $expmi_plans ) ) {
							// Its ok - load the plan
							$subscription_plan = new SubscriptionPlan( $database );
							$subscription_plan->load( $metaUser->focusSubscription->plan );
							$userplan_mis = explode( ';', $subscription_plan->micro_integrations );

							// Get the right MIs
							$user_pexpmis = array_intersect( $userplan_mis, $mi_pexp );

							// loop through MIs and apply pre exp action
							$check_actions = $exp_actions;

							foreach ( $user_pexpmis as $mi_id ) {
								$mi = new microIntegration( $database );
								$mi->load( $mi_id );

								if ( $mi->callIntegration() ) {
									// Do the actual pre expiration check on this MI
									if ( $metaUser->focusSubscription->is_expired( $mi->pre_exp_check ) ) {
										$result = $mi->pre_expiration_action( $metaUser, $subscription_plan );
										if ( $result ) {
											$exp_actions++;
										}
									}
								}
							}

							if ( $exp_actions > $check_actions ) {
								$exp_users++;
							}
						}
					}
				}
			}
		}

		$short	= _AEC_LOG_SH_HEARTBEAT;
		$event	= _AEC_LOG_LO_HEARTBEAT . ' ';
		$tags	= array( 'heartbeat' );

		if ( $e ) {
			if ( $e > 1 ) {
				$event .= 'Expires ' . $e . ' users';
			} else {
				$event .= 'Expires 1 user';
			}
			$tags[] = 'expiration';
			if ( $exp_actions ) {
				$event .= ', ';
			}
		}
		if ( $exp_actions ) {
			$event .= $exp_actions . ' Pre-expiration action';
			$event .= ( $exp_actions > 1 ) ? 's' : '';
			$event .= ' for ' . $exp_users . ' user';
			$event .= ( $exp_users > 1 ) ? 's' : '';
			$tags[] = 'pre-expiration';
		}

		if ( strcmp( _AEC_LOG_LO_HEARTBEAT . ' ', $event ) === 0 ) {
			$event .= _AEC_LOG_AD_HEARTBEAT_DO_NOTHING;
		}

		$eventlog = new eventLog( $database );
		$eventlog->issue( $short, implode( ',', $tags ), $event, 2 );

	}

}

class displayPipelineHandler
{
	function displayPipelineHandler()
	{

	}

	function getUserPipelineEvents( $userid )
	{
		global $database, $mosConfig_offset_user;

		// Entries for this user only
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_displaypipeline'
				. ' WHERE `userid` = \'' . $userid . '\' AND `only_user` = \'1\''
				;
		$database->setQuery( $query );
		$events = $database->loadResultArray();

		// Entries for all users
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_displaypipeline'
				. ' WHERE `only_user` = \'0\''
				;
		$database->setQuery( $query );
		$events = array_merge( $events, $database->loadResultArray() );

		$return = '';
		if ( empty( $events ) ) {
			return $return;
		}

		foreach ( $events as $eventid ) {
			$displayPipeline = new displayPipeline( $database );
			$displayPipeline->load( $eventid );
			if ( $displayPipeline->id ) {

				// If expire & expired -> delete
				if ( $displayPipeline->expire ) {
					$expstamp = strtotime( $displayPipeline->expstamp );
					if ( ( $expstamp - ( time() + $mosConfig_offset_user*3600 ) ) < 0 ) {
						$displayPipeline->delete();
						continue;
					}
				}

				// If displaymax exceeded -> delete
				$displayremain = $displayPipeline->displaymax - $displayPipeline->displaycount;
				if ( $displayremain <= 0 ) {
					$displayPipeline->delete();
					continue;
				}

				// If this can only be displayed once per user, prevent it from being displayed again
				if ( $displayPipeline->once_per_user ) {
					$params = $displayPipeline->getParams();

					if ( isset( $params['displayedto'] ) ) {
						$users = explode( ';', $params['displayedto'] );
						if ( in_array( $userid, $users ) ) {
							continue;
						} else {
							$users[] = $userid;
							$params['displayedto'] = implode( ';', $users );
							$displayPipeline->setParams( $params );
						}
					}
				}

				// Ok, now append text
				$return .= stripslashes( $displayPipeline->displaytext );

				// Update display if at least one display would remain
				if ( $displayremain > 1 ) {
					$displayPipeline->displaycount = $displayPipeline->displaycount + 1;
					$displayPipeline->check();
					$displayPipeline->store();
				} else {
					$displayPipeline->delete();
				}
			}
		}

		// Return the string
		return $return;
	}
}

class displayPipeline extends paramDBTable
{
	/** @var int Primary key */
	var $id				= null;
	/** @var int */
	var $userid			= null;
	/** @var int */
	var $only_user		= null;
	/** @var int */
	var $once_per_user	= null;
	/** @var datetime */
	var $timestamp		= null;
	/** @var int */
	var $expire			= null;
 	/** @var datetime */
	var $expstamp 		= null;
 	/** @var int */
	var $displaycount	= null;
	/** @var int */
	var $displaymax		= null;
	/** @var text */
	var $displaytext	= null;
	/** @var text */
	var $params			= null;

	/**
	 * @param database A database connector object
	 */
	function displayPipeline( &$db )
	{
	 	$this->mosDBTable( '#__acctexp_displaypipeline', 'id', $db );
	}

	function create( $userid, $only_user, $once_per_user, $expire, $expiration, $displaymax, $displaytext, $params=null )
	{
		global $mosConfig_offset_user;

		$this->id				= 0;
		$this->userid			= $userid;
		$this->only_user		= $only_user;
		$this->once_per_user	= $once_per_user;
		$this->timestamp		= gmstrftime ( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );
		$this->expire			= $expire;
		$this->expstamp			= gmstrftime ( '%Y-%m-%d %H:%M:%S', strtotime( $expiration ) );
		$this->displaycount		= 0;
		$this->displaymax		= $displaymax;

		if ( !get_magic_quotes_gpc() ) {
			$this->displaytext	= addslashes( $displaytext );
		} else {
			$this->displaytext	= $displaytext;
		}

		if ( is_array( $params ) ) {
			$this->setParams( $params );
		}

		$this->check();

		if ( $this->store() ) {
			return true;
		} else {
			return false;
		}
	}
}


class eventLog extends paramDBTable
{
	/** @var int Primary key */
	var $id			= null;
	/** @var datetime */
	var $datetime	= null;
	/** @var string */
	var $short 		= null;
	/** @var text */
	var $tags 		= null;
	/** @var text */
	var $event 		= null;
	/** @var int */
	var $level		= null;
	/** @var int */
	var $notify		= null;
	/** @var text */
	var $params		= null;

	/**
	 * @param database A database connector object
	 */
	function eventLog( &$db )
	{
	 	$this->mosDBTable( '#__acctexp_eventlog', 'id', $db );
	}

	function issue( $short, $tags, $text, $level = 2, $params = null, $force_notify = 0, $force_email = 0 )
	{
		global $mosConfig_offset_user, $aecConfig;

		$legal_levels = array( 2, 8, 32, 128 );

		if ( !in_array( $level, $legal_levels ) ) {
			$levels = $legal_levels[0];
		}

		$this->datetime	= gmstrftime ( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );
		$this->short	= $short;
		$this->tags		= $tags;
		$this->event	= $text;
		$this->level	= (int) $level;

		// Create a notification link if this matches the desired level
		if ( $this->level >= $aecConfig->cfg['error_notification_level'] ) {
			$this->notify	= 1;
		} else {
			$this->notify	= $force_notify ? 1 : 0;
		}

		// Mail out notification to all admins if this matches the desired level
		if ( ( $this->level >= $aecConfig->cfg['email_notification_level'] ) || $force_email ) {
			global $mainframe, $database;

			// check if Global Config `mailfrom` and `fromname` values exist
			if ( $mainframe->getCfg( 'mailfrom' ) != '' && $mainframe->getCfg( 'fromname' ) != '' ) {
				$adminName2 	= $mainframe->getCfg( 'fromname' );
				$adminEmail2 	= $mainframe->getCfg( 'mailfrom' );
			} else {
				// use email address and name of first superadmin for use in email sent to user
				$query = 'SELECT `name`, `email`'
						. ' FROM #__users'
						. ' WHERE LOWER( usertype ) = \'superadministrator\''
						. ' OR LOWER( usertype ) = \'super administrator\''
						;
				$database->setQuery( $query );
				$rows = $database->loadObjectList();

				$adminName2 	= $rows[0]->name;
				$adminEmail2 	= $rows[0]->email;
			}

			// Send notification to all administrators
			$subject2	= sprintf( _AEC_ASEND_NOTICE, constant( "_AEC_NOTICE_NUMBER_" . $this->level ), $this->short, $mainframe->getCfg( 'sitename' ) );
			$message2	= sprintf( _AEC_ASEND_NOTICE_MSG, $this->event  );

			$subject2	= html_entity_decode( $subject2, ENT_QUOTES );
			$message2	= html_entity_decode( $message2, ENT_QUOTES );

			// get email addresses of all admins and superadmins set to recieve system emails
			$query = 'SELECT `email`'
					. ' FROM #__users'
					. ' WHERE ( `gid` = 24 OR `gid` = 25 )'
					. ' AND `sendEmail` = 1'
					. ' AND `block` = 0'
					;
			$database->setQuery( $query );
			$admins = $database->loadObjectList();

			foreach ( $admins as $admin ) {
				// send email to admin & super admin set to recieve system emails
				mosMail( $adminEmail2, $adminName2, $admin->email, $subject2, $message2 );
			}
		}

		if ( !empty( $params ) && is_array( $params ) ) {
			$this->setParams( $params );
		}

		$this->check();
		$this->store();
	}

}

class PaymentProcessorHandler
{

	function PaymentProcessorHandler()
	{
		global $mosConfig_absolute_path;
		$this->pp_dir = $mosConfig_absolute_path . '/components/com_acctexp/processors';
	}

	function getProcessorList()
	{
		$list = AECToolbox::getFileArray( $this->pp_dir, 'php', false, true );

		$pp_list = array();
		foreach ( $list as $name ) {
			$parts		= explode( '.', $name );
			$pp_list[] = $parts[0];
		}

		return $pp_list;
	}

	function getProcessorIdfromName( $name )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_config_processors'
				. ' WHERE `name` = \'' . $name . '\'';
		$database->setQuery( $query );

		return $database->loadResult();
	}

	/**
	 * gets installed and active processors
	 *
	 * @param bool	$active		get only active objects
	 * @return array of (active) payment processors
	 */
	function getInstalledObjectList( $active = false )
	{
		global $database;

		$query = 'SELECT `id`, `active`, `name`'
				. ' FROM #__acctexp_config_processors'
				;
		if ( $active ) {
			$query .= ' WHERE `active` = \'1\'';
		}
		$database->setQuery( $query );

		return $database->loadObjectList();
	}

	function getInstalledNameList($active=false)
	{
		global $database;

		$query = 'SELECT `name`'
				. ' FROM #__acctexp_config_processors'
				;
		if ( $active !== false ) {
			$query .= ' WHERE `active` = \'' . $active . '\'';
		}
		$database->setQuery( $query );

		return $database->loadResultArray();
	}

	function processorReply( $url, $reply, $get = 0 )
	{
		$fp = null;
		// try to use fsockopen. some hosting systems disable fsockopen (godaddy.com)
		$fp = $this->doTheHttp( $url, $reply, $get );
		if ( !$fp ) {
			// If fsockopen doesn't work try using curl
			$fp = $this->doTheCurl( $url, $reply );
		}

		return $fp;
	}

	function doTheCurl( $url, $req )
	{
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER,	FALSE );
		curl_setopt( $ch, CURLOPT_URL,				$url );
		curl_setopt( $ch, CURLOPT_POST,				1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS,		$req );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,	1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT,			120 );
		$fp = curl_exec( $ch );
		curl_close( $ch );

		return $fp;
	}

	function doTheHttp( $url, $req, $get)
	{
		$header  = ''
		. 'POST https://' . $url . '/cgi-bin/webscr HTTP/1.0' . "\r\n"
		. 'Host: ' . $url  . ':80' . "\r\n"
		. 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
		. 'Content-Length: ' . strlen($req) . "\r\n\r\n"
		;
		$fp = fsockopen( $url, 80, $errno, $errstr, 30 );

		if ( !$fp ) {
			return 'ERROR';
		} else {
			fputs( $fp, $header . $req );
			while ( !feof( $fp ) ) {
				$res = fgets( $fp, 1024 );
				if ( strcmp( $res, 'VERIFIED' ) == 0 ) {
					return 'VERIFIED';
				} elseif ( strcmp( $res, 'INVALID' ) == 0 ) {
					return 'INVALID';
				}
			}
			fclose( $fp );
		}
		return 'ERROR';
	}

}

class PaymentProcessor
{
	/** var object **/
	var $pph = null;
	/** var int **/
	var $id = null;
	/** var string **/
	var $processor_name = null;
	/** var object **/
	var $processor = null;
	/** var array **/
	var $settings = null;
	/** var array **/
	var $info = null;

	function PaymentProcessor()
	{
		// Init Payment Processor Handler
		$this->pph = new PaymentProcessorHandler ();
	}

	function loadName( $name )
	{
		global $database;

		// Set Name
		$this->processor_name = strtolower( $name );

		// See if the processor is installed & set id
		$query = 'SELECT id'
				. ' FROM #__acctexp_config_processors'
				. ' WHERE `name` = \'' . $this->processor_name . '\''
				;
		$database->setQuery( $query );
		$result = $database->loadResult();
		$this->id = $result ? $result : 0;

		$file = $this->pph->pp_dir . '/' . $this->processor_name . '.php';

		// Check whether processor exists
		if ( file_exists( $file ) ) {

			if ( !defined( '_AEC_LANG_PROCESSOR' ) ) {
				$langPath = $this->pph->pp_dir . '/com_acctexp_language_processors/';
				// Include language files for processors
				if ( file_exists( $langPath . $GLOBALS['mosConfig_lang'] . '.php' ) ) {
					include_once( $langPath . $GLOBALS['mosConfig_lang'] . '.php' );
				} else {
					include_once( $langPath . 'english.php' );
				}
			}

			// Call Integration file
			include_once $this->pph->pp_dir . '/' . $this->processor_name . '.php';

			// Initiate Payment Processor Class
			$class_name = 'processor_' . $this->processor_name;
			$this->processor = new $class_name( $database );
			return true;
		} else {
			return false;
		}
	}

	function loadId( $ppid )
	{
		global $database;

		// Fetch name from db and load processor
		$query = 'SELECT `name`'
				. ' FROM #__acctexp_config_processors'
				. ' WHERE `id` = \'' . $ppid . '\''
				;
		$database->setQuery( $query );
		$name = $database->loadResult();
		if ( $name ) {
			return $this->loadName( $name );
		} else {
			return false;
		}
	}

	function fullInit()
	{
		$this->init();
		$this->getInfo();
		$this->getSettings();
	}

	function init()
	{
		global $database;

		if ( !$this->id ) {
			// Install and recurse
			$this->install();
			$this->init();
		} else {
			// Initiate processor from db
			$this->processor->load( $this->id );
		}
	}

	function install()
	{
		global $database;

		// Create new db entry
		$this->processor->load( 0 );

		// Call default values for Info and Settings
		$this->getInfo();
		$this->getSettings();

		// Set name and activate
		$this->processor->name		= $this->processor_name;
		$this->processor->active	= 1;

		// Set values from defaults and store
		$this->processor->setParams( $this->info, 'info' );
		$this->processor->setParams( $this->settings, 'settings' );
		$this->processor->check();
		$this->processor->store();

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_config_processors'
				. ' WHERE `name` = \'' . $this->processor_name . '\''
				;
		$database->setQuery( $query );
		$result = $database->loadResult();

		$this->id = $result ? $result : 0;
	}

	function getInfo()
	{
		$this->info	= $this->processor->getParams( 'info' );
		$original	= $this->processor->info();

		foreach ( $original as $name => $var ) {
			if ( !isset( $this->info[$name] ) ) {
				$this->info[$name] = $var;
			}
		}
	}

	function getSettings()
	{
		$this->settings	= $this->processor->getParams( 'settings' );
		$original		= $this->processor->settings();

		foreach ( $original as $name => $var ) {
			if ( !isset( $this->settings[$name] ) ) {
				$this->settings[$name] = $var;
			}
		}
	}

	function setSettings()
	{
		// Test if values really are an array and write them to db
		if ( is_array( $this->settings ) ) {
			$this->processor->setParams( $this->settings, 'settings' );
			$this->processor->check();
			$this->processor->store();
		}
	}

	function exchangeSettings( $plan, $plan_params=null )
	{
		$this->getSettings();

		if ( empty( $plan_params ) ) {
			$planparams = $plan->getProcessorParameters( $this->id );
		}

		if ( isset( $planparams['aec_overwrite_settings'] ) ) {
			if ( $planparams['aec_overwrite_settings'] ) {
				$this->settings = $this->processor->exchangeSettings( $this->settings, $planparams );
			}
		}
	}

	function setInfo()
	{
		// Test if values really are an array and write them to db
		if ( is_array( $this->info ) ) {
			$this->processor->setParams( $this->info, 'info' );
			$this->processor->check();
			$this->processor->store();
		}
	}

	function getBackendSettings()
	{
		if ( !isset( $this->settings ) ) {
			$this->getSettings();
		}

		return $this->processor->backend_settings( $this->settings );
	}

	function checkoutAction( $int_var, $metaUser, $new_subscription )
	{
		$this->getSettings();

		return $this->processor->checkoutAction( $int_var, $this->settings, $metaUser, $new_subscription );
	}

	function customAction( $action, $invoice, $metaUser )
	{
		$this->getSettings();

		$method = 'customaction_' . $action;

		if ( method_exists( $this->processor, $method ) ) {
			return $this->processor->$method( $this, $this->settings, $invoice, $metaUser );
		} else {
			return false;
		}
	}

	function getParamsHTML( $params, $values )
	{
		$return = false;
		if ( !empty( $values['params'] ) ) {
			if ( is_array( $values['params'] ) ) {
				if ( isset( $values['params']['lists'] ) ) {
					$lists = $values['params']['lists'];
					unset( $values['params']['lists'] );
				} else {
					$lists = null;
				}

				if ( count( $params['params'] ) > 2 ) {
					$table = 1;
					$return .= '<table>';
				} else {
					$table = 0;
				}

				foreach ( $values['params'] as $name => $entry ) {
					if ( !is_null( $name ) && !( $name == '' ) ) {
						$return .= aecHTML::createFormParticle( $name, $entry, $lists, $table ) . "\n";
					}
				}

				$return .= $table ? '</table>' : '';

				unset( $values['params'] );
			}
		}

		return $return;
	}

	function getParams( $params )
	{
		$this->getSettings();

		if ( method_exists( $this->processor, 'Params' ) ) {
			return $this->processor->Params( $this->settings, $params );
		} else {
			return false;
		}
	}

	function getCustomPlanParams()
	{
		$this->getSettings();

		if ( method_exists( $this->processor, 'CustomPlanParams' ) ) {
			return $this->processor->CustomPlanParams( $this->settings );
		} else {
			return false;
		}
	}

	function invoiceCreationAction( $objinvoice )
	{
		$this->getSettings();

		if ( method_exists( $this->processor, 'invoiceCreationAction' ) ) {
			$this->processor->invoiceCreationAction( $this->settings, $objinvoice );
		} else {
			return false;
		}
	}

	function parseNotification( $post )
	{
		$this->getSettings();

		return $this->processor->parseNotification( $post, $this->settings );
	}

	function validateNotification( $response, $post, $invoice )
	{
		if ( method_exists( $this->processor, 'validateNotification' ) ) {
			$response = $this->processor->validateNotification( $response, $post, $this->settings, $invoice );
		}

		return $response;
	}

	function prepareValidation( $subscription_list )
	{
		$this->getSettings();

		if ( method_exists( $this->processor, 'prepareValidation' ) ) {
			$response = $this->processor->prepareValidation( $this->settings, $subscription_list );
		} else {
			$response = null;
		}

		return $response;
	}

	function validateSubscription( $subscription_id )
	{
		$this->getSettings();

		if ( method_exists( $this->processor, 'validateSubscription' ) ) {
			$response = $this->processor->validateSubscription( $this->settings, $subscription_id );
		} else {
			$response = false;
		}

		return $response;
	}

}

class processor extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $name				= null;
	/** @var int */
	var $active				= null;
	/** @var text */
	var $info				= null;
	/** @var text */
	var $settings			= null;
	/** @var text */
	var $params				= null;

	/**
	* @param database A database connector object
	*/
	function processor( &$db )
	{
		$this->mosDBTable( '#__acctexp_config_processors', 'id', $db );
	}

	function loadName( $name )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_config_processors'
				. ' WHERE `name` = \'' . $name . '\''
				;
		$database->setQuery( $query );
		$this->load( $database->loadResult() );
	}

	function createNew( $name, $info, $settings )
	{
		$this->id		= 0;
		$this->name		= $name;
		$this->active	= 1;
		$this->info		= $info;
		$this->settings	= $settings;

		$this->check();
		$this->store();
	}

	function checkoutAction( $int_var, $settings, $metaUser, $new_subscription )
	{
		return '<p>' . $settings['info'] . '</p>';
	}

	function exchangeSettings( $settings, $planvars )
	{
		 foreach ( $settings as $key => $value ) {
		 	if ( isset( $planvars[$key] ) ) {
				if ( !is_null( $planvars[$key] ) && ( $planvars[$key] != '' ) ) {
		 			if ( strcmp( $planvars[$key], '[[SET_TO_NULL]]' ) === 0 ) {
		 				$settings[$key] = '';
		 			} else {
		 				$settings[$key] = $planvars[$key];
		 			}
				}
		 	}
		 }

		return $settings;
	}
}

class XMLprocessor extends processor
{
	function checkoutAction( $int_var, $settings, $metaUser, $new_subscription )
	{
		global $aecConfig;

		$var = $this->checkoutform( $int_var, $settings, $metaUser, $new_subscription );

		$return = '<form action="' . AECToolbox::deadsureURL( '/index.php?option=com_acctexp&amp;task=checkout', true ) . '" method="post">' . "\n";
		$return .= $this->getParamsHTML( $var ) . '<br /><br />';
		$return .= '<input type="hidden" name="invoice" value="' . $int_var['invoice'] . '" />' . "\n";
		$return .= '<input type="hidden" name="userid" value="' . $metaUser->userid . '" />' . "\n";
		$return .= '<input type="hidden" name="task" value="checkout" />' . "\n";
		$return .= '<input type="submit" class="button" value="' . _BUTTON_CHECKOUT . '" /><br /><br />' . "\n";
		$return .= '</form>' . "\n";

		return $return;
	}

	function getParamsHTML( $params )
	{
		$return = '';
		if ( !empty( $params['params'] ) ) {
			if ( is_array( $params['params'] ) ) {
				if ( isset( $params['params']['lists'] ) ) {
					$lists = $params['params']['lists'];
					unset( $params['params']['lists'] );
				} else {
					$lists = null;
				}

				if ( count( $params['params'] ) > 2 ) {
					$table = 1;
					$return .= '<table>';
				} else {
					$table = 0;
				}

				foreach ( $params['params'] as $name => $entry ) {
					if ( !is_null( $name ) && !( $name == '' ) ) {
						$return .= aecHTML::createFormParticle( $name, $entry, $lists, $table ) . "\n";
					}
				}

				$return .= $table ? '</table>' : '';

				unset( $params['params'] );
			}
		}

		return $return;
	}

	function getCCform( $var=array() )
	{
		// Request the Card number
		$var['params']['cardNumber'] = array( 'inputC', _AEC_CCFORM_CARDNUMBER_NAME, _AEC_CCFORM_CARDNUMBER_NAME, '');

		// Create a selection box with 12 months
		$months = array();
		for( $i = 1; $i < 13; $i++ ){
			$month = str_pad( $i, 2, "0", STR_PAD_LEFT );
			$months[] = mosHTML::makeOption( $month, $month );
		}

		$var['params']['lists']['expirationMonth'] = mosHTML::selectList($months, 'expirationMonth', 'size="1" style="width:50px;"', 'value', 'text', 0);
		$var['params']['expirationMonth'] = array( 'list', _AEC_CCFORM_EXPIRATIONMONTH_NAME, _AEC_CCFORM_EXPIRATIONMONTH_DESC);

		// Create a selection box with the next 10 years
		$year = date('Y');
		$years = array();
		for( $i = $year; $i < $year + 15; $i++ ) {
			$years[] = mosHTML::makeOption( $i, $i );
		}

		$var['params']['lists']['expirationYear'] = mosHTML::selectList($years, 'expirationYear', 'size="1" style="width:70px;"', 'value', 'text', 0);
		$var['params']['expirationYear'] = array( 'list', _AEC_CCFORM_EXPIRATIONYEAR_NAME, _AEC_CCFORM_EXPIRATIONYEAR_DESC);

		return $var;
	}

	function checkoutProcess( $int_var, $pp, $metaUser, $new_subscription, $invoice )
	{
		global $database;

		// Create the xml string
		if ( isset( $int_var['planparams']['aec_overwrite_settings'] ) ) {
			if ( $int_var['planparams']['aec_overwrite_settings'] ) {
				$settings = $this->exchangeSettings( $pp->settings, $int_var['planparams']);
			}
		} else {
			$settings = $pp->settings;
		}

		$xml = $this->createRequestXML( $int_var, $settings, $metaUser, $new_subscription, $invoice );

		// Transmit xml to server
		$response = $this->transmitRequestXML( $xml, $int_var, $settings, $metaUser, $new_subscription, $invoice );

		if ( !empty( $response['error'] ) ) {
			return $response;
		}

		if ( $response != false ) {

			$invoice = new Invoice( $database );
			$invoice->loadInvoiceNumber( $response['invoice'] );

			if ( isset( $response['raw'] ) ) {
				$responsestring = $response['raw'];
				unset( $response['raw'] );
			} else {
				$responsestring = '';
			}

			$invoice->processorResponse( $pp, $response, $responsestring );
		} else {
			return false;
		}
	}

	function transmitRequest( $url, $path, $content, $port=443 )
	{
		$response = null;

		$response = $this->doTheCurl( $url, $content );
		if ( !$response ) {
			// If curl doesn't work try using fsockopen
			$response = $this->doTheHttp( $url, $path, $content, $port );
		}

		return $response;
	}

	function doTheHttp( $url, $path, $content, $port=443 )
	{
		$header  =	"Host: " . $url  . "\r\n"
					. "User-Agent: PHP Script\r\n"
					. "Content-Type: text/xml\r\n"
					. "Content-Length: " . strlen($content) . "\r\n\r\n"
					. "Connection: close\r\n\r\n";
					;
		$connection = fsockopen( $url, $port, $errno, $errstr, 30 );

		if ( !$connection ) {
			return false;
		} else {
			fwrite( $connection, "POST " . $path . " HTTP/1.1\r\n" );
			fwrite( $connection, $header . $content );

			while ( !feof( $connection ) ) {
				$res = fgets( $connection, 1024 );
				if ( strcmp( $res, 'VERIFIED' ) == 0 ) {
					return true;
				} elseif ( strcmp( $res, 'INVALID' ) == 0 ) {
					return false;
				}
			}
			fclose( $connection );
		}
		return false;
	}

	function doTheCurl( $url, $content )
	{
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml") );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $content );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return $response;
	}

}

class POSTprocessor extends processor
{
	function checkoutAction( $int_var, $settings, $metaUser, $new_subscription )
	{
		if ( isset( $int_var['planparams']['aec_overwrite_settings'] ) ) {
			if ( $int_var['planparams']['aec_overwrite_settings'] ) {
				$settings = $this->exchangeSettings( $settings, $int_var['planparams']);
			}
		}

		$var = $this->createGatewayLink( $int_var, $settings, $metaUser, $new_subscription );

		$return = '<form action="' . $var['post_url'] . '" method="post">' . "\n";
		unset( $var['post_url'] );

		foreach ( $var as $key => $value ) {
			$return .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
		}

		$return .= '<input type="submit" class="button" value="' . _BUTTON_CHECKOUT . '" />' . "\n";
		$return .= '</form>' . "\n";

		return $return;
	}
}

class GETprocessor extends processor
{
	function checkoutAction( $int_var, $settings, $metaUser, $new_subscription )
	{
		if ( isset( $int_var['planparams']['aec_overwrite_settings'] ) ) {
			if ( $int_var['planparams']['aec_overwrite_settings'] ) {
				$settings = $this->exchangeSettings( $settings, $int_var['planparams']);
			}
		}

		$var = $this->createGatewayLink( $int_var, $settings, $metaUser, $new_subscription );

		$return = '<form action="' . $var['post_url'] . '" method="get">' . "\n";
		unset( $var['post_url'] );

		foreach ( $var as $key => $value ) {
			$return .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
		}

		$return .= '<input type="submit" class="button" value="' . _BUTTON_CHECKOUT . '" />' . "\n";
		$return .= '</form>' . "\n";

		return $return;
	}
}

class URLprocessor extends processor
{
	function checkoutAction( $int_var, $settings, $metaUser, $new_subscription )
	{
		if ( isset( $int_var['planparams']['aec_overwrite_settings'] ) ) {
			if ( $int_var['planparams']['aec_overwrite_settings'] ) {
				$settings = $this->exchangeSettings( $settings, $int_var['planparams']);
			}
		}

		$var = $this->createGatewayLink( $int_var, $settings, $metaUser, $new_subscription );

		$return = '<a href="' . $var['post_url'];
		unset( $var['post_url'] );

		$vars = array();
		foreach ( $var as $key => $value ) {
			$vars[] .= $key . '=' . $value;
		}

		$return .= implode( '&amp;', $vars );
		$return .= '" >' . _BUTTON_CHECKOUT . '</a>' . "\n";

		return $return;
	}
}

class aecSettings
{

	function aecSettings( $area, $subarea='' )
	{
		$this->area				= $area;
		$this->original_subarea	= $subarea;
		$this->subarea			= $subarea;
	}

	function fullSettingsArray( $params, $params_values, $lists = array(), $settings = array() ) {
		$this->params			= $params;
		$this->params_values	= $params_values;
		$this->lists			= $lists;
		$this->settings			= $settings;

		foreach ( $this->params as $name => $content ) {

			// $content[0] = type
			// $content[1] = value
			// $content[2] = disabled?
			// $content[3] = set name
			// $content[4] = set description

			if ( isset( $this->params_values[$name] ) ) {
				$value = $this->params_values[$name];
			} else {
				if ( isset( $content[3] ) ) {
					$value						= $content[3];
					$this->params_values[$name] = $content[3];
				} elseif ( isset( $content[1] ) && !isset( $content[2] ) ) {
					$value						= $content[1];
					$this->params_values[$name] = $content[1];
				} else {
					$value						= '';
					$this->params_values[$name] = '';
				}
			}

			// Checking for remap functions
			$remap = 'remap_' . $content[0];

			if ( method_exists( $this, $remap ) ) {
				$type = $this->$remap( $name, $value );
			} else {
				$type = $content[0];
			}

			if ( strcmp( $type, 'DEL' ) === 0 ) {
				continue;
			}

			if ( !isset( $content[2] ) || !$content[2] ) {
				// Create constant names
				$constant_generic	= '_' . strtoupper($this->area)
										. '_' . strtoupper( $this->original_subarea )
										. '_' . strtoupper( $name );
				$constant			= '_' . strtoupper( $this->area )
										. '_' . strtoupper( $this->subarea )
										. '_' . strtoupper( $name );
				$constantname		= $constant . '_NAME';
				$constantdesc		= $constant . '_DESC';

				// If the constantname does not exists, try a generic name or insert an error
				if ( defined( $constantname ) ) {
					$info_name = constant( $constantname );
				} else {
					$genericname = $constant_generic . '_NAME';
					if ( defined( $genericname ) ) {
						$info_name = constant( $genericname );
					} else {
						$info_name = sprintf( _AEC_CMN_LANG_CONSTANT_IS_MISSING, $constantname );
					}
				}

				// If the constantname does not exists, try a generic name or insert an error
				if ( defined( $constantdesc ) ) {
					$info_desc = constant( $constantdesc );
				} else {
					$genericdesc = $constant_generic . '_DESC';
					if ( defined( $genericname ) ) {
						$info_desc = constant( $genericdesc );
					} else {
						$info_desc = sprintf( _AEC_CMN_LANG_CONSTANT_IS_MISSING, $constantdesc );
					}
				}
			} else {
				$info_name = $content[1];
				$info_desc = $content[2];
			}

			$this->settings[$name] = array($type, $info_name, $info_desc, $value);
		}
	}

	function remap_subarea_change( $name, $value )
	{
		$this->subarea = $value;
		return 'DEL';
	}

	function remap_list_yesno( $name, $value )
	{
		$this->lists[$name] = mosHTML::yesnoSelectList( $name, '', $value );
		return 'list';
	}

	function remap_list_date( $name, $value )
	{
		// mic: fix wrong name
		$this->lists[$name] = '<input class="text_area" type="text" name="' . $name . '" id="' . $name . '" size="19" maxlength="19" value="' . $value . '"/>'
		.'<input type="reset" name="reset" class="button" onClick="return showCalendar(\'' . $name . '\', \'y-mm-dd\');" value="..." />';
		return 'list';
	}
}

class aecHTML
{

	function aecHTML( $rows, $lists=null )
	{
		//$this->area = $area;
		//$this->fallback = $fallback;

		$this->rows		= $rows;
		$this->lists	= $lists;
	}

	function createSettingsParticle( $name )
	{

		$row	= $this->rows[$name];
		$type	= $row[0];

		if ( isset( $row[2] ) ) {
			if ( isset( $row[3] ) ) {
				$value = $row[3];
			} else {
				$value = '';
			}

			if ( !empty( $row[1] ) && !empty( $row[2] ) ) {
				$return = '<div class="setting_desc">' . $this->ToolTip( $row[2], $row[1] ) . $row[1] . '</div>';
			}
		} else {
			if ( isset( $row[1] ) ) {
				$value = $row[1];
			} else {
				$value = '';
			}
		}

		switch ( $type ) {
			case 'inputA':
				$return .= '<div class="setting_form">';
				$return .= '<input name="' . $name . '" type="text" size="4" value="' . $value . '" />';
				break;
			case 'inputB':
				$return .= '<div class="setting_form">';
				$return .= '<input class="inputbox" type="text" name="' . $name . '" size="8" value="' . $value . '" />';
				$return .= '</div>';
				break;
			case 'inputC':
				$return .= '<div class="setting_form">';
				$return .= '<input type="text" size="20" name="' . $name . '" class="inputbox" value="' . $value . '" />';
				$return .= '</div>';
				break;
			case 'inputD':
				$return .= '<div class="setting_form">';
				$return .= '<textarea cols="50" rows="5" name="' . $name . '" />' . $value . '</textarea>';
				$return .= '</div>';
				break;
			case 'inputE':
				$return .= '<div class="setting_form">';
				$return .= '<textarea style="width:520px" cols="450" rows="1" name="' . $name . '" />' . $value . '</textarea>';
				$return .= '</div>';
				break;
			case 'checkbox':
				$return .= '<div class="setting_form">';
				$return .= '<input type="checkbox" name="' . $name . '" ' . ( $value ? 'checked="checked" ' : '' ) . '/>';
				$return .= '</div>';
				break;
			case 'editor':
				$return = '<p>' . $this->ToolTip( $row[2], $row[1]) . $row[1] . '</p>';
				$return .= '<div class="setting_form">';
				$return .= editorArea( $name, $value, $name, '100%;', '250', '10', '60' );
				$return .= '</div>';
				break;
			case 'list':
				$return .= '<div class="setting_form">';
				$return .= $this->lists[$name];
				$return .= '</div>';
				break;
			case 'fieldset':
				$return = '<div class="setting_form">' . "\n"
				. '<fieldset><legend>' . $row[1] . '</legend>' . "\n"
				. '<table cellpadding="1" cellspacing="1" border="0">' . "\n"
				. '<tr align="left" valign="middle" ><td>' . $row[2] . '</td></tr>' . "\n"
				. '</table>' . "\n"
				. '</fieldset>' . "\n"
				. '</div>'
				;
				break;
		}
		return $return;
	}

	function createFormParticle( $name, $row, $lists, $table=0 )
	{
		$return = '';
		if ( isset( $row[3] ) ) {
			$value = $row[3];
		} else {
			$value = '';
		}

		$return .= $table ? '<tr><td class="cleft">' : '<p>';

		$return .= '<strong>' . $row[1] . ':</strong>';

		$return .= $table ? '</td><td class="cright">' : ' ';

		switch ( $row[0] ) {
			case "inputA":
				$return .= '<input name="' . $name . '" type="text" size="4" maxlength="5" value="' . $value . '"/>';
				break;
			case "inputB":
				$return .= '<input class="inputbox" type="text" name="' . $name . '" size="2" maxlength="10" value="' . $value . '" />';
				break;
			case "inputC":
				$return .= '<input type="text" size="20" name="' . $name . '" class="inputbox" value="' . $value . '" />';
				break;
			case "inputD":
				$return .= '<textarea align="left" cols="60" rows="5" name="' . $name . '" />' . $value . '</textarea>';
				break;
			case "list":
				$return .= $lists[$value ? $value : $name];
				break;
			default:
				$return .= '<' . $row[0] . '>' . $row[2] . '</' . $row[0] . '>';
				break;
		}

		$return .= $table ? '</td></tr>' : '</p>';

		return $return;
	}

	/**
	* Utility function to provide ToolTips
	* @param string ToolTip text
	* @param string Box title
	* @returns HTML code for ToolTip
	*/
	function ToolTip( $tooltip, $title='', $width='', $image='help.png', $text='', $href='#', $link=1 )
	{
		global $mosConfig_live_site;

		if ( $width ) {
			$width = ', WIDTH, \''.$width .'\'';
		}
		if ( $title ) {
			$title = ', CAPTION, \''.$title .'\'';
		}
		if ( !$text ) {
			$image 	= $mosConfig_live_site . '/administrator/components/com_acctexp/images/icons/'. $image;
			$text 	= '<img src="'. $image .'" border="0" alt=""/>';
		}
		$style = 'style="text-decoration: none; color: #586C79;"';
		if ( $href ) {
			$style = '';
		} else{
			$href = '#';
		}

		$mousover = 'return overlib(\''. htmlentities( $tooltip ) .'\''. $title .', BELOW, RIGHT'. $width .');';

		$tip = '';
		if ( $link ) {
			$tip .= '<a href="'. $href .'" onmouseover="'. $mousover .'" onmouseout="return nd();" '. $style .'>'. $text .'</a>';
		} else {
			$tip .= '<span onmouseover="'. $mousover .'" onmouseout="return nd();" '. $style .'>'. $text .'</span>';
		}

		return $tip . '&nbsp;';
	}

	/**
	 * displays an icon
	 * mic: corrected name
	 *
	 * @param 	string	$image	image name
	 * @param	string	$alt	optional alt/title text
	 * @return html string
	 */
	function Icon( $image = 'error.png', $alt = '' )
	{
		global $mosConfig_live_site;

		if ( !$alt ) {
			$name	= explode( '.', $image );
			$alt	= $name[0];
		}
		$image 	= $mosConfig_live_site . '/administrator/components/com_acctexp/images/icons/'. $image;

		return '<img src="'. $image .'" border="0" alt="' . $alt . '" title="' . $alt . '" class="aec_icon" />';
	}

}

class SubscriptionPlanHandler
{
	function getPlanUserlist( $planid )
	{
		global $database;

		$query = 'SELECT `userid`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `plan` = \'' . $planid . '\' AND ( `status` = \'Active\' OR `status` = \'Trial\' ) '
				;
		$database->setQuery( $query );

		return $database->loadResultArray();
	}
}

class PlanGroup extends paramDBTable
{
	/** @var int Primary key */
	var $id 				= null;
	/** @var int */
	var $active				= null;
	/** @var int */
	var $visible			= null;
	/** @var int */
	var $parent				= null;
	/** @var int */
	var $ordering			= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $desc				= null;
	/** @var text */
	var $params 			= null;
	/** @var text */
	var $custom_params		= null;
	/** @var text */
	var $restrictions		= null;

	function PlanGroup( &$db )
	{
		$this->mosDBTable( '#__acctexp_plangroups', 'id', $db );
	}

}

class SubscriptionPlan extends paramDBTable
{
	/** @var int Primary key */
	var $id 				= null;
	/** @var int */
	var $active				= null;
	/** @var int */
	var $visible			= null;
	/** @var int */
	var $ordering			= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $desc				= null;
	/** @var string */
	var $email_desc			= null;
	/** @var text */
	var $params 			= null;
	/** @var text */
	var $custom_params		= null;
	/** @var text */
	var $restrictions		= null;
	/** @var text */
	var $micro_integrations	= null;

	function SubscriptionPlan( &$db )
	{
		$this->mosDBTable( '#__acctexp_plans', 'id', $db );
	}

	function getProperty( $name )
	{
		if ( isset( $this->$name ) ) {
			return stripslashes( $this->$name );
		} else {
			return null;
		}
	}

	function applyPlan( $userid, $processor = 'none', $silent = 0, $multiplicator = 1, $invoice = null )
	{
		global $database, $mainframe, $mosConfig_offset_user, $aecConfig;

		if ( is_int( $multiplicator ) && ( $multiplicator < 1 ) ) {
			$multiplicator = 1;
		}

		if ( $userid ) {
			$metaUser = new metaUser( $userid );

			$params			= $this->getParams();

			if ( is_object( $invoice ) ) {
				$invoice_params	= $invoice->getParams();
			} else {
				$invoice_params	= array();
			}

			if ( !isset( $params['make_primary'] ) ) {
				$params['make_primary'] = 1;
			}

			if ( !$metaUser->hasSubscription || empty( $params['make_primary'] ) ) {
				$metaUser->establishFocus( $this, $processor );
			}

			$comparison		= $this->doPlanComparison( $metaUser->focusSubscription );
			if ( empty( $comparison['renew'] ) ) {
				$renew = 0;
			} else {
				$renew = 1;
			}

			$is_pending		= ( strcmp( $metaUser->focusSubscription->status, 'Pending' ) === 0 );
			$is_trial		= ( strcmp( $metaUser->focusSubscription->status, 'Trial' ) === 0 );
			$lifetime		= $metaUser->focusSubscription->lifetime;

			if ( ( $comparison['total_comparison'] === false ) || $is_pending ) {
				// If user is using global trial period he still can use the trial period of a plan
				if ( ( $params['trial_period'] > 0 ) && !$is_trial ) {
					$trial		= true;
					$value		= $params['trial_period'];
					$perunit	= $params['trial_periodunit'];
					$params['lifetime']	= 0; // We are entering the trial period. The lifetime will come at the renew.
				} else {
					$trial		= false;
					$value		= $params['full_period'];
					$perunit	= $params['full_periodunit'];
				}
			} elseif ( !$is_pending ) {
				$trial		= false;
				$value		= $params['full_period'];
				$perunit	= $params['full_periodunit'];
			} else {
				return;
			}

			if ( $params['lifetime'] || ( strcmp( $multiplicator, 'lifetime' ) === 0 ) ) {
				$metaUser->focusSubscription->expiration = '9999-12-31 00:00:00';
				$metaUser->focusSubscription->lifetime = 1;
			} else {
				$metaUser->focusSubscription->lifetime = 0;

				$value *= $multiplicator;

				if ( ( $comparison['comparison'] == 2 ) && !$lifetime ) {
					$metaUser->focusSubscription->setExpiration( $perunit, $value, 1 );
				} else {
					$metaUser->focusSubscription->setExpiration( $perunit, $value, 0 );
				}
			}

			if ( $is_pending ) {
				// Is new = set signup date
				$metaUser->focusSubscription->signup_date = gmstrftime( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );
				if ( $params['trial_period'] > 0 && !$is_trial ) {
					$status = 'Trial';
				} else {
					if ( $params['full_period'] || $params['lifetime'] ) {
						if ( !isset( $params['make_active'] ) ) {
							$status = 'Active';
						} else {
							$status = ( $params['make_active'] ? 'Active' : 'Pending' );
						}
					} else {
						// This should not happen
						$status = 'Pending';
					}
				}
			} else {
				// Renew subscription - Do NOT set signup_date
				if ( !isset( $params['make_active'] ) ) {
					$status = $trial ? 'Trial' : 'Active';
				} else {
					$status = ( $params['make_active'] ? ( $trial ? 'Trial' : 'Active' ) : 'Pending' );
				}
				$renew = 1;
			}

			$metaUser->focusSubscription->status = $status;
			$metaUser->focusSubscription->setPlanID( $this->id );

			$metaUser->focusSubscription->lastpay_date	= gmstrftime( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );
			$metaUser->focusSubscription->type = $processor;

			// Clear parameters
			$metaUser->focusSubscription->params = '';

			if ( !empty( $invoice_params['creator_ip'] ) ) {
				$metaUser->focusSubscription->addParams( array( 'creator_ip' => $invoice_params['creator_ip'] ), 'params', false );
			}

			$pp = new PaymentProcessor();
			if ( $pp->loadName( strtolower( $processor ) ) ) {
				$pp->init();
				$pp->getInfo();
				$metaUser->focusSubscription->recurring = $pp->info['recurring'];
			} else {
				$metaUser->focusSubscription->recurring = 0;
			}
		}

		$micro_integrations = $this->getMicroIntegrations();

		if ( is_array( $micro_integrations ) ) {
			foreach ( $micro_integrations as $mi_id ) {
				$mi = new microIntegration( $database );
				if ( $mi->mi_exists( $mi_id ) ) {
					$mi->load( $mi_id );
					if ( $mi->callIntegration() ) {
						if ( ( ( strcmp( $mi->class_name, 'mi_email' ) === 0 ) && !$silent ) || ( strcmp( $mi->class_name, 'mi_email' ) !== 0 ) ) {
							if ( $mi->action( $metaUser, null, $invoice, $this ) === false ) {
								return false;
							}
						}
					}
				}
				unset($mi);
			}
		}

		if ( $userid ) {
			if ( $params['gid_enabled'] ) {
				$metaUser->instantGIDchange($params['gid']);
			}

			$metaUser->focusSubscription->check();
			$metaUser->focusSubscription->store();
		}

		if ( !( $silent && $aecConfig->cfg['noemails'] ) ) {
			if ( ( $this->id !== $aecConfig->cfg['entry_plan'] ) ) {
				$metaUser->focusSubscription->sendEmailRegistered( $renew );
			}
		}

		return $renew;
	}

	function SubscriptionAmount( $recurring, $user_subscription )
	{
		global $database;

		if ( is_object( $user_subscription ) ) {
			$comparison				= $this->doPlanComparison( $user_subscription );
			$plans_comparison		= $comparison['comparison'];
			$plans_comparison_total	= $comparison['total_comparison'];
			$renew					= $comparison['renew'] ? 1 : 0;
			$is_trial				= (strcmp($user_subscription->status, 'Trial') === 0);
		} else {
			$plans_comparison		= false;
			$plans_comparison_total	= false;
			$renew					= 0;
			$is_trial				= 0;
		}

		$var		= null;
		$free_trial = 0;
		$params		= $this->getParams();

		if ( !empty( $recurring ) ) {
			$amount = array();

			// Only Allow a Trial when the User is coming from a different PlanGroup or is new
			if ( ( $plans_comparison === false ) && ( $plans_comparison_total === false ) && !empty( $params['trial_period'] ) ) {
				if ( $params['trial_free'] ) {
					$amount['amount1'] = '0.00';
					$free_trial = 1;
				} else {
					$amount['amount1']	= $params['trial_amount'];
				}
				$amount['period1']	= $params['trial_period'];
				$amount['unit1']	= $params['trial_periodunit'];
			}

			if ( $params['full_free'] ) {
				$amount['amount3'] = '0.00';
			} else {
				$amount['amount3']	= $params['full_amount'];
			}

			$amount['period3']		= $params['full_period'];
			$amount['unit3']		= $params['full_periodunit'];
		} else {
			if ( !$params['trial_period'] && $params['full_free'] && $params['trial_free'] ) {
				$amount = '0.00';
			} else {
				if ( ( $plans_comparison === false ) && ( $plans_comparison_total === false ) ) {
					if ( !$is_trial && !empty($params['trial_period']) ) {
						if ( $params['trial_free'] ) {
							$amount = '0.00';
							$free_trial = 1;
						} else {
							$amount = $params['trial_amount'];
						}
					} else {
						if ( $params['full_free'] ) {
							$amount = '0.00';
						} else {
							$amount = $params['full_amount'];
						}
					}
				} else {
					if ( $params['full_free'] ) {
						$amount = '0.00';
					} else {
						$amount = $params['full_amount'];
					}
				}
			}
		}

		$return_url	= AECToolbox::deadsureURL( '/index.php?option=com_acctexp&amp;task=thanks&amp;renew=' . $renew );

		$return['return_url']	= $return_url;
		$return['amount']		= $amount;
		$return['free_trial']	= $free_trial;

		return $return;
	}

	function doPlanComparison( $user_subscription )
	{
		global $database;

		$return['total_comparison']	= false;
		$return['comparison']		= false;
		$return['renew']			= 0;

		if ( !is_null( $user_subscription->plan ) ) {
			$return['renew'] = 1;

			if ( $user_subscription->used_plans ) {
				$used_plans			= explode( ';', $user_subscription->used_plans );
				$plans_comparison	= false;
				$thisparams			= $this->getParams();

				if ( is_array( $used_plans ) ) {
					foreach ( $used_plans as $used_plan_id ) {
						if ( $used_plan_id ) {
							$planid = explode( ',', $used_plan_id);

							if ( isset( $planid[0] ) ){
								if ( empty( $planid[0] ) ) {
									continue;
								} else {
									$planid = $planid[0];
								}
							} else {
								continue;
							}

							$used_subscription = new SubscriptionPlan( $database );
							$used_subscription->load( $planid );

							if ( $this->id === $used_subscription->id ) {
								$used_comparison = 2;
							} elseif ( empty( $thisparams['similarplans'] ) && empty( $thisparams['equalplans'] ) ) {
								$used_comparison = false;
							} else {
								$used_comparison = $this->compareToPlan( $used_subscription );
							}

							if ( $used_comparison > $plans_comparison ) {
								$plans_comparison = $used_comparison;
							}
							unset( $used_subscription );
						}
					}
					$return['total_comparison'] = $plans_comparison;
				}
			}

			$last_subscription = new SubscriptionPlan( $database );
			$last_subscription->load( $user_subscription->plan );

			if ( $this->id === $last_subscription->id ) {
				$return['comparison'] = 2;
			} else {
				$return['comparison'] = $this->compareToPlan( $last_subscription );
			}
		}
		return $return;
	}

	function compareToPlan( $plan )
	{
		$thisparams = $this->getParams();
		$planparams = $plan->getParams();

		$spg1		= explode( ';', $thisparams['similarplans'] );
		$spg2		= explode( ';', $planparams['similarplans'] );

		$epg1		= explode( ';', $thisparams['equalplans'] );
		$epg2		= explode( ';', $planparams['equalplans'] );

		if ( in_array( $this->id, $epg2 ) || in_array( $plan->id, $epg1 ) ) {
			return 2;
		} elseif ( in_array( $this->id, $spg2 ) || in_array( $plan->id, $spg1 ) ) {
			return 1;
		} else {
			return false;
		}
	}

	function getMicroIntegrations()
	{
		if ( strlen( $this->micro_integrations ) ) {
			return explode( ';', $this->micro_integrations );
		} else {
			return false;
		}
	}

	function getProcessorParameters( $processorid )
	{
		$params = $this->getParams( 'custom_params' );

		$procparams = array();
		if ( !empty( $params ) ) {
			foreach ( $params as $name => $value ) {
				$realname = explode( '_', $name, 2 );

				if ( $realname[0] == $processorid ) {
					$procparams[$realname[1]] = $value;
				}
			}
		}

		return $procparams;
	}

	function getRestrictionsArray()
	{
		$restrictions = $this->getParams( 'restrictions' );

		$planrestrictions = array();

		// Check for a fixed GID - this certainly overrides the others
		if ( !empty( $restrictions['fixgid_enabled'] ) ) {
			$planrestrictions['fixgid'] = (int) $restrictions['fixgid'];
		} else {
			// No fixed GID, check for min GID
			if ( !empty( $restrictions['mingid_enabled'] ) ) {
				$planrestrictions['mingid'] = (int) $restrictions['mingid'];
			}
			// Check for max GID
			if ( !empty( $restrictions['maxgid_enabled'] ) ) {
				$planrestrictions['maxgid'] = (int) $restrictions['maxgid'];
			}
		}

		// Check for a directly previously used plan
		if ( !empty( $restrictions['previousplan_req_enabled'] ) ) {
			if ( isset( $restrictions['previousplan_req'] ) ) {
				$planrestrictions['plan_previous'] = $restrictions['previousplan_req'];
			}
		}

		// Check for a currently used plan
		if ( !empty( $restrictions['currentplan_req_enabled'] ) ) {
			if ( isset( $restrictions['currentplan_req'] ) ) {
				$planrestrictions['plan_present'] = $restrictions['currentplan_req'];
			}
		}

		// Check for a overall used plan
		if ( !empty( $restrictions['overallplan_req_enabled'] ) ) {
			if ( isset( $restrictions['overallplan_req'] ) ) {
				$planrestrictions['plan_overall'] = $restrictions['overallplan_req'];
			}
		}

		// Check for a directly previously used plan
		if ( !empty( $restrictions['previousplan_req_enabled_excluded'] ) ) {
			if ( isset( $restrictions['previousplan_req_excluded'] ) ) {
				$planrestrictions['plan_previous_excluded'] = $restrictions['previousplan_req_excluded'];
			}
		}

		// Check for a currently used plan
		if ( !empty( $restrictions['currentplan_req_enabled_excluded'] ) ) {
			if ( isset( $restrictions['currentplan_req_excluded'] ) ) {
				$planrestrictions['plan_present_excluded'] = $restrictions['currentplan_req_excluded'];
			}
		}

		// Check for a overall used plan
		if ( !empty( $restrictions['overallplan_req_enabled_excluded'] ) ) {
			if ( isset( $restrictions['overallplan_req_excluded'] ) ) {
				$planrestrictions['plan_overall_excluded'] = $restrictions['overallplan_req_excluded'];
			}
		}

		// Check for a overall used plan with amount minimum
		if ( !empty( $restrictions['used_plan_min_enabled'] ) ) {
			if ( isset( $restrictions['used_plan_min_amount'] ) && isset( $restrictions['used_plan_min'] ) ) {
				$planrestrictions['plan_amount_min'] = ( (int) $restrictions['used_plan_min'] )
				. ',' . ( (int) $restrictions['used_plan_min_amount'] );
			}
		}

		// Check for a overall used plan with amount maximum
		if ( !empty( $restrictions['used_plan_max_enabled'] ) ) {
			if ( isset( $restrictions['used_plan_max_amount'] ) && isset( $restrictions['used_plan_max'] ) ) {
				$planrestrictions['plan_amount_max'] = ( (int) $restrictions['used_plan_max'] )
				. ',' . ( (int) $restrictions['used_plan_max_amount'] );
			}
		}

		// Check for a directly previously used plan
		if ( !empty( $restrictions['custom_restrictions_enabled'] ) ) {
			if ( isset( $restrictions['custom_restrictions'] ) ) {
				$planrestrictions['custom_restrictions'] = $this->transformCustomRestrictions( $restrictions['custom_restrictions'] );
			}
		}

		return $planrestrictions;
	}

	function transformCustomRestrictions( $customrestrictions )
	{
		$cr = explode( "\n", $customrestrictions);

		$custom = array();
		foreach ( $cr as $field ) {
			$custom[] = explode( ' ', $field );
		}

		return $custom;
	}

	function savePOSTsettings( $post )
	{
		global $database;

		// Fake knowing the planid if is zero. TODO: This needs to replaced with something better later on!
		if ( !empty( $post['id'] ) ) {
			$planid = $post['id'];
		} else {
			$query = 'SELECT MAX(id)'
					. ' FROM #__acctexp_plans'
					;
			$database->setQuery( $query );
			$planid = $database->loadResult() + 1;
		}

		if ( isset( $post['id'] ) ) {
			unset( $post['id'] );
		}

		// Filter out fixed variables
		$fixed = array( 'active', 'visible', 'name', 'desc', 'email_desc', 'micro_integrations' );

		foreach ( $fixed as $varname ) {
			if ( is_array( $post[$varname] ) ) {
				$this->$varname = implode( ';', $post[$varname] );
			} else {
				if ( !get_magic_quotes_gpc() ) {
					$this->$varname = addslashes( $post[$varname] );
				} else {
					$this->$varname = $post[$varname];
				}
			}
			unset( $post[$varname] );
		}

		// Get selected processors ( have to be filtered out )

		$processors = array();
		foreach ( $post as $key => $value ) {
			if ( ( strpos( $key, 'processor_' ) === 0 ) && ( $value == 'on') ) {
				$processors[] = str_replace( 'processor_', '', $key );
				unset( $post[$key] );
			}
		}

		// Filter out params
		$fixed = array( 'full_free', 'full_amount', 'full_period', 'full_periodunit',
						'trial_free', 'trial_amount', 'trial_period', 'trial_periodunit',
						'gid_enabled', 'gid', 'lifetime', 'fallback',
						'similarplans', 'equalplans', 'make_active', 'make_primary', 'update_existing' );

		$params = array();
		foreach ( $fixed as $varname ) {
			if ( is_array( $post[$varname] ) ) {
				$params[$varname] = implode( ';', $post[$varname] );
			} elseif ( empty( $post[$varname] ) ) {
				$params[$varname] = 0;
			} else {
				$params[$varname] = $post[$varname];
			}
			unset( $post[$varname] );
		}

		$params['processors'] = implode( ';', $processors );

		$this->saveParams( $params );

		// Filter out restrictions
		$fixed = array( 'mingid_enabled', 'mingid', 'fixgid_enabled', 'fixgid',
						'maxgid_enabled', 'maxgid', 'previousplan_req_enabled', 'previousplan_req',
						'currentplan_req_enabled', 'currentplan_req', 'overallplan_req_enabled', 'overallplan_req',
						'previousplan_req_enabled_excluded', 'previousplan_req_excluded', 'currentplan_req_enabled_excluded', 'currentplan_req_excluded',
						'overallplan_req_enabled_excluded', 'overallplan_req_excluded', 'used_plan_min_enabled', 'used_plan_min_amount',
						'used_plan_min', 'used_plan_max_enabled', 'used_plan_max_amount', 'used_plan_max',
						'custom_restrictions_enabled', 'custom_restrictions' );

		$restrictions = array();
		foreach ( $fixed as $varname ) {
			if ( is_array( $post[$varname] ) ) {
				$restrictions[$varname] = implode( ';', $post[$varname] );
			} elseif ( empty( $post[$varname] ) ) {
				$restrictions[$varname] = 0;
			} else {
				$restrictions[$varname] = $post[$varname];
			}
			unset( $post[$varname] );
		}

		$this->saveRestrictions($restrictions);

		// The rest of the vars are custom params
		$custom_params = array();
		foreach ( $post as $varname => $content ) {
			if ( substr( $varname, 0, 4 ) != 'mce_' ) {
				if ( is_array( $content ) ) {
					$custom_params[$varname] = implode( ';', $content );
				} else {
					$custom_params[$varname] = $content;
				}
			}
			unset( $post[$varname] );
		}

		$this->saveCustomParams($custom_params);
	}

	function saveParams( $params )
	{
		global $database;

		// If the admin wants this to be a free plan, we have to make this more explicit
		// Setting processors to zero and full_free
		if ( $params['full_free'] && ( $params['processors'] == '' ) ) {
			$params['processors']	= '';
		} elseif ( !$params['full_amount'] || ( $params['full_amount'] == '0.00' ) || ( $params['full_amount'] == '' ) ) {
			$params['full_free']	= 1;
			$params['processors']	= '';
		}

		// Correct a malformed Full Amount
		if ( !strlen( $params['full_amount'] ) ) {
			$params['full_amount']	= '0.00';
			$params['full_free']	= 1;
			$params['processors']	= '';
		} else {
			$params['full_amount'] = AECToolbox::correctAmount( $params['full_amount'] );
		}

		// Correct a malformed Trial Amount
		if ( strlen( $params['trial_amount'] ) ) {
			$params['trial_amount'] = AECToolbox::correctAmount( $params['trial_amount'] );
		}

		// Prevent setting Trial Amount to 0.00 if no free trial was asked for
		if ( !$params['trial_free'] && ( strcmp( $params['trial_amount'], "0.00" ) === 0 ) ) {
			$params['trial_amount'] = '';
		}

		// TODO: Check for Similarity/Equality relations on other plans

		$this->setParams( $params );
	}

	function saveRestrictions( $restrictions )
	{
		$this->setParams( $restrictions, 'restrictions' );
	}

	function saveCustomParams( $custom_params )
	{
		$this->setParams( $custom_params, 'custom_params' );
	}
}

class logHistory extends mosDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $proc_id;
	/** @var string */
	var $proc_name;
	/** @var int */
	var $user_id;
	/** @var string */
	var $user_name;
	/** @var int */
	var $plan_id;
	/** @var string */
	var $plan_name;
	/** @var datetime */
	var $transaction_date	= null;
	/** @var string */
	var $amount;
	/** @var string */
	var $invoice_number;
	/** @var string */
	var $response;

	/**
	* @param database A database connector object
	*/

	function logHistory( &$db )
	{
		$this->mosDBTable( '#__acctexp_log_history', 'id', $db );
	}

	function entryFromInvoice( $objInvoice, $response, $pp )
	{
		global $database, $mosConfig_offset_user;

		$user = new mosUser($database);
		$user->load( $objInvoice->userid );

		$plan = new SubscriptionPlan( $database );
		$plan->load( $objInvoice->usage );

		$this->proc_id			= $pp->id;
		$this->proc_name		= $pp->processor_name;
		$this->user_id			= $user->id;
		$this->user_name		= $user->username;
		$this->plan_id			= $plan->id;
		$this->plan_name		= $plan->name;
	    $this->transaction_date	= gmstrftime ( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );
	    $this->amount			= $objInvoice->amount;
	    $this->invoice_number	= $objInvoice->invoice_number;
	    $this->response			= $response;

		$short	= 'history entry';
		$event	= 'Processor (' . $pp->processor_name . ') notification for ' . $objInvoice->invoice_number;
		$tags	= 'history,processor,payment';
		$params = array( 'invoice_number' => $objInvoice->invoice_number );

		$eventlog = new eventLog( $database );
		$eventlog->issue( $short, $tags, $event, 2, $params );

		if ( !$this->check() ) {
			echo "<script> alert('".$this->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}
		if ( !$this->store() ) {
			echo "<script> alert('".$this->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}
	}
}

class InvoiceFactory
{
	/** @var int */
	var $userid			= null;
	/** @var string */
	var $usage			= null;
	/** @var string */
	var $processor		= null;
	/** @var string */
	var $invoice		= null;
	/** @var int */
	var $confirmed		= null;

	function InvoiceFactory( $userid=null, $usage=null, $processor=null, $invoice=null )
	{
		global $database, $mainframe, $my;

		$this->userid = $userid;
		$this->authed = false;

		require_once( $mainframe->getPath( 'front_html', 'com_acctexp' ) );

		// Check whether this call is legitimate
		if ( !$my->id ) {
			if ( !$this->userid ) {
				// Its ok, this is a registration/subscription hybrid call
				$this->authed = true;
			} elseif ( $this->userid ) {
				if ( AECToolbox::quickVerifyUserID( $this->userid ) === true ) {
					// This user is not expired, so he could log in...
					mosNotAuth();
					return;
				} else {
					$this->userid = $userid;
				}
			}
		} else {
			// Overwrite the given userid when user is logged in
			$this->userid = $my->id;
			$this->authed = true;
		}

		// Init variables
		$this->usage		= $usage;
		$this->processor	= $processor;
		$this->invoice		= $invoice;

		if ( !is_null( $this->userid ) ) {
			$query = 'SELECT `id`'
					. ' FROM #__users'
					. ' WHERE `id` = \'' . $this->userid . '\'';
			$database->setQuery( $query );

			if ( !$database->loadResult() ) {
				$this->userid = null;
			}
		}

		if ( $this->usage && $this->userid ) {
			$this->verifyUsage();
		}
	}

	function verifyUsage()
	{
		global $database;

		if ( !is_object( $this->metaUser ) ) {
			$this->metaUser = new metaUser( $this->userid );
		}

		$row = new SubscriptionPlan( $database );
		$row->load( $this->usage );

		$restrictions = $row->getRestrictionsArray();

		if ( count( $restrictions ) ) {
			$status = $this->metaUser->permissionResponse( $restrictions );

			foreach ( $status as $stname => $ststatus ) {
				if ( !( $ststatus === true ) ) {
					mosNotAuth();
				}
			}
		}
	}

	function puffer( $option )
	{
		global $database;

		if ( $this->usage ) {
			// get the payment plan
			$this->objUsage = new SubscriptionPlan( $database );
			$this->objUsage->load( $this->usage );
		} else {
			mosNotAuth();
		}

		if ( !is_null( $this->processor ) && !( $this->processor == '' ) ) {
			switch ( $this->processor ) {
				case 'free':
					$this->payment->method_name = _AEC_PAYM_METHOD_FREE;
					$this->pp					= false;
					$this->recurring			= 0;
					$currency					= '';
					break;

				case 'none':
					$this->payment->method_name = _AEC_PAYM_METHOD_NONE;
					$this->pp					= false;
					$this->recurring			= 0;
					$currency					= '';
					break;

				default:
					$this->pp = new PaymentProcessor();
					if ( $this->pp->loadName( $this->processor ) ) {
						$this->pp->fullInit();
						$this->pp->exchangeSettings( $this->objUsage );
						$this->payment->method_name	= $this->pp->info['longname'];
						$this->recurring			= isset( $this->pp->info['recurring'] ) ? $this->pp->info['recurring'] : 0;
						$currency					= isset( $this->pp->settings['currency'] ) ? $this->pp->settings['currency'] : '';
					} else {
						$this->payment->method_name = _AEC_PAYM_METHOD_NONE;
						$this->pp					= false;
						$this->recurring			= 0;
						$currency					= '';
						// TODO: Log Error
					}
					break;
			}
		} else {
			mosNotAuth();
		}

		$user_subscription = false;
		$this->renew = 0;

		if ( !empty( $this->userid ) ) {
			if ( AECfetchfromDB::SubscriptionIDfromUserID( $this->userid ) ) {
				$user_subscription = new Subscription( $database );
				$user_subscription->loadUserID( $this->userid );

				if ( !( strcmp( $user_subscription->lastpay_date, '0000-00-00 00:00:00' ) === 0 ) ) {
					$this->renew = 1;
				}
			}
		}

		$return = $this->objUsage->SubscriptionAmount( $this->recurring, $user_subscription );

		$this->payment->freetrial = 0;

		if ( is_array( $return['amount'] ) ) {
			$this->payment->amount = false;

			if ( isset( $return['amount']['amount1'] ) ) {
				if ( !is_null( $return['amount']['amount1'] ) ) {
					$this->payment->amount = $return['amount']['amount1'];
					if ( $this->payment->amount == '0.00' ) {
						$this->payment->freetrial = 1;
					}
				}
			}

			if ( $this->payment->amount === false ) {
				if ( isset( $return['amount']['amount2'] ) ) {
					if ( !is_null( $return['amount']['amount2'] ) ) {
						$this->payment->amount = $return['amount']['amount2'];
						if ( $this->payment->amount == '0.00' ) {
							$this->payment->freetrial = 1;
						}
					}
				}
			}

			if ( $this->payment->amount === false ) {
				if ( isset( $return['amount']['amount3'] ) ) {
					if ( !is_null( $return['amount']['amount3'] ) ) {
						$this->payment->amount = $return['amount']['amount3'];
					}
				}
			}
		} else {
			$this->payment->amount = $return['amount'];
			if ( ( $this->payment->amount == '0.00' ) && $return['free_trial'] ) {
				$this->payment->freetrial = 1;
			}
		}

		$this->payment->currency = $currency;

		return;
	}

	function touchInvoice( $option, $invoice_number=false )
	{
		global $database;

		// Checking whether we are trying to repeat an invoice
		if ( $invoice_number !== false ) {
			// Make sure the invoice really exists and that its the correct user carrying out this action
			$invoiceid = AECfetchfromDB::InvoiceIDfromNumber($invoice_number, $this->userid);

			if ( $invoiceid ) {
				$this->invoice = $invoice_number;
			}
		}

		$this->objInvoice = new Invoice( $database );

		if ( $this->invoice ) {
			$this->objInvoice->loadInvoiceNumber($this->invoice);
			$this->objInvoice->computeAmount();

			$this->processor = $this->objInvoice->method;
			$this->usage = $this->objInvoice->usage;

			if ( empty( $this->usage ) && empty( $this->objInvoice->conditions ) ) {
				$this->create( $option, 0, 0, $this->invoice_number );
			} elseif ( empty( $this->processor ) ) {
				$this->create( $option, 0, $this->usage, $this->invoice_number );
			}
		} else {
			$this->objInvoice->create( $this->userid, $this->usage, $this->processor );
			$this->objInvoice->computeAmount();

			// Reset parameters
			$this->processor	= $this->objInvoice->method;
			$this->usage		= $this->objInvoice->usage;
			$this->invoice		= $this->objInvoice->invoice_number;
		}

		return;
	}

	function loadMetaUser( $passthrough=false, $force=false )
	{
		if ( is_object( $this->metaUser ) && !$force ) {
			return false;
		}

		if ( empty( $this->userid ) ) {
			// Creating a dummy user object
			$this->metaUser = new metaUser( 0 );
			$this->metaUser->cmsUser = new stdClass();
			$this->metaUser->cmsUser->gid = 29;
			$this->metaUser->hasSubscription = false;
			$this->metaUser->hasExpiration = false;

			if ( is_array( $passthrough ) && !empty( $passthrough ) ) {
				$cpass = $passthrough;
				unset( $cpass['id'] );

				$cmsfields = array( 'name', 'username', 'email', 'password' );

				// Create dummy CMS user
				foreach( $cmsfields as $cmsfield ) {
					$this->metaUser->cmsUser->$cmsfield = $cpass[$cmsfield];
					unset( $cpass[$cmsfield] );
				}

				// Create dummy CB/CBE user
				if ( GeneralInfoRequester::detect_component( 'CB' ) || GeneralInfoRequester::detect_component( 'CBE' ) ) {
					$this->metaUser->hasCBprofile = 1;
					$this->metaUser->cbUser = new stdClass();

					foreach ( $cpass as $cbfield => $cbvalue ) {
						if ( is_array( $cbvalue ) ) {
							$this->metaUser->cbUser->$cbfield = implode( ';', $cbvalue );
						} else {
							$this->metaUser->cbUser->$cbfield = $cbvalue;
						}
					}
				}

				return false;
			} else {
				return true;
			}
		} else {
			// Loading the actual user
			$this->metaUser = new metaUser( $this->userid );
			return false;
		}
	}

	function checkAuth( $option, $var )
	{
		$return = true;

		if ( !is_object( $this->metaUser ) ) {
			$this->loadMetaUser();
		}

		if ( empty( $this->authed ) ) {
			if ( !$this->metaUser->getTempAuth() ) {
				if ( isset( $var['password'] ) ) {
					if ( !$this->metaUser->setTempAuth( $var['password'] ) ) {
						unset( $var['password'] );
						$this->promptpassword( $option, $var, true );
						$return = false;
					}
				} else {
					$this->promptpassword( $option, $var );
					$return = false;
				}
			}
		}

		return $return;
	}

	function promptpassword( $option, $var, $wrong=false )
	{
		$passthrough = array();
		foreach ( $var as $ke => $va ) {
			if ( is_array( $va ) ) {
				foreach ( $va as $con ) {
					$passthrough[] = array( $ke . '[]', $con );
				}
			} else {
				$passthrough[] = array( $ke, $va );
			}
		}

		Payment_HTML::promptpassword( $option, $passthrough, $wrong );
	}

	function create( $option, $intro=0, $usage=0, $processor=null, $invoice=0, $passthrough=false )
	{
		global $database, $mainframe, $my, $aecConfig;

		$register = $this->loadMetaUser( $passthrough );

		$where = array();

		if ( $this->metaUser->hasSubscription ) {
			$subscriptionClosed = ( strcmp( $this->metaUser->objSubscription->status, 'Closed' ) === 0 );
		} else {
			$subscriptionClosed = false;
			// TODO: Check if the user has already subscribed once, if not - link to intro
			// TODO: Make sure a registration hybrid wont get lost here
			if ( !$intro && !empty( $aecConfig->cfg['customintro'] ) ) {
				mosRedirect( $aecConfig->cfg['customintro'] );
			}
		}

		$where[] = '`active` = \'1\'';

		if ( $usage ) {
			$where[] = '`id` = ' . $usage;
		} else {
			$where[] = '`visible` != \'0\'';
		}

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_plans'
				. ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
				. ' ORDER BY `ordering`'
				;
	 	$database->setQuery( $query );
		$rows = $database->loadResultArray();
	 	if ( $database->getErrorNum() ) {
	 		echo $database->stderr();
	 		return false;
	 	}

	 	// There are no plans to begin with, so we need to punch out an error here
		if ( count( $rows ) == 0 ) {
			mosRedirect( AECToolbox::deadsureURL( '/index.php?mosmsg=' . _NOPLANS_ERROR ), false, true );
	 		return;
	 	}

		$plans	= array();
		$i		= 0;

		foreach ( $rows as $planid ) {
			$row = new SubscriptionPlan($database);
			$row->load($planid);

			$restrictions = $row->getRestrictionsArray();

			if ( count( $restrictions ) ) {
				$status = array();

				if ( isset( $restrictions['custom_restrictions'] ) ) {
					$status = array_merge( $status, $this->metaUser->CustomRestrictionResponse( $restrictions['custom_restrictions'] ) );
					unset( $restrictions['custom_restrictions'] );
				}

				$status = array_merge( $status, $this->metaUser->permissionResponse( $restrictions ) );

				foreach ( $status as $stname => $ststatus ) {
					if ( !$ststatus ) {
						continue 2;
					}
				}
			}

			$plan_params = $row->getParams();

			$plans[$i]['name']		= $row->name;
			$plans[$i]['desc']		= $row->getProperty( 'desc' );
			$plans[$i]['id']		= $row->id;
			$plans[$i]['ordering']	= $row->ordering;
			$plans[$i]['lifetime']	= $plan_params['lifetime'];
			$plans[$i]['gw']		= array();

			if ( $plan_params['full_free'] ) {
				$plans[$i]['gw'][0]					= array();
				$plans[$i]['gw'][0]['name']		= 'free';
				$plans[$i]['gw'][0]['recurring']	= 0;
				$plans[$i]['gw'][0]['statement']	= '';
				$i++;
			} else {
				if ( ( $plan_params['processors'] != '' ) && !is_null( $plan_params['processors'] ) ) {
					$processors = explode( ';', $plan_params['processors'] );

					if ( !empty( $this->processor ) ) {
						$processorid = PaymentProcessorHandler::getProcessorIdfromName( $this->processor );
						if ( in_array( $processorid, $processors ) ) {
							$processors = array( $processorid );
						}
					}

					$plan_gw = array();
					if ( count( $processors ) ) {
						$k = 0;
						foreach ( $processors as $n ) {
							if ($n) {
								$pp = new PaymentProcessor();
								$loadproc = $pp->loadId( $n );
								if ( $loadproc ) {
									$pp->init();
									$pp->getInfo();

									if ( !($plan_params['lifetime'] && $pp->info['recurring'] ) ) {
										$plan_gw[$k]['name']		= $pp->processor_name;
										$plan_gw[$k]['statement']	= $pp->info['statement'];
									}
									$k++;
								}
							}
						}
					}

					if ( !empty( $plan_gw ) ) {
						$plans[$i]['gw'] = $plan_gw;
					} else {
						unset( $plans[$i] );
					}
					unset( $plan_gw );
					$i++;
				} else {
					unset( $plans[$i] );
				}
			}
			unset( $row );
		}

	 	// After filtering out the processors, no plan can be used, so we have to again issue an error
		 if ( count( $plans ) == 0 ) {
			mosRedirect( AECToolbox::deadsureURL( '/index.php?mosmsg=' . _NOPLANS_ERROR ), false, true );
	 		return;
	 	}

	 	$nochoice = ( count( $plans ) === 1 ) && ( count( $plans[0]['gw'] ) === 1 );

		// If we have only one processor on one plan, there is no need for a decision
		if ( $nochoice && !$aecConfig->cfg['show_fixeddecision'] ) {
			// If the user also needs to register, we need to guide him there after the selection has now been made
			if ( $register ) {
				// The plans are supposed to be first, so the details form should hold the values
				if ( $aecConfig->cfg['plans_first'] && !empty( $plans[0]['id'] ) ) {
					$_POST['usage']		= $plans[0]['id'];
					$_POST['processor']	= $plans[0]['gw'][0]['name'];
				}

				// Send to CB or joomla!
				if ( GeneralInfoRequester::detect_component( 'CB' ) || GeneralInfoRequester::detect_component( 'CBE' ) ) {
					// This is a CB registration, borrowing their code to register the user

					global $task;

					$savetask	= $task;
					$task = 'done';

					include_once( $mainframe->getCfg( 'absolute_path' ) . '/components/com_comprofiler/comprofiler.html.php' );
					include_once( $mainframe->getCfg( 'absolute_path' ) . '/components/com_comprofiler/comprofiler.php' );

					$task = $savetask;

					registerForm($option, $mainframe->getCfg( 'emailpass' ), null);
				} elseif ( GeneralInfoRequester::detect_component( 'JUSER' ) ) {
					// This is a JUSER registration, borrowing their code to register the user

					global $task, $mosConfig_absolute_path;

					$savetask	= $task;
					$task = 'blind';

					include_once( $mainframe->getCfg( 'absolute_path' ) . '/components/com_juser/juser.html.php' );
					include_once( $mainframe->getCfg( 'absolute_path' ) . '/components/com_juser/juser.php' );

					$task = $savetask;

					userRegistration( $option, null );
				} else {
					if ( !isset( $_POST['usage'] ) ) {
						$_POST['intro'] = $intro;
						$_POST['usage'] = $usage;
					}

					//include_once( $mainframe->getCfg( 'absolute_path' ) . '/components/com_acctexp/acctexp.html.php' );
					joomlaregisterForm( $option, $mainframe->getCfg( 'useractivation' ) );
				}
			} else {
				// The user is already existing, so we need to move on to the confirmation page with the details

				$this->usage		= $plans[0]['id'];
				if ( isset( $plans[0]['gw'][0]['recurring'] ) ) {
					$this->recurring	= $plans[0]['gw'][0]['recurring'];
				} else {
					$this->recurring	= 0;
				}
				$this->processor	= $plans[0]['gw'][0]['name'];

				if ( ( $invoice != 0 ) && !is_null( $invoice ) ) {
					$this->invoice	= $invoice;
				}

				$this->confirm ( $option, array(), $passthrough );
			}
		} else {
			// Reset $register if we seem to have all data
			// TODO: find better solution for this
			if ( $register && isset( $passthrough['username'] ) ) {
				$register = 0;
			} else {

			}

			// Of to the Subscription Plan Selection Page!
			Payment_HTML::selectSubscriptionPlanForm( $option, $this->userid, $plans, $subscriptionClosed, $passthrough, $register );
		}
	}

	function confirm( $option, $var=array(), $passthrough=false )
	{
		global $database, $my, $aecConfig, $mosConfig_absolute_path;

		if ( !$passthrough ) {
			if ( !$this->checkAuth( $option, $var ) ) {
				return false;
			}
		}

		if ( isset( $var['task'] ) ) {
			unset( $var['task'] );
			unset( $var['option'] );
		}

		if ( $this->userid ) {
			$user = new mosUser( $database );
			$user->load( $this->userid );

			$passthrough = false;
		} else {
			unset( $var['usage'] );
			unset( $var['processor'] );
			unset( $var['currency'] );
			unset( $var['amount'] );

			if ( is_array( $passthrough ) ) {
				$user = new mosUser( $database );
				$user->name		= $passthrough['name'];
				$user->username = $passthrough['username'];
				$user->email	= $passthrough['email'];
			} else {
				$user = new mosUser( $database );
				$user->name		= $var['name'];
				$user->username = $var['username'];
				$user->email	= $var['email'];

				$passthrough = array();
				foreach ( $var as $ke => $va ) {
					if ( is_array( $va ) ) {
						foreach ( $va as $con ) {
							$passthrough[] = array( $ke . '[]', $con );
						}
					} else {
						$passthrough[] = array( $ke, $va );
					}
				}
			}
		}

		if ( $aecConfig->cfg['use_recaptcha'] && !empty( $aecConfig->cfg['recaptcha_privatekey'] ) && isset( $_POST["recaptcha_challenge_field"] ) && isset( $_POST["recaptcha_response_field"] ) ) {
			// require the recaptcha library
			require_once( $mosConfig_absolute_path . '/components/com_acctexp/lib/recaptcha/recaptchalib.php' );

			//finally chack with reCAPTCHA if the entry was correct
			$resp = recaptcha_check_answer ( $aecConfig->cfg['recaptcha_privatekey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"] );

			//if the response is INvalid, then go back one page, and try again. Give a nice message
			if (!$resp->is_valid) {
				echo "<script> alert('The reCAPTCHA entered incorrectly. Go back and try it again. reCAPTCHA said: " . $resp->error . "'); window.history.go(-1); </script>\n";
				exit();
			}
		}

		$this->puffer( $option );

		$this->coupons = array();
		$this->coupons['active'] = $aecConfig->cfg['enable_coupons'];

		if ( !empty( $aecConfig->cfg['skip_confirmation'] ) ) {
			$this->save( $option, $var );
		} else {
			Payment_HTML::confirmForm( $option, $this, $user, $passthrough );
		}
	}


	function save( $option, $var )
	{
		global $database, $mainframe, $task;

		if ( isset( $var['task'] ) ) {
			unset( $var['task'] );
			unset( $var['option'] );
		}

		if ( $this->usage == '' ) {
			$this->usage = $var['usage'];
		}
		if ( $this->processor == '' ) {
			$this->processor = $var['processor'];
		}
		$this->confirmed = 1;

		if ( $this->userid ) {
			$user = new mosUser( $database );
			$user->load( $this->userid );
		} else {
			$this->userid = AECToolbox::saveUserRegistration( $option, $var );

			$this->loadMetaUser( false, true );
			$this->metaUser->setTempAuth();
		}

		$this->touchInvoice( $option );
		$this->checkout( $option );
	}

	function checkout( $option, $repeat=0 )
	{
		global $database, $aecConfig;

		if ( !$this->checkAuth( $option, $_POST ) ) {
			return false;
		}

		$this->puffer( $option );

		if ( empty( $repeat ) ) {
			$repeat = 0;
		}

		if ( ( strcmp( strtolower( $this->processor ), 'none' ) === 0 ) || ( strcmp( strtolower( $this->processor ), 'free' ) === 0 ) ) {
			$params = $this->objUsage->getParams();

		 	if ( $params['full_free'] || ( $params['trial_free'] &&
		 	( strcmp( $this->objInvoice->transaction_date, '0000-00-00 00:00:00' ) === 0 ) ) ) {
				if ( $this->objInvoice->pay() !== false ) {
					thanks ( $option, $this->renew, 1 );
					return;
				} else {
					return;
				}
		 	} else {
		 		return;
		 	}
		} elseif ( strcmp( strtolower( $this->processor ), 'error' ) === 0 ) {
	 		// Nope, won't work buddy
		 	notAllowed( $option );
		}

		$first = $repeat ? 0 : 1;

		if ( !empty( $this->pp->info['secure'] ) && !( $_SERVER['HTTPS'] == 'on' ) && !$aecConfig->cfg['override_reqssl'] ) {
		    mosRedirect( AECToolbox::deadsureURL( "/index.php?option=" . $option . "&task=repeatPayment&invoice=" . $this->objInvoice->invoice_number . "&first=" . $first, true, false ) );
		    exit();
		};

		$amount				= $this->objUsage->SubscriptionAmount( $this->recurring, $this->metaUser->objSubscription );
		$original_amount	= $amount;
		$warning			= 0;

		if ( !empty( $aecConfig->cfg['enable_coupons'] ) ) {
			$this->coupons['active'] = 1;

			if ( $this->objInvoice->coupons ) {
				$coupons = explode( ';', $this->objInvoice->coupons );

				$this->coupons['coupons'] = array();

				$applied_coupons = array();
				$global_nomix = array();
				foreach ( $coupons as $id => $coupon_code ) {
					$cph = new couponHandler();
					$cph->load( $coupon_code );

					if ( $cph->restrictions['restrict_combination'] ) {
						$nomix = explode( ';', $cph->restrictions['bad_combinations'] );
					} else {
						$nomix = array();
					}

					if ( count( array_intersect( $applied_coupons, $nomix ) ) || in_array( $coupon_code, $global_nomix ) ) {
						// This coupon either interferes with one of the coupons already applied, or the other way round
						$cph->setError( _COUPON_ERROR_COMBINATION );
					} else {
						$cph->getInfo( $amount );
						$cph->checkRestrictions( $this->metaUser, $original_amount, $this );

						if ( $cph->status ) {
							$this->coupons['coupons'][$id]['code']		= $cph->code;
							$this->coupons['coupons'][$id]['name']		= $cph->name;
							$this->coupons['coupons'][$id]['discount']	= AECToolbox::correctAmount( $cph->discount_amount );
							$this->coupons['coupons'][$id]['action']	= $cph->action;
							$this->coupons['coupons'][$id]['nodirectaction'] = 0;

							$applied_coupons[] = $coupon_code;
							$global_nomix = array_merge( $global_nomix, $nomix );

							// Set a warning notice that the amount doesn't seem to have changed althout its only for the next amount
							if ( is_array( $amount ) ) {
								if ( isset( $amount['amount']['amount1'] ) ) {
									if ( $amount['amount']['amount1'] == $cph->amount ) {
										$this->coupons['coupons'][$id]['nodirectaction'] = 1;
										$warning = 1;
									}
								} elseif ( isset( $amount['amount']['amount2'] ) ) {
									if ( $amount['amount']['amount2'] == $cph->amount ) {
										$this->coupons['coupons'][$id]['nodirectaction'] = 1;
										$warning = 1;
									}
								} elseif ( isset( $amount['amount']['amount3'] ) ) {
									if ( $amount['amount']['amount3'] == $cph->amount ) {
										$this->coupons['coupons'][$id]['nodirectaction'] = 1;
										$warning = 1;
									}
								}
							} else {
								if ( $amount == $cph->amount ) {
									$this->coupons['coupons'][$id]['nodirectaction'] = 1;
									$warning = 1;
								}
							}
							$amount = AECToolbox::correctAmount( $cph->amount );
						}
					}

					if ( !$cph->status ) {
						// Set Error
						$this->coupons['error']		= 1;
						$this->coupons['errormsg']	= $cph->error;
						// Remove Coupon
						$this->objInvoice->removeCoupon( $coupon_code );
						// Recalculate Amount
						$this->objInvoice->computeAmount();
						// Check and store
						$this->objInvoice->check();
						$this->objInvoice->store();
					}
				}
			}

			if ( $warning ) {
				$this->coupons['warning'] = 1;
				$this->coupons['warningmsg'] = html_entity_decode( _COUPON_WARNING_AMOUNT );
			}
		} else {
			$this->coupons['active'] = 0;
		}

		if ( $amount <= 0 )	{
			$this->objInvoice->pay();
			thanks ( $option, $this->renew, 1 );
			return;
		}

		$this->InvoiceToCheckout( $option, $repeat );
	}

	function InvoiceToCheckout( $option, $repeat=0 )
	{
		$var = $this->objInvoice->prepareProcessorLink();

		$this->objInvoice->formatInvoiceNumber();

		Payment_HTML::checkoutForm( $option, $var['var'], $var['params'], $this, $repeat );
	}

	function internalcheckout( $option )
	{
		global $database;

		$this->metaUser = new metaUser( $this->userid );

		$this->puffer( $option );

		$var = $this->objInvoice->getFullVars();

		$new_subscription = new SubscriptionPlan( $database );
		$new_subscription->load( $this->objInvoice->usage );

		$badbadvars = array( 'userid', 'invoice', 'task', 'option' );
		foreach ( $badbadvars as $badvar ) {
			if ( isset( $_POST[$badvar] ) ) {
				unset( $_POST[$badvar] );
			}
		}

		$var['params'] = array();
		foreach ( $_POST as $varname => $varvalue ) {
			$var['params'][$varname] = $varvalue;
		}

		$response = $this->pp->processor->checkoutProcess( $var, $this->pp, $this->metaUser, $new_subscription, $this->objInvoice );

		if ( isset( $response['error'] ) ) {
			$this->error( $option, $this->metaUser->cmsUser, $this->objInvoice->invoice_number, $response['error'] );
		}
	}

	function planprocessoraction( $action, $subscr=null )
	{
		global $database;

		$this->metaUser = new metaUser( $this->userid );

		$invoice = new Invoice( $database );

		if ( !empty( $subscr ) ) {
			if ( $this->metaUser->moveFocus( $subscr ) ) {
				$invoice->loadbySubscription( $this->metaUser->focusSubscription->id, $this->metaUser->userid );
			}
		}

		if ( empty( $invoice->id ) ) {
			$invoice->load( AECfetchfromDB::lastClearedInvoiceIDbyUserID( $this->userid, $this->metaUser->focusSubscription->plan ) );
		}

		if ( empty( $invoice->id ) ) {
			$invoice->load( AECfetchfromDB::lastUnclearedInvoiceIDbyUserID( $this->userid, $this->metaUser->focusSubscription->plan ) );
		}

		$pp = new PaymentProcessor( $database );
		$pp->loadName( $invoice->method );

		$pp->customAction( $action, $invoice, $this->metaUser );
	}

	function thanks( $option, $renew, $free )
	{
		global $database, $mosConfig_useractivation, $ueConfig, $mosConfig_dbprefix;

		if ( $renew ) {
			$msg = _SUB_FEPARTICLE_HEAD_RENEW
			. '</p><p>'
			. _SUB_FEPARTICLE_THANKSRENEW;
			if ( $free ) {
				$msg .= _SUB_FEPARTICLE_LOGIN;
			} else {
				$msg .= _SUB_FEPARTICLE_PROCESSPAY
				. _SUB_FEPARTICLE_MAIL;
			}
		} else {
			$msg = _SUB_FEPARTICLE_HEAD
			. '</p><p>'
			. _SUB_FEPARTICLE_THANKS;
			if ( $free ) {
				if ( $mosConfig_useractivation ) {
					$msg .= _SUB_FEPARTICLE_PROCESS
					. _SUB_FEPARTICLE_ACTMAIL;
				} else {
					$msg .= _SUB_FEPARTICLE_PROCESS
					. _SUB_FEPARTICLE_MAIL;
				}
			} else {
				if ( $mosConfig_useractivation ) {
					$msg .= _SUB_FEPARTICLE_PROCESSPAY
					. _SUB_FEPARTICLE_ACTMAIL;
				} else {
					$msg .= _SUB_FEPARTICLE_PROCESSPAY
					. _SUB_FEPARTICLE_MAIL;
				}
			}
		}

		// Look whether we have a custom ThankYou page
		if ( $aecConfig->cfg['customthanks'] ) {
			mosRedirect( $aecConfig->cfg['customthanks'] );
		} else {
			HTML_Results::thanks( $option, $msg );
		}
	}

	function error( $option, $objUser, $invoice, $error )
	{
		Payment_HTML::error( $option, $objUser, $invoice, $error );
	}
}

class Invoice extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $active 			= null;
	/** @var int */
	var $counter 			= null;
	/** @var int */
	var $userid 			= null;
	/** @var int */
	var $subscr_id 			= null;
	/** @var string */
	var $invoice_number 	= null;
	/** @var string */
	var $secondary_ident 	= null;
	/** @var datetime */
	var $created_date	 	= null;
	/** @var datetime */
	var $transaction_date 	= null;
	/** @var string */
	var $method 			= null;
	/** @var string */
	var $amount 			= null;
	/** @var string */
	var $currency 			= null;
	/** @var string */
	var $usage				= null;
	/** @var int */
	var $fixed	 			= null;
	/** @var text */
	var $coupons 			= null;
	/** @var text */
	var $params 			= null;
	/** @var text */
	var $conditions			= null;

	function Invoice(&$db)
	{
		$this->mosDBTable( '#__acctexp_invoices', 'id', $db );
	}

	function loadInvoiceNumber( $invoiceNum )
	{
		global $database;

		$query = 'SELECT id'
		. ' FROM #__acctexp_invoices'
		. ' WHERE invoice_number = \'' . $invoiceNum . '\''
		. ' OR secondary_ident = \'' . $invoiceNum . '\''
		;
		$database->setQuery( $query );
		$this->load($database->loadResult());
	}

	function formatInvoiceNumber( $invoice=null )
	{
		global $aecConfig;

		if ( empty( $invoice ) ) {
			$invoice_number	= $this->invoice_number;
			$invoice_id		= $this->id;
		} else {
			$invoice_number = $invoice->invoice_number;
			$invoice_id		= $invoice->id;
		}

		if ( empty( $aecConfig->cfg['invoicenum_display_id'] ) ) {
			if ( !empty( $aecConfig->cfg['invoicenum_display_case'] ) ) {
				switch ( $aecConfig->cfg['invoicenum_display_case'] ) {
					case 'UPPER':
						$invoice_number = strtoupper( $invoice_number );
						break;
					case 'LOWER':
						$invoice_number = strtolower( $invoice_number );
						break;
				}
			}
		} else {
			if ( !empty( $aecConfig->cfg['invoicenum_display_idinflate'] ) ) {
				$invoice_number = (string) ( $invoice_id + $aecConfig->cfg['invoicenum_display_idinflate'] );
			} else {
				$invoice_number = (string) $invoice_id;
			}
		}

		if ( !empty( $aecConfig->cfg['invoicenum_display_chunking'] ) ) {
			if ( !empty( $aecConfig->cfg['invoicenum_display_separator'] ) ) {
				$separator = $aecConfig->cfg['invoicenum_display_separator'];
			} else {
				$separator = '-';
			}

			if ( function_exists( 'str_split' ) ) {
				$chunks = str_split( $invoice_number, $aecConfig->cfg['invoicenum_display_chunking'] );
			} else {
				$chunks = AECToolbox::str_split_php4( $invoice_number, $aecConfig->cfg['invoicenum_display_chunking'] );
			}
			$invoice_number = implode( $separator, $chunks );
		}

		if ( empty( $invoice ) ) {
			$this->invoice_number = $invoice_number;
			$this->id = $invoice_id;
			return true;
		} else {
			return $invoice_number;
		}

	}

	function deformatInvoiceNumber( $invoice=null )
	{
		global $aecConfig;

		if ( empty( $invoice ) ) {
			$invoice_number	= $this->invoice_number;
			$invoice_id		= $this->id;
		} else {
			$invoice_number = $invoice->invoice_number;
			$invoice_id		= $invoice->id;
		}

		if ( !empty( $aecConfig->cfg['invoicenum_display_chunking'] ) ) {
			if ( !empty( $aecConfig->cfg['invoicenum_display_separator'] ) ) {
				$separator = $aecConfig->cfg['invoicenum_display_separator'];
			} else {
				$separator = '-';
			}

			$invoice_number = str_replace( $separator, '', $invoice_number);
			$invoice_id = str_replace( $separator, '', $invoice_id);
		}

		if ( !empty( $aecConfig->cfg['invoicenum_display_id'] ) ) {
			if ( !empty( $aecConfig->cfg['invoicenum_display_idinflate'] ) ) {
				$invoice_number = (string) ( $invoice_id - $aecConfig->cfg['invoicenum_display_idinflate'] );
			} else {
				$invoice_number = (string) $invoice_id;
			}
		}

		if ( empty( $invoice ) ) {
			$this->invoice_number = $invoice_number;
			$this->id = $invoice_id;
			return true;
		} else {
			return $invoice_number;
		}

	}

	function loadbySubscriptionId( $subscrid, $userid=null )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_invoices'
				. ' WHERE `subscr_id` = \'' . $subscrid . '\''
				. ' ORDER BY `transaction_date` DESC'
				;

		if ( !empty( $userid ) ) {
			$query .= ' AND `userid` = \'' . $userid . '\'';
		}

		$database->setQuery( $query );
		$this->load( $database->loadResult() );
	}

	function hasDuplicate( $userid, $invoiceNum )
	{
		$db2 = $this->get( "_db" );
		$query = 'SELECT count(*)'
				. ' FROM #__acctexp_invoices'
				. ' WHERE `userid` = ' . (int) $userid
				. ' AND `invoice_number` = \'' . $invoiceNum . '\''
				;
		$db2->setQuery( $query );
		return $db2->loadResult();
	}

	function computeAmount()
	{
		global $database;

		if ( !is_null( $this->usage ) && !( $this->usage == '' ) ) {
			$plan = new SubscriptionPlan( $database );
			$plan->load( $this->usage );

			$metaUser = new metaUser( $this->userid ? $this->userid : 0 );

			$recurring = '';

			switch ( $this->method ) {
				case 'none':
				case 'free':
					break;
				default:
					$pp = new PaymentProcessor();
					if ( $pp->loadName( $this->method ) ) {
						$pp->fullInit();
						$pp->exchangeSettings( $plan );

						if ( isset( $pp->info['recurring'] ) ) {
							$recurring = $pp->info['recurring'];
						} else {
							$recurring = 0;
						}

						if ( empty( $this->currency ) ) {
							$this->currency = isset( $pp->settings['currency'] ) ? $pp->settings['currency'] : '';
						}
					} else {
						// Log Error
						return;
					}
			}

			if ( $metaUser->hasSubscription ) {
				$return = $plan->SubscriptionAmount( $recurring, $metaUser->objSubscription );
			} else {
				$return = $plan->SubscriptionAmount( $recurring, false );
			}

			if ( $this->coupons ) {
				$coupons = explode( ';', $this->coupons );

				$cpsh = new couponsHandler();

				$return['amount'] = $cpsh->applyCoupons( $return['amount'], $coupons, $metaUser );
			}

			if ( is_array( $return['amount'] ) ) {
				// Check whether we have a trial amount and whether this invoice has had a trial with a payment already
				$this->amount = false;

				if ( isset( $return['amount']['amount1'] ) ) {
					if ( !is_null( $return['amount']['amount1'] )
					&& !( ( $this->amount == $return['amount']['amount1'] )
					&& !( strcmp( $this->transaction_date, '0000-00-00 00:00:00' ) === 0 ) ) ) {
						$this->amount = $return['amount']['amount1'];
					}
				}

				if ( $this->amount === false ) {
					if ( isset( $return['amount']['amount2'] ) ) {
						if ( !is_null( $return['amount']['amount2'] ) ) {
							$this->amount = $return['amount']['amount2'];
						}
					}
				}

				if ( $this->amount === false ) {
					if ( isset( $return['amount']['amount3'] ) ) {
						if ( !is_null( $return['amount']['amount3'] ) ) {
							$this->amount = $return['amount']['amount3'];
						}
					}
				}

				if ( $this->amount === false ) {
					$this->amount = '0.00';
				}
			} else {
				$this->amount = $return['amount'];
			}

			// We cannot afford to have this ever come out as null, so we will rather have it as gratis
			if ( empty( $this->amount ) ) {
				$this->amount = '0.00';
			}

			if ( ( strcmp( $this->amount, '0.00' ) === 0 ) && !$recurring ) {
				$this->method = 'free';
			} elseif ( strcmp( $this->method, 'free' ) === 0 ) {
				$this->method = 'error';
				// TODO: Log Error
			}
		}
	}

	function create( $userid, $usage, $processor, $second_ident=null )
	{
		global $mosConfig_offset_user;

		$invoice_number			= $this->generateInvoiceNumber();

		$this->load(0);
		$this->invoice_number	= $invoice_number;

		if ( !is_null( $second_ident ) ) {
			$this->secondary_ident		= $second_ident;
		}

		$this->active			= 1;
		$this->fixed			= 0;
		$this->created_date		= gmstrftime ( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );
		$this->transaction_date	= '0000-00-00 00:00:00';
		$this->userid			= $userid;
		$this->method			= $processor;
		$this->usage			= $usage;

		$pp = new PaymentProcessor();
		if ( $pp->loadName( $processor ) ) {
			$pp->init();
			$pp->invoiceCreationAction( $this );
		}

		$this->computeAmount();

		$this->addParams( array( 'creator_ip' => $_SERVER['REMOTE_ADDR'] ), 'params', false );

		if ( !$this->check() ) {
			echo "<script> alert('problem with storing an invoice: ".$this->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}

		if ( !$this->store() ) {
			echo "<script> alert('problem with storing an invoice: ".$this->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}
	}

	function generateInvoiceNumber( $maxlength = 16 )
	{
		global $database;

		$numberofrows	= 1;
		while ( $numberofrows ) {
			$inum =	'I' . substr( base64_encode( md5( rand() ) ), 0, $maxlength );
			// Check if already exists
			$query = 'SELECT count(*)'
					. ' FROM #__acctexp_invoices'
					. ' WHERE `invoice_number` = \'' . $inum . '\''
					. ' OR `secondary_ident` = \'' . $inum . '\''
					;
			$database->setQuery( $query );
			$numberofrows = $database->loadResult();
		}
		return $inum;
	}

	function processorResponse( $pp, $response, $responsestring='' )
	{
		global $database;

		$this->computeAmount();

		$plan = new SubscriptionPlan( $database );
		$plan->load( $this->usage );
		$plan_params = $plan->getParams( 'params' );

		$pp->exchangeSettings( $plan, $plan_params );
		$response = $pp->validateNotification( $response, $_POST, $this );

		if ( isset( $response['invoiceparams'] ) ) {
			$this->addParams( $response['invoiceparams'] );
			unset( $response['invoiceparams'] );
		}

		if ( isset( $response['multiplicator'] ) ) {
			$multiplicator = $response['multiplicator'];
			unset( $response['multiplicator'] );
		} else {
			$multiplicator = 1;
		}

		if ( isset( $response['responsestring'] ) ) {
			$responsestring = $response['responsestring'];
			unset( $response['responsestring'] );
		}

		// Create history entry
		$history = new logHistory( $database );
		$history->entryFromInvoice( $this, $responsestring, $pp );

		$short = _AEC_MSG_PROC_INVOICE_ACTION_SH;
		$event = _AEC_MSG_PROC_INVOICE_ACTION_EV . "\n";
		foreach ($response as $key => $value) {
			$event .= $key . "=" . $value . "\n";
		}
		$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_STATUS;
		$tags	= 'invoice,processor';
		$params = array( 'invoice_number' => $this->invoice_number );

		$event .= ' ';

		if ( $response['valid'] ) {
			$break = 0;

			if ( isset( $response['amount_paid'] ) ) {
				if ( $response['amount_paid'] != $this->amount ) {
					// Amount Fraud, cancel payment and create error log addition
					$event	.= sprintf( _AEC_MSG_PROC_INVOICE_ACTION_EV_FRAUD, $response['amount_paid'], $this->amount );
					$tags	.= ',fraud_attempt,amount_fraud';
					$break	= 1;
				}
			}
			if ( isset( $response['amount_currency'] ) ) {
				if ( $response['amount_currency'] != $this->currency ) {
					// Amount Fraud, cancel payment and create error log addition
					$event	.= sprintf( _AEC_MSG_PROC_INVOICE_ACTION_EV_CURR, $response['amount_currency'], $this->currency );
					$tags	.= ',fraud_attempt,currency_fraud';
					$break	= 1;
				}
			}

			if ( !$break ) {
				$renew	= $this->pay( $multiplicator );
				if ( !empty( $pp->info['notify_trail_thanks'] ) ) {
					thanks( 'com_acctexp', $renew, ($pp === 0) );
				}
				$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_VALID;
				$tags	.= ',payment,action';
			}
		} else {
			if ( isset( $response['pending'] ) ) {
				if ( strcmp( $response['pending_reason'], 'signup' ) === 0 ) {
					if ( $plan_params['trial_free'] ) {
						$this->pay( $multiplicator );
						$this->setParams( array( 'free_trial' => $response['pending_reason'] ) );
						$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_TRIAL;
						$tags	.= ',payment,action,trial';
					}
				} else {
					$this->setParams( array( 'pending_reason' => $response['pending_reason'] ) );
					$event	.= sprintf( _AEC_MSG_PROC_INVOICE_ACTION_EV_PEND, $response['pending_reason'] );
					$tags	.= ',payment,pending' . $response['pending_reason'];
				}

				$this->check();
				$this->store();
			} elseif ( isset( $response['cancel'] ) ) {
				$metaUser = new metaUser( $this->userid );
				$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_CANCEL;
				$tags	.= ',cancel';

				if ( $metaUser->hasSubscription ) {
					$metaUser->objSubscription->cancel( $this );
					$event .= _AEC_MSG_PROC_INVOICE_ACTION_EV_USTATUS;
				}
			} elseif ( isset( $response['delete'] ) ) {
				$metaUser = new metaUser( $this->userid );
				$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_REFUND;
				$tags	.= ',refund';

				if ( $metaUser->hasSubscription ) {
					$metaUser->objSubscription->expire();
					$event .= _AEC_MSG_PROC_INVOICE_ACTION_EV_EXPIRED;
				}
			} elseif ( isset( $response['eot'] ) ) {
				$metaUser = new metaUser( $this->userid );
				$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_EOT;
				$tags	.= ',eot';
			} elseif ( isset( $response['duplicate'] ) ) {
				$metaUser = new metaUser( $this->userid );
				$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_DUPLICATE;
				$tags	.= ',duplicate';
			} else {
				$event	.= _AEC_MSG_PROC_INVOICE_ACTION_EV_U_ERROR;
				$tags	.= ',general_error';
			}
		}

		$eventlog = new eventLog( $database );
		$eventlog->issue( $short, $tags, $event, 2, $params );
	}

	function pay( $multiplicator=1 )
	{
		global $database;

		$metaUser = false;
		$new_plan = false;

		if ( !empty( $this->userid ) ) {
			$metaUser = new metaUser( $this->userid );
		}

		if ( !empty( $this->usage ) ) {
			$new_plan = new SubscriptionPlan( $database );
			$new_plan->load( $this->usage );
		}

		if ( is_object( $metaUser ) && is_object( $new_plan ) ) {
			if ( $metaUser->userid ) {
				if ( empty( $this->subscr_id ) ) {
					$metaUser->establishFocus( $new_plan, $this->method );

					$this->subscr_id = $metaUser->focusSubscription->id;
				} else {
					$metaUser->focusSubscription->load( $this->subscr_id );
				}

				// Apply the Plan
				$application = $metaUser->focusSubscription->applyUsage( $this->usage, $this->method, 0, $multiplicator, $this );
			} else {
				$application = $new_plan->applyPlan( 0, $this->method, 0, $multiplicator, $this );
			}

			if ( $application === false ) {
				return false;
			}
		}

		if ( !empty( $this->conditions ) ) {
			$micro_integrations = false;

			if ( strpos( $this->conditions, 'mi_attendevents' ) ) {
				$micro_integration['name'] = 'mi_attendevents';
				$micro_integration['parameters'] = array( 'registration_id' => $this->substring_between( $this->conditions, '<registration_id>', '</registration_id>' ) );
				$micro_integrations = array();
				$micro_integrations[] = $micro_integration;
			}

			if ( is_array( $micro_integrations ) ) {
				foreach ( $micro_integrations as $micro_int ) {
					$mi = new microIntegration( $database );

					if ( isset( $micro_integration['parameters'] ) ) {
						$exchange = $micro_integration['parameters'];
					} else {
						$exchange = null;
					}

					if ( isset( $micro_int['name'] ) ) {
						if ( $mi->callDry( $micro_int['name'] ) ) {
							if ( is_object( $metaUser ) ) {
								$mi->action( $metaUser, $exchange, $this, $new_plan );
							} else {
								$mi->action( false, $exchange, $this, $new_plan );
							}
						}
					} elseif ( isset( $micro_int['id'] ) ) {
						if ( $mi->mi_exists( $micro_int['id'] ) ) {
							$mi->load( $micro_int['id'] );
							if ( $mi->callIntegration() ) {
								if ( is_object( $metaUser ) ) {
									$mi->action( $metaUser, $exchange, $this, $new_plan );
								} else {
									$mi->action( false, $exchange, $this, $new_plan );
								}
							}
						}
					}

					unset( $mi );
				}
			}
		}

		if ( $this->coupons ) {
			$coupons = explode( ';', $this->coupons );
			foreach ( $coupons as $coupon_code ) {
				$cph = new couponHandler();
				$cph->load( $coupon_code );

				if ( $cph->coupon->micro_integrations ) {
					$micro_integrations = explode( ';', $cph->coupon->micro_integrations );
					foreach ( $micro_integrations as $mi_id ) {
						$mi = new microIntegration( $database );
						if ( $mi->mi_exists( $mi_id ) ) {
							$mi->load( $mi_id );
							if ( $mi->callIntegration() ) {
								if ( is_object( $metaUser ) ) {
									if ( $mi->action( $metaUser->userid, null, $this, $new_plan ) === false ) {
										return false;
									} else {
									}
								} else {
									if ( $mi->action( false, null, $this, $new_plan ) === false ) {
										return false;
									} else {
									}
								}
							}
						}
					}
				}
			}
		}

		$this->setTransactionDate();

		return $application;
	}

	function substring_between( $haystack, $start, $end )
	{
		if ( strpos( $haystack, $start ) === false || strpos( $haystack, $end ) === false ) {
			return false;
	   } else {
			$start_position = strpos( $haystack, $start ) + strlen( $start );
			$end_position = strpos( $haystack, $end );
			return substr( $haystack, $start_position, $end_position - $start_position );
		}
	}

	function setTransactionDate()
	{
		global $database, $mosConfig_offset_user, $aecConfig;

		$time_passed		= ( strtotime( $this->transaction_date ) - time() + $mosConfig_offset_user*3600 ) / 3600;
		$transaction_date	= gmstrftime ( '%Y-%m-%d %H:%M:%S', time() + $mosConfig_offset_user*3600 );

		if ( ( strcmp( $this->transaction_date, '0000-00-00 00:00:00' ) === 0 )
			|| ( $time_passed > $aecConfig->cfg['invoicecushion'] ) ) {
			$this->counter = $this->counter + 1;
			$this->transaction_date	= $transaction_date;

			$transactions = $this->getParams( 'transactions' );
			$transactions[] = $transaction_date . ";" . $this->amount . ";" . $this->currency . ";" . $this->method;
			$transactions = $this->setParams( $transactions, 'transactions' );

			$this->check();
			$this->store();
		} else {
			return;
		}
	}

	function getFullVars()
	{
		global $database, $mosConfig_live_site;

		$int_var['params'] = $this->getParams();

		// Filter non-processor params
		$nonproc = array( 'pending_reason', 'deactivated' );
		foreach ( $nonproc as $param ) {
			if ( isset( $int_var['params'][$param] ) ) {
				unset( $int_var['params'][$param] );
			}
		}

		$metaUser = new metaUser( $this->userid );

		$new_subscription = new SubscriptionPlan( $database );
		$new_subscription->load( $this->usage );

		$pp = new PaymentProcessor();
		if ( !$pp->loadName( strtolower( $this->method ) ) ) {
	 		// Nope, won't work buddy
		 	notAllowed( 'com_acctexp' );
		}

		$pp->init();
		$pp->getInfo();

		$int_var['planparams'] = $new_subscription->getProcessorParameters( $pp->id );

		if ( isset( $pp->info['recurring'] ) ) {
			$int_var['recurring'] = $pp->info['recurring'];
		} else {
			$int_var['recurring'] = 0;
		}

		$amount = $new_subscription->SubscriptionAmount( $int_var['recurring'], $metaUser->objSubscription );

		if ( !empty( $this->coupons ) ) {
			$coupons = explode( ';', $this->coupons);

			$cph = new couponsHandler();

			$amount['amount'] = $cph->applyCoupons( $amount['amount'], $coupons, $metaUser );
		}

		$int_var['amount']		= $amount['amount'];
		$int_var['return_url']	= $amount['return_url'];
		$int_var['invoice']		= $this->invoice_number;
		$int_var['usage']		= $this->invoice_number;

		return $int_var;
	}

	function prepareProcessorLink()
	{
		global $database, $mosConfig_live_site;

		$int_var['params'] = $this->getParams();

		// Filter non-processor params
		$nonproc = array( 'pending_reason', 'deactivated' );
		foreach ( $nonproc as $param ) {
			if ( isset( $int_var['params'][$param] ) ) {
				unset( $int_var['params'][$param] );
			}
		}

		$metaUser = new metaUser( $this->userid );

		$pp = new PaymentProcessor();
		if ( !$pp->loadName( strtolower( $this->method ) ) ) {
	 		// Nope, won't work buddy
		 	notAllowed( 'com_acctexp' );
		}

		$pp->init();
		$pp->getInfo();

		if ( $this->usage ) {
			$new_subscription = new SubscriptionPlan( $database );
			$new_subscription->load( $this->usage );

			$int_var['planparams'] = $new_subscription->getProcessorParameters( $pp->id );
			if ( isset( $pp->info['recurring'] ) ) {
				$int_var['recurring'] = $pp->info['recurring'];
			} else {
				$int_var['recurring'] = 0;
			}

			$amount = $new_subscription->SubscriptionAmount( $int_var['recurring'], $metaUser->objSubscription );
		} else {
			$amount['amount'] = $this->amount;
			$int_var['recurring'] = 0;
		}

		if ( !empty( $this->coupons ) ) {
			$coupons = explode( ';', $this->coupons);

			$cph = new couponsHandler();

			$amount['amount'] = $cph->applyCoupons( $amount['amount'], $coupons, $metaUser );
		}

		$int_var['amount']		= $amount['amount'];

		if ( !empty( $amount['return_url'] ) ) {
			$int_var['return_url'] = $amount['return_url'];
		} else {
			$int_var['return_url'] = AECToolbox::deadsureURL( '/index.php?option=com_acctexp&amp;task=thanks&amp;renew=0' );
		}

		$int_var['invoice']		= $this->invoice_number;
		$int_var['usage']		= $this->invoice_number;

		// Assemble Checkout Response
		$return['var']		= $pp->checkoutAction( $int_var, $metaUser, $new_subscription );
		$return['params']	= $pp->getParamsHTML( $int_var['params'], $pp->getParams( $int_var['params'] ) );

		if ( empty( $return['params'] ) ) {
			$return['params'] = null;
		}

		return $return;
	}

	function addCoupon( $couponcode )
	{
		if ( $this->coupons ) {
			$oldcoupons = explode( ';', $this->coupons );
		} else {
			$oldcoupons = array();
		}

		if ( !in_array( $couponcode, $oldcoupons ) ) {
			$oldcoupons[] = $couponcode;

			$cph = new couponHandler();
			$cph->load( $couponcode );

			if ( $cph->status ) {
				$cph->incrementCount( $this );
			}
		}

		$this->coupons = implode( ';', $oldcoupons );
	}

	function removeCoupon( $couponcode )
	{
		$oldcoupons = explode( ';', $this->coupons );

		if ( in_array( $couponcode, $oldcoupons ) ) {
			foreach ( $oldcoupons as $id => $cc ) {
				if ( $cc == $couponcode ) {
					unset( $oldcoupons[$id] );
				}
			}

			$cph = new couponHandler();
			$cph->load( $couponcode );
			if ($cph->status) {
				$cph->coupon->decrementCount();
			}
		}

		$this->coupons = implode( ';', $oldcoupons );
	}

	function savePostParams( $array )
	{
		unset( $array['task'] );
		unset( $array['option'] );
		unset( $array['invoice'] );

		$this->addParams( $array );
		return true;
	}

	function printInvoice($option)
	{
		global $mosConfig_sitename, $mosConfig_mailfrom, $database;


		if($this->usage != 2 && $this->usage != 4){

			$query = "SELECT * FROM #__vm_user_info WHERE user_id = ".$this->userid;
			$database->setQuery($query);
			$vm_user_details = $database->loadAssocList();
			$vm_user_details = $vm_user_details[0];

			$user = new metaUser($this->userid);
			$body = '';
			$body = str_replace("{inv_no}",$this->invoice_number,$body);
			$body = str_replace("{date}",date('D, jS M Y',strtotime($this->created_date)),$body);
			$body = str_replace("{name}",$user->cmsUser->name,$body);
			$body = str_replace("{username}",$user->cmsUser->username,$body);
			$body = str_replace("{company}",$vm_user_details['company'],$body);
			$body = str_replace("{address}",$vm_user_details['address_1'],$body);
			$body = str_replace("{city}",$vm_user_details['city'],$body);
			$body = str_replace("{state}",$vm_user_details['state'],$body);
			$body = str_replace("{postcode}",$vm_user_details['zip'],$body);
			$body = str_replace("{country}",$vm_user_details['country'],$body);
			$body = str_replace("{phone}",$vm_user_details['phone_1'],$body);
			$body = str_replace("{email}",$user->cmsUser->email,$body);
			if($this->usage == 3){
				$body = str_replace("{invoice_desc}","",$body);
			}else{
				$body = str_replace("{invoice_desc}","",$body);
			}

			$body = str_replace("{cost}","$".$this->amount,$body);
			$body = str_replace("{total}","$".$this->amount,$body);
			$body = str_replace("{gst}","$".number_format($this->amount / 11,2),$body);

			$subject = "".$this->invoice_number;

			$query = "SELECT * FROM #__vm_vendor WHERE vendor_id = 1";
			$database->setQuery($query);
			$vm_vendor = $database->loadAssocList();
			$vm_vendor = $vm_vendor[0];

			if($this->method == 'transfer'){
				if($this->transaction_date == '0000-00-00 00:00:00'){

					mosMail($mosConfig_mailfrom, $mosConfig_sitename, $user->cmsUser->email, $subject, $body, 1);
					mosMail($mosConfig_mailfrom, $mosConfig_sitename, $vm_vendor['contact_email'], $subject, $body, 1);
				}
			}else{
				if($this->transaction_date != '0000-00-00 00:00:00'){

					mosMail($mosConfig_mailfrom, $mosConfig_sitename, $user->cmsUser->email, $subject, $body, 1);
					mosMail($mosConfig_mailfrom, $mosConfig_sitename, $vm_vendor['contact_email'], $subject, $body, 1);
				}
			}
		}
	}
}

/**
 * User management
 *
 */
class Subscription extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $userid				= null;
	/** @var int */
	var $primary			= null;
	/** @var string */
	var $type				= null;
	/** @var string */
	var $status				= null;
	/** @var datetime */
	var $signup_date		= null;
	/** @var datetime */
	var $lastpay_date		= null;
	/** @var datetime */
	var $cancel_date		= null;
	/** @var datetime */
	var $eot_date			= null;
	/** @var string */
	var $eot_cause			= null;
	/** @var int */
	var $plan				= null;
	/** @var int */
	var $previous_plan		= null;
	/** @var string */
	var $used_plans			= null;
	/** @var string */
	var $recurring			= null;
	/** @var int */
	var $lifetime			= null;
	/** @var datetime */
	var $expiration			= null;
	/** @var text */
	var $params 			= null;
	/** @var text */
	var $custom_params		= null;

	/**
	* @param database A database connector object
	*/
	function Subscription( &$db )
	{
		$this->mosDBTable( '#__acctexp_subscr', 'id', $db );
	}

	/**
	 * loads specified user
	 *
	 * @param int $userid
	 */
	function loadUserid( $userid )
	{
		$this->load( $this->getSubscriptionID( $userid ) );
	}

	function getSubscriptionID( $userid, $usage=null, $primary=1 )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `userid` = \'' . $userid . '\''
				;

		if ( !empty( $usage ) ) {
			$query .= ' AND `plan` = \'' . $usage . '\'';
		}

		if ( $primary ) {
			$query .= ' AND `primary` = \'1\'';
		} elseif ( $primary === false ) {
			$query .= ' AND `primary` = \'0\'';
		}

		$database->setQuery( $query );

		return $database->loadResult();
	}

	function makePrimary()
	{
		global $database;

		$query = 'UPDATE #__acctexp_subscr'
				. ' SET `primary` = \'0\''
				. ' WHERE `userid` = \'' . $this->userid . '\''
				;
		$database->setQuery( $query );
		$database->query();

		$this->primary = 1;
		$this->check();
		$this->store();
	}

	function manualVerify()
	{
		if ( $this->is_expired() ) {
			mosRedirect( AECToolbox::deadsureURL( '/index.php?option=com_acctexp&task=expired&userid=' . (int) $this->userid ), false, true );
			return false;
		} else {
			return true;
		}
	}


	function createNew( $userid, $processor, $pending, $primary=1 )
	{
		global $mosConfig_offset_user;

		$this->userid		= $userid;
		$this->primary		= $primary;
		$this->signup_date	= date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );
		$this->expiration	= date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );
		$this->status		= $pending ? 'Pending' : 'Active';
		$this->type			= $processor;

		$this->check();
		$this->store();
		$this->id = $this->getMax();
	}


	function is_expired( $offset=false )
	{
		global $database, $mosConfig_offset_user, $aecConfig;

		if ( !($this->expiration === '9999-12-31 00:00:00') ) {
			$expiration_cushion = str_pad( $aecConfig->cfg['expiration_cushion'], 2, '0', STR_PAD_LEFT );

			if ( $offset ) {
				$expstamp = strtotime( ( '-' . $offset . ' days' ), strtotime( $this->expiration ) );
			} else {
				$expstamp = strtotime( ( '+' . $expiration_cushion . ' hours' ), strtotime( $this->expiration ) );
			}

			if ( ( $expstamp > 0 ) && ( ( $expstamp - ( time() + $mosConfig_offset_user*3600 ) ) < 0 ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function setExpiration( $unit, $value, $extend )
	{
		global $mainframe, $mosConfig_offset_user;

		$now = time() + $mosConfig_offset_user*3600;

		if ( $extend ) {
			$current = strtotime( $this->expiration );

			if ( $current < $now ) {
				$current = $now;
			}
		} else {
			$current = $now;
		}

		$this->expiration = AECToolbox::computeExpiration( $value, $unit, $current );
	}


	/**
	* Get alert level for a subscription
	* @param int user id
	* @return Object alert
	* alert['level'] = -1 means no threshold has been reached
	* alert['level'] =  0 means subscription expired
	* alert['level'] =  1 means most critical threshold has been reached (default: 7 days or less to expire)
	* alert['level'] =  2 means second level threshold has been reached (default: 14 days or less to expire)
	* alert['daysleft'] = number of days left to expire
	*/
	function GetAlertLevel()
	{
		global $database, $mosConfig_offset_user, $aecConfig;

		if ( $this->expiration ) {
			$alert['level']		= -1;
			$alert['daysleft']	= 0;

			$expstamp = strtotime( $this->expiration );

			// Get how many days left to expire (3600 sec = 1 hour)
			$alert['daysleft']	= round( ( $expstamp - ( time() + $mosConfig_offset_user*3600 ) ) / ( 3600 * 24 ) );

			if ( $alert['daysleft'] < 0 ) {
				// Subscription already expired. Alert Level 0!
				$alert['level']	= 1;
			} else {
				// Get alert levels
				if ( $alert['daysleft'] <= $aecConfig->cfg['alertlevel1'] ) {
					// Less than $numberofdays to expire! This is a level 1
					$alert['level']		= 1;
				} elseif ( ( $alert['daysleft'] > $aecConfig->cfg['alertlevel1'] ) && ( $alert['daysleft'] <= $aecConfig->cfg['alertlevel2'] ) ) {
					$alert['level']		= 2;
				} elseif ( $alert['daysleft'] > $aecConfig->cfg['alertlevel2'] ) {
					// Everything is ok. Level 3 means no threshold was reached
					$alert['level']		= 3;
				}
			}
		}
		return $alert;
	}

	function verifylogin( $block )
	{
		global $mosConfig_live_site, $database, $aecConfig;

		if ( strcmp( $this->status, 'Excluded' ) === 0 ) {
			$expired = false;
		} elseif ( strcmp( $this->status, 'Expired' ) === 0 ) {
			$expired = true;
		} else {
			$expired = $this->is_expired();
		}

		if ( $expired ) {
			$pp = new PaymentProcessor();

			if ( $pp->loadName( $subscription->type ) ) {
				$validation = $pp->validateSubscription();
			} else {
				$validation = false;
			}
		}

		if ( ( $expired || ( strcmp( $this->status, 'Closed' ) === 0 ) ) && $aecConfig->cfg['require_subscription'] ) {
			$expire = $this->expire();

			if ( $expire ) {
				mosRedirect( AECToolbox::deadsureURL( '/index.php?option=com_acctexp&task=expired&userid=' . $this->userid ), false, true );
			}
		} elseif ( ( strcmp( $this->status, 'Pending' ) === 0 ) || $block ) {
			mosRedirect( AECToolbox::deadsureURL( '/index.php?option=com_acctexp&task=pending&userid=' . $this->userid ), false, true );
		}
	}

	function verify( $block )
	{
		global $mosConfig_live_site, $database, $aecConfig;

		if ( strcmp( $this->status, 'Excluded' ) === 0 ) {
			$expired = false;
		} elseif ( strcmp( $this->status, 'Expired' ) === 0 ) {
			$expired = true;
		} else {
			$expired = $this->is_expired();
		}

		if ( $expired ) {
			$pp = new PaymentProcessor();

			if ( $pp->loadName( $this->type ) ) {
				$expired = !$pp->validateSubscription( $this );
			}
		}

		if ( ( $expired || ( strcmp( $this->status, 'Closed' ) === 0 ) ) && $aecConfig->cfg['require_subscription'] ) {
			$expire = $this->expire();

			if ( $expire ) {
				return 'expired';
			}
		} elseif ( ( strcmp( $this->status, 'Pending' ) === 0 ) || $block ) {
			return 'pending';
		}

		return true;
	}

	function expire( $overridefallback=false )
	{
		global $database;

		// Users who are excluded cannot expire
		if ( strcmp( $this->status, 'Excluded' ) === 0 ) {
			return false;
		}

		// Load plan variables, otherwise load dummies
		if ( $this->plan ) {
			$subscription_plan = new SubscriptionPlan( $database );
			$subscription_plan->load( $this->plan );
			$plan_params = $subscription_plan->getParams();
		} else {
			$plan_params = array();
			$subscription_plan = false;
		}

		$this_params = $this->getParams();

		// Move the focus Subscription
		$metaUser = new metaUser( $this->userid );
		$metaUser->moveFocus( $this->id );

		// Recognize the fallback plan, if not overridden
		if ( $plan_params['fallback'] && !$overridefallback ) {
			$mih = new microIntegrationHandler();
			$mih->userPlanExpireActions( $metaUser, $subscription_plan );

			$this->applyUsage( $plan_params['fallback'], 'none', 1 );
			return false;
		} else {
			// Set a Trial flag if this is an expired Trial for further reference
			if ( strcmp( $this->status, 'Trial' ) === 0 ) {
				$this->addParams( array( 'trialflag' => 1 ) );
			} elseif ( is_array( $this_params ) ) {
				if ( in_array( 'trialflag', $this_params ) ) {
					$this->delParams( array( 'trialflag' ) );
				}
			}

			if ( !( strcmp( $this->status, 'Expired' ) === 0 ) || !( strcmp( $this->status, 'Closed' ) === 0 ) ) {
				$this->status = 'Expired';
				$this->check();
				$this->store();
			} else {
				return false;
			}

			// Call Expiration MIs
			$mih = new microIntegrationHandler();
			$mih->userPlanExpireActions( $metaUser, $subscription_plan );

			return true;
		}
	}

	function cancel( $invoice=null, $overridefallback=false )
	{
		global $database;

		// Since some processors do not notify each period, we need to check whether the expiration
		// lies to far in the future and cut it down to the end of the period the user has paid

		if ( $this->expire( $overridefallback ) ) {
			if ( $this->plan ) {
				global $mosConfig_offset_user;

				$subscription_plan = new SubscriptionPlan( $database );
				$subscription_plan->load( $this->plan );
				$plan_params = $subscription_plan->getParams();

				// Resolve blocks that we are going to substract from the set expiration date
				$unit = 60*60*24;
				switch ( $plan_params['full_periodunit'] ) {
					case 'D': $periodlength = $plan_params['full_period'] * $unit; break;
					case 'W': $unit *= 7;	$periodlength = $plan_params['full_period'] * $unit; break;
					case 'M': $unit *= 31;	$periodlength = $plan_params['full_period'] * $unit; break;
					case 'Y': $unit *= 356;	$periodlength = $plan_params['full_period'] * $unit; break;
				}

				$newexpiration = strtotime( $this->expiration );
				$now = time() + $mosConfig_offset_user*3600;

				// ...cut away blocks until we are in the past
				for ( $i=$newexpiration; $i>=$now; $i-=$unit ) {
					$newexpiration = $i;
				}

				// And we get the bare expiration date
				$this->expiration = date( 'Y-m-d H:i:s', $newexpiration );
				$this->check();
				$this->store();

				$this->setStatus( 'Cancelled' );

				return true;
			}
		}

		return false;
	}

	function setStatus( $status )
	{
		$this->status = $status;
		$this->check();
		$this->store();
	}

	function applyUsage( $usage = 0, $processor = 'none', $silent = 0, $multiplicator = 1, $invoice=null )
	{
		global $database;

		if ( !$usage ) {
			$usage = $this->plan;
		}

		$new_plan = new SubscriptionPlan( $database );
		$new_plan->load( $usage );

		if ( $new_plan->id ) {
			return $new_plan->applyPlan( $this->userid, $processor, $silent, $multiplicator, $invoice );
		} else {
			return false;
		}
	}

	function setPlanID( $id )
	{
		if ( $this->plan ) {
			$this->previous_plan = $this->plan;
			$this->setUsedPlan( $this->plan );
		}
		$this->plan	= $id;
	}

	function setUsedPlan( $id )
	{
		$used_plans = $this->getUsedPlans();

		if ( isset( $used_plans[$id] ) ) {
			$used_plans[$id]++;
		} else {
			$used_plans[$id] = 1;
		}

		$new_used_plans = array();
		foreach ( $used_plans as $planid => $n ) {
			$new_used_plans[] = $planid . ',' . $n;
		}

		$this->used_plans = implode( ';', $new_used_plans );
	}

	function getUsedPlans()
	{
		$used_plans = explode( ';', $this->used_plans );

		$array = array();
		foreach ( $used_plans as $entry ) {
			$entryarray = explode( ',', $entry );

			if ( !empty( $entryarray[0] ) ) {
				if ( !empty( $entryarray[1] ) ) {
					$amount = $entryarray[1];
				} else {
					$amount = 1;
				}

				if ( isset( $array[$entryarray[0]] ) ) {
					$array[$entryarray[0]] += $amount;
				} else {
					$array[$entryarray[0]] = $amount;
				}
			}
		}

		return $array;
	}

	function sendEmailRegistered( $renew )
	{
		global $database, $acl, $mainframe;

		$langPath = $mainframe->getCfg( 'absolute_path' ) . '/components/com_acctexp/com_acctexp_language/';
		if ( file_exists( $langPath . $mainframe->getCfg( 'mosConfig_lang' ) . '.php' ) ) {
			include_once( $langPath . $mainframe->getCfg( 'mosConfig_lang' ) . '.php' );
		} else {
			include_once( $langPath . 'english.php' );
		}

		$free = ( strcmp( strtolower( $this->type ), 'none' ) == 0 || strcmp( strtolower( $this->type ), 'free' ) == 0 );

		$urow = new mosUser( $database );
		$urow->load( $this->userid );

		$plan = new SubscriptionPlan( $database );
		$plan->load( $this->plan );

		$name			= $urow->name;
		$email			= $urow->email;
		$username		= $urow->username;
		$pwd			= $urow->password;
		$activationcode	= $urow->activation;

		$message = sprintf( _ACCTEXP_MAILPARTICLE_GREETING, $name );

		// Assemble E-Mail Subject & Message
		if ( $renew ) {
			$subject = sprintf( _ACCTEXP_SEND_MSG_RENEW, $name, $mainframe->getCfg( 'sitename' ) );

			$message .= sprintf( _ACCTEXP_MAILPARTICLE_THANKSREN, $mainframe->getCfg( 'sitename' ) );

			if ( $plan->email_desc ) {
				$message .= "\n\n" . $plan->email_desc . "\n\n";
			} else {
				$message .= " ";
			}

			if ( $free ) {
				$message .= sprintf( _ACCTEXP_MAILPARTICLE_LOGIN, $mainframe->getCfg( 'live_site' ) );
			} else {
				$message .= _ACCTEXP_MAILPARTICLE_PAYREC . " "
				. sprintf( _ACCTEXP_MAILPARTICLE_LOGIN, $mainframe->getCfg( 'live_site' ) );
			}
		} else {
			$subject = sprintf( _ACCTEXP_SEND_MSG, $name, $mainframe->getCfg( 'sitename' ) );

			$message .= sprintf(_ACCTEXP_MAILPARTICLE_THANKSREG, $mainframe->getCfg( 'sitename' ) );

			if ( $plan->email_desc ) {
				$message .= "\n\n" . $plan->email_desc . "\n\n";
			} else {
				$message .= " ";
			}

			if ( $free ) {
				$message .= sprintf( _ACCTEXP_MAILPARTICLE_LOGIN, $mainframe->getCfg( 'live_site' ) );
			} else {
				$message .= _ACCTEXP_MAILPARTICLE_PAYREC . " "
				. sprintf( _ACCTEXP_MAILPARTICLE_LOGIN, $mainframe->getCfg( 'live_site' ) );
			}
		}

		$message .= _ACCTEXP_MAILPARTICLE_FOOTER;

		$subject = html_entity_decode( $subject, ENT_QUOTES );
		$message = html_entity_decode( $message, ENT_QUOTES );

		// Send email to user
		if ( $mainframe->getCfg( 'mailfrom' ) != '' && $mainframe->getCfg( 'fromname' ) != '' ) {
			$adminName2		= $mainframe->getCfg( 'fromname' );
			$adminEmail2	= $mainframe->getCfg( 'mailfrom' );
		} else {
			$query = 'SELECT `name`, `email`'
					. ' FROM #__users'
					. ' WHERE `usertype` = \'superadministrator\''
					;
			$database->setQuery( $query );
			$rows = $database->loadObjectList();
			$row2 = $rows[0];

			$adminName2		= $row2->name;
			$adminEmail2	= $row2->email;
		}

		mosMail( $adminEmail2, $adminName2, $email, $subject, $message );

		// Send notification to all administrators
		$aecUser = AECToolbox::_aecIP();

		if ( $renew ) {
			$subject2 = sprintf( _ACCTEXP_SEND_MSG_RENEW, $name, $mainframe->getCfg( 'sitename' ) );
			$message2 = sprintf( _ACCTEXP_ASEND_MSG_RENEW, $adminName2, $mainframe->getCfg( 'sitename' ), $name, $email, $username, $plan->id, $plan->name, $aecUser['ip'], $aecUser['isp'] );
		} else {
			$subject2 = sprintf( _ACCTEXP_SEND_MSG, $name, $mainframe->getCfg( 'sitename' ) );
			$message2 = sprintf( _ACCTEXP_ASEND_MSG, $adminName2, $mainframe->getCfg( 'sitename' ), $name, $email, $username, $plan->id, $plan->name, $aecUser['ip'], $aecUser['isp'] );
		}

		$subject2 = html_entity_decode( $subject2, ENT_QUOTES );
		$message2 = html_entity_decode( $message2, ENT_QUOTES );

		// get superadministrators id
		$admins = $acl->get_group_objects( 25, 'ARO' );

		foreach ( $admins['users'] AS $id ) {
			$query = 'SELECT `email`, `sendEmail`'
					. ' FROM #__users'
					. ' WHERE `id` = \'' . $id . '\''
					;
			$database->setQuery( $query );
			$rows = $database->loadObjectList();

			$row = $rows[0];

			if ( $row->sendEmail ) {
				mosMail( $adminEmail2, $adminName2, $row->email, $subject2, $message2 );
			}
		}
	}

	function getMIflags( $usage, $mi )
	{
		// Get Params
		$params = $this->getParams();

		// Create the Params Prefix
		$flag_name = 'MI_FLAG_USAGE_' . strtoupper( $usage ) . '_MI_' . strtoupper( $mi );

		// Filter out the params for this usage and MI
		$mi_params = array();
		if ( $params ) {
			foreach ( $params as $name => $value ) {
				if ( strpos( $name, $flag_name ) == 0 ) {
					$paramname = substr( strtoupper( $name ), strlen( $flag_name ) + 1 );
					$mi_params[$paramname] = $value;
				}
			}
		}

		// Only return params if they exist
		if ( count( $mi_params ) ) {
			return $mi_params;
		} else {
			return false;
		}
	}

	function setMIflags( $usage, $mi, $flags )
	{
		// Get Params
		$params = $this->getParams();

		// Create the Params Prefix
		$flag_name = 'MI_FLAG_USAGE_' . strtoupper( $usage ) . '_MI_' . $mi;

		// Write to $params array
		foreach ( $flags as $name => $value ) {
			$param_name = $flag_name . '_' . strtoupper( $name );
			$params[$param_name] = $value;
		}

		// Set Params
		$this->setParams( $params );
		$this->check();
		$this->store();
	}
}

class GeneralInfoRequester
{
	/**
	 * Check which CMS system is running
	 * @return string
	 */
	function getCMSName()
	{
		global $mosConfig_absolute_path;

		$filename	= $mosConfig_absolute_path . '/includes/version.php';

		if ( file_exists( $filename ) ) {
			$originalFileHandle = fopen( $filename, 'r' ) or die ( "Cannot open $filename<br />" );
			// Transfer File into variable
			$Data = fread( $originalFileHandle, filesize( $filename ) );
			fclose( $originalFileHandle );

			if ( strpos( $Data, '@package Joomla' ) ) {
				return 'Joomla';
			} elseif ( strpos( $Data, '@package Mambo' ) ) {
				return 'Mambo';
			} else {
				return 'UNKNOWN'; // mic: DO NOT CHANGE THIS VALUE!! (used later)
			}
		} elseif (  defined( 'JPATH_BASE' ) ) {
			return 'Joomla15';
		}
	}

	/**
	 * Check whether a component is installed
	 * @return Bool
	 */
	function detect_component( $component )
	{
		global $database, $mainframe, $aecConfig;

		$tables	= array();
		$tables	= $database->getTableList();

		$overrides = explode( ' ', $aecConfig->cfg['bypassintegration'] );

		if ( in_array( $component, $overrides ) ) {
			return false;
		}

		$pathCB		= $mainframe->getCfg( 'absolute_path' ) . '/components/com_comprofiler/';
		$pathSMF	= $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_smf/';
		switch ( $component ) {
			case 'CB': // Community Builder
				$is_cbe	= ( is_dir( $pathCB. 'enhanced' ) || is_dir( $pathCB . 'enhanced_admin' ) );
				$is_cb	= ( is_dir( $pathCB ) && !$is_cbe );
				return $is_cb;
				break;

			case 'CBE': // Community Builder Enhanced
				$is_cbe = ( is_dir( $pathCB . 'enhanced' ) || is_dir( $pathCB . 'enhanced_admin' ) );
				return $is_cbe;
				break;

			case 'CBM': // Community Builder Moderator for Workflows
				return file_exists( $mainframe->getCfg( 'absolute_path' ) . '/modules/mod_comprofilermoderator.php' );
				break;

			case 'UE': // User Extended
				return in_array( $mainframe->getCfg( 'dbprefix' ) . 'user_extended', $tables );
				break;

			case 'SMF': // Simple Machines Forum
				return file_exists( $pathSMF . 'config.smf.php') || file_exists( $pathSMF . 'smf.php' );
				break;

			case 'VM': // VirtueMart
				return in_array( $mainframe->getCfg( 'dbprefix' ) . 'vm_orders', $tables );
				break;

			case 'JACL': // JACL
				return in_array( $mainframe->getCfg( 'dbprefix' ) . 'jaclplus', $tables );
				break;

         	case 'UHP2':
            	return file_exists( $mainframe->getCfg( 'absolute_path' ) . '/modules/mod_uhp2_manage.php' );
            	break;

         	case 'JUSER':
            	return file_exists( $mainframe->getCfg( 'absolute_path' ) . '/components/com_juser/juser.php' );
            	break;
		}
	}

	/**
	 * Return the list of group id with lower priviledge
	 * @parameter group id
	 * @return string
	 */
	function getLowerACLGroup( $group_id )
	{
		global $database;

		$group_list	= array();
		$groups		= '';

		$query = 'SELECT g2.group_id'
				. ' FROM #__core_acl_aro_groups AS g1'
				. ' INNER JOIN #__core_acl_aro_groups AS g2 ON g1.lft >= g2.lft AND g1.lft <= g2.rgt'
				. ' WHERE g1.group_id = ' . $group_id
				. ' GROUP BY g2.group_id'
				. ' ORDER BY g2.lft'
				;
		$database->setQuery( $query );
		$rows = $database->loadObjectList();

		for( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
		    $row = &$rows[$i];
		    $group_list[$i] = $row->group_id;
		}

		if ( count( $group_list ) > 0 ) {
			return $group_list;
		} else {
			return array();
		}
	}
}

class AECfetchfromDB
{
	function UserIDfromInvoiceNumber( $invoice_number )
	{
		global $database;

		$query = 'SELECT `userid`'
				. ' FROM #__acctexp_invoices'
				. ' WHERE `invoice_number` = \'' . $invoice_number . '\''
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

	function InvoiceIDfromNumber( $invoice_number, $userid = 0, $override_active = false )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_invoices'
				;

		if ( $override_active ) {
			$query .= ' WHERE';
		} else {
			$query .= ' WHERE `active` = \'1\' AND';
		}

		$query .= ' ( `invoice_number` LIKE \'' . $invoice_number . '\''
				. ' OR `secondary_ident` LIKE \'' . $invoice_number . '\' )'
				;

		if ( $userid ) {
			$query .= ' AND `userid` = \'' . $userid . '\'';
		}

		$database->setQuery( $query );
		return $database->loadResult();
	}

	function lastUnclearedInvoiceIDbyUserID( $userid )
	{
		global $database;

		$query = 'SELECT `invoice_number`'
				. ' FROM #__acctexp_invoices'
				. ' WHERE `userid` = \'' . (int) $userid . '\''
				. ' AND `transaction_date` = \'0000-00-00 00:00:00\''
				. ' AND `active` = \'1\''
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

	function lastClearedInvoiceIDbyUserID( $userid, $planid=0 )
	{
		global $database;

		$query = 'SELECT id'
				. ' FROM #__acctexp_invoices'
				. ' WHERE `userid` = \'' . (int) $userid . '\''
				;

		if ( $planid ) {
			$query .= ' AND `usage` = \'' . (int) $planid . '\'';
		}

		$query .= ' ORDER BY `transaction_date` DESC';

		$database->setQuery( $query );
		return $database->loadResult();
	}

	function InvoiceCountbyUserID( $userid )
	{
		global $database;

		$query = 'SELECT count(*)'
				. ' FROM #__acctexp_invoices'
				. ' WHERE `userid` = \'' . (int) $userid . '\''
				. ' AND `active` = \'1\''
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

	function SubscriptionIDfromUserID( $userid )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `userid` = \'' . (int) $userid . '\''
				. ' ORDER BY `primary` DESC'
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

	function UserIDfromSubscriptionID( $susbcriptionid )
	{
		global $database;

		$query = 'SELECT `userid`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `id` = \'' . (int) $susbcriptionid . '\''
				. ' ORDER BY `primary` DESC'
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

}

class AECToolbox
{
	/**
	 * Builds a list of valid currencies
	 *
	 * @param bool	$currMain	main (most important currencies)
	 * @param bool	$currGen	second important currencies
	 * @param bool	$currOth	rest of the world currencies
	 * @since 0.12.4
	 * @return array
	 */
	function _aecCurrencyField( $currMain = false, $currGen = false, $currOth = false, $list_only = false )
	{
		$currencies = array();

		if ( $currMain ) {
			$currencies[] = 'EUR,USD,CHF,CAD,DKK,SEK,NOK,GBP,JPY';
		}

		if ( $currGen ) {
			$currencies[]	= 'AUD,CYP,CZK,EGP,HUF,GIP,HKD,UAH,ISK,'
			. 'EEK,HRK,GEL,LVL,RON,BGN,LTL,MTL,FIM,MDL,ILS,NZD,ZAR,RUB,SKK,'
			. 'TRY,PLN'
			;
		}

		if ( $currOth ) {
			$currencies[]	= 'AFA,DZD,ARS,AMD,AWG,AZM,'
			. 'BSD,BHD,THB,PAB,BBD,BYB,BZD,BMD,VEB,BOB,'
			. 'BRL,BND,BIF,CVE,KYD,GHC,XOF,XAF,XPF,'
			. 'CLP,COP,KMF,BAM,NIO,CRC,CUP,GMD,'
			. 'MKD,AED,DJF,STD,DOP,VND,XCD,SVC,'
			. 'ETB,FKP,FJD,CDF,FRF,HTG,PYG,GNF,'
			. 'GWP,GYD,HKD,UAH,INR,IRR,IQD,JMD,'
			. 'JOD,KES,PGK,LAK,KWD,MWK,ZMK,AOR,MMK,'
			. 'LBP,ALL,HNL,SLL,LRD,LYD,SZL,'
			. 'LSL,MGF,MYR,TMM,MUR,MZM,MXN,'
			. 'MXV,MAD,ERN,NAD,NPR,ANG,'
			. 'AON,TWD,ZRN,BTN,KPW,PEN,MRO,TOP,'
			. 'PKR,XPD,MOP,UYU,PHP,XPT,BWP,QAR,GTQ,'
			. 'ZAL,OMR,KHR,MVR,IDR,RWF,SAR,'
			. 'SCR,XAG,SGD,SBD,KGS,SOS,LKR,SHP,ECS,'
			. 'SDD,SRG,SYP,TJR,BDT,WST,TZS,KZT,TPE,'
			. 'TTD,MNT,TND,UGX,ECV,CLF,USN,USS,UZS,'
			. 'VUV,KRW,YER,CNY,ZWD'
			;
		}

		if ( $list_only ) {
			$currency_code_list = implode( ',', $currencies);
		} else {
			$currency_code_list = array();

			foreach ( $currencies as $currencyfield ) {
				$currency_array = explode( ',', $currencyfield );
				foreach ( $currency_array as $currency ) {
					$currency_code_list[] = mosHTML::makeOption( $currency, constant( '_CURRENCY_' . $currency ) );
				}

				$currency_code_list[] = mosHTML::makeOption( '" disabled="disabled', '- - - - - - - - - - - - - -' );
			}
		}

		return $currency_code_list;
	}

	/**
	 * get user ip & isp
	 *
	 * @return array w/ values
	 */
	function _aecIP()
	{
		// userip & hostname
		$aecUser['ip'] 	= $_SERVER['REMOTE_ADDR'];
		$aecUser['isp'] = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );

		return $aecUser;
	}

	/**
	 * Return a URL based on the sef and user settings
	 * @parameter url
	 * @return string
	 */
	function backendTaskLink( $task, $text )
	{
		global $mosConfig_live_site;

		return '<a href="' .  $mosConfig_live_site . '/administrator/index2.php?option=com_acctexp&amp;task=' . $task . '" title="' . $text . '">' . $text . '</a>';
	}

	/**
	 * Return a URL based on the sef and user settings
	 * @parameter url
	 * @return string
	 */
	function deadsureURL( $url, $secure=false, $internal=false )
	{
		global $mosConfig_live_site, $mosConfig_absolute_path, $database, $aecConfig;

		if ( $aecConfig->cfg['override_reqssl'] ) {
			$secure = false;
		}

		if ( $aecConfig->cfg['simpleurls'] ) {
			$new_url = $mosConfig_live_site . $url;
		} else {
			if ( !strrpos( strtolower( $url ), 'itemid' ) ) {
				global $Itemid;
				if ( $Itemid ) {
					$url .= '&amp;Itemid=' . $Itemid;
				} else {
					$url .= '&amp;Itemid=';
				}
			}

			if ( !function_exists( 'sefRelToAbs' ) ) {
				include_once( $mosConfig_absolute_path . '/includes/sef.php' );
			}

			$new_url = sefRelToAbs( $url );

			if ( !( strpos( $new_url, $mosConfig_live_site ) === 0 ) ) {
				// look out for malformed live_site
				if ( strpos( $mosConfig_live_site, '/' ) === strlen( $mosConfig_live_site ) ) {
					$new_url = substr( $mosConfig_live_site, 0, -1 ) . $new_url;
				} else {
					// It seems we have a sefRelToAbs malfunction (subdirectory is not appended)
					$metaurl = explode( '/', $mosConfig_live_site );
					$rooturl = $metaurl[0] . '//' . $metaurl[2];

					// Replace root to include subdirectory - if all fails, just prefix the live site
					if ( strpos( $new_url, $rooturl ) === 0 ) {
						$new_url = $mosConfig_live_site . substr( $new_url, strlen( $rooturl ) );
					} else {
						$new_url = $mosConfig_live_site . $new_url;
					}
				}
			}
		}

		if ( $secure && ( strpos( $new_url, 'https:' ) !== 0 ) ) {
			$new_url = str_replace( 'http:', 'https:', $new_url );
		}

		if ( $internal ) {
			$new_url = str_replace( '&amp;', '&', $new_url );
		}

		return $new_url;
	}

	/**
	 * Return true if the user exists and is not expired, false if user does not exist
	 * Will reroute the user if he is expired
	 * @parameter username
	 * @return bool
	 */
	function VerifyUsername( $username )
	{
		global $database, $aecConfig;

		$heartbeat = new aecHeartbeat( $database );
		$heartbeat->frontendping();

		$query = 'SELECT id'
		. ' FROM #__users'
		. ' WHERE username = \'' . $username . '\''
		;
		$database->setQuery( $query );
		$id = $database->loadResult();

		$metaUser = new metaUser( $id );

		if ( $metaUser->hasSubscription ) {
			$metaUser->objSubscription->verifyLogin( $metaUser->cmsUser->block );
		} else {
			if ( $aecConfig->cfg['require_subscription'] ) {
				if ( $aecConfig->cfg['entry_plan'] ) {
					$user_subscription = new Subscription( $database );
					$user_subscription->load(0);
					$user_subscription->createNew( $id, 'Free', 1 );

					$metaUser = new metaUser( $id );
					$metaUser->objSubscription->applyUsage( $aecConfig->cfg['entry_plan'], 'none', 1 );
					AECToolbox::VerifyUsername( $username );
				} else {
					$invoices = AECfetchfromDB::InvoiceCountbyUserID( $metaUser->userid );

					if ( $invoices ) {
						$invoice = AECfetchfromDB::lastUnclearedInvoiceIDbyUserID( $metaUser->userid );

						if ( $invoice ) {
							mosRedirect( AECToolbox::deadsureURL( '/index.php?option=com_acctexp&task=pending&userid=' . $id ), false, true );
						}
					}

					mosRedirect( AECToolbox::deadsureURL( '/index.php?option=com_acctexp&task=subscribe&userid=' . $id ), false, true );
					return null;
				}
			}
		}
		return true;
	}

	function VerifyUser( $username )
	{
		global $database, $aecConfig;

		$heartbeat = new aecHeartbeat( $database );
		$heartbeat->frontendping();

		$query = 'SELECT id'
		. ' FROM #__users'
		. ' WHERE username = \'' . $username . '\''
		;
		$database->setQuery( $query );
		$id = $database->loadResult();

		$metaUser = new metaUser( $id );

		if ( $metaUser->hasSubscription ) {
			return $metaUser->objSubscription->verify( $metaUser->cmsUser->block );
		} else {
			if ( $aecConfig->cfg['require_subscription'] ) {
				if ( $aecConfig->cfg['entry_plan'] ) {
					$user_subscription = new Subscription( $database );
					$user_subscription->load(0);
					$user_subscription->createNew( $id, 'Free', 1 );

					$metaUser = new metaUser( $id );
					$metaUser->objSubscription->applyUsage( $aecConfig->cfg['entry_plan'], 'none', 1 );
					return AECToolbox::VerifyUser( $username );
				} else {
					$invoices = AECfetchfromDB::InvoiceCountbyUserID( $metaUser->userid );

					if ( $invoices ) {
						$invoice = AECfetchfromDB::lastUnclearedInvoiceIDbyUserID( $metaUser->userid );

						if ( $invoice ) {
							return 'open_invoice';
						}
					}

					return 'subscribe';
				}
			}
		}
		return true;
	}

	function saveUserRegistration( $option, $var, $internal=false )
	{
		global $database, $mainframe, $task, $acl, $aecConfig; // Need to load $acl for Joomla and CBE

		// Let CB/JUSER think that everything is going fine
		if ( GeneralInfoRequester::detect_component( 'CB' ) || GeneralInfoRequester::detect_component( 'CBE' ) ) {
			if ( GeneralInfoRequester::detect_component( 'CBE' ) ) {
				global $ueConfig;
			}

			$savetask	= $task;
			$task		= 'done';
			include_once ( $mainframe->getCfg( 'absolute_path' ) . '/components/com_comprofiler/comprofiler.php' );
			$task		= $savetask;
		} elseif ( GeneralInfoRequester::detect_component( 'JUSER' ) ) {
			global $mosConfig_absolute_path;

			$savetask	= $task;
			$task		= 'blind';
			include_once( $mainframe->getCfg( 'absolute_path' ) . '/components/com_juser/juser.php' );
			include_once( $mosConfig_absolute_path .'/administrator/components/com_juser/juser.class.php' );
			$task		= $savetask;
		}

		// For joomla and CB, we must filter out some internal variables before handing over the POST data
		$badbadvars = array( 'userid', 'method_name', 'usage', 'processor', 'currency', 'amount', 'invoice', 'id', 'gid' );
		foreach ( $badbadvars as $badvar ) {
			if ( isset( $var[$badvar] ) ) {
				unset( $var[$badvar] );
			}
		}

		$_POST = $var;

		if ( GeneralInfoRequester::detect_component( 'CB' ) || GeneralInfoRequester::detect_component( 'CBE' ) ) {
			// This is a CB registration, borrowing their code to save the user
			@saveRegistration( $option );
		} elseif ( GeneralInfoRequester::detect_component( 'JUSER' ) ) {
			// This is a JUSER registration, borrowing their code to save the user
			saveRegistration( $option );

			$query = 'SELECT `id`'
					. ' FROM #__users'
					. ' WHERE `username` = \'' . $var['username'] . '\''
					;
			$database->setQuery( $query );
			$uid = $database->loadResult();
			JUser::saveUser_ext( $uid );
			//synchronize dublicate user data
			$query = 'SELECT `id`' .
					' FROM #__juser_integration' .
					' WHERE `published` = \'1\'' .
					' AND `export_status` = \'1\'';
    		$database->setQuery( $query );
    		$components = $database->loadObjectList();
    		if ( !empty( $components ) ) {
	    		foreach ( $components as $component ) {
					$synchronize = require_integration( $component->id );
					$synchronize->synchronizeFrom( $uid );
				}
			}
		} else {
			// This is a joomla registration, borrowing their code to save the user
			global $mosConfig_useractivation, $mosConfig_sitename, $mosConfig_live_site;


			if ( defined( 'JPATH_BASE' ) ) {
				global $mainframe;

				// Check for request forgeries
				JRequest::checkToken() or die( 'Invalid Token' );

				// Get required system objects
				$user 		= clone(JFactory::getUser());
				$pathway 	=& $mainframe->getPathway();
				$config		=& JFactory::getConfig();
				$authorize	=& JFactory::getACL();
				$document   =& JFactory::getDocument();

				// If user registration is not allowed, show 403 not authorized.
				$usersConfig = &JComponentHelper::getParams( 'com_users' );
				if ($usersConfig->get('allowUserRegistration') == '0') {
					JError::raiseError( 403, JText::_( 'Access Forbidden' ));
					return;
				}

				// Initialize new usertype setting
				$newUsertype = $usersConfig->get( 'new_usertype' );
				if (!$newUsertype) {
					$newUsertype = 'Registered';
				}

				// Bind the post array to the user object
				if (!$user->bind( JRequest::get('post'), 'usertype' )) {
					JError::raiseError( 500, $user->getError());
				}

				// Set some initial user values
				$user->set('id', 0);
				$user->set('usertype', '');
				$user->set('gid', $authorize->get_group_id( '', $newUsertype, 'ARO' ));

				// TODO: Should this be JDate?
				$user->set('registerDate', date('Y-m-d H:i:s'));

				// If user activation is turned on, we need to set the activation information
				$useractivation = $usersConfig->get( 'useractivation' );
				if ($useractivation == '1')
				{
					jimport('joomla.user.helper');
					$user->set('activation', md5( JUserHelper::genRandomPassword()) );
					$user->set('block', '1');
				}

				// If there was an error with registration, set the message and display form
				if ( !$user->save() )
				{
					JError::raiseWarning('', JText::_( $user->getError()));
					echo JText::_( $user->getError());
					return false;
				}

				$row = $user;
			} else {
				// simple spoof check security
				if ( function_exists( 'josSpoofCheck' ) && !$internal ) {
					josSpoofCheck();
				}

				$row = new mosUser( $database );

				if ( !$row->bind( $_POST, 'usertype' )) {
					mosErrorAlert( $row->getError() );
				}

				mosMakeHtmlSafe( $row );

				$row->id 		= 0;
				$row->usertype 	= '';
				$row->gid 		= $acl->get_group_id( 'Registered', 'ARO' );

				if ( $mosConfig_useractivation == 1 ) {
					$row->activation = md5( mosMakePassword() );
					$row->block = '1';
				}

				if ( !$row->check() ) {
					echo '<script>alert(\''
					. html_entity_decode( $row->getError() )
					. '\');window.history.go(-1);</script>' . "\n";
					exit();
				}

				$pwd 				= $row->password;
				$row->password 		= md5( $row->password );

				$row->registerDate 	= date( 'Y-m-d H:i:s' );

				if ( !$row->store() ) {
					echo '<script>alert(\''
					. html_entity_decode( $row->getError())
					. '\');window.history.go(-1);</script>' . "\n";
					exit();
				}
				$row->checkin();
			}

			$mih = new microIntegrationHandler();
			$mih->userchange($row, $_POST, 'registration');

			$name 		= $row->name;
			$email 		= $row->email;
			$username 	= $row->username;

			$subject 	= sprintf (_SEND_SUB, $name, $mainframe->getCfg( 'sitename' ) );
			$subject 	= html_entity_decode( $subject, ENT_QUOTES );

			if ($mosConfig_useractivation == 1){
				$message = sprintf (_USEND_MSG_ACTIVATE, $name, $mosConfig_sitename, $mosConfig_live_site."/index.php?option=com_registration&task=activate&activation=".$row->activation, $mosConfig_live_site, $username, $pwd);
			} else {
				$message = sprintf (_USEND_MSG, $name, $mosConfig_sitename, $mosConfig_live_site);
			}

			$message = html_entity_decode( $message, ENT_QUOTES );

			// check if Global Config `mailfrom` and `fromname` values exist
			if ( $mainframe->getCfg( 'mailfrom' ) != '' && $mainframe->getCfg( 'fromname' ) != '' ) {
				$adminName2 	= $mainframe->getCfg( 'fromname' );
				$adminEmail2 	= $mainframe->getCfg( 'mailfrom' );
			} else {
				// use email address and name of first superadmin for use in email sent to user
				$query = 'SELECT `name`, `email`'
						. ' FROM #__users'
						. ' WHERE LOWER( usertype ) = \'superadministrator\''
						. ' OR LOWER( usertype ) = \'super administrator\''
						;
				$database->setQuery( $query );
				$rows = $database->loadObjectList();
				$row2 			= $rows[0];

				$adminName2 	= $row2->name;
				$adminEmail2 	= $row2->email;
			}

			// Send email to user
			if ( !$aecConfig->cfg['nojoomlaregemails'] ) {
				mosMail( $adminEmail2, $adminName2, $email, $subject, $message );
			}

			// Send notification to all administrators
			$aecUser	= AECToolbox::_aecIP();

			$subject2	= sprintf( _SEND_SUB, $name, $mainframe->getCfg( 'sitename' ) );
			$message2	= sprintf( _AEC_ASEND_MSG_NEW_REG, $adminName2, $mainframe->getCfg( 'sitename' ), $row->name, $email, $username, $aecUser['ip'], $aecUser['isp'] );

			$subject2	= html_entity_decode( $subject2, ENT_QUOTES );
			$message2	= html_entity_decode( $message2, ENT_QUOTES );

			// get email addresses of all admins and superadmins set to recieve system emails
			$query = 'SELECT email, sendEmail'
			. ' FROM #__users'
			. ' WHERE ( gid = 24 OR gid = 25 )'
			. ' AND sendEmail = 1'
			. ' AND block = 0'
			;
			$database->setQuery( $query );
			$admins = $database->loadObjectList();

			foreach ( $admins as $admin ) {
				// send email to admin & super admin set to recieve system emails
				mosMail( $adminEmail2, $adminName2, $admin->email, $subject2, $message2 );
			}
		}

		// We need the new userid, so we're fetching it from the newly created entry here
		$query = 'SELECT `id`'
				. ' FROM #__users'
				. ' WHERE `username` = \'' . $var['username'] . '\''
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

	function quickVerifyUserID( $userid )
	{
		global $database;

		$query = 'SELECT `status`'
				. ' FROM #__acctexp_subscr'
				. ' WHERE `userid` = \'' . (int) $userid . '\''
				. ' AND `primary` = \'1\''
				;
	 	$database->setQuery( $query );
		$aecstatus = $database->loadResult();

		if ( $aecstatus ) {
			if ( ( strcmp( $aecstatus, 'Active' ) === 0 ) || ( strcmp( $aecstatus, 'Trial' ) === 0 ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return null;
		}
	}

	function correctAmount( $amount )
	{
		if ( strpos( $amount, '.' ) === 0 ) {
			$amount = '0' . $amount;
		} elseif ( strpos( $amount, '.') === false ) {
			if ( strpos( $amount, ',' ) !== false ) {
				$amount = str_replace( ',', '.', $amount );
			} else {
				$amount = $amount . '.00';
			}
		}

		if ( strpos( $amount, '-') ) {
			$amount = '0.00';
		}

		$a		= explode( '.', $amount );
		$amount = $a[0] . '.' . substr( str_pad( $a[1], 2, '0' ), 0, 2 );

		return $amount;
	}

	function computeExpiration( $value, $unit, $timestamp )
	{
		$sign = strpos( $value, '-' ) ? '-' : '+';

		switch ( $unit ) {
			case 'D':
				$add = $sign . $value . ' day';
				break;
			case 'W':
				$add = $sign . $value . ' week';
				break;
			case 'M':
				$add = $sign . $value . ' month';
				break;
			case 'Y':
				$add = $sign . $value . ' year';
				break;
		}

		$timestamp = strtotime( $add, $timestamp );
		return date( 'Y-m-d H:i:s', $timestamp );
	}

	function cleanPOST( $post )
	{
		$badparams = array( 'option', 'task' );

		foreach ( $badparams as $param ) {
			if ( isset( $post[$param] ) ) {
				unset( $post[$param] );
			}
		}

		return $post;
	}


	function getFileArray( $dir, $extension=false, $listDirectories=false, $skipDots=true )
	{
		$dirArray	= array();
		$handle		= dir( $dir );

		while ( false !== ( $file = $handle->read() ) ) {
			if ( ( $file != '.' && $file != '..' ) || $skipDots === true ) {
				if ( $listDirectories === false ) {
					if ( is_dir( $file ) ) {
						continue;
					}
				}
				if ( $extension !== false ) {
					if ( strpos( basename( $file ), $extension ) === false ) {
						continue;
					}
				}

				array_push( $dirArray, basename( $file ) );
       		}
   		}
   		$handle->close();
   		return $dirArray;
	}

	function visualstrlen( $string )
	{

		// Visually Short Chars
		$srt = array( 'i', 'j', 'l', ',', '.' );
		// Visually Long Chars
		$lng = array( 'm', 'w', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Y', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );

		// Break String into individual characters
		$char_array = preg_split( '#(?<=.)(?=.)#s', $string );

		$vlen = 0;
		// Iterate through array counting the visual length of the string
		foreach ( $char_array as $char ) {
			if ( in_array( $char, $srt ) ) {
				$vlen += 0.5;
			} elseif ( in_array( $char, $srt ) ) {
				$vlen += 2;
			} else {
				$vlen += 1;
			}
		}

		return (int) $vlen;
	}

	function str_split_php4( $text, $split = 1 ) {
		// place each character of the string into and array
		$array = array();
		for ( $i=0; $i < strlen( $text ); ){
			$key = NULL;
			for ( $j = 0; $j < $split; $j++, $i++ ) {
				$key .= $text[$i];
			}
			array_push( $array, $key );
		}
		return $array;
	}

	function rewriteEngine( $subject, $metaUser=null, $subscriptionPlan=null, $invoice=null )
	{
		global $aecConfig, $database, $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_offset_user;

		// Check whether a replacement exists at all
		if ( strpos( $subject, '[[' ) === false ) {
			return $subject;
		}

		$rewrite = array();

		$rewrite['system_timestamp']			= strftime( $aecConfig->cfg['display_date_frontend'],  time() + $mosConfig_offset_user * 3600 );
		$rewrite['system_timestamp_backend']	= strftime( $aecConfig->cfg['display_date_backend'], time() + $mosConfig_offset_user * 3600 );
		$rewrite['system_serverstamp_time']	= strftime( $aecConfig->cfg['display_date_frontend'], time() );
		$rewrite['system_server_timestamp_backend']	= strftime( $aecConfig->cfg['display_date_backend'], time() );

		$rewrite['cms_absolute_path']	= $mosConfig_absolute_path;
		$rewrite['cms_live_site']		= $mosConfig_live_site;

		if ( is_object( $metaUser ) ) {

			if ( !empty( $metaUser->hasExpiration ) ) {
				$rewrite['expiration_date'] = $metaUser->objExpiration->expiration;
			}

			// Explode Name
			$namearray		= explode( " ", $metaUser->cmsUser->name );
			$maxname		= count($namearray) - 1;
			$lastname		= $namearray[$maxname];
			for ( $i=0; $i<$maxname; $i++ ) {
				$firstname .= $namearray[$i];
			}
			$firstfirstname	= $namearray[0];

			$rewrite['user_id']					= $metaUser->cmsUser->id;
			$rewrite['user_username']			= $metaUser->cmsUser->username;
			$rewrite['user_name']				= $metaUser->cmsUser->name;
			$rewrite['user_first_name']			= $firstname;
			$rewrite['user_first_first_name']	= $firstfirstname;
			$rewrite['user_last_name']			= $lastname;
			$rewrite['user_email']				= $metaUser->cmsUser->email;

			if ( GeneralInfoRequester::detect_component( 'CB' ) || GeneralInfoRequester::detect_component( 'CBE' ) ) {
				if ( !$metaUser->hasCBprofile ) {
					$metaUser->loadCBuser();
				}

				if ( $metaUser->hasCBprofile ) {
					$query = 'SELECT `name`'
							. ' FROM #__comprofiler_fields'
							. ' WHERE `table` != \'#__users\''
							. ' AND `name` != \'NA\'';
					$database->setQuery( $query );
					$fields = $database->loadResultArray();

					if ( !empty( $fields ) ) {
						foreach ( $fields as $fieldname ) {
							$rewrite['user_' . $fieldname] = $metaUser->cbUser->$fieldname;
						}
					}

					$rewrite['user_activationcode']		= $metaUser->cbUser->cbactivation;
					$rewrite['user_activationlink']		= $mosConfig_live_site."/index.php?option=com_comprofiler&task=confirm&confirmcode=" . $metaUser->cbUser->cbactivation;
				} else {
					$rewrite['user_activationcode']		= $metaUser->cmsUser->activation;
					$rewrite['user_activationlink']		= $mosConfig_live_site."/index.php?option=com_registration&task=activate&activation=" . $metaUser->cmsUser->activation;
				}
			} else {
				$rewrite['user_activationcode']		= $metaUser->cmsUser->activation;
				$rewrite['user_activationlink']		= $mosConfig_live_site."/index.php?option=com_registration&task=activate&activation=" . $metaUser->cmsUser->activation;
			}

			if ( $metaUser->hasSubscription ) {
				$rewrite['subscription_type']			= $metaUser->focusSubscription->type;
				$rewrite['subscription_status']			= $metaUser->focusSubscription->status;
				$rewrite['subscription_signup_date']	= $metaUser->focusSubscription->signup_date;
				$rewrite['subscription_lastpay_date']	= $metaUser->focusSubscription->lastpay_date;
				$rewrite['subscription_plan']			= $metaUser->focusSubscription->plan;
				$rewrite['subscription_previous_plan']	= $metaUser->focusSubscription->previous_plan;
				$rewrite['subscription_recurring']		= $metaUser->focusSubscription->recurring;
				$rewrite['subscription_lifetime']		= $metaUser->focusSubscription->lifetime;
				$rewrite['subscription_expiration_date']		= strftime( $aecConfig->cfg['display_date_frontend'], strtotime( $metaUser->focusSubscription->expiration ) );
				$rewrite['subscription_expiration_date_backend']		= strftime( $aecConfig->cfg['display_date_backend'], strtotime( $metaUser->focusSubscription->expiration ) );
			}

			if ( is_null( $invoice ) ) {
				$lastinvoice = AECfetchfromDB::lastClearedInvoiceIDbyUserID( $metaUser->cmsUser->id );

				$invoice = new Invoice( $database );
				$invoice->load( $lastinvoice );
			}

			if ( is_object( $invoice ) ) {
				$rewrite['invoice_id']					= $invoice->id;
				$rewrite['invoice_number']				= $invoice->invoice_number;
				$rewrite['invoice_created_date']		= $invoice->created_date;
				$rewrite['invoice_transaction_date']	= $invoice->transaction_date;
				$rewrite['invoice_method']				= $invoice->method;
				$rewrite['invoice_amount']				= $invoice->amount;
				$rewrite['invoice_currency']			= $invoice->currency;
				$rewrite['invoice_coupons']				= $invoice->coupons;
			} else {
				$rewrite['invoice_id']					= '';
				$rewrite['invoice_number']				= '';
				$rewrite['invoice_created_date']		= '';
				$rewrite['invoice_transaction_date']	= '';
				$rewrite['invoice_method']				= '';
				$rewrite['invoice_amount']				= '';
				$rewrite['invoice_currency']			= '';
				$rewrite['invoice_coupons']				= '';
			}
		}

		if ( is_object( $subscriptionPlan ) ) {
			$rewrite['plan_name'] = $subscriptionPlan->name;
			$rewrite['plan_desc'] = $subscriptionPlan->getProperty( 'desc' );
		}

		$search = array();
		$replace = array();
		foreach ( $rewrite as $name => $replacement ) {
			$search[]	= '[[' . $name . ']]';
			$replace[]	= $replacement;
		}

		return str_replace( $search, $replace, $subject );
	}

	function rewriteEngineInfo( $switches=array() )
	{
		if ( !count( $switches ) ) {
			$switches = array( 'cms', 'expiration', 'user', 'subscription', 'invoice', 'plan', 'system' );
		}


		$rewrite = array();

		if ( in_array( 'system', $switches ) ) {
			$rewrite['system'][] = 'timestamp';
			$rewrite['system'][] = 'timestamp_backend';
			$rewrite['system'][] = 'server_timestamp';
			$rewrite['system'][] = 'server_timestamp_backend';
		}

		if ( in_array( 'cms', $switches ) ) {
			$rewrite['cms'][] = 'absolute_path';
			$rewrite['cms'][] = 'live_site';
		}

		if ( in_array( 'user', $switches ) ) {
			$rewrite['user'][] = 'id';
			$rewrite['user'][] = 'username';
			$rewrite['user'][] = 'name';
			$rewrite['user'][] = 'first_name';
			$rewrite['user'][] = 'first_first_name';
			$rewrite['user'][] = 'last_name';
			$rewrite['user'][] = 'email';
			$rewrite['user'][] = 'activationcode';
			$rewrite['user'][] = 'activationlink';

			if ( GeneralInfoRequester::detect_component( 'CB' ) || GeneralInfoRequester::detect_component( 'CBE' ) ) {
				global $database;

				$query = 'SELECT name, title'
						. ' FROM #__comprofiler_fields'
						. ' WHERE `table` != \'#__users\''
						. ' AND name != \'NA\'';
				$database->setQuery( $query );
				$objects = $database->loadObjectList();

				if ( is_array( $objects ) ) {
					foreach ( $objects as $object ) {
						$rewrite['user'][] = $object->name;

						if ( strpos( $object->title, '_' ) === 0 ) {
							$content = $object->name;
						} else {
							$content = $object->title;
						}

						$name = '_REWRITE_KEY_USER_' . strtoupper( $object->name );
						if ( !defined( $name ) ) {
							define( $name, $content );
						}
					}
				}
			}
		}

		if ( in_array( 'subscription', $switches ) ) {
			$rewrite['subscription'][] = 'type';
			$rewrite['subscription'][] = 'status';
			$rewrite['subscription'][] = 'signup_date';
			$rewrite['subscription'][] = 'lastpay_date';
			$rewrite['subscription'][] = 'plan';
			$rewrite['subscription'][] = 'previous_plan';
			$rewrite['subscription'][] = 'recurring';
			$rewrite['subscription'][] = 'lifetime';
			$rewrite['subscription'][] = 'expiration_date';
			$rewrite['subscription'][] = 'expiration_date_backend';
		}

		if ( in_array( 'invoice', $switches ) ) {
			$rewrite['invoice'][] = 'id';
			$rewrite['invoice'][] = 'number';
			$rewrite['invoice'][] = 'created_date';
			$rewrite['invoice'][] = 'transaction_date';
			$rewrite['invoice'][] = 'method';
			$rewrite['invoice'][] = 'amount';
			$rewrite['invoice'][] = 'currency';
			$rewrite['invoice'][] = 'coupons';
		}

		if ( in_array( 'plan', $switches ) ) {
			$rewrite['plan'][] = 'name';
			$rewrite['plan'][] = 'desc';
		}

		$return = '';
		foreach ( $rewrite as $area => $keys ) {
			$return .= '<div class="rewriteinfoblock">' . "\n"
			. '<p><strong>' . constant( '_REWRITE_AREA_' . strtoupper( $area ) ) . '</strong></p>' . "\n"
			. '<ul>' . "\n";

			foreach ( $keys as $key ) {
				$return .= '<li>[[' . $area . "_" . $key . ']] =&gt; ' . constant( '_REWRITE_KEY_' . strtoupper( $area . "_" . $key ) ) . '</li>' . "\n";
			}
			$return .= '</ul>' . "\n"
			. '</div>' . "\n";
		}

		return $return;
	}

}

class microIntegrationHandler
{
	function microIntegrationHandler()
	{
		global $mainframe;

		$this->mi_dir = $mainframe->getCfg( 'absolute_path' ) . '/components/com_acctexp/micro_integration';
	}

	function getIntegrationList()
	{
		$list = AECToolbox::getFileArray( $this->mi_dir, 'php', false, true );

		$integration_list = array();
		foreach ( $list as $name ) {
			$parts = explode( '.', $name );
			$integration_list[] = $parts[0];
		}

		return $integration_list;
	}

	function getPlansbyMI ( $mi_id )
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_plans'
				. ' WHERE `micro_integrations` != \'\''
				;
		$database->setQuery( $query );
		$plans = $database->loadResultArray();

		$plan_list = array();
		foreach ( $plans as $planid ) {
			$plan = new SubscriptionPlan( $database );
			$plan->load( $planid );
			$mis = $plan->getMicroIntegrations();
			if ( is_array( $mis ) ) {
				if ( in_array( $mi_id, $mis ) ) {
					$plan_list[] = $planid;
				}
			}
		}

		return $plan_list;
	}

	function userPlanExpireActions( $metaUser, $subscription_plan )
	{
		global $database;

		$mi_autointegrations = $this->getAutoIntegrations();

		if ( is_array( $mi_autointegrations ) || ( $subscription_plan !== false ) ) {

			$user_integrations		= explode( ';', $subscription_plan->micro_integrations );
			$user_auto_integrations = array_intersect( $user_integrations, $mi_autointegrations );

			if ( count( $user_auto_integrations ) ) {
				foreach ( $user_auto_integrations as $mi_id ) {
					$mi = new microIntegration( $database );
					$mi->load( $mi_id );
					if ( $mi->callIntegration() ) {
						$mi->expiration_action( $metaUser, $subscription_plan );
					}
				}
			}
		}
	}

	function getHacks()
	{
		$integrations = $this->getIntegrationList();

		$hacks = array();
		foreach ( $integrations as $n => $name ) {
			$file = $this->mi_dir . '/' . $name . '.php';

			if ( file_exists( $file ) ) {
				include_once $file;

				$mi = new $name();

				if ( method_exists( $mi, 'hacks' ) ) {
					if ( method_exists( $mi, 'detect_application' ) ) {
						if ( $mi->detect_application() ) {
							$mihacks = $mi->hacks();
							if ( is_array( $mihacks ) ) {
								$hacks = array_merge( $hacks, $mihacks );
							}
						}
					}
				}
			}
		}

		return $hacks;
	}

	function getAutoIntegrations()
	{
		global $database;

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `auto_check` = \'1\''
				;
		$database->setQuery( $query );
		return $database->loadResultArray();
	}

	function getUserChangeIntegrations()
	{
		global $database;

		$query = 'SELECT id'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `active` = \'1\''
				. ' AND `on_userchange` = \'1\''
				;
		$database->setQuery( $query );
		return $database->loadResultArray();
	}

	function userchange( $row, $post, $trace = '' )
	{
		global $database;

		$mi_list = $this->getUserChangeIntegrations();

		if ( !is_object( $row ) ) {
			$userid = $row;

			$row = new mosUser( $database );
			$row->load( $userid );
		}

		if ( count( $mi_list ) > 0 ) {
			foreach ( $mi_list as $mi_id ) {
				if ( !is_null( $mi_id ) && ( $mi_id != '' ) && $mi_id ) {
					$mi = new microIntegration($database);
					$mi->load( $mi_id );
					if ( $mi->callIntegration() ) {
						$mi->on_userchange_action( $row, $post, $trace );
					}
				}
			}
		}
	}
}

class MI
{
	function autoduplicatesettings( $settings, $ommit=array() )
	{
		if ( isset( $settings['lists'] ) ) {
			$lists = $settings['lists'];
			unset( $settings['lists'] );
		} else {
			$lists = array();
		}

		$new_settings = array();
		$new_lists = array();
		foreach ( $settings as $name => $content ) {
			if ( in_array( $name, $ommit) ) {
				continue;
			}

			$new_settings[$name]				= $content;
			$new_settings[$name.'_exp']		= $content;
			$new_settings[$name.'_pre_exp']	= $content;
		}

		if ( !empty( $new_lists ) ) {
			$new_settings['lists'] = $lists;
		}

		return $new_settings;
	}
}

class microIntegration extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $active 			= null;
	/** @var int */
	var $system 			= null;
	/** @var int */
	var $ordering			= null;
	/** @var string */
	var $name				= null;
	/** @var text */
	var $desc				= null;
	/** @var string */
	var $class_name			= null;
	/** @var text */
	var $params				= null;
	/** @var int */
	var $auto_check			= null;
	/** @var int */
	var $pre_exp_check		= null;
	/** @var int */
	var $on_userchange		= null;

	function microIntegration(&$db)
	{
		$this->mosDBTable( '#__acctexp_microintegrations', 'id', $db );

		if ( !defined( '_AEC_LANG_INCLUDED_MI' ) ) {
			$this->_callMILanguage();
		}
	}

	function _callMILanguage()
	{
		global $mainframe;

		$langPathMI = $mainframe->getCfg( 'absolute_path' ) . '/components/com_acctexp/micro_integration/language/';
		if ( file_exists( $langPathMI . $mainframe->getCfg( 'lang' ) . '.php' ) ) {
			include_once( $langPathMI . $mainframe->getCfg( 'lang' ) . '.php' );
		} else {
			include_once( $langPathMI . 'english.php' );
		}
	}

	function mi_exists( $mi_id )
	{
		global $database;

		$query = 'SELECT count(*)'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `id` = \'' . $mi_id . '\''
				;
		$database->setQuery( $query );
		return $database->loadResult();
	}

	function callDry( $mi_name )
	{
		global $mosConfig_absolute_path;

		$this->class_name = $mi_name;

		$filename = $mosConfig_absolute_path . '/components/com_acctexp/micro_integration/' . $this->class_name . '.php';

		$this->callIntegration( true );
	}

	function callIntegration( $override = 0 )
	{
		global $mosConfig_absolute_path;

		$filename = $mosConfig_absolute_path . '/components/com_acctexp/micro_integration/' . $this->class_name . '.php';

		if ( ( ( !$this->active && $this->id ) || !file_exists( $filename ) ) && !$override ) {
			// MI does not exist or is deactivated
			return false;
		} elseif ( file_exists( $filename ) ) {
			include_once $filename;

			$class = $this->class_name;

			$this->mi_class = new $class();

			$this->mi_class->id = $this->id;

			$info = $this->mi_class->Info();

			if ( is_null( $this->name ) || ( $this->name == '' ) ) {
				$this->name = $info['name'];
			}

			if ( is_null( $this->desc ) || ( $this->desc == '' ) ) {
				$this->desc = $info['desc'];
			}

			return true;
		} else {
			return false;
		}
	}

	function action( $metaUser, $exchange=null, $invoice=null, $objplan=null )
	{
		if ( !is_array( $exchange ) ) {
			$params = $this->getExchangedSettings( $exchange );
		} else {
			$params = $this->getParams();
		}

		return $this->mi_class->action( $params, $metaUser, $invoice, $objplan );
	}

	function pre_expiration_action( $metaUser, $objplan=null )
	{
		if ( method_exists( $this->mi_class, 'pre_expiration_action' ) ) {
			$userflags = $metaUser->focusSubscription->getMIflags( $objplan->id, $this->id );

			// We need the standard variables and their uppercase pendants
			// System MI vars have to be stored and will automatically converted to uppercase
			$spc = strtoupper( 'system_preexp_call' );
			$spca = $spc . strtoupper( '_abandoncheck' );

			// Check whether we have userflags to work with
			if ( is_array( $userflags ) && !empty( $userflags ) ) {
				// Check for this specific flag
				if ( isset( $userflags[$spc] ) && isset( $userflags[$spca] ) ) {
					if ( !( time() > $userflags[$spc] ) ) {
						// This call has already been made
						return false;
					}
				}
			}

			$newflags[$spc]		= strtotime( $metaUser->focusSubscription->expiration );
			$newflags[$spca]	= time();

			// Create the new flags
			$metaUser->focusSubscription->setMIflags( $objplan->id, $this->id, $newflags );

			$params = $this->getParams();

			return $this->mi_class->pre_expiration_action( $params, $metaUser, $objplan );
		} else {
			return null;
		}
	}

	function expiration_action( $metaUser, $objplan=null )
	{
		if ( method_exists( $this->mi_class, 'expiration_action' ) ) {
			$params = $this->getParams();

			return $this->mi_class->expiration_action( $params, $metaUser, $objplan );
		} else {
			return null;
		}
	}

	function on_userchange_action( $row, $post, $trace )
	{
		if ( method_exists( $this->mi_class, 'on_userchange_action' ) ) {
			$params = $this->getParams();

			return $this->mi_class->on_userchange_action( $params, $row, $post, $trace );
		} else {
			return null;
		}
	}

	function profile_info( $userid )
	{
		$params = $this->getParams();

		if ( method_exists( $this->mi_class, 'profile_info' ) ) {
			return $this->mi_class->profile_info( $params, $userid );
		} else {
			return null;
		}
	}

	function getSettings()
	{
		// See whether an install is neccessary (and possible)
		if ( method_exists( $this->mi_class, 'checkInstallation' ) && method_exists( $this->mi_class, 'install' ) ) {
			if ( !$this->mi_class->checkInstallation() ) {
				$this->mi_class->install();
			}
		}

		$params = $this->getParams();

		if ( method_exists( $this->mi_class, 'Settings' ) ) {
			if ( method_exists( $this->mi_class, 'Defaults' ) && empty( $params ) ) {
				$params = $this->mi_class->Defaults();
			}

			$settings = $this->mi_class->Settings( $params );

			// Autoload Params if they have not been called in by the MI
			foreach ( $settings as $name => $setting ) {
				// Do we have a parameter at first position?
				if ( isset( $setting[1] ) && !isset( $setting[3] ) ) {
					if ( isset( $params[$name] ) ) {
						$settings[$name][3] = $params[$name];
					}
				} else {
					if ( isset( $params[$name] ) ) {
						$settings[$name][1] = $params[$name];
					}
				}
			}

			return $settings;
		} else {
			return false;
		}
	}

	function getExchangedSettings( $exchange )
	{
		$settings = $this->getParams();

		 foreach ( $settings as $key => $value ) {
		 	if ( isset( $exchange[$key] ) ) {
				if ( !is_null( $exchange[$key] ) && ( $exchange[$key] != '' ) ) {
		 			// Exception for NULL case
		 			// TODO: SET_TO_NULL undocumented!!!
		 			if ( strcmp( $exchange[$key], '[[SET_TO_NULL]]' ) === 0 ) {
		 				$settings[$key] = '';
		 			} else {
		 				$settings[$key] = $exchange[$key];
		 			}
				}
		 	}
		 }

		return $settings;
	}

	function savePostParams( $array )
	{
		// Strip out params that we don't need
		$params = $this->stripNonParams($array);

		// Add variables that should be common to all calls
		// TODO: Replace this with setting properties of the object
		$params = $this->addCommonParamInfo($params);

		// Check whether there is a custom function for saving params
		if ( method_exists( $this->mi_class, 'saveparams' ) ) {
			$new_params = $this->mi_class->saveparams( $params );
		} else {
			$new_params = $params;
		}

		// Strip out common variables
		$new_params = $this->stripcommonParamInfo( $new_params );

		$this->setParams( $new_params );

		return true;
	}

	function stripNonParams( $array )
	{
		// All variables of the class have to be stripped out
		$vars = get_class_vars( 'microIntegration' );

		foreach ( $vars as $name => $blind ) {
			if ( isset( $array[$name] ) ) {
				unset( $array[$name] );
			}
		}

		return $array;
	}

	function addCommonParamInfo( $params=array() )
	{
		$params['MI_ID'] = $this->id;

		return $params;
	}

	function stripcommonParamInfo( $params )
	{
		// Borrowing the original array for this
		$commonparams = $this->addCommonParamInfo();

		foreach ($commonparams as $key) {
			if (isset($params[$key])) {
				unset($params[$key]);
			}
		}

		return $params;
	}

	function getSettingsDescriptions()
	{
		// TODO: Find out what this was about!
	}

	function delete ()
	{
		$params = $this->getParams();

		// Maybe this function needs special actions on delete?
		// TODO: There should be a way to manage complete deletion of use of an MI type
		if ( method_exists( $this->mi_class, 'delete' ) ){
			$this->mi_class->delete( $params );
		}
	}
}

class couponsHandler
{
	function applyCoupons( $amount, $coupons, $metaUser )
	{
		$applied_coupons = array();
		$global_nomix = array();
		foreach ( $coupons as $arrayid => $coupon_code ) {
			$cph = new couponHandler();
			$cph->load( $coupon_code );

			// Get the coupons that this one cannot be mixed with
			if ( $cph->restrictions['restrict_combination'] ) {
				$nomix = explode( ';', $cph->restrictions['bad_combinations'] );
			} else {
				$nomix = array();
			}

			if ( count( array_intersect( $applied_coupons, $nomix ) ) || in_array( $coupon_code, $global_nomix ) ) {
				// This coupon either interferes with one of the coupons already applied, or the other way round
			} else {
				if ( $cph->status ) {
					// Coupon approved, checking restrictions
					$cph->checkRestrictions( $metaUser );
					if ( $cph->status ) {
						$amount = $cph->applyCoupon( $amount );
						$applied_coupons[] = $coupon_code;
						$global_nomix = array_merge( $global_nomix, $nomix );
					} else {
						// Coupon restricted for this user, thus it needs to be deleted later on
					}
				} else {
					// Coupon not approved, thus it needs to be deleted later on
				}
			}
		}

		return $amount;
	}
}

class couponHandler
{
	/** @var bool */
	var $status				= null;
	/** @var string */
	var $error				= null;
	/** @var object */
	var $coupon				= null;

	function couponHandler()
	{
	}

	function setError( $error )
	{
		// Status = NOT OK
		$this->status = false;
		// Set error message
		$this->error = $error;
	}

	function load( $coupon_code )
	{
		global $database;

		// Get this coupons id from the static table
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_coupons_static'
				. ' WHERE `coupon_code` = \'' . $coupon_code . '\''
				;
		$database->setQuery( $query );
		$couponid = $database->loadResult();

		if ( $couponid ) {
			// Its static, so set type to 1
			$this->type = 1;
		} else {
			// Coupon not found, take the regular table
			$query = 'SELECT `id`'
					. ' FROM #__acctexp_coupons'
					. ' WHERE `coupon_code` = \'' . $coupon_code . '\''
					;
			$database->setQuery( $query );
			$couponid = $database->loadResult();

			// Its not static, so set type to 0
			$this->type = 0;
		}

		if ( $couponid ) {
			// Status = OK
			$this->status = true;

			// establish coupon object
			$this->coupon = new coupon( $database, $this->type );
			$this->coupon->load( $couponid );

			// Check whether coupon is active
			if ( !$this->coupon->active ) {
				$this->setError( _COUPON_ERROR_EXPIRED );
			}

			// load parameters into local array
			$this->discount		= $this->coupon->getParams( 'discount' );
			$this->restrictions = $this->coupon->getParams( 'restrictions' );

			// Check whether coupon can be used yet
			if ( $this->restrictions['has_start_date'] ) {
				$expstamp = strtotime( $this->restrictions['start_date'] );

				// Error: Use of this coupon has not started yet
				if ( ( $expstamp > 0 ) && ( ( $expstamp-time() ) > 0 ) ) {
					$this->setError( _COUPON_ERROR_NOTSTARTED );
				}
			}

			// Check whether coupon is expired
			if ( $this->restrictions['has_expiration'] ) {
				$expstamp = strtotime( $this->restrictions['expiration'] );

				// Error: Use of this coupon has expired
				if ( ( $expstamp > 0 ) && ( ( $expstamp-time() ) < 0 ) ) {
					$this->setError( _COUPON_ERROR_EXPIRED );
					$this->coupon->deactivate();
				}
			}

			// Check for max reuse
			if ( $this->restrictions['has_max_reuse'] ) {
				if ( $this->restrictions['max_reuse'] ) {

					// Error: Max Reuse of this coupon is exceeded
					if ( (int) $this->coupon->usecount > (int) $this->restrictions['max_reuse'] ) {
						$this->setError( _COUPON_ERROR_MAX_REUSE );
						return;
					}
				}
			}

			// Check for dependency on subscription
			if ( !empty( $this->restrictions['depend_on_subscr_id'] ) ) {
				if ( $this->restrictions['subscr_id_dependency'] ) {
					// See whether this subscription is active
					$query = 'SELECT `status`'
							. ' FROM #__acctexp_subscr'
							. ' WHERE `id` = \'' . $this->restrictions['subscr_id_dependency'] . '\''
							;
					$database->setQuery( $query );

					$subscr_status = strtolower( $database->loadResult() );

					// Error: The Subscription this Coupon depends on has run out
					if ( ( strcmp( $subscr_status, 'active' ) === 0 ) || ( ( strcmp( $subscr_status, 'trial' ) === 0 ) && $this->restrictions['allow_trial_depend_subscr'] ) ) {
						$this->setError( _COUPON_ERROR_SPONSORSHIP_ENDED );
						return;
					}
				}
			}
		} else {
			// Error: Coupon does not exist
			$this->setError( _COUPON_ERROR_NOTFOUND );
		}
	}

	function switchType()
	{
		global $database;

		// Duplicate Coupon at other table
		$newcoupon = new coupon( $database, !$this->type );
		$newcoupon->createNew( $this->coupon->coupon_code, $this->coupon->created_date );

		// Switch id over to new table max
		$oldid = $this->coupon->id;
		$newid = $newcoupon->getMax();

		// Delete old coupon
		$this->coupon->delete();

		// Create new entry
		$this->coupon = $newcoupon;

		// Migrate usage entries
		$query = 'UPDATE #__acctexp_couponsxuser'
				. ' SET `coupon_id` = \'' . $newid . '\''
				. ' WHERE `coupon_id` = \'' . $oldid . '\''
				;

		$database->setQuery( $query );
		$database->query();
	}

	function incrementCount( $invoice )
	{
		global $database;

		// Get existing coupon relations for this user
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_couponsxuser'
				. ' WHERE `userid` = \'' . $invoice->userid . '\''
				. ' AND `coupon_id` = \'' . $this->coupon->id . '\''
				. ' AND `type` = \'' . $this->type . '\''
				;

		$database->setQuery( $query );
		$id = $database->loadResult();

		$couponxuser = new couponXuser( $database );

		if ( $id ) {
			// Relation exists, update count
			global $mosConfig_offset_user;

			$couponxuser->load( $id );
			$couponxuser->usecount += 1;
			$couponxuser->addInvoice( $invoice->invoice_number );
			$couponxuser->last_updated = date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );
			$couponxuser->check();
			$couponxuser->store();
		} else {
			// Relation does not exist, create one
			$couponxuser->createNew( $invoice->userid, $this->coupon, $this->type );
			$couponxuser->addInvoice( $invoice->invoice_number );
			$couponxuser->check();
			$couponxuser->store();
		}

		$this->coupon->incrementcount();
	}

	function decrementCount( $invoice )
	{
		global $database;

		// Get existing coupon relations for this user
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_couponsxuser'
				. ' WHERE `userid` = \'' . $invoice->userid . '\''
				. ' AND `coupon_id` = \'' . $this->coupon->id . '\''
				. ' AND `type` = \'' . $this->type . '\''
				;

		$database->setQuery( $query );
		$id = $database->loadResult();

		$couponxuser = new couponXuser( $database );

		// Only do something if a relation exists
		if ( $id ) {
			global $mosConfig_offset_user;

			// Decrement use count
			$couponxuser->load( $id );
			$couponxuser->usecount -= 1;
			$couponxuser->last_updated = date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );

			if ( $couponxuser->usecount ) {
				// Use count is 1 or above - break invoice relation but leave overall relation intact
				$couponxuser->delInvoice( $invoice->invoice_number );
				$couponxuser->check();
				$couponxuser->store();
			} else{
				// Use count is 0 or below - delete relationship
				$couponxuser->delete();
			}
		}

		$this->coupon->decrementcount();
	}

	function checkRestrictions( $metaUser, $original_amount=null, $invoiceFactory=null )
	{
		// Load Restrictions and resulting Permissions
		$restrictions	= $this->getRestrictionsArray();
		$permissions	= $metaUser->permissionResponse( $restrictions );

		// Check for a set usage
		if ( !empty( $this->restrictions['usage_plans_enabled'] ) && !is_null( $invoiceFactory ) ) {
			if ( $this->restrictions['usage_plans'] ) {
				// Check whether this usage is restricted
				// TODO: Make this convert to an array (I think something went wrong when I last tried it)
				if ( strpos( $this->restrictions['usage_plans'], ';' ) !== false ) {
					$plans = explode( ';', $this->restrictions['usage_plans'] );

					if ( in_array( $invoiceFactory->usage, $plans ) ) {
						$permissions['usage'] = true;
					} else {
						$permissions['usage'] = false;
					}
				} else {
					if ( (int) $invoiceFactory->usage === (int) $this->restrictions['usage_plans'] ) {
						$permissions['usage'] = true;
					} else {
						$permissions['usage'] = false;
					}
				}
			}
		}

		// Check for Trial only
		if ( $this->discount['useon_trial'] && !$this->discount['useon_full'] && !is_null( $original_amount ) ) {
			if ( !is_null( $original_amount ) ) {
				if ( is_array( $original_amount ) ) {
					if ( isset( $original_amount['amount']['amount1'] ) ) {
						$permissions['trial_only'] = true;
					} else {
						$permissions['trial_only'] = false;
					}
				}
			}
		}

		// Check for max reuse per user
		if ( $this->restrictions['has_max_peruser_reuse'] ) {
			if ( $this->restrictions['has_max_reuse'] ) {
				if ( (int) $metaUser->usedCoupon( $this->coupon->id, $this->type ) > (int) $this->restrictions['has_max_reuse'] ) {
					$this->setError( _COUPON_ERROR_MAX_REUSE );
					return;
				}
			}
		}

		// Plot out error messages
		if ( count( $permissions ) ) {
			foreach ( $permissions as $name => $value ) {
				if ( !$permissions[$name] ) {
					switch ( $name ) {
						// ACL Permission Errors
						case 'mingid':			$this->setError( _COUPON_ERROR_PERMISSION );			break;
						case 'maxgid':			$this->setError( _COUPON_ERROR_PERMISSION );			break;
						case 'setgid':			$this->setError( _COUPON_ERROR_PERMISSION );			break;
						// Plan Permission Errors
						case 'usage':			$this->setError( _COUPON_ERROR_WRONG_USAGE );			break;
						case 'trial_only':		$this->setError( _COUPON_ERROR_TRIAL_ONLY );			break;
						// Plan History or Status Errors
						case 'plan_previous':	$this->setError( _COUPON_ERROR_WRONG_PLAN_PREVIOUS );	break;
						case 'plan_present':	$this->setError( _COUPON_ERROR_WRONG_PLAN_PRESENT );	break;
						case 'plan_overall':	$this->setError( _COUPON_ERROR_WRONG_PLANS_OVERALL );	break;
						case 'plan_amount_min': $this->setError( _COUPON_ERROR_WRONG_PLAN_PRESENT );	break;
						case 'plan_amount_max': $this->setError( _COUPON_ERROR_WRONG_PLANS_OVERALL );	break;
					}
					return false;
				}
			}
		}

		return true;
	}

	function getRestrictionsArray()
	{
		$restrictions = array();

		// Check for a fixed GID - this certainly overrides the others
		if ( !empty( $this->restrictions['fixgid_enabled'] ) ) {
			$restrictions['fixgid'] = (int) $this->restrictions['fixgid'];
		} else {
			// No fixed GID, check for min GID
			if ( !empty( $this->restrictions['mingid_enabled'] ) ) {
				$restrictions['mingid'] = (int) $this->restrictions['mingid'];
			}
			// Check for max GID
			if ( !empty( $this->restrictions['maxgid_enabled'] ) ) {
				$restrictions['maxgid'] = (int) $this->restrictions['maxgid'];
			}
		}

		// Check for a directly previously used plan
		if ( !empty( $this->restrictions['previousplan_req_enabled'] ) ) {
			if ( $this->restrictions['previousplan_req'] ) {
				$restrictions['plan_previous'] = (int) $this->restrictions['previousplan_req'];
			}
		}

		// Check for a currently used plan
		if ( !empty( $this->restrictions['currentplan_req_enabled'] ) ) {
			if ( $this->restrictions['currentplan_req'] ) {
				$restrictions['plan_present'] = (int) $this->restrictions['currentplan_req'];
			}
		}

		// Check for a overall used plan
		if ( !empty( $this->restrictions['currentplan_req_enabled'] ) ) {
			if ( $this->restrictions['currentplan_req'] ) {
				$restrictions['plan_overall'] = (int) $this->restrictions['currentplan_req'];
			}
		}

		// Check for a overall used plan with amount minimum
		if ( !empty( $this->restrictions['used_plan_min_enabled'] ) ) {
			if ( $this->restrictions['used_plan_min_amount'] && $this->restrictions['used_plan_min'] ) {
				$restrictions['plan_amount_min'] = ( (int) $this->restrictions['used_plan_min'] )
				. ',' . ( (int) $this->restrictions['used_plan_min_amount'] );
			}
		}

		// Check for a overall used plan with amount maximum
		if ( !empty( $this->restrictions['used_plan_max_enabled'] ) ) {
			if ( $this->restrictions['used_plan_max_amount'] && $this->restrictions['used_plan_max'] ) {
				$restrictions['plan_amount_max'] = ( (int) $this->restrictions['used_plan_max'] )
				. ',' . ( (int) $this->restrictions['used_plan_max_amount'] );
			}
		}

		return $restrictions;
	}

	function getInfo( $amount )
	{
		$this->code = $this->coupon->coupon_code;
		$this->name = $this->coupon->name;

		if ( is_array( $amount ) ) {
			$newamount = $this->applyCoupon( $amount['amount'] );
		} else {
			$newamount = $this->applyCoupon( $amount );
		}

		// Load amount or convert amount array to current amount
		if ( is_array( $newamount ) ) {
			if ( isset( $newamount['amount1'] ) ) {
				$this->amount = $newamount['amount1'];
			} elseif ( isset( $newamount['amount2'] ) ) {
				$this->amount = $newamount['amount2'];
			} elseif ( isset( $newamount['amount3'] ) ) {
				$this->amount = $newamount['amount3'];
			}
		} else {
			$this->amount = $newamount;
		}

		// Load amount or convert discount amount array to current amount
		if ( is_array( $newamount ) ) {
			if ( isset( $newamount['amount1'] ) ) {
				$this->discount_amount = $amount['amount']['amount1'] - $newamount['amount1'];
			} elseif ( isset( $newamount['amount2'] ) ) {
				$this->discount_amount = $amount['amount']['amount3'] - $newamount['amount2'];
			} elseif ( isset( $newamount['amount3'] ) ) {
				$this->discount_amount = $amount['amount']['amount3'] - $newamount['amount3'];
			}
		} else {
			$this->discount_amount = $amount['amount'] - $newamount;
		}

		$action = '';

		// Convert chosen rules to user information
		if ( $this->discount['percent_first'] ) {
			if ( $this->discount['amount_percent_use'] ) {
				$action .= '-' . $this->discount['amount_percent'] . '%';
			}
			if ( $this->discount['amount_use'] ) {
				if ( !( $action === '' ) ) {
					$action .= ' &amp; ';
				}
				$action .= '-' . $this->discount['amount'];
			}
		} else {
			if ( $this->discount['amount_use']) {
				$action .= '-' . $this->discount['amount'];
			}
			if ($this->discount['amount_percent_use']) {
				if ( !( $action === '' ) ) {
					$action .= ' &amp; ';
				}
				$action .= '-' . $this->discount['amount_percent'] . '%';
			}
		}

		$this->action = $action;
	}

	function applyCoupon( $amount )
	{
		// Distinguish between recurring and one-off payments
		if ( is_array( $amount ) ) {
			// Check for Trial Rules
			if ( isset( $amount['amount1'] ) ) {
				if ( $this->discount['useon_trial'] ) {
					if ( $amount['amount1'] > 0 ) {
						$amount['amount1'] = $this->applyDiscount( $amount['amount1'] );
					}
				}
			}

			// Check for Full Rules
			if ( isset( $amount['amount3'] ) ) {
				if ( $this->discount['useon_full'] ) {
					if ( $this->discount['useon_full_all'] ) {
						$amount['amount3']	= $this->applyDiscount($amount['amount3']);
					} else {
						if ( $amount['amount1'] > 0 ) {
							$amount['amount2']	= $this->applyDiscount($amount['amount3']);
							$amount['period2']	= $amount['period3'];
							$amount['unit2']	= $amount['unit3'];
						} else {
							$amount['amount1']	= $this->applyDiscount($amount['amount3']);
							$amount['period1']	= $amount['period3'];
							$amount['unit1']	= $amount['unit3'];
						}
					}
				}
			}
		} else {
			$amount = $this->applyDiscount( $amount );
		}

		return $amount;
	}

	function applyDiscount( $amount )
	{
		// Apply Discount according to rules
		if ( $this->discount['percent_first'] ) {
			if ( $this->discount['amount_percent_use'] ) {
				$amount -= ( ( $amount / 100 ) * $this->discount['amount_percent'] );
			}
			if ( $this->discount['amount_use'] ) {
				$amount -= $this->discount['amount'];
			}
		} else {
			if ( $this->discount['amount_use'] ) {
				$amount -= $this->discount['amount'];
			}
			if ( $this->discount['amount_percent_use'] ) {
				$amount -= ( ( $amount / 100 ) * $this->discount['amount_percent'] );
			}
		}

		// Fix Amount if broken and return
		return AECToolbox::correctAmount( $amount );
	}
}

class coupon extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $active				= null;
	/** @var int */
	var $ordering			= null;
	/** @var string */
	var $coupon_code		= null;
	/** @var datetime */
	var $created_date 		= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $desc				= null;
	/** @var text */
	var $discount			= null;
	/** @var text */
	var $restrictions		= null;
	/** @var text */
	var $params				= null;
	/** @var int */
	var $usecount			= null;
	/** @var text */
	var $micro_integrations	= null;

	function coupon( &$db, $type )
	{
		if ( $type ) {
			$this->mosDBTable( '#__acctexp_coupons_static', 'id', $db );
		} else {
			$this->mosDBTable( '#__acctexp_coupons', 'id', $db );
		}
	}

	function deactivate()
	{
		$this->active = 0;
		$this->check();
		$this->store();
	}

	function createNew( $code=null, $created=null )
	{
		$this->id		= 0;
		$this->active	= 1;
		// Override creation of new Coupon Code if one is supplied
		if ( is_null( $code ) ) {
			$this->coupon_code = $this->generateCouponCode();
		} else {
			$this->coupon_code = $code;
		}
		// Set created date if supplied
		if ( is_null( $created ) ) {
			global $mosConfig_offset_user;

			$this->created_date = date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );
		} else {
			$this->created_date = $created;
		}
		$this->usecount = 0;
	}

	function savePOSTsettings( $post )
	{

		// Filter out fixed variables
		$fixed = array( 'active', 'name', 'desc', 'coupon_code', 'usecount', 'micro_integrations' );

		foreach ( $fixed as $varname ) {
			if ( is_array( $post[$varname] ) ) {
				$this->$varname = implode( ';', $post[$varname] );
			} else {
				$this->$varname = $post[$varname];
			}
			unset( $post[$varname] );
		}

		// Filter out params
		$fixed = array( 'amount_use', 'amount', 'amount_percent_use', 'amount_percent', 'percent_first', 'useon_trial', 'useon_full', 'useon_full_all' );

		$params = array();
		foreach ( $fixed as $varname ) {
			if ( is_array( $post[$varname] ) ) {
				$params[$varname] = implode( ';', $post[$varname] );
			} else {
				$params[$varname] = $post[$varname];
			}
			unset( $post[$varname] );
		}

		$this->saveDiscount( $params );

		// The rest of the vars are restrictions
		$restrictions = array();

		foreach ( $post as $varname => $content ) {
			if ( is_array( $content ) ) {
				$restrictions[$varname] = implode( ';', $content );
			} else {
				$restrictions[$varname] = $content;
			}
			unset( $post[$varname] );
		}

		$this->saveRestrictions( $restrictions );
	}

	function saveDiscount( $params )
	{
		// Correct a malformed Amount
		if ( !strlen( $params['amount'] ) ) {
			$params['amount_use'] = 0;
		} else {
			$params['amount'] = AECToolbox::correctAmount( $params['amount'] );
		}

		$this->setParams( $params, 'discount' );
	}

	function saveRestrictions( $restrictions )
	{
		$this->setParams( $restrictions, 'restrictions' );
	}

	function incrementCount()
	{
		$this->usecount += 1;
		$this->check();
		$this->store();
	}

	function decrementCount()
	{
		$this->usecount -= 1;
		$this->check();
		$this->store();
	}

	function generateCouponCode( $maxlength = 6 )
	{
		global $database;

		$numberofrows = 1;

		while ( $numberofrows ) {
			$inum =	strtoupper( substr( base64_encode( md5( rand() ) ), 0, $maxlength ) );
			// check single coupons
			$query = 'SELECT count(*)'
					. ' FROM #__acctexp_coupons'
					. ' WHERE `coupon_code` = \'' . $inum . '\''
					;
			$database->setQuery( $query );
			$numberofrows_normal = $database->loadResult();

			// check static coupons
			$query = 'SELECT count(*)'
					. ' FROM #__acctexp_coupons_static'
					. ' WHERE `coupon_code` = \'' . $inum . '\''
					;
			$database->setQuery( $query );
			$numberofrows_static = $database->loadResult();

			$numberofrows = $numberofrows_normal + $numberofrows_static;
		}
		return $inum;
	}
}

class couponXuser extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $coupon_id			= null;
	/** @var int */
	var $coupon_type		= null;
	/** @var string */
	var $coupon_code		= null;
	/** @var int */
	var $userid				= null;
	/** @var datetime */
	var $created_date 		= null;
	/** @var datetime */
	var $last_updated		= null;
	/** @var text */
	var $params				= null;
	/** @var int */
	var $usecount			= null;

	function couponXuser( &$db )
	{
		$this->mosDBTable( '#__acctexp_couponsxuser', 'id', $db );
	}

	function createNew( $userid, $coupon, $type, $params=null )
	{
		global $mosConfig_offset_user;

		$this->id = 0;
		$this->coupon_id = $coupon->id;
		$this->coupon_type = $type;
		$this->coupon_code = $coupon->coupon_code;
		$this->userid = $userid;
		$this->created_date = date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );
		$this->last_updated = date( 'Y-m-d H:i:s', time() + $mosConfig_offset_user*3600 );

		if ( is_array( $params ) ) {
			$this->setParams( $params );
		}

		$this->usercount = 1;

		$this->check();
		$this->store();
	}

	function getInvoiceList()
	{
		$params = $this->getParams();

		$invoicelist = array();
		if ( isset( $params['invoices'] ) ) {
			$invoices = explode( ';', $params['invoices'] );

			foreach ( $invoices as $invoice ) {
				$inv = explode( ',', $invoice );

				$invoicelist[$invoice[0]] = $invoice[1];
			}
		}

		return $invoicelist;
	}

	function setInvoiceList( $invoicelist )
	{
		$invoices = array();

		foreach ( $invoicelist as $invoicenumber => $counter ) {
			$invoices[] = $invoicenumber . ',' . $counter;
		}

		$params['invoices'] = implode( ';', $invoices );

		$this->addParams( $params );
	}

	function addInvoice( $invoicenumber )
	{
		$invoicelist = $this->getInvoiceList();

		if ( isset( $invoicelist[$invoicenumber] ) ) {
			$invoicelist[$invoicenumber] += 1;
		} else {
			$invoicelist[$invoicenumber] = 1;
		}

		$this->setInvoiceList( $invoicelist );
	}

	function delInvoice( $invoicenumber )
	{
		$invoicelist = $this->getInvoiceList();

		if ( !isset( $invoicelist[$invoicenumber] ) ) {
			$invoicelist[$invoicenumber] -= 1;
		}

		if ( $invoicelist[$invoicenumber] === 0 ) {
			unset( $invoicelist[$invoicenumber] );
		}

		$this->setInvoiceList( $invoicelist );
	}
}

// Not yet active code for future features

class tokenCheck
{
	function tokenCheck( $token_id, $userid )
	{
		global $database;

		$token = new accessToken($database);
		$token->load( $token_id );

		$user = new mosUser( $database );
		$user->load( $userid );
		$groups	= GeneralInfoRequester::getLowerACLGroup( $this->mingid );

		$status = array();
		$status['status'] = true;
		$status['reason'] = 'none';

		if ( !$token->active ) {
			$status['status'] = false;
			$status['reason'] = 'deactivated';
		} elseif ( !in_array( $user->gid, $groups ) ) {
			$status['status'] = false;
			$status['reason'] = 'permissions';
		}

		if ( !$status['reason'] ) {
			return $status;
		}

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_usertokens'
				. ' WHERE `userid` = \'' . (int) $userid . '\''
				. ' AND `token_id` = \'' . $token . '\''
				;
		$database->setQuery( $query );
		$usertoken_id = $database->loadResult();

		if ( $usertoken_id ) {
			$usertoken = new userToken( $database );
			$usertoken->load( $usertoken_id );
		} else {
			return false;
		}
	}
}

class userToken extends paramDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $userid				= null;
	/** @var int */
	var $active				= null;
	/** @var string */
	var $token_id			= null;
	/** @var datetime */
	var $created_date 		= null;
	/** @var datetime */
	var $firstused_date		= null;
	/** @var datetime */
	var $expiration			= null;

	function userToken( &$db )
	{
		$this->mosDBTable( '#__acctexp_usertokens', 'id', $db );
	}

	function createToken( $groupid, $tokenid, $userid )
	{
		global $database, $mosConfig_offset_user;

		$now = time() + $mosConfig_offset_user*3600;

		$token_group = new tokenGroup($database);
		$token_group->load($groupid);

		$this->id			= 0;
		$this->userid		= $userid;
		$this->token_id		= $tokenid;
		$this->created_date	= $now;

		if ( $token_group->peramount ) {
			$expiration = AECToolbox::computeExpiration( $token_group->peramount, $token_group->perunit, $now );
			$this->expiration = $expiration;
		} else {
			// We'll leave this at zero - the best indication that there is no expiration
		}
	}
}

class tokenBatch extends mosDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $userid				= null;
	/** @var int */
	var $amount				= null;
	/** @var int */
	var $group_id			= null;
	/** @var datetime */
	var $created_date 		= null;
	/** @var datetime */
	var $expiration			= null;

	function tokenBatch(&$db)
	{
		$this->mosDBTable( '#__acctexp_tokenbatches', 'id', $db );
	}

	function tearToken( $tokenid, $userid )
	{
		global $database;

		$token = new userToken($database);
		$result = $token->createToken( $this->group_id, $tokenid, $userid );

		if ( $result === true ) {

		} else {

		}
	}
}

class accessToken extends mosDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $token_id			= null;
	/** @var int */
	var $active				= null;
	/** @var int */
	var $token_group_id		= null;

	function accessToken(&$db)
	{
		$this->mosDBTable( '#__acctexp_accesstokens', 'id', $db );
	}
}

class tokenGroup extends mosDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $active				= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $desc				= null;
	/** @var string */
	var $amount				= null;
	/** @var int */
	var $period				= null;
	/** @var string */
	var $perunit			= null;
	/** @var datetime */
	var $created_date 		= null;
	/** @var int */
	var $has_start_date		= null;

	function tokenGroup(&$db)
	{
		$this->mosDBTable( '#__acctexp_tokengroups', 'id', $db );
	}
}
?>

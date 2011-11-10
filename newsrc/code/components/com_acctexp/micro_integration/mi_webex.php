<?php
/**
 * @version $Id: mi_webex.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - Webex
 * @copyright 2011 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_webex
{
	function Info()
	{
		$info = array();
		$info['name'] = JText::_('AEC_MI_NAME_IDEV');
		$info['desc'] = JText::_('AEC_MI_DESC_IDEV');

		return $info;
	}

	function Settings()
	{
		$settings = array();
		$settings['setupinfo']			= array( 'fieldset' );
		$settings['profile']			= array( 'inputC' );
		$settings['directory']			= array( 'inputC' );
		$settings['use_curl']			= array( 'list_yesno' );
		$settings['onlycustomparams']	= array( 'list_yesno' );
		$settings['customparams']		= array( 'inputD' );

		$rewriteswitches				= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );

		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		return $settings;
	}

	function CommonData()
	{
		return array( 'profile', 'directory', 'use_curl' );
	}

	function action( $request )
	{
		$db = &JFactory::getDBO();

		$rooturl = $this->getPath();

		$getparams = array();

		if ( !empty( $this->settings['profile'] ) ) {
			$getparams[] = 'profile=' . $this->settings['profile'];
		}

		if ( empty( $request->invoice->amount ) ) {
			return null;
		}

		$getparams[] = 'idev_saleamt=' . $request->invoice->amount;
		$getparams[] = 'idev_ordernum=' . $request->invoice->invoice_number;

		if ( !empty( $this->settings['onlycustomparams'] ) && !empty( $this->settings['customparams'] ) ) {
			$getparams = array();
		}

		$userflags = $request->metaUser->focusSubscription->getMIflags( $request->plan->id, $this->id );

		if ( !empty( $userflags['idev_ip_address'] ) ) {
			$ip = $userflags['idev_ip_address'];
		} else {
			if ( isset( $request->metaUser->focusSubscription->params['creator_ip'] ) ) {
				$ip = $request->metaUser->focusSubscription->params['creator_ip'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			$newflags['idev_ip_address'] = $ip;
			$request->metaUser->focusSubscription->setMIflags( $request->plan->id, $this->id, $newflags );
		}

		$getparams[] = 'ip_address=' . $ip;

		if ( !empty( $this->settings['customparams'] ) ) {
			$rw_params = AECToolbox::rewriteEngineRQ( $this->settings['customparams'], $request );

			if ( strpos( $rw_params, "\r\n" ) !== false ) {
				$cps = explode( "\r\n", $rw_params );
			} else {
				$cps = explode( "\n", $rw_params );
			}

			foreach ( $cps as $cp ) {
				$getparams[] = $cp;
			}
		}

		$newget = array();
		foreach ( $getparams as $v ) {
			$va = explode( '=', $v, 2 );

			$newget[] = urlencode($va[0]) . '=' . urlencode($va[1]);
		}

		if ( !empty( $this->settings['use_curl'] ) ) {
			$ch = curl_init();
			$curl_url = $rooturl . "/sale.php?" . implode( '&', $newget );
			curl_setopt($ch, CURLOPT_URL, $curl_url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_exec($ch);
			curl_close($ch);
		} else {
			$text = '<img border="0" '
					.'src="' . $rooturl .'/sale.php?' . implode( '&amp;', $newget ) . '" '
					.'width="1" height="1" />';

			$displaypipeline = new displayPipeline($db);
			$displaypipeline->create( $request->metaUser->userid, 1, 0, 0, null, 1, $text );
		}

		return true;
	}

	function apiUserSignup( $request )
	{
		$name = $request->metaUser->explodeName();

		$array = array(	'AT' => 'SU',
						'FN' => $name['first'],
						'LN' => $name['last'],
						'EM' => $request->metaUser->cmsUser->email,
						'PW' => $request->metaUser->cmsUser->password,
						'PID' => $this->settings['pid'],
						'WID' => $request->metaUser->username,
		);

		return $this->apiCall( $array );
	}

	function apiActivateUser( $request )
	{
		$array = array(	'AT' => 'AC',
						'PID' => $this->settings['pid'],
						'WID' => $request->metaUser->username,
		);

		return $this->apiCall( $array );
	}

	function apiDeactivateUser( $request )
	{
		$array = array(	'AT' => 'IN',
						'PID' => $this->settings['pid'],
						'WID' => $request->metaUser->username,
		);

		return $this->apiCall( $array );
	}

	function apiCall( $array )
	{
		
	}
}
?>
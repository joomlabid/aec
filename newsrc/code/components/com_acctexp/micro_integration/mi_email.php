<?php
/**
 * @version $Id: mi_email.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - Email
 * @copyright 2006-2010 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_email extends MI
{
	function Info()
	{
		$info = array();
		$info['name'] = _AEC_MI_NAME_EMAIL;
		$info['desc'] = _AEC_MI_DESC_EMAIL;

		return $info;
	}

	function Settings()
	{
		$rewriteswitches				= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );

		$settings = array();
		$settings['sender']				= array( 'inputE' );
		$settings['sender_name']		= array( 'inputE' );

		$settings['recipient']			= array( 'inputE' );
		$settings['cc']					= array( 'inputE' );
		$settings['bcc']				= array( 'inputE' );
		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		$settings['aectab_reg']			= array( 'tab', 'Regular Email', 'Regular Email' );
		$settings['subject']			= array( 'inputE' );
		$settings['text_html']			= array( 'list_yesno' );
		$settings['text']				= array( !empty( $this->settings['text_html'] ) ? 'editor' : 'inputD' );
		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		$settings['aectab_first']		= array( 'tab', 'First Email', 'First Email' );
		$settings['subject_first']		= array( 'inputE' );
		$settings['text_first_html']	= array( 'list_yesno' );
		$settings['text_first']			= array( !empty( $this->settings['text_first_html'] ) ? 'editor' : 'inputD' );
		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		$settings['aectab_exp']			= array( 'tab', 'Expiration Email', 'Expiration Email' );
		$settings['subject_exp']		= array( 'inputE' );
		$settings['text_exp_html']		= array( 'list_yesno' );
		$settings['text_exp']			= array( !empty( $this->settings['text_exp_html'] ) ? 'editor' : 'inputD' );
		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		$settings['aectab_preexp']		= array( 'tab', 'Pre-Expiration Email', 'Pre-Expiration Email' );
		$settings['subject_pre_exp']	= array( 'inputE' );
		$settings['text_pre_exp_html']	= array( 'list_yesno' );
		$settings['text_pre_exp']		= array( !empty( $this->settings['text_pre_exp_html'] ) ? 'editor' : 'inputD' );
		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		return $settings;
	}

	function relayAction( $request )
	{
		if ( $request->action == 'action' ) {
			if ( !empty( $this->settings['text_first'] ) ) {
				if ( empty( $request->metaUser->objSubscription->previous_plan ) ) {
					$request->area = '_first';
				}
			}
		}

		if ( !isset( $this->settings['text' . $request->area] ) || !isset( $this->settings['subject' . $request->area] ) ) {
			return null;
		}

		$message	= AECToolbox::rewriteEngineRQ( $this->settings['text' . $request->area], $request );
		$subject	= AECToolbox::rewriteEngineRQ( $this->settings['subject' . $request->area], $request );

		if ( empty( $message ) ) {
			return null;
		}

		$recipients = $cc = $bcc = array();

		$rec_groups = array( "recipients", "cc", "bcc" );

		foreach ( $rec_groups as $setting ) {
			$list = AECToolbox::rewriteEngineRQ( $this->settings[$setting], $request );

			$recipient_array = explode( ',', $list );

	        if ( !empty( $recipient_array ) ) {
		        foreach ( $recipient_array as $k => $email ) {
		            $$setting[$k] = trim( $email );
		        }
	        }
		}

		JUTility::sendMail( $this->settings['sender'], $this->settings['sender_name'], $recipients, $subject, $message, $this->settings['text' . $request->area . '_html'], $cc, $bcc );

		return true;
	}
}
?>

<?php
/**
 * @version $Id: payboxat.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Paybox.ch
 * @copyright 2007-2013 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class processor_payboxat extends SOAPprocessor
{
	function info()
	{
		$info = array();
		$info['name'] 			= 'payboxat';
		$info['longname']	 	= JText::_('CFG_PAYBOXAT_LONGNAME');
		$info['statement']		= JText::_('CFG_PAYBOXAT_STATEMENT');
		$info['description']	= JText::_('CFG_PAYBOXAT_DESCRIPTION');
		$info['currencies']		= AECToolbox::aecCurrencyField( true, true, true, true );
		$info['languages']		= AECToolbox::getISO639_1_codes();
		$info['cc_list'] 		= "visa,mastercard,discover,americanexpress,echeck,jcb,dinersclub";
		$info['recurring'] 		= 0;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']			= 0;
		$settings['username']			= "your_username";
		$settings['password']			= "your_password";
		$settings['merchant_phone']		= "your_phone_number";
		$settings['currency']			= "EUR";
		$settings['language']			= "DE";
		$settings['item_name']			= sprintf( JText::_('CFG_PROCESSOR_ITEM_NAME_DEFAULT'), '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		$settings['customparams']		= '';

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['testmode']			= array( "toggle" );
		$settings['username'] 			= array( "inputC" );
		$settings['password'] 			= array( "inputC" );
		$settings['merchant_phone']		= array( "inputC" );
		$settings['currency']			= array( "list_currency" );
		$settings['language']			= array( "list_language" );
		$settings['item_name']			= array( "inputE" );
		$settings['customparams']		= array( 'inputD' );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	function checkoutform( $request )
	{
		$var = $this->getUserform( array(), array( 'phone' ) );

		return $var;
	}

	function createRequestXML( $request )
	{
		$a = array();

		$a['language']		= strtolower( $this->settings['language'] );
		$a['isTest']		= $this->settings['testmode'] ? true : false;
		$a['payer']			= $request->int_var['params']['billPhone'];
		$a['payee']			= $this->settings['merchant_phone'];
		$a['caller']		= null;
		$a['amount']		= (int) ( $request->int_var['amount'] * 100 );
		$a['currency']		= $this->settings['currency'];
		$a['paymentDays']	= null;
		$a['timestamp']		= strftime("%H:%M:%S.%Y%m%d");
		$a['posId']			= null;
		$a['orderId']		= $request->invoice->invoice_number;
		$a['text']			= substr( AECToolbox::rewriteEngineRQ( $this->settings['item_name'], $request ), 0, 16 );
		$a['sessionId']		= session_id();

		$a = $this->customParams( $this->settings['customparams'], $a, $request );

		return $a;
	}

	function transmitRequestXML( $content, $request )
	{
		$path = "/gw-tx/services/PayboxServices?wsdl";

		$url = "https://" . $this->settings['username'] . ":" . $this->settings['password'] . "@www.paybox.at" . $path;

		$headers = '<credentials>'
					. '<username>' . (string) $this->settings['username'] . '</username>'
					. '<password>' . (string) $this->settings['password'] . '</password>'
					. '</credentials>';

		//echo "<p>Bitte warten Sie w&auml;hrend das paybox-System versucht Sie anzurufen.</p>";

		$options = array( "login" => $this->settings['username'], "password" => $this->settings['password'] );

		$response = $this->transmitRequest( $url, 'payment', $content, $headers, $options );

		$return['valid']	= false;
		$return['raw']		= $response['raw'];

		if ( $response ) {
			if ( empty( $response['error'] ) ) {
				// acknowledge the transaction to Paybox
				if ( is_object( $response['raw'] ) ) {
					$id = $response['raw']->idTransaction;
				} else {
					$id = $response['raw']['idTransaction'];
				}

				$params = array('language' => strtolower( $this->settings['language'] ), 'transactionRef' => $id );

				$resp = $this->followupRequest('acknowledge', $params );

				if ( !isset( $resp['error'] ) ) {
					$return['valid'] = true;
				} else {
					$return['error'] = $resp['error'];
				}
			} else {
				$return['error'] = $response['errorDescription'];
			}
		}

		return $return;
	}
}
?>

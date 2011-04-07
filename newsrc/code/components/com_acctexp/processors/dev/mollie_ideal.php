<?php
/**
 * @version $Id: mollie_ideal.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Mollie iDEAL
 * @author Thailo van Ree, David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @copyright 2006-2011 Copyright (C) David Deutsch
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

define( '_CFG_MOLLIE_IDEAL_LONGNAME', 'iDEAL via Mollie' );
define( '_CFG_MOLLIE_IDEAL_STATEMENT', 'iDEAL via Mollie zonder vaste kosten of procedures' );
define( '_CFG_MOLLIE_IDEAL_DESCRIPTION', 'iDEAL via Mollie zonder vaste kosten of procedures' );

define( '_CFG_MOLLIE_IDEAL_PARTNER_ID_NAME', 'Partner ID' );
define( '_CFG_MOLLIE_IDEAL_PARTNER_ID_DESC', 'Uw Mollie Partner ID' );
define( '_CFG_MOLLIE_IDEAL_DESCRIPTION_NAME', 'Omschrijving' );
define( '_CFG_MOLLIE_IDEAL_DESCRIPTION_DESC', 'Orderinfo t.b.v. payment processor' );


class processor_mollie_ideal extends XMLprocessor
{
	function info()
	{
		$info = array();
		$info['name']					= 'mollie_ideal';
		$info['longname']				= _CFG_MOLLIE_IDEAL_LONGNAME;
		$info['statement']				= _CFG_MOLLIE_IDEAL_STATEMENT;
		$info['description']			= _CFG_MOLLIE_IDEAL_DESCRIPTION;
		$info['currencies']				= 'EUR';
		$info['languages']				= 'NL';
		$info['recurring']	   			= 0;
		$info['notify_trail_thanks']	= true;

		return $info;
	}

	function settings()
	{
		$settings = array();

		$settings['testmode']		= 0;
		$settings['partner_id']		= "00000";
		$settings['currency']		= 'EUR';
		$settings['description']	= sprintf( _CFG_PROCESSOR_ITEM_NAME_DEFAULT, '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		
		$settings['customparams']	= "";
		
		return $settings;
	}

	function backend_settings()
	{
		$settings = array();

		$settings['testmode']		= array( 'list_yesno' );
		$settings['partner_id']		= array( 'inputC' );
		$settings['currency']		= array( 'list_currency' );
		$settings['description']	= array( 'inputE' );
		
		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	function checkoutform( $request )
	{
		require_once('mollie/cls.ideal.php');
		
		$var = array();
		
		$mollieIdeal = new iDEAL_Payment( $this->settings['partner_id'] );
		
		if ( $this->settings['testmode'] ) {
			$mollieIdeal->setTestmode( true );
		} else {
			$mollieIdeal->setTestmode( false );
		}		
	
		$bankList = $mollieIdeal->getBanks();

		if( $bankList ) {
		
			foreach ( $bankList as $key => $name ) {
				$options[]	= JHTML::_('select.option', $key, $name );
			}
	
			$var['params']['lists']['bank_id'] = JHTML::_( 'select.genericlist', $options, 'bank_id', 'size="1"', 'value', 'text', null );
			$var['params']['bank_id'] = array( 'list', 'Kies uw bank', null );			

		} else {		
		
			// error handling
			$this->___logError( "iDEAL_Payment::getBanks failed", 
								$mollieIdeal->getErrorCode(), 
								$mollieIdeal->getErrorMessage() 
								);
		}

		return $var;
	}

	function createRequestXML( $request )
	{
		return "";
	}

	function transmitRequestXML( $xml, $request )
	{
		require_once('mollie/cls.ideal.php');

		$response			= array();
		$response['valid']	= false;

		$description 	= substr( AECToolbox::rewriteEngineRQ( $this->settings['description'], $request ), 0, 29 );
		$report_url		= AECToolbox::deadsureURL( "index.php?option=com_acctexp&task=mollie_idealnotification" );
		$return_url		= $request->int_var['return_url'];
		$amount			= $request->int_var['amount']*100;
		
		$mollieIdeal = new iDEAL_Payment( $this->settings['partner_id'] );

		if ( $this->settings['testmode'] ) {
			$mollieIdeal->setTestmode( true );
		} else {
			$mollieIdeal->setTestmode( false );
		}		

		if ( $mollieIdeal->createPayment( $request->int_var['params']['bank_id'], $amount, $description, $return_url, $report_url ) ) {
			
			// ...Request valid transaction id from Mollie and store it...
			$request->invoice->secondary_ident = $mollieIdeal->getTransactionId();
			$request->invoice->storeload();
			
			// Redirect to issuer bank
			return aecRedirect( $mollieIdeal->getBankURL() );
			
		} else {
		
			// error handling
			$this->___logError( "iDEAL_Payment::createPayment failed", 
								$mollieIdeal->getErrorCode(), 
								$mollieIdeal->getErrorMessage() 
								);
			
			return $response;
		}
	}

	function parseNotification( $post )
	{
		$response				= array();
		$response['valid']		= false;
		$response['invoice']	= aecGetParam( 'transaction_id', '', true, array( 'word', 'string', 'clear_nonalnum' ) );
		
		return $response;
	}

	function validateNotification( $response, $post, $invoice )
	{
		require_once('mollie/cls.ideal.php');

		$response				= array();
		$response['valid']		= false;

		$transaction_id = aecGetParam( 'transaction_id', '', true, array( 'word', 'string', 'clear_nonalnum' ) );
		
		if ( strlen( $transaction_id ) ) {
			
			$mollieIdeal = new iDEAL_Payment( $this->settings['partner_id'] );	
			$mollieIdeal->checkPayment( $transaction_id );
			
			if ( $mollieIdeal->getPaidStatus() ) {				
				$response['valid'] = true;				
			} else {
				// error handling
				$response['error']		= true;
				$response['errormsg']	= 'iDEAL_Payment::checkPayment failed';			
				
				$this->___logError( "iDEAL_Payment::checkPayment failed", 
									$mollieIdeal->getErrorCode(), 
									$mollieIdeal->getErrorMessage() 
									);
			}
		}
		
		return $response;
	}
	
	function ___logError( $shortdesc, $errorcode, $errordesc )
	{
		$db = &JFactory::getDBO();

		$short	= $shortdesc;
		$event	= $shortdesc . '; Error code: ' . $errorcode . '; Error(s): ' . $errordesc;
		$tags	= 'processor,mollie ideal';
		$params = array();

		$eventlog = new eventLog( $db );
		$eventlog->issue( $short, $tags, $event, 128, $params );		
	}
}
?>
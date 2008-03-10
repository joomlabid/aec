<?php
// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/**
* AcctExp Component
* @package AcctExp
* @subpackage processor
* @copyright 2007 Helder Garcia, David Deutsch
* @license http://www.gnu.org/copyleft/gpl.html. GNU Public License
* @author Bruno Pourtier <bruno.pourtier@gmail.com>
**/

class processor_ewayXML extends XMLprocessor
{
	var $processor_name = 'ewayXML';

	function info()
	{
		$info = array();
		$info['name']			= 'ewayXML';
		$info['longname']		= _CFG_EWAYXML_LONGNAME;
		$info['statement']		= _CFG_EWAYXML_STATEMENT;
		$info['description']	= _CFG_EWAYXML_DESCRIPTION;
		$info['cc_list']		= 'visa,mastercard';
		$info['recurring']		= 0;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']		= "1";
		$settings['custId']			= "87654321";
		$settings['tax']			= "10";
		$settings['autoRedirect']	= 1;
		$settings['testAmount']		= "00";
		$settings['item_name']		= sprintf( _CFG_PROCESSOR_ITEM_NAME_DEFAULT, '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		$settings['rewriteInfo']	= ''; // added mic
		$settings['SiteTitle']		= '';

		return $settings;
	}

	function backend_settings( $cfg )
	{
		$settings = array();
		$rewriteswitches			= array( 'cms', 'user', 'expiration', 'subscription', 'plan' );

		$settings['testmode']		= array( 'list_yesno' );
		$settings['custId']			= array( 'inputC' );
		$settings['autoRedirect']	= array( 'list_yesno' ) ;
		$settings['SiteTitle']		= array( 'inputC' );
		$settings['item_name']		= array( 'inputE' );

        $settings = AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		return $settings;
	}

	function createRequestXML($int_var, $settings, $metaUser, $new_subscription){

		$order_total = $int_var['amount'] * 100;
		$my_trxn_number = uniqid( "eway_" );

		$nodes = array(	"ewayCustomerID" => $settings['custId'],
					"ewayTotalAmount" => $order_total,
					"ewayCustomerFirstName" => $metaUser->cmsUser->username,
					"ewayCustomerLastName" => $metaUser->cmsUser->name,
					"ewayCustomerInvoiceDescription" => AECToolbox::rewriteEngine( $settings['item_name'], $metaUser, $new_subscription ),
					"ewayCustomerInvoiceRef" => $int_var['invoice'],
					"ewayOption1" => $metaUser->cmsUser->id, //Send in option1, the id of the user
					"ewayOption2" => $int_var['invoice'], //Send in option2, the invoice number
					"ewayTrxnNumber" => $my_trxn_number,
					"ewaySiteTitle" => $settings['SiteTitle'],
					"ewayCardHoldersName" => $int_var['params']['cardHolder'],
					"ewayCardNumber" => $int_var['params']['cardNumber'],
					"ewayCardExpiryMonth" => $int_var['params']['expirationMonth'],
					"ewayCardExpiryYear" => $int_var['params']['expirationYear'],
					"ewayCustomerEmail" => $metaUser->cmsUser->email,
					"ewayCustomerAddress" => '',
					"ewayCustomerPostcode" => '',
					"ewayOption3" => ''
					);
		$xml = '<ewaygateway>';

		foreach($nodes as $name => $value){
			$xml .= "<" . $name . ">" . $value . "</" . $name . ">";
		}
		$xml .= '</ewaygateway>';

		return $xml;
	}

	function transmitRequestXML($xml, $int_var, $settings, $metaUser, $new_subscription)
	{
		if($settings['testmode']){
			$url = 'https://www.eway.com.au/gateway/xmltest/testpage.asp';
		}else{
			$url = 'https://www.eway.com.au/gateway/xmlpayment.asp';
		}
		$response = array();

		if($objResponse = simplexml_load_string($this->transmitRequest($url,'',$xml))){


			$response['amount_paid'] = $objResponse->ewayReturnAmount / 100;
			$response['invoice'] = $objResponse->ewayTrxnOption2;
			//$response['raw'] = $objResponse->ewayTrxnError;

			if($objResponse->ewayTrxnStatus == 'True'){
				$response['valid'] = 1;
			}else{
				$response['valid'] = 0;
				$response['error'] = $objResponse->ewayTrxnError;
			}
		}else{
			$response['valid'] = 0;
			$response['error'] = _CFG_EWAYXML_CONNECTION_ERROR;
		}

		return $response;
	}

	function checkoutform()
	{
		$var['params']['cardHolder'] = array( 'inputC', _AEC_CCFORM_CARDHOLDER_NAME, _AEC_CCFORM_CARDHOLDER_NAME, '');
		// Request the Card number
		$var['params']['cardNumber'] = array( 'inputC', _AEC_CCFORM_CARDNUMBER_NAME, _AEC_CCFORM_CARDNUMBER_NAME, '');

		// Create a selection box with 12 months
		$months = array();
		for( $i = 1; $i < 13; $i++ ){
			$month = str_pad( $i, 2, "0", STR_PAD_LEFT );
			$months[] = mosHTML::makeOption( $month, $month );
		}

		$var['params']['lists']['expirationMonth'] = mosHTML::selectList($months, 'expirationMonth', 'size="4"', 'value', 'text', 0);
		$var['params']['expirationMonth'] = array( 'list', _AEC_CCFORM_EXPIRATIONMONTH_NAME, _AEC_CCFORM_EXPIRATIONMONTH_DESC);

		// Create a selection box with the next 10 years
		$year = date('Y');
		$years = array();
		for( $i = $year; $i < $year + 10; $i++ ) {
			$years[] = mosHTML::makeOption( $i, $i );
		}

		$var['params']['lists']['expirationYear'] = mosHTML::selectList($years, 'expirationYear', 'size="4"', 'value', 'text', 0);
		$var['params']['expirationYear'] = array( 'list', _AEC_CCFORM_EXPIRATIONYEAR_NAME, _AEC_CCFORM_EXPIRATIONYEAR_DESC);

		return $var;
	}

	function doTheCurl( $url, $content )
	{
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml") );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $content );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return $response;
	}
}
?>
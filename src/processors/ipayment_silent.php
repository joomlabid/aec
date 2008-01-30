<?php
// Copyright (C) 2006-2007 David Deutsch
// All rights reserved.
// This source file is part of the Account Expiration Control Component, a  Joomla
// custom Component By Helder Garcia and David Deutsch - http://www.globalnerd.org
//
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
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.

// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class processor_ipayment_silent extends XMLprocessor
{
	function info()
	{
		$info = array();
		$info['name'] = 'ipayment_silent';
		$info['longname'] = _CFG_IPAYMENT_SILENT_LONGNAME;
		$info['statement'] = _CFG_IPAYMENT_SILENT_STATEMENT;
		$info['description'] = _CFG_IPAYMENT_SILENT_DESCRIPTION;
		$info['currencies'] = 'AFA,DZD,ADP,ARS,AMD,AWG,AUD,AZM,BSD,BHD,THB,PAB,BBD,BYB,BEF,BZD,BMD,VEB,BOB,BRL,BND,BGN,BIF,CAD,CVE,KYD,GHC,XOF,XAF,XPF,CLP,COP,KMF,BAM,NIO,CRC,CUP,CYP,CZK,GMD,'.
								'DKK,MKD,DEM,AED,DJF,STD,DOP,VND,GRD,XCD,EGP,SVC,ETB,EUR,FKP,FJD,HUF,CDF,FRF,GIP,XAU,HTG,PYG,GNF,GWP,GYD,HKD,UAH,ISK,INR,IRR,IQD,IEP,ITL,JMD,JOD,KES,PGK,LAK,EEK,'.
								'HRK,KWD,MWK,ZMK,AOR,MMK,GEL,LVL,LBP,ALL,HNL,SLL,ROL,BGL,LRD,LYD,SZL,LTL,LSL,LUF,MGF,MYR,MTL,TMM,FIM,MUR,MZM,MXN,MXV,MDL,MAD,BOV,NGN,ERN,NAD,NPR,ANG,NLG,YUM,ILS,'.
								'AON,TWD,ZRN,NZD,BTN,KPW,NOK,PEN,MRO,TOP,PKR,XPD,MOP,UYU,PHP,XPT,PTE,GBP,BWP,QAR,GTQ,ZAL,ZAR,OMR,KHR,MVR,IDR,RUB,RUR,RWF,SAR,ATS,SCR,XAG,SGD,SKK,SBD,KGS,SOS,ESP,'.
								'LKR,SHP,ECS,SDD,SRG,SEK,CHF,SYP,TJR,BDT,WST,TZS,KZT,TPE,SIT,TTD,MNT,TND,TRL,UGX,ECV,CLF,USN,USS,USD,UZS,VUV,KRW,YER,JPY,CNY,ZWD,PLN';
		$info['cc_list'] = "visa,mastercard,discover,americanexpress,echeck,jcb,dinersclub";
		$info['secure'] = 1;

		return $info;
	}

	function settings()
	{
		$settings = array();

		$settings['testmode']		= 0;
		$settings['fake_account']	= 0;
		$settings['user_id']		= "user_id";
		$settings['password']		= "password";
		$settings['currency']		= "USD";
		$settings['promptAddress']	= 0;
		$settings['item_name']		= sprintf( _CFG_PROCESSOR_ITEM_NAME_DEFAULT, '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		$settings['rewriteInfo']	= '';

		return $settings;
	}

	function backend_settings( $cfg )
	{
		$settings = array();
		$settings['testmode']		= array("list_yesno");
		$settings['fake_account']	= array("list_yesno");
		$settings['user_id'] 		= array("inputC");
		$settings['password']		= array("inputC");
		$settings['currency']		= array("list_currency");
		$settings['promptAddress']	= array("list_yesno");
		$settings['item_name']		= array("inputE");
 		$rewriteswitches 			= array("cms", "user", "expiration", "subscription", "plan");
		$settings['rewriteInfo']	= array("fieldset", "Rewriting Info", AECToolbox::rewriteEngineInfo($rewriteswitches));

		return $settings;
	}

	function checkoutform( $int_var, $cfg, $metaUser, $new_subscription )
	{
		global $mosConfig_live_site;

		$var['params']['accountName'] = array( 'inputC', _AEC_WTFORM_ACCOUNTNAME_NAME, _AEC_WTFORM_ACCOUNTNAME_NAME, $metaUser->cmsUser->name );
		$var['params']['accountNumber'] = array( 'inputC', _AEC_WTFORM_ACCOUNTNUMBER_NAME, _AEC_WTFORM_ACCOUNTNUMBER_NAME, '' );
		$var['params']['bankNumber'] = array( 'inputC', _AEC_WTFORM_BANKNUMBER_NAME, _AEC_WTFORM_BANKNUMBER_NAME, '' );
		$var['params']['bankName'] = array( 'inputC', _AEC_WTFORM_BANKNAME_NAME, _AEC_WTFORM_BANKNAME_NAME, '' );

		$name = explode( ' ', $metaUser->cmsUser->name );

		if ( empty( $name[1] ) ) {
			$name[1] = "";
		}

		$var['params']['billInfo'] = array( 'p', _AEC_IPAYMENT_SILENT_PARAMS_BILLINFO, _AEC_IPAYMENT_SILENT_PARAMS_BILLINFO );
		$var['params']['billFirstName'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLFIRSTNAME_NAME, _AEC_IPAYMENT_SILENT_PARAMS_BILLFIRSTNAME_DESC, $name[0] );
		$var['params']['billLastName'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLLASTNAME_NAME, _AEC_IPAYMENT_SILENT_PARAMS_BILLLASTNAME_DESC, $name[1] );

		if ( !empty( $cfg['promptAddress'] ) ) {
			$var['params']['billAddress'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLADDRESS_NAME );
			$var['params']['billCity'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLCITY_NAME );
			$var['params']['billState'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLSTATE_NAME );
			$var['params']['billZip'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLZIP_NAME );
			$var['params']['billCountry'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLCOUNTRY_NAME );
			$var['params']['billTelephone'] = array( 'inputC', _AEC_IPAYMENT_SILENT_PARAMS_BILLTELEPHONE_NAME );
		}

		return $var;
	}

	function createRequestXML( $int_var, $cfg, $metaUser, $new_subscription )
	{
		global $mosConfig_live_site;

		$subscr_params = $metaUser->focusSubscription->getParams();

		if ( isset( $subscr_params['creator_ip'] ) ) {
			$ip = $subscr_params['creator_ip'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$a = array();

		$a['trx_paymenttyp']	= 'elv';

		if ( $cfg['fake_account'] ) {
			$a['trxuser_id']		= '99999';
			$a['trxpassword']		= '0';
		} else {
			$a['trxuser_id']		= $cfg['user_id'];
			$a['trxpassword']		= $cfg['password'];
		}

		$a['order_id']			= AECfetchfromDB::InvoiceIDfromNumber( $int_var['invoice'] );
		$a['from_ip']			= $ip;
		$a['trx_currency']		= $cfg['currency'];
		$a['trx_amount']		= $int_var['amount'];
		$a['trx_typ']			= 'auth';
		$a['invoice_text']		= $int_var['invoice'];
		$a['addr_email']		= $metaUser->cmsUser->email;

		$varray = array(	'addr_street'	=>	'billAddress',
							'addr_street'	=>	'billAddress',
							'addr_city'	=>	'billCity',
							'addr_zip'	=>	'billZip',
							'addr_country'	=>	'billCountry',
							'addr_state'	=>	'billState',
							'addr_telefon'	=>	'billTelephone',
							'cc_number'	=>	'cardNumber',
							'cc_expdate_month'	=>	'expirationMonth',
							'cc_expdat_year'	=>	'expirationYear',
							'cc_checkcode'	=>	'',
							'bank_accountnumber'	=>	'accountNumber',
							'bank_code'	=>	'bankNumber',
							'bank_name'	=>	'bankName',
							'bank_code'	=>	'billAddress',
							'bank_code'	=>	'billAddress',

						);
		foreach ( $varray as $n => $p ) {
			if ( isset( $int_var['params'][$p] ) ) {
				$a[$n] = $int_var['params'][$p];
			}
		}

		$a['client_name']		= 'aec';
		$a['client_version']	= '0.12';
		$a['silent']			= 1;

		$stringarray = array();
		foreach ( $a as $name => $value ) {
			$stringarray[] = $name . '=' . urlencode( $value );
		}

		$string = implode( '&', $stringarray );

		return $string;
	}

	function transmitRequestXML( $xml, $int_var, $settings, $metaUser, $new_subscription )
	{
		if ( $settings['testmode'] || $settings['fake_account'] ) {
			if ( $settings['fake_account'] ) {
				$path = "99999/example.php";
			} else {
				$url = $settings['account_id'] . "/example.php";
			}
		} else {
			$url = $settings['account_id'] . "/processor.php";
		}

		$url = "https://ipayment.de/merchant/" . $path;

		$response = $this->transmitRequest( $url, $path, $xml, 443 );

		$return['valid'] = false;
		$return['raw'] = $response;

		if ( $response ) {
			$resp_array = explode( "&", $response );

			foreach ( $resp_array as $arr_id => $arr_content ) {
				$ac = explode( "=", $arr_content );
				$resp_array[$ac[0]] = $ac[1];

				unset( $resp_array[$arr_id] );
			}


			$return['invoice'] = $this->substring_between($response,'<refId>','</refId>');
			$resultCode = $this->substring_between($response,'<resultCode>','</resultCode>');

			$code = $this->substring_between($response,'<code>','</code>');
			$text = $this->substring_between($response,'<text>','</text>');

			if ( strcmp( $resultCode, 'Ok' ) === 0) {
				$return['valid'] = 1;
			} else {
				$return['error'] = $text;
			}

			if ( $settings['totalOccurrences'] > 1 ) {
				$return['multiplicator'] = $settings['totalOccurrences'];
			}

			$subscriptionId = $this->substring_between($response,'<subscriptionId>','</subscriptionId>');

			$return['invoiceparams'] = array( "subscriptionid" => $subscriptionId );
		}

		return $return;
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

}
?>
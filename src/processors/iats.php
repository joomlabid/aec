<?php
/**
 * @version $Id: iats.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - iATS
 * @copyright 2007-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class processor_iats extends XMLprocessor
{
	function info()
	{
		$info = array();
		$info['name']			= 'iats';
		$info['longname']		= _CFG_IATS_LONGNAME;
		$info['statement']		= _CFG_IATS_STATEMENT;
		$info['description']	= _CFG_IATS_DESCRIPTION;
		$info['currencies']		= 'USD';
		$info['languages']		= 'GB';
		$info['cc_list']		= 'visa,mastercard,discover,americanexpress';
		$info['recurring']		= 2;
		$info['actions']		= array('cancel');
		$info['secure']			= 1;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']	= 0;

		$settings['agent_code']	= '';
		$settings['password']	= '';

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['testmode']	= array( 'list_yesno' );

		$settings['agent_code']	= array( 'inputC' );
		$settings['password']	= array( 'inputC' );

		return $settings;
	}

	function registerProfileTabs()
	{
		$tab			= array();
		$tab['details']	= _AEC_USERFORM_BILLING_DETAILS_NAME;

		return $tab;
	}

	function customtab_details( $request )
	{
		$invoiceparams = $request->invoice->getParams();
		$profileid = $invoiceparams['iats_customerProfileId'];

		$billfirstname	= aecGetParam( 'billFirstName', null );
		$billcardnumber	= aecGetParam( 'cardNumber', null );

		$updated = null;

		if ( !empty( $billfirstname ) && !empty( $billcardnumber ) && ( strpos( $billcardnumber, 'X' ) === false ) ) {
			$var['Method']					= 'UpdateRecurringPaymentsProfile';
			$var['Profileid']				= $profileid;

			$var['card_type']				= aecGetParam( 'cardType' );
			$var['card_number']				= aecGetParam( 'cardNumber' );
			$var['expDate']					= str_pad( aecGetParam( 'expirationMonth' ), 2, '0', STR_PAD_LEFT ) . aecGetParam( 'expirationYear' );
			$var['CardVerificationValue']	= aecGetParam( 'cardVV2' );

			$udata = array( 'firstname' => 'billFirstName', 'lastname' => 'billLastName', 'street' => 'billAddress', 'street2' => 'billAddress2',
							'city' => 'billCity', 'state' => 'billState', 'zip' => 'billZip', 'country' => 'billCountry'
							);

			foreach ( $udata as $authvar => $aecvar ) {
				$value = trim( aecGetParam( $aecvar ) );

				if ( !empty( $value ) ) {
					$var[$authvar] = $value;
				}
			}

			$result = $this->ProfileRequest( $request, $profileid, $var );

			$updated = true;
		}

		if ( $profileid ) {
			$var['Method']				= 'GetRecurringPaymentsProfileDetails';
			$var['Profileid']			= $profileid;

			$vars = $this->ProfileRequest( $request, $profileid, $var );

			$vcontent = array();
			$vcontent['card_type']		= strtolower( $vars['CREDITCARDTYPE'] );
			$vcontent['card_number']	= 'XXXX' . $vars['ACCT'];
			$vcontent['firstname']		= $vars['FIRSTNAME'];
			$vcontent['lastname']		= $vars['LASTNAME'];

			if ( isset( $vars['STREET1'] ) ) {
				$vcontent['address']		= $vars['STREET1'];
				$vcontent['address2']		= $vars['STREET2'];
			} else {
				$vcontent['address']		= $vars['STREET'];
			}

			$vcontent['city']			= $vars['CITY'];
			$vcontent['state_usca']		= $vars['STATE'];
			$vcontent['zip']			= $vars['ZIP'];
			$vcontent['country_list']	= $vars['COUNTRY'];
		} else {
			$vcontent = array();
		}

		$var = $this->checkoutform( $request, $vcontent, $updated );

		$return = '<form action="' . AECToolbox::deadsureURL( '/index.php?option=com_acctexp&amp;task=iats_details', true ) . '" method="post">' . "\n";
		$return .= $this->getParamsHTML( $var ) . '<br /><br />';
		$return .= '<input type="hidden" name="userid" value="' . $request->metaUser->userid . '" />' . "\n";
		$return .= '<input type="hidden" name="task" value="subscriptiondetails" />' . "\n";
		$return .= '<input type="hidden" name="sub" value="iats_details" />' . "\n";
		$return .= '<input type="submit" class="button" value="' . _BUTTON_APPLY . '" /><br /><br />' . "\n";
		$return .= '</form>' . "\n";

		return $return;
	}

	function checkoutform( $request, $vcontent=null, $updated=null )
	{
		global $mosConfig_live_site;

		$var = array();

		if ( !empty( $vcontent ) ) {
			if ( !empty( $updated ) ) {
				$msg = _AEC_CCFORM_UPDATE2_DESC;
			} else {
				$msg = _AEC_CCFORM_UPDATE_DESC;
			}

			$var['params']['billUpdateInfo'] = array( 'p', _AEC_CCFORM_UPDATE_NAME, $msg, '' );
		}

		$values = array( 'card_type', 'card_number', 'card_exp_month', 'card_exp_year', 'card_cvv2' );
		$var = $this->getCCform( $var, $values, $vcontent );

		$values = array( 'firstname', 'lastname', 'address', 'address2', 'city', 'state_usca', 'zip', 'country_list' );
		$var = $this->getUserform( $var, $values, $request->metaUser, $vcontent );

		return $var;
	}

	function createRequestXML( $request )
	{
		global $mosConfig_live_site, $mosConfig_offset_user;

		$ppParams = $request->metaUser->meta->getProcessorParams( $request->parent->id );

		$var = array();

		$var['AgentCode']			= $this->settings['agent_code'];
		$var['Password']			= $this->settings['password'];
		$var['CustCode']			= $request->int_var['params']['customer_id'];

		$var['FirstName']			= trim( $request->int_var['params']['billFirstName'] );
		$var['LastName']			= trim( $request->int_var['params']['billLastName'] );

		$var['Address']				= $request->int_var['params']['billAddress'];
		$var['City']				= $request->int_var['params']['billCity'];
		$var['State']				= $request->int_var['params']['billState'];
		$var['ZipCode']				= $request->int_var['params']['billZip'];

		if ( is_array( $request->int_var['amount'] ) ) {
			$tvar = array();
			$fvar = array();

         $params = $params . "&Amount1="      .  $this->dollarAmount;

         $params = $params . "&BeginDate1="   .  $this->beginDate;
         $params = $params . "&EndDate1="     .  $this->endDate;
         $params = $params . "&ScheduleType1="     .  $this->scheduleType;
         $params = $params . "&ScheduleDate1="     .  $this->scheduleDate;
         $params = $params . "&Reoccurring1="      .  $this->reoccuringStatus;

			if ( isset( $request->int_var['amount']['amount1'] ) ) {

				$t = $this->convertPeriodUnit( $request->int_var['amount']['period1'], $request->int_var['amount']['unit1'] );
				$tvar['MOP']			= $request->int_var['params']['cardType'];
				$tvar['CCNum']			= $request->int_var['params']['cardNumber'];
				$tvar['CCEXPIRY']		= str_pad( $request->int_var['params']['expirationMonth'], 2, '0', STR_PAD_LEFT ).'/'.$request->int_var['params']['expirationYear'];

				$tvar['CVV2']			= $request->int_var['params']['cardVV2'];

				$tvar['Amount']			= $request->int_var['amount'];
				$tvar['Reoccurring']	= "OFF";


				$timestamp = time() - ($mosConfig_offset_user*3600) + $offset;
			} else {
				$timestamp = time() - $mosConfig_offset_user*3600;
			}

			$var['ProfileStartDate']    = date( 'Y-m-d', $timestamp ) . 'T' . date( 'H:i:s', $timestamp ) . 'Z';

			$full = $this->convertPeriodUnit( $request->int_var['amount']['period3'], $request->int_var['amount']['unit3'] );

			$var['ScheduleType1']		= $full['unit'];
			$var['BillingFrequency']	= $full['period'];
			$var['amt']					= $request->int_var['amount']['amount3'];
			$var['ProfileReference']	= $request->int_var['invoice'];
		} else {
			$tvar['MOP']			= $request->int_var['params']['cardType'];
			$tvar['CCNum']			= $request->int_var['params']['cardNumber'];
			$tvar['CCEXPIRY']		= str_pad( $request->int_var['params']['expirationMonth'], 2, '0', STR_PAD_LEFT ).'/'.$request->int_var['params']['expirationYear'];

			$tvar['CVV2']			= $request->int_var['params']['cardVV2'];

			$var['Total']			= $request->int_var['amount'];
		}

		$var['InvoiceNum']			= $request->int_var['invoice'];

		$var['Version']				= "1.30";

		$content = array();
		foreach ( $var as $name => $value ) {
			$content[] .= strtoupper( $name ) . '=' . urlencode( stripslashes( $value ) );
		}

		return implode( '&', $content );
	}

	function transmitToTicketmaster( $xml, $request )
	{
		$path = "/itravel/itravel.pro";

		if ( $this->settings['server_type'] == 1 ) {
			$iats = 'iatsuk';
		} else {
			$iats = 'iats';
		}

		if ( $this->settings['testmode'] ) {
			$url = "http://www." . $iats . ".ticketmaster.com" . $path;
			$port = 80;
		} else {
			$url = "https://www." . $iats . ".ticketmaster.com" . $path;
			$port = 443;
		}

		$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";

		$curlextra = array();
		$curlextra[CURLOPT_SSL_VERIFYHOST] = 2;
		$curlextra[CURLOPT_USERAGENT] = $user_agent;
		$curlextra[CURLOPT_SSL_VERIFYHOST] = 1;

		$cookieFile = "cookie" .date("his"). ".txt";

		if ( $this->settings['server_type'] == 1 ) {
			$curlextra[CURLOPT_COOKIEFILE] = $cookieFile;
		} else {
			$curlextra[CURLOPT_USERPWD] = $this->settings['agent_code'] . ":" . $this->settings['password'];
		}

		return $this->transmitRequest( $url, $path, $xml, $port, $curlextra );
	}

	function transmitRequestXML( $xml, $request )
	{
		$response = $this->transmitToTicketmaster( $xml, $request );

		$return = array();
		$return['valid'] = false;
		$return['raw'] = $response;

		$iatsReturn = stristr( $response, "AUTHORIZATION RESULT:" );
		$iatsReturn = substr( $iatsReturn, strpos( $iatsReturn, ":" ) + 1, strpos( $iatsReturn , "<" ) - strpos( $iatsReturn , ":" ) - 1 );

		if ( $iatsReturn == "" ) {
			$response['error'] = 1;
			$response['errormsg'] = 'Rejected: Error Page';
		} else {
			$return['valid'] = true;
		}


		return $return;
	}

	function convertPeriodUnit( $period, $unit )
	{
		$return = array();
		switch ( $unit ) {
			case 'D':
				$period = 1;
			case 'W':
				$return['unit'] = 'WEEKLY';
				$return['period'] = $period;
				break;
			case 'Y':
				$period *= 12;
			case 'M':
				$return['unit'] = 'MONTHLY';
				$return['period'] = $period;
				break;
		}

		return $return;
	}

	function ProfileRequest( $request, $profileid, $var )
	{
		$var['']				= '';

		$content = array();
		foreach ( $var as $name => $value ) {
			$content[] .= strtoupper( $name ) . '=' . urlencode( $value );
		}

		$xml = implode( '&', $content );

		return $this->transmitToTicketmaster( $xml, $request );
	}

}

?>
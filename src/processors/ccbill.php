<?php

/**
* CCBill process interface BETA 1.0
*
* @copyright 2007 Ben Ingram
* @license http://www.gnu.org/copyleft/gpl.html. GNU Public License
* @version $Revision: 1.0 $
* @author Ben Ingram <beningram@hotmail.com>, David Deutsch <skore@skore.de>
**
*********************************************************************
** If you find this useful, why not make my day and send a donation to me by PayPal
** My account is beningram@hotmail.com - thanks and happy trading
*********************************************************************
**/
//
// Copyright (C) 2007 Ben Ingram
// All rights reserved.
// This source file works with the Account Expiration Control Component, a  Joomla
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

class processor_ccbill extends POSTprocessor
{
	function info()
	{
		$info = array();
		$info['name'] = 'ccbill';
		$info['longname'] = _CFG_CCBILL_LONGNAME;
		$info['statement'] = _CFG_CCBILL_STATEMENT;
		$info['description'] = _CFG_CCBILL_DESCRIPTION;
		$info['cc_list'] = "visa,mastercard,discover,echeck,jcb";
		$info['currencies'] = "USD";
		$info['recurring'] = 0;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['clientAccnum']	= "Account Number";
		$settings['clientSubacc']	= "Sub Account";
		$settings['formName']		= "Form Name";
		$settings['secretWord']		= "Secret Word";

		return $settings;
	}

	function backend_settings( $cfg )
	{
		$settings = array();
		$settings['clientAccnum']	= array( 'inputC' );
		$settings['clientSubacc']	= array( 'inputC' );
		$settings['formName']		= array( 'inputC' );
		$settings['secretWord']		= array( 'inputC' );
		$settings['info']			= array( 'fieldset' );
		return $settings;
	}

	function CustomPlanParams()
	{
		$p = array();
		$p['Allowedtypes']	= array( 'inputC' );

		return $p;
	}

	function createGatewayLink( $int_var, $cfg, $metaUser, $new_subscription )
	{
		global $mosConfig_live_site;

		$var['post_url']		= "https://bill.ccbill.com/jpost/signup.cgi";
		$var['clientAccnum']	= $cfg['clientAccnum'];
		$var['clientSubacc']	= $cfg['clientSubacc'];
		$var['formName']		= $cfg['formName'];

		$var['invoice']			= $int_var['invoice'];
		$var['username']		= $metaUser->cmsUser->username;
		$var['password']		= "xxxxxx"; // hard coded because the CCBILL system can't deal with an empty password - despite having an option to ignore it...
		$var['email']			= $metaUser->cmsUser->email;
		$var['checksum']		= md5($cfg['secretWord'] . $metaUser->cmsUser->username);

		if ( !empty( $int_var['planparams']['Allowedtypes'] ) ) {
			$var['allowedTypes'] = $int_var['planparams']['Allowedtypes'];
		}

		return $var;
	}

	/*
	CCBILL Response parameters - for future reference...

	customer_fname		Customer first name
	customer_lname		Customer last name
	email				Customer Email address, i.e., custmail@host.com
	username			Customer username
	password			Customer password
	productDesc			Product description
	price				Product price, i.e., $5.95 for 30 days (non-recurring)
	subscription_id		Subscription ID Number, i.e., 1000000000 (Approval Post URL only)
	reasonForDecline	The decline reason (Decline Post URL only)*
	clientAccnum		CCBill client main account number , i.e., 900100
	clientSubacc		CCBill client subaccount Number , i.e., 0000
	address1			Customer address
	city				Customer city
	state				Customer state
	country				Customer country
	phone_number		Customer phone number
	zipcode				Customer Zip Code
	start_date			The subscription start date Used to show individual corresponding yearly, monthly or daily dates for report data. The date function's format is year-month-day; for example, 2002-01-01., i.e., 2002-08-05 15:18:17
	referer				Use other Affiliate Program (non-CCBill)
	ccbill_referer		Use CCBill Affiliate Program
	reservationId		Customer�s subscription Reservation ID number
	initialPrice		The initial price of the subscription
	initialPeriod		The initial period of the subscription
	recurringPrice		The price of the subscription (recurring)
	recurringPeriod		The period of the subscription (recurring)
	rebills				The number of subscription rebills
	ip_address			Customer�s IP address , such as: 64.38.194.13
	*/


	function parseNotification( $post, $cfg )
	{
		$invoice			= $post['invoice'];
		$username			= $post['username'];
		$reasonForDecline	= $post['reasonForDecline'];
		$checksum			= $post['checksum'];
		$customer_fname		= $post['customer_fname'];
		$customer_lname		= $post['customer_lname'];
		$email				= $post['email'];
		$password			= $post['password'];
		$productDesc		= $post['productDesc'];
		$price				= $post['price'];
		$clientAccnum		= $post['clientAccnum'];
		$clientSubacc		= $post['clientSubacc'];
		$address1			= $post['address1'];
		$city				= $post['city'];
		$state				= $post['state'];
		$country			= $post['country'];
		$subscription_id	= $post['subscription_id'];
		$phone_number		= $post['phone_number'];
		$zipcode			= $post['zipcode'];
		$start_date			= $post['start_date'];
		$referer			= $post['referer'];
		$ccbill_referer		= $post['ccbill_referer'];
		$reservationId		= $post['reservationId'];
		$initialPrice		= $post['initialPrice'];
		$initialPeriod		= $post['initialPeriod'];
		$recurringPrice		= $post['recurringPrice'];
		$recurringPeriod	= $post['recurringPeriod'];
		$rebills			= $post['rebills'];
		$ip_address			= $post['ip_address'];

		// Check whether this is a passive registration
		if ( empty( $invoice ) ) {
			// Make sure that we have not set this up before
			if ( empty( AECfetchfromDB::InvoiceIDfromNumber( $subscription_id ) ) ) {
				$user = array();
				$user['name'] = $customer_fname . ' ' . $customer_lname;
				$user['email'] = $email;
				$user['username'] = $email;
				$user['password'] = $password;
				$user['email'] = $email;

				$payment['secondary_ident'] = $subscription_id;
				$payment['processor'] = 'ccbill';

				$metaUser = new metaUser();
				$metaUser->procTriggerCreate( $user, $invoice, $post['usage'] );
			} else {
				$invoice = $subscription_id;
			}
		}

		$response = array();
		$response['invoice'] = $invoice;
		$response['valid'] = 1;

		if ( strlen( $reasonForDecline ) > 0 ) {
			$response['pending_reason'] = $reasonForDecline;
		}

		$response['checksum'] = $checksum;

		if ( $initialPrice ) {
			$response['amount_paid'] = $initialPrice;
		} else {
			$response['amount_paid'] = $recurringPrice;
		}

		return $response;

	}

	function validateNotification( $response, $post, $cfg, $invoice )
	{
		$username = $post['username'];

		$validate = md5( $cfg['secretWord'] . $username );

		if ( isset( $response['pending_reason'] ) ){
			$response['valid'] = 0;
			return $response;
		}

		$response['valid'] = ( strcmp( $validate, $response['checksum'] ) == 0 );

		return $response;
	}
}
?>
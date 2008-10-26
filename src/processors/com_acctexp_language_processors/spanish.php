<?php
/**
 * @version $Id: spanish.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processor languages
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @copyright 2006-2008 Copyright (C) David Deutsch
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

define( '_AEC_LANG_PROCESSOR', 1 );

// ################## new 0.12.4
	// paypal
define( '_AEC_PROC_INFO_PP_LNAME',			'PayPal' );
define( '_AEC_PROC_INFO_PP_STMNT',			'Make payments with PayPal - it\'s fast, free and secure!' );
	// paypal subscription
define( '_AEC_PROC_INFO_PPS_LNAME',			'PayPal Subscription' );
define( '_AEC_PROC_INFO_PPS_STMNT',			'Make payments with PayPal - it\'s fast, free and secure!' );
	// 2CheckOut
define( '_AEC_PROC_INFO_2CO_LNAME',			'2CheckOut' );
define( '_AEC_PROC_INFO_2CO_STMNT',			'Make payments with 2Checkout!' );
	// alertpay
define( '_AEC_PROC_INFO_AP_LNAME',			'AlertPay' );
define( '_AEC_PROC_INFO_AP_STMNT',			'Payments with AlertPay' );


define( '_DESCRIPTION_PAYPAL', 'PayPal lets you send money to anyone with email. PayPal is free for consumers and works seamlessly with your existing credit card and checking account.');
define( '_DESCRIPTION_PAYPAL_SUBSCRIPTION', 'PayPal Subscription is the Subscription Service that will <strong>automatically bill your account each subscription period</strong>. You can cancel a subscription any time you want from your PayPal account. PayPal is free for consumers and works seamlessly with your existing credit card and checking account.');
define( '_DESCRIPTION_AUTHORIZE', 'Payment gateway enables internet merchants to accept online payments via credit card and e-check.');
define( '_DESCRIPTION_VIAKLIX', 'Provides integrated credit and debit card payment processing, electronic check conversion and related software applications..');
define( '_DESCRIPTION_ALLOPASS', 'AlloPass, European leader in its domain is a micropayment system and allows phone, sms and credit card billing.');
define( '_DESCRIPTION_2CHECKOUT', 'Instant credit card processing services accounts for merchants with internet businesses.');
define( '_DESCRIPTION_EPSNETPAY', 'Der eps ist das einfache, sichere und kostenlose Zahlungssystem der &ouml;sterreichischen Banken f&uuml;r Eink&auml;ufe im Internet.');
define( '_DESCRIPTION_ALERTPAY', 'Your money is safe with AlertPay\'s account safety policy. AlertPay is open to all businesses.');

// Generic Processor Names&Descs
define( '_CFG_PROCESSOR_TESTMODE_NAME', 'Test Mode?');
define( '_CFG_PROCESSOR_TESTMODE_DESC', 'Select Yes if you want to run this processor in test mode. Transactions will not be forwarded to the real processor, but will be either redirected to a testing environment or always return an approved result. If you do not know what this is, just leave it No.');
define( '_CFG_PROCESSOR_CURRENCY_NAME', 'Currency Selection');
define( '_CFG_PROCESSOR_CURRENCY_DESC', 'Select the currency that you want to use for this processor.');
define( '_CFG_PROCESSOR_NAME_NAME', 'Displayed Name');
define( '_CFG_PROCESSOR_NAME_DESC', 'Change how this Processor is called.');
define( '_CFG_PROCESSOR_DESC_NAME', 'Displayed Description');
define( '_CFG_PROCESSOR_DESC_DESC', 'Change the description of this Processor, which is for example shown on the NotAllowed page, Confirmation and Checkout.');
define( '_CFG_PROCESSOR_ITEM_NAME_NAME', 'Item Description');
define( '_CFG_PROCESSOR_ITEM_NAME_DESC', 'The Item Description transmitted to the processor.');
define( '_CFG_PROCESSOR_ITEM_NAME_DEFAULT',	'Subscription at %s - User: %s (%s)' );
define( '_CFG_PROCESSOR_CUSTOMPARAMS_NAME', 'Custom Parameters');
define( '_CFG_PROCESSOR_CUSTOMPARAMS_DESC', 'Custom Parameters that the AEC should transmit to the Payment Processor on Checkout. Separated by linebreaks in the form of "parameter_name=parameter_value". The RewriteEngine works as specified below.');
define( '_CFG_PROCESSOR_PLAN_PARAMS_RECURRING_NAME', 'Recurring Payment');
define( '_CFG_PROCESSOR_PLAN_PARAMS_RECURRING_DESC', 'Choose what type of billing to use for this.');
define( '_CFG_PROCESSOR_LANGUAGE_NAME', 'Language');
define( '_CFG_PROCESSOR_LANGUAGE_DESC', 'Select one of the possible language settings for site that your user will see when issuing a payment.');
define( '_CFG_PROCESSOR_RECURRING_NAME', 'Recurring Payment');
define( '_CFG_PROCESSOR_RECURRING_DESC', 'Choose what type of billing to use for this.');
define( '_CFG_PROCESSOR_TAX_NAME', 'Tax:');
define( '_CFG_PROCESSOR_TAX_DESC', 'Set the percentage that should be split to taxes. For example if you want 10% of 10$ to be tax - put in a 10. This will result in an amount of 9.09 and a tax amount of additional 0.91.');
define( '_CFG_PROCESSOR_GENERIC_BUTTONS_NAME', 'Generic Buttons:');
define( '_CFG_PROCESSOR_GENERIC_BUTTONS_DESC', 'Do not show buttons with the processor logo, but plan "Buy Now" and "Subscribe" buttons instead.');

define( '_AEC_SELECT_RECURRING_NO', 'Non-Recurring');
define( '_AEC_SELECT_RECURRING_YES', 'Recurring');
define( '_AEC_SELECT_RECURRING_BOTH', 'Both');

// Generic User Account Form
define( '_AEC_USERFORM_BILLING_DETAILS_NAME', 'Billing Details');
define( '_AEC_USERFORM_SHIPPING_DETAILS_NAME', 'Shipping Details');
define( '_AEC_USERFORM_BILLFIRSTNAME_NAME', 'First Name');
define( '_AEC_USERFORM_BILLLASTNAME_NAME', 'Last Name');
define( '_AEC_USERFORM_BILLADDRESS_NAME', 'Address');
define( '_AEC_USERFORM_BILLADDRESS2_NAME', 'Address (continued)');
define( '_AEC_USERFORM_BILLCITY_NAME', 'City');
define( '_AEC_USERFORM_BILLSTATE_NAME', 'State');
define( '_AEC_USERFORM_BILLSTATEPROV_NAME', 'State/Prov');
define( '_AEC_USERFORM_BILLZIP_NAME', 'Zip');
define( '_AEC_USERFORM_BILLCOUNTRY_NAME', 'Country');
define( '_AEC_USERFORM_BILLPHONE_NAME', 'Phone');
define( '_AEC_USERFORM_BILLFAX_NAME', 'Fax');
define( '_AEC_USERFORM_BILLCOMPANY_NAME', 'Company');

// Generic Credit Card Form
define( '_AEC_CCFORM_TABNAME', 'Credit Card');
define( '_AEC_CCFORM_CARDHOLDER_NAME', 'Card owner Name');
define( '_AEC_CCFORM_CARDHOLDER_DESC', 'The name of the credit card holder');
define( '_AEC_CCFORM_CARDNUMBER_NAME', 'Card Number');
define( '_AEC_CCFORM_CARDNUMBER_DESC', 'The number of your credit card');
define( '_AEC_CCFORM_EXPIRATIONYEAR_NAME', 'Expiration Year');
define( '_AEC_CCFORM_EXPIRATIONYEAR_DESC', 'The Year your card will expire');
define( '_AEC_CCFORM_EXPIRATIONMONTH_NAME', 'Expiration Month');
define( '_AEC_CCFORM_EXPIRATIONMONTH_DESC', 'The Month your card will expire');
define( '_AEC_CCFORM_CARDTYPE_NAME', 'Card Type');
define( '_AEC_CCFORM_CARDTYPE_DESC', 'The type of the credit card');
define( '_AEC_CCFORM_CARDVV2_NAME', 'Card Verification Number');
define( '_AEC_CCFORM_CARDVV2_DESC', 'The Verification Number of the Credit Card');
define( '_AEC_CCFORM_UPDATE_NAME', 'Update Notice');
define( '_AEC_CCFORM_UPDATE_DESC', 'To update your billing details, we need you to enter your CreditCard details again.<br />Leave fields as they are if you want to use all your previous details.');
define( '_AEC_CCFORM_UPDATE2_DESC', 'Updated! Thank you for keeping us up to date.');

// Generic eCheck Form
define( '_AEC_ECHECKFORM_TABNAME', 'eCheck');
define( '_AEC_ECHECKFORM_ROUTING_NO_NAME', 'Routing Number');
define( '_AEC_ECHECKFORM_ROUTING_NO_DESC', 'Your Routing Number');
define( '_AEC_ECHECKFORM_ACCOUNT_NO_NAME', 'Account Number');
define( '_AEC_ECHECKFORM_ACCOUNT_NO_DESC', 'Your Account Number');
define( '_AEC_ECHECKFORM_ACCOUNT_NAME_NAME', 'Account Name');
define( '_AEC_ECHECKFORM_ACCOUNT_NAME_DESC', 'Your Name for this Account');
define( '_AEC_ECHECKFORM_BANK_NAME_NAME', 'Bank Name');
define( '_AEC_ECHECKFORM_BANK_NAME_DESC', 'The Bank Name');

// Generic Wire Transfer Form
define( '_AEC_WTFORM_ACCOUNTNAME_NAME', 'Account owner Name');
define( '_AEC_WTFORM_ACCOUNTNAME_DESC', 'The name of the person holding this account');
define( '_AEC_WTFORM_ACCOUNTNUMBER_NAME', 'Account Number');
define( '_AEC_WTFORM_ACCOUNTNUMBER_DESC', 'The number of the account');
define( '_AEC_WTFORM_BANKNUMBER_NAME', 'Bank Number');
define( '_AEC_WTFORM_BANKNUMBER_DESC', 'The Bank Number');
define( '_AEC_WTFORM_BANKNAME_NAME', 'Bank Name');
define( '_AEC_WTFORM_BANKNAME_DESC', 'The Name of the Bank');

// Paypal Settings
define( '_CFG_PAYPAL_BUSINESS_NAME', 'Business ID:');
define( '_CFG_PAYPAL_BUSINESS_DESC', 'Tu Merchant ID (email) en PayPal.');
define( '_CFG_PAYPAL_CHECKBUSINESS_NAME', 'Check Business ID:');
define( '_CFG_PAYPAL_CHECKBUSINESS_DESC', 'Select Yes to enable a security check procedure when receiving payment confirmation. The receiver ID field must be equal to the PayPal business ID to the payment be accepted, if this checking is enabled.');
define( '_CFG_PAYPAL_NO_SHIPPING_NAME', 'No Shipping Required:');
define( '_CFG_PAYPAL_NO_SHIPPING_DESC', 'Set this to NO if you want your customers to specify a shipping address - in case you offer a product that needs to be physically distributed');
define( '_CFG_PAYPAL_ALTIPNURL_NAME', 'Alternate IPN Notification Domain:');
define( '_CFG_PAYPAL_ALTIPNURL_DESC', 'If you use server workload balancing (switching between IP adresses), it might be that Paypal dislikes this and breaks the connection when trying to send an IPN. To work around this, you can for example create a new subdomain on this server and disable the loadbalancing for this. Putting this address in here (In the form "http://subdomain.domain.com" - no trailing slash and whatever you want for subdomain and domain) will make sure that Paypal sends only the IPN back to this Address. <strong>If you are not sure what this means, leave it completely blank!</strong>');
define( '_CFG_PAYPAL_LC_NAME', 'Language:');
define( '_CFG_PAYPAL_LC_DESC', 'Select one of the possible language settings for the paypal site that your user will see when issuing a payment.');
define( '_CFG_PAYPAL_TAX_NAME', 'Tax:');
define( '_CFG_PAYPAL_TAX_DESC', 'Set the percentage that should be split to taxes. For example if you want 10% of 10$ to be tax - put in a 10. This will result in an amount of 9.09 and a tax amount of additional 0.91.');
define( '_CFG_PAYPAL_ACCEPTPENDINGECHECK_NAME', 'Accept Pending eCheck:');
define( '_CFG_PAYPAL_ACCEPTPENDINGECHECK_DESC', 'Accept Pending eChecks which usually take 4 days to clear. Set this to No to prevent eCheck fraud.');

define( '_CFG_PAYPAL_CBT_NAME', 'Continue Button');
define( '_CFG_PAYPAL_CBT_DESC', 'Sets the text for the Continue button on the PayPal "Payment Complete" page.');
define( '_CFG_PAYPAL_CN_NAME', 'Note Label');
define( '_CFG_PAYPAL_CN_DESC', 'The label above the note field.');
define( '_CFG_PAYPAL_CPP_HEADER_IMAGE_NAME', 'Header Image');
define( '_CFG_PAYPAL_CPP_HEADER_IMAGE_DESC', 'URL for the image at the top left of the payment page (the maximum image size being 750x90 pixels)');
define( '_CFG_PAYPAL_CPP_HEADERBACK_COLOR_NAME', 'Headerback Color');
define( '_CFG_PAYPAL_CPP_HEADERBACK_COLOR_DESC', 'Background color for the payment page header (6 character HTML hexadecimal color code in ASCII)');
define( '_CFG_PAYPAL_CPP_HEADERBORDER_COLOR_NAME', 'Headerborder Color');
define( '_CFG_PAYPAL_CPP_HEADERBORDER_COLOR_DESC', 'Border color for the payment page header (6 character HTML hexadecimal color code in ASCII)');
define( '_CFG_PAYPAL_CPP_PAYFLOW_COLOR_NAME', 'Payflow Color');
define( '_CFG_PAYPAL_CPP_PAYFLOW_COLOR_DESC', 'Background color for the payment page below the header (6 character HTML hexadecimal color code in ASCII)');
define( '_CFG_PAYPAL_CS_NAME', 'Background Tint');
define( '_CFG_PAYPAL_CS_DESC', 'The default - "No" - leaves the overall background color at white, setting it to "Yes" will change it to black');
define( '_CFG_PAYPAL_IMAGE_URL_NAME', 'Logo');
define( '_CFG_PAYPAL_IMAGE_URL_DESC', 'URL of the image displayed as your logo in the upperleft corner of PayPals pages (150x50 pixels)');
define( '_CFG_PAYPAL_PAGE_STYLE_NAME', 'Page Style');
define( '_CFG_PAYPAL_PAGE_STYLE_DESC', 'Sets the custom payment page style for payment pages. Reserved: "primary" - Always use the page style set as primary, "paypal" - Use the default PayPal style. Any other name has to refer to the page style you have defined in the PayPal Backend (alphanumeric ASCII lower 7-bit characters only, no underscore nor spaces)');

// Paypal Subscriptions Settings
define( '_CFG_PAYPAL_SUBSCRIPTION_BUSINESS_NAME', _CFG_PAYPAL_BUSINESS_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_BUSINESS_DESC', _CFG_PAYPAL_BUSINESS_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CHECKBUSINESS_NAME', _CFG_PAYPAL_CHECKBUSINESS_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CHECKBUSINESS_DESC', _CFG_PAYPAL_CHECKBUSINESS_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_NO_SHIPPING_NAME', _CFG_PAYPAL_NO_SHIPPING_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_NO_SHIPPING_DESC', _CFG_PAYPAL_NO_SHIPPING_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_ALTIPNURL_NAME', _CFG_PAYPAL_ALTIPNURL_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_ALTIPNURL_DESC', _CFG_PAYPAL_ALTIPNURL_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_LC_NAME', _CFG_PAYPAL_LC_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_LC_DESC', _CFG_PAYPAL_LC_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_TAX_NAME', _CFG_PAYPAL_TAX_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_TAX_DESC', _CFG_PAYPAL_TAX_DESC);
define( '_PAYPAL_SUBSCRIPTION_CANCEL_INFO', 'If you want to change your subscription, you first have to cancel your current subscription in your PayPal account!');
define( '_CFG_PAYPAL_SUBSCRIPTION_ACCEPTPENDINGECHECK_NAME', _CFG_PAYPAL_ACCEPTPENDINGECHECK_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_ACCEPTPENDINGECHECK_DESC', _CFG_PAYPAL_ACCEPTPENDINGECHECK_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_SRT_NAME', 'Total Occurances');
define( '_CFG_PAYPAL_SUBSCRIPTION_SRT_DESC', 'If you want to limit the number of total subscription payments, you can do so with this field.');

define( '_CFG_PAYPAL_SUBSCRIPTION_CBT_NAME', _CFG_PAYPAL_CBT_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CBT_DESC', _CFG_PAYPAL_CBT_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CN_NAME', _CFG_PAYPAL_CN_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CN_DESC', _CFG_PAYPAL_CN_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_HEADER_IMAGE_NAME', _CFG_PAYPAL_CPP_HEADER_IMAGE_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_HEADER_IMAGE_DESC', _CFG_PAYPAL_CPP_HEADER_IMAGE_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_HEADERBACK_COLOR_NAME', _CFG_PAYPAL_CPP_HEADERBACK_COLOR_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_HEADERBACK_COLOR_DESC', _CFG_PAYPAL_CPP_HEADERBACK_COLOR_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_HEADERBORDER_COLOR_NAME', _CFG_PAYPAL_CPP_HEADERBORDER_COLOR_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_HEADERBORDER_COLOR_DESC', _CFG_PAYPAL_CPP_HEADERBORDER_COLOR_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_PAYFLOW_COLOR_NAME', _CFG_PAYPAL_CPP_PAYFLOW_COLOR_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CPP_PAYFLOW_COLOR_DESC', _CFG_PAYPAL_CPP_PAYFLOW_COLOR_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_CS_NAME', _CFG_PAYPAL_CS_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_CS_DESC', _CFG_PAYPAL_CS_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_IMAGE_URL_NAME', _CFG_PAYPAL_IMAGE_URL_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_IMAGE_URL_DESC', _CFG_PAYPAL_IMAGE_URL_DESC);
define( '_CFG_PAYPAL_SUBSCRIPTION_PAGE_STYLE_NAME', _CFG_PAYPAL_PAGE_STYLE_NAME);
define( '_CFG_PAYPAL_SUBSCRIPTION_PAGE_STYLE_DESC', _CFG_PAYPAL_PAGE_STYLE_DESC);

// Transfer Settings
define( '_CFG_TRANSFER_TITLE', 'Transfer');
define( '_CFG_TRANSFER_SUBTITLE', 'Non-Automatic Payments.');
define( '_CFG_TRANSFER_ENABLE_NAME', 'Habilitar pagos no automatizados?');
define( '_CFG_TRANSFER_ENABLE_DESC', 'Seleccione "Si" si quere dar la opcion de pagos no automatizados, como transferencias bancarias. El usuario registrado vera las instrucciones que ponga a continuacion acerca de como pagar su suscripcion. Esta opcion no esta automatizada, asique tendra que configurar las fechas de exiparion manualmente desde el area administrativa.');
define( '_CFG_TRANSFER_INFO_NAME', 'Info para pago Manual/Alternativo:');
define( '_CFG_TRANSFER_INFO_DESC', 'Texto que sera presentado al usuario antes de su registracion inicial (use etiquetas HTML). Luego de la registracion, en el primer login, una fecha de expiracion sera puesta en forma automatica (configurada en la primera pestaa) para la cuenta del usuario. El usuario debera seguir sus instrucciones de como pagar la suscripcion. Nesecitara confirmar el pago usted mismo y reconfigurar la caducidad de la cuenta de dicho usuario.');

// Viaklix Settings
define( '_CFG_VIAKLIX_ACCOUNTID_NAME', 'Account ID');
define( '_CFG_VIAKLIX_ACCOUNTID_DESC', 'Your Account ID on viaKLIX.');
define( '_CFG_VIAKLIX_USERID_NAME', 'User ID');
define( '_CFG_VIAKLIX_USERID_DESC', 'Your User ID on viaKLIX.');
define( '_CFG_VIAKLIX_PIN_NAME', 'PIN');
define( '_CFG_VIAKLIX_PIN_DESC', 'PIN of the terminal.');

// VirtualMerchant (successor of Viaklix) Settings
define( '_CFG_VIRTUALMERCHANT_ACCOUNTID_NAME', 'Account ID');
define( '_CFG_VIRTUALMERCHANT_ACCOUNTID_DESC', 'Your Account ID on viaKLIX.');
define( '_CFG_VIRTUALMERCHANT_USERID_NAME', 'User ID');
define( '_CFG_VIRTUALMERCHANT_USERID_DESC', 'Your User ID on viaKLIX.');
define( '_CFG_VIRTUALMERCHANT_PIN_NAME', 'PIN');
define( '_CFG_VIRTUALMERCHANT_PIN_DESC', 'PIN of the terminal.');

// Authorize.net Settings
define( '_CFG_AUTHORIZE_LOGIN_NAME', 'API Login ID');
define( '_CFG_AUTHORIZE_LOGIN_DESC', 'Your API Login ID on Authorize.net.');
define( '_CFG_AUTHORIZE_TRANSACTION_KEY_NAME', 'Transaction Key');
define( '_CFG_AUTHORIZE_TRANSACTION_KEY_DESC', 'Your Transaction Key on Authorize.net.');
define( '_CFG_AUTHORIZE_TIMESTAMP_OFFSET_NAME', 'Timestamp offest');
define( '_CFG_AUTHORIZE_TIMESTAMP_OFFSET_DESC', 'If you get an Error 97 when trying to create a transaction, please <a href="http://developer.authorize.net/tools/responsecode97/">take a look at this</a>. It might be that you need to set a timestamp offset here.');
define( '_CFG_AUTHORIZE_X_LOGO_URL_NAME', 'Logo URL');
define( '_CFG_AUTHORIZE_X_LOGO_URL_DESC', 'This field is ideal for displaying a merchant logo on a page. The target of this URL will be displayed on the header of the Payment Form and Receipt Page.');
define( '_CFG_AUTHORIZE_X_BACKGROUND_URL_NAME', 'Background URL');
define( '_CFG_AUTHORIZE_X_BACKGROUND_URL_DESC', 'This field will allow the merchant to customize the background image of the Payment Form and Receipt Page. The target of the specified URL will be displayed as the background.');
define( '_CFG_AUTHORIZE_X_COLOR_BACKGROUND_NAME', 'Background Color');
define( '_CFG_AUTHORIZE_X_COLOR_BACKGROUND_DESC', 'Value in this field will set the background color for the Payment Form and Receipt Page.');
define( '_CFG_AUTHORIZE_X_COLOR_LINK_NAME', 'Color Link');
define( '_CFG_AUTHORIZE_X_COLOR_LINK_DESC', 'This field allows the color of the HTML links for the Payment Form and Receipt Page to be set to the value submitted in this field.');
define( '_CFG_AUTHORIZE_X_COLOR_TEXT_NAME', 'Color Text');
define( '_CFG_AUTHORIZE_X_COLOR_TEXT_DESC', 'This field allows the color of the text on the Payment Form and the Receipt Page to be set to the value submitted in this field.');
define( '_CFG_AUTHORIZE_X_HEADER_HTML_RECEIPT_NAME', 'Header Receipt Page');
define( '_CFG_AUTHORIZE_X_HEADER_HTML_RECEIPT_DESC', 'The text contained in this field will be displayed at the top of the Receipt Page.');
define( '_CFG_AUTHORIZE_X_FOOTER_HTML_RECEIPT_NAME', 'Footer Receipt Page');
define( '_CFG_AUTHORIZE_X_FOOTER_HTML_RECEIPT_DESC', 'The text contained in this field will be displayed at the bottom of the Receipt Page.');

// Allopass Settings
define( '_CFG_ALLOPASS_SITEID_NAME', 'SITE_ID');
define( '_CFG_ALLOPASS_SITEID_DESC', 'Your SITE_ID on AlloPass.');
define( '_CFG_ALLOPASS_DOCID_NAME', 'DOC_ID');
define( '_CFG_ALLOPASS_DOCID_DESC', 'Your DOC_ID on AlloPass.');
define( '_CFG_ALLOPASS_AUTH_NAME', 'AUTH');
define( '_CFG_ALLOPASS_AUTH_DESC', 'AUTH on AlloPass.');

// 2Checkout Settings
define( '_CFG_2CHECKOUT_SID_NAME', 'SID');
define( '_CFG_2CHECKOUT_SID_DESC', 'Your 2checkout account number.');
define( '_CFG_2CHECKOUT_SECRET_WORD_NAME', 'Secret Word');
define( '_CFG_2CHECKOUT_SECRET_WORD_DESC', 'Same secret word set by yourself on the Look and Feel page.');
define( '_CFG_2CHECKOUT_INFO_NAME', 'IMPORTANT NOTE!');
define( '_CFG_2CHECKOUT_INFO_DESC', 'On your 2Checkout Account Homepage, "Helpful Links" section, locate and click the "Look and Feel" link. Set up the field "Approved URL" with the URL "http://yoursite.com/index.php?option=com_acctexp&task=2conotification". Replace "yoursite.com" with your own domain.');
define( '_CFG_2CHECKOUT_ALT2COURL_NAME', 'Alternate Url');
define( '_CFG_2CHECKOUT_ALT2COURL_DESC', 'Try this in case you encounter a parameter error.');

// WorldPay Settings
define( '_CFG_WORLDPAY_LONGNAME',		'WorldPay' );
define( '_CFG_WORLDPAY_STATEMENT',		'Payments with WorldPay' );
define( '_CFG_WORLDPAY_DESCRIPTION',	'Accept payments on the internet, by phone, fax or mail. Credit and debit cards, bank transfers and instalments. In any language and most currencies' );
define( '_CFG_WORLDPAY_INSTID_NAME', 	'instId');
define( '_CFG_WORLDPAY_INSTID_DESC', 	'Your WorldPay Installation Id.');
define( '_CFG_WORLDPAY_INFO_NAME', 	'Callback URL');
define( '_CFG_WORLDPAY_INFO_DESC', 	'You need to set the Callback URL in the Configuration Options of your installation on the Customer Management System in your Worldpay Account... the url is:<br />http://[YOUR JOOMLA LOCATION]/index.php?option=com_acctexp&task=worldpaynotification<br />Thats it. More detailed information <a href="http://support.worldpay.com/kb/integration_guides/junior/integration/help/payment_response/sjig_5127.html">here</a>');
define( '_CFG_WORLDPAY_CALLBACKPW_NAME', 	'Callback Password');
define( '_CFG_WORLDPAY_CALLBACKPW_DESC', 	'Please set a Callback Password in your Worldpay Account and enter it here once again. With this, the payment notifications can .');

// WorldPay Futurepay Settings
define( '_CFG_WORLDPAY_FUTUREPAY_LONGNAME',		'WorldPay Futurepay' );
define( '_CFG_WORLDPAY_FUTUREPAY_STATEMENT',	'Recurring Payments with WorldPay' );
define( '_CFG_WORLDPAY_FUTUREPAY_DESCRIPTION',	'Accepts payments on the internet, by phone, fax or mail. Credit and debit cards, bank transfers and instalments. In any language and most currencies' );
define( '_CFG_WORLDPAY_FUTUREPAY_INSTID_NAME', 	'instId');
define( '_CFG_WORLDPAY_FUTUREPAY_INSTID_DESC', 	'Your WorldPay Installation Id.');
define( '_CFG_WORLDPAY_FUTUREPAY_INFO_NAME', 	'Callback URL');
define( '_CFG_WORLDPAY_FUTUREPAY_INFO_DESC', 	'You need to set the Callback URL in the Configuration Options of your installation on the Customer Management System in your Worldpay Account... the url is:<br />http://[YOUR JOOMLA LOCATION]/index.php?option=com_acctexp&task=worldpay_futurepaynotification<br />Thats it. More detailed information <a href="http://support.worldpay.com/kb/integration_guides/junior/integration/help/payment_response/sjig_5127.html">here</a>');
define( '_CFG_WORLDPAY_FUTUREPAY_CALLBACKPW_NAME', 	'Callback Password');
define( '_CFG_WORLDPAY_FUTUREPAY_CALLBACKPW_DESC', 	'Please set a Callback Password in your Worldpay Account and enter it here once again. With this, the payment notifications can .');

// epsNetpay Settings
define( '_CFG_EPSNETPAY_MERCHANTID_NAME', 'Merchant ID');
define( '_CFG_EPSNETPAY_MERCHANTID_DESC', 'Your epsNetpay account number.');
define( '_CFG_EPSNETPAY_MERCHANTPIN_NAME', 'Merchant PIN');
define( '_CFG_EPSNETPAY_MERCHANTPIN_DESC', 'Your Merchant PIN.');
define( '_CFG_EPSNETPAY_ACTIVATE_NAME', 'Activate');
define( '_CFG_EPSNETPAY_ACTIVATE_DESC', 'Offer this Bank.');
define( '_CFG_EPSNETPAY_ACCEPTVOK_NAME', 'Accept VOK');
define( '_CFG_EPSNETPAY_ACCEPTVOK_DESC', 'It might be that due to the account type you have, you will never get an "OK" response, but always "VOK". If that is the case, please switch this on.');

// TAB 10 - Paysignet Settings
define( '_CFG_PAYSIGNET_MERCHANT_NAME', 'Merchant');
define( '_CFG_PAYSIGNET_MERCHANT_DESC', 'Your Merchant Name.');

// AlertPay Settings
define( '_CFG_ALERTPAY_MERCHANT_NAME', 'Merchant');
define( '_CFG_ALERTPAY_MERCHANT_DESC', 'Your Merchant Name.');
define( '_CFG_ALERTPAY_SECURITYCODE_NAME', 'Security Code');
define( '_CFG_ALERTPAY_SECURITYCODE_DESC', 'Your Security Code.');

// eWay Settings
define( '_CFG_EWAY_LONGNAME', 'eWay');
define( '_CFG_EWAY_STATEMENT', 'Make payments with eWAY Shared Payment Solution!');
define( '_CFG_EWAY_DESCRIPTION', 'eWAY is the easiest and most affordable payment gateway in Australia. Process credit card payments via eWAY\'s own secure Shared Payment Page in real-time.');
define( '_CFG_EWAY_CUSTID_NAME', 'Customer ID');
define( '_CFG_EWAY_CUSTID_DESC', 'Your Customer ID.');
define( '_CFG_EWAY_AUTOREDIRECT_NAME', 'Autoredirect');
define( '_CFG_EWAY_AUTOREDIRECT_DESC', 'Automatic Redirect for eWay Transaction');
define( '_CFG_EWAY_SITETITLE_NAME', 'Site Title');
define( '_CFG_EWAY_SITETITLE_DESC', 'The Site Title of the eWay Transaction');

// eWayXML Settings
define( '_CFG_EWAYXML_LONGNAME', 'eWayXML');
define( '_CFG_EWAYXML_STATEMENT', 'Make payments with eWAY Shared Payment Solution!');
define( '_CFG_EWAYXML_DESCRIPTION', 'eWAY is the easiest and most affordable payment gateway in Australia. Process credit card payments via eWAY\'s own secure Shared Payment Page in real-time.');
define( '_CFG_EWAYXML_CUSTID_NAME', 'Customer ID');
define( '_CFG_EWAYXML_CUSTID_DESC', 'Your Customer ID.');
define( '_CFG_EWAYXML_AUTOREDIRECT_NAME', 'Autoredirect');
define( '_CFG_EWAYXML_AUTOREDIRECT_DESC', 'Automatic Redirect for eWay Transaction');
define( '_CFG_EWAYXML_SITETITLE_NAME', 'Site Title');
define( '_CFG_EWAYXML_SITETITLE_DESC', 'The Site Title of the eWay Transaction');

// MoneyProxy Settings
define( '_CFG_MONEYPROXY_LONGNAME', 'MoneyProxy');
define( '_CFG_MONEYPROXY_STATEMENT', 'Make Payments in different digital currencies with Money Proxy!');
define( '_CFG_MONEYPROXY_DESCRIPTION', 'Accept payments on a website in different digital currencies with a single merchant account.');
define( '_CFG_MONEYPROXY_MERCHANT_ID_NAME', 'Merchant ID');
define( '_CFG_MONEYPROXY_MERCHANT_ID_DESC', 'Your merchant identifier at MoneyProxy.');
define( '_CFG_MONEYPROXY_FORCE_CLIENT_RECEIPT_NAME', 'Force Receipt');
define( '_CFG_MONEYPROXY_FORCE_CLIENT_RECEIPT_DESC', 'By setting this parameter to "Yes", it forces Money Proxy to ask an e-mail address where to send a receipt of the payment. By default, the customer can skip the receipt without entering any e-mail address.');
define( '_CFG_MONEYPROXY_SECRET_KEY_NAME', 'Site Title');
define( '_CFG_MONEYPROXY_SECRET_KEY_DESC', 'Your secret key at MoneyProxy.');
define( '_CFG_MONEYPROXY_SUGGESTEDMEMO_NAME', 'Suggested Memo');
define( '_CFG_MONEYPROXY_SUGGESTEDMEMO_DESC', 'This parameter is used to pre-fill the memo field for many payment system. Unfortunately, it is possible that some payment systems do not support this feature. Maximum of 40 characters.');
define( '_CFG_MONEYPROXY_PAYMENT_ID_NAME', 'Payment ID');
define( '_CFG_MONEYPROXY_PAYMENT_ID_DESC', 'The merchant can use this field to track the payment when the status URL is called. It can be up to 10 digits with only letters and numbers (0-9a-zA-Z). You can use Rewrite tags here.');

// Offline Payment
define( '_CFG_OFFLINE_PAYMENT_LONGNAME', 'Offline Payment');
define( '_CFG_OFFLINE_PAYMENT_STATEMENT', 'You can use this option to not pay through the Internet');
define( '_CFG_OFFLINE_PAYMENT_DESCRIPTION', 'You can use this option to not pay through the Internet');
define( '_CFG_OFFLINE_PAYMENT_INFO_NAME', 'Info');
define( '_CFG_OFFLINE_PAYMENT_INFO_DESC', 'The Info that will be displayed to the user on checkout');
define( '_CFG_OFFLINE_PAYMENT_WAITINGPLAN_NAME', 'Waiting Plan');
define( '_CFG_OFFLINE_PAYMENT_WAITINGPLAN_DESC', 'You can assign a user to this plan while he or she waits for the payment to arrive');

// Verotel
define( '_CFG_VEROTEL_LONGNAME', 'Verotel');
define( '_CFG_VEROTEL_STATEMENT', 'Use Verotel: Putting Trust in Global Payments');
define( '_CFG_VEROTEL_DESCRIPTION', 'erotel offers a range of billing methods for your website, including VISA/MasterCard/JCB, Chinese debit, Direct Debit in European countries, pay per phonecall, pay per minute, SMS billing and more!');
define( '_CFG_VEROTEL_MERCHANTID_NAME', 'Merchant ID');
define( '_CFG_VEROTEL_MERCHANTID_DESC', 'Your merchant identifier at Verotel.');
define( '_CFG_VEROTEL_SITEID_NAME', 'Site ID');
define( '_CFG_VEROTEL_SITEID_DESC', 'Your site identifier for this website.');
define( '_CFG_VEROTEL_RESELLERID_NAME', 'Reseller ID');
define( '_CFG_VEROTEL_RESELLERID_DESC', 'Your Reseller ID (if any).');
define( '_CFG_VEROTEL_SECRETCODE_NAME', 'Secret Code');
define( '_CFG_VEROTEL_SECRETCODE_DESC', 'Your secret Verotel code.');
define( '_CFG_VEROTEL_USE_TICKETSCLUB_NAME', 'Tickets Club');
define( '_CFG_VEROTEL_USE_TICKETSCLUB_DESC', 'Use Tickets Club?');
define( '_CFG_VEROTEL_PLAN_PARAMS_VEROTEL_PRODUCT_NAME', 'Product Name');
define( '_CFG_VEROTEL_PLAN_PARAMS_VEROTEL_PRODUCT_DESC', 'The Product Name identifying this plan to Verotel');

// Cybermut
define( '_CFG_CYBERMUT_LONGNAME', 'Cybermut');
define( '_CFG_CYBERMUT_STATEMENT', 'Cybermut - Le groupe Cr&eacute;dit Mutuel');
define( '_CFG_CYBERMUT_DESCRIPTION', 'Cybermut - Le groupe Cr&eacute;dit Mutuel');
define( '_CFG_CYBERMUT_TPE_NAME', 'TPE');
define( '_CFG_CYBERMUT_TPE_DESC', 'No TPE &racute; 7 chiffres, fourni par la banque');
define( '_CFG_CYBERMUT_VER_NAME', 'Version');
define( '_CFG_CYBERMUT_VER_DESC', 'The Protocol Version - leave at "1.2open" if you don\'t know what this is');
define( '_CFG_CYBERMUT_SOC_NAME', 'Code Soci&eacute;t&eacute;');
define( '_CFG_CYBERMUT_SOC_DESC', 'Code Soci&eacute;t&eacute;, fourni par la banque');
define( '_CFG_CYBERMUT_PASS_NAME', 'pass');
define( '_CFG_CYBERMUT_PASS_DESC', 'Valeur de la variable pass');
define( '_CFG_CYBERMUT_KEY_NAME', 'Cl&eacute;');
define( '_CFG_CYBERMUT_KEY_DESC', 'cl&eacute; info');
define( '_CFG_CYBERMUT_SERVER_NAME', 'Serveur bancair');
define( '_CFG_CYBERMUT_SERVER_DESC', 'Choissisez votre banque');
define( '_CFG_CYBERMUT_LANGUAGE_NAME', 'Language:');
define( '_CFG_CYBERMUT_LANGUAGE_DESC', 'Select one of the possible language settings for the paypal site that your user will see when issuing a payment.');

// Authorize.net ARB
define( '_CFG_AUTHORIZE_ARB_LONGNAME', 'Authorize.net ARB');
define( '_CFG_AUTHORIZE_ARB_STATEMENT', 'Make recurring payments with Authorize.net');
define( '_CFG_AUTHORIZE_ARB_DESCRIPTION', 'Make recurring payments with Authorize.net');
define( '_CFG_AUTHORIZE_ARB_LOGIN_NAME', 'API Login ID');
define( '_CFG_AUTHORIZE_ARB_LOGIN_DESC', 'Your API Login ID on Authorize.net.');
define( '_CFG_AUTHORIZE_ARB_TRANSACTION_KEY_NAME', 'Transaction Key');
define( '_CFG_AUTHORIZE_ARB_TRANSACTION_KEY_DESC', 'Your Transaction Key on Authorize.net.');
define( '_CFG_AUTHORIZE_ARB_PROMPTADDRESS_NAME', 'Prompt for Address');
define( '_CFG_AUTHORIZE_ARB_PROMPTADDRESS_DESC', 'Ask the user to put in an Address with the Billing Name.');
define( '_CFG_AUTHORIZE_ARB_TOTALOCCURRENCES_NAME', 'Total Occurances');
define( '_CFG_AUTHORIZE_ARB_TOTALOCCURRENCES_DESC', 'Authorize.net requires that you set the total amount of occurances of a payment. Make sure the total lifespan of a subscription does not exceed three years');
define( '_CFG_AUTHORIZE_ARB_TRIALOCCURRENCES_NAME', 'Trial Occurances');
define( '_CFG_AUTHORIZE_ARB_TRIALOCCURRENCES_DESC', 'Specify the amount of trial periods that are granted to the user. This amount will be substracted from the total occurances.');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLFIRSTNAME_NAME', 'First Name');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLLASTNAME_NAME', 'Last Name');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLADDRESS_NAME', 'Address');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLCITY_NAME', 'City');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLSTATE_NAME', 'State');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLZIP_NAME', 'Zip');
define( '_AEC_AUTHORIZE_ARB_PARAMS_BILLCOUNTRY_NAME', 'Country');
define( '_CFG_AUTHORIZE_ARB_USESILENTPOSTRESPONSE_NAME', 'Use Silent Post Response');
define( '_CFG_AUTHORIZE_ARB_USESILENTPOSTRESPONSE_DESC', 'Please read explanation below');
define( '_CFG_AUTHORIZE_ARB_SILENTPOST_INFO_NAME', 'Silent Postback');
define( '_CFG_AUTHORIZE_ARB_SILENTPOST_INFO_DESC', 'When a recurring payment is set up with ARB, the AEC normally applies a multiplicated subscription period accordig to the Total Occurances. This way, the user will stay active throughout the subscription until it runs out or is cancelled. However, this also means that you would have to check for unpaid bills and manually deactivate the subscriptions if such a thing occurs. The other option is to use the Silent Postback which sends notifications for each subsequent payment that was successful. This in turn triggers the AEC to activate the user for another term. Please consult <a href="http://www.authorize.net/support/Merchant/Integration_Settings/Receipt_Page_Options.htm">this page</a> to find out how to set up the Silent Post Url. Enter http://[YOUR JOOMLA LOCATION]/index.php?option=com_acctexp&task=authorize_arbnotification as the Url.');

// CCBill
define( '_CFG_CCBILL_LONGNAME', 'CCBill');
define( '_CFG_CCBILL_STATEMENT', 'Make payments with CCBill!');
define( '_CFG_CCBILL_DESCRIPTION', 'CCBill');
define( '_CFG_CCBILL_CLIENTACCNUM_NAME', 'Client Account');
define( '_CFG_CCBILL_CLIENTACCNUM_DESC', 'Your CCBill Client Account Number');
define( '_CFG_CCBILL_CLIENTSUBACC_NAME', 'Client SubAccount');
define( '_CFG_CCBILL_CLIENTSUBACC_DESC', 'Your CCBill Client Sub Account Number');
define( '_CFG_CCBILL_SECRETWORD_NAME', 'Secret Word');
define( '_CFG_CCBILL_SECRETWORD_DESC', 'Your secret word used to encrypt and protect transactions');
define( '_CFG_CCBILL_FORMNAME_NAME', 'Form ID');
define( '_CFG_CCBILL_FORMNAME_DESC', 'The CCBill layout you wish to use (look at the HTML form downloaded from CCBILL)');
define( '_CFG_CCBILL_INFO_NAME', 'Postback URL');
define( '_CFG_CCBILL_INFO_DESC', 'You need to remember to set the \'postback\' url in the CCBILL control panel... for both approves and declines this should be...<br />http://[YOUR JOOMLA LOCATION]/index.php?option=com_acctexp&task=ccbillnotification<br />Thats it.');
define( '_CFG_CCBILL_PLAN_PARAMS_ALLOWEDTYPES_NAME', 'Allowed types');
define( '_CFG_CCBILL_PLAN_PARAMS_ALLOWEDTYPES_DESC', 'insert the payment options that the user is supposed to have after being led on to CCBill (refer to your CCBill account for the product IDs you have set up)');
define( '_CFG_CCBILL_DATALINK_USERNAME_NAME', 'Datalink Username');
define( '_CFG_CCBILL_DATALINK_USERNAME_DESC', 'If you want to use Recurring Billing, you need to supply a Datalink Username here. Also remember to set the "recurring" switch in the payment plans that are supposed to have this functionality.');

// iDeal Basic
define( '_CFG_IDEAL_BASIC_LONGNAME', 'iDeal');
define( '_CFG_IDEAL_BASIC_STATEMENT', 'Make payments with iDeal');
define( '_CFG_IDEAL_BASIC_DESCRIPTION', 'De veilige manier van betalen op internet.');
define( '_CFG_IDEAL_BASIC_MERCHANTID_NAME', 'Merchant ID');
define( '_CFG_IDEAL_BASIC_MERCHANTID_DESC', 'Your Merchant ID');
define( '_CFG_IDEAL_BASIC_TESTMODESTAGE_NAME', 'Testmode Stage');
define( '_CFG_IDEAL_BASIC_TESTMODESTAGE_DESC', 'Which of the tests (1-7) do you want to conduct?');
define( '_CFG_IDEAL_BASIC_SUBID_NAME', 'Testmode Stage');
define( '_CFG_IDEAL_BASIC_SUBID_DESC', 'Set to 0 unless you have specific reasons not to');
define( '_CFG_IDEAL_BASIC_KEY_NAME', 'Key');
define( '_CFG_IDEAL_BASIC_KEY_DESC', 'Your secret key');
define( '_CFG_IDEAL_BASIC_DESCRIPTION_NAME', 'Item Description');
define( '_CFG_IDEAL_BASIC_DESCRIPTION_DESC', 'The Item description that is shown on the invoice - max 32 characters!');

// iDeal Advanced
define( '_CFG_IDEAL_ADVANCED_LONGNAME', 'iDeal');
define( '_CFG_IDEAL_ADVANCED_STATEMENT', 'Make payments with iDeal');
define( '_CFG_IDEAL_ADVANCED_DESCRIPTION', 'De veilige manier van betalen op internet.');
define( '_CFG_IDEAL_ADVANCED_MERCHANTID_NAME', 'Merchant ID');
define( '_CFG_IDEAL_ADVANCED_MERCHANTID_DESC', 'Your Merchant ID');
define( '_CFG_IDEAL_ADVANCED_BANK_NAME', 'Bank');
define( '_CFG_IDEAL_ADVANCED_BANK_DESC', 'The Bank Name');
define( '_CFG_IDEAL_ADVANCED_TESTMODESTAGE_NAME', 'Testmode Stage');
define( '_CFG_IDEAL_ADVANCED_TESTMODESTAGE_DESC', 'Which of the tests (1-7) do you want to conduct?');
define( '_CFG_IDEAL_ADVANCED_SUBID_NAME', 'Testmode Stage');
define( '_CFG_IDEAL_ADVANCED_SUBID_DESC', 'Set to 0 unless you have specific reasons not to');
define( '_CFG_IDEAL_ADVANCED_KEY_NAME', 'Key');
define( '_CFG_IDEAL_ADVANCED_KEY_DESC', 'Your secret key');
define( '_CFG_IDEAL_ADVANCED_DESCRIPTION_NAME', 'Item Description');
define( '_CFG_IDEAL_ADVANCED_DESCRIPTION_DESC', 'The Item description that is shown on the invoice - max 32 characters!');

// Authorize.net AIM
define( '_CFG_AUTHORIZE_AIM_LONGNAME', 'Authorize.net AIM');
define( '_CFG_AUTHORIZE_AIM_STATEMENT', 'Make on-site CreditCard payments with Authorize.net');
define( '_CFG_AUTHORIZE_AIM_DESCRIPTION', 'Make on-site CreditCard payments with Authorize.net');
define( '_CFG_AUTHORIZE_AIM_LOGIN_NAME', 'API Login ID');
define( '_CFG_AUTHORIZE_AIM_LOGIN_DESC', 'Your API Login ID on Authorize.net.');
define( '_CFG_AUTHORIZE_AIM_TRANSACTION_KEY_NAME', 'Transaction Key');
define( '_CFG_AUTHORIZE_AIM_TRANSACTION_KEY_DESC', 'Your Transaction Key on Authorize.net.');
define( '_CFG_AUTHORIZE_AIM_PROMPTADDRESS_NAME', 'Prompt for Address');
define( '_CFG_AUTHORIZE_AIM_PROMPTADDRESS_DESC', 'Ask the user to put in an Address with the Billing Name.');
define( '_CFG_AUTHORIZE_AIM_PROMPTZIPONLY_NAME', 'Prompt for Zip only');
define( '_CFG_AUTHORIZE_AIM_PROMPTZIPONLY_DESC', 'Ask the user to put in a Zip code only.');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLFIRSTNAME_NAME', 'First Name');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLLASTNAME_NAME', 'Last Name');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLADDRESS_NAME', 'Address');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLCITY_NAME', 'City');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLSTATE_NAME', 'State');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLZIP_NAME', 'Zip');
define( '_AEC_AUTHORIZE_AIM_PARAMS_BILLCOUNTRY_NAME', 'Country');

// iPayment Silent
define( '_CFG_IPAYMENT_SILENT_LONGNAME', 'iPayment');
define( '_CFG_IPAYMENT_SILENT_STATEMENT', 'Make on-site CreditCard payments with iPayment');
define( '_CFG_IPAYMENT_SILENT_DESCRIPTION', 'Make on-site CreditCard payments with iPayment');
define( '_CFG_IPAYMENT_SILENT_FAKE_ACCOUNT_NAME', 'Fake Account');
define( '_CFG_IPAYMENT_SILENT_FAKE_ACCOUNT_DESC', 'Use a fake account (99999) for testing purposes.');
define( '_CFG_IPAYMENT_SILENT_USER_ID_NAME', 'User Id');
define( '_CFG_IPAYMENT_SILENT_USER_ID_DESC', 'The User Id of your iPayment Account.');
define( '_CFG_IPAYMENT_SILENT_ACCOUNT_ID_NAME', 'Account Id');
define( '_CFG_IPAYMENT_SILENT_ACCOUNT_ID_DESC', 'The Account Id of your iPayment Account.');
define( '_CFG_IPAYMENT_SILENT_PASSWORD_NAME', 'Password');
define( '_CFG_IPAYMENT_SILENT_PASSWORD_DESC', 'The Password of your iPayment Account.');
define( '_CFG_IPAYMENT_SILENT_PROMPTADDRESS_NAME', 'Prompt for Address');
define( '_CFG_IPAYMENT_SILENT_PROMPTADDRESS_DESC', 'Ask the user to put in an Address with the Billing Name.');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLINFO', 'Billing Details');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLFIRSTNAME_NAME', 'First Name');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLLASTNAME_NAME', 'Last Name');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLADDRESS_NAME', 'Address');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLCITY_NAME', 'City');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLSTATE_NAME', 'State');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLZIP_NAME', 'Zip');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLCOUNTRY_NAME', 'Country');
define( '_AEC_IPAYMENT_SILENT_PARAMS_BILLTELEPHONE_NAME', 'Telephone');

// Paysite Cash
define( '_CFG_PAYSITE_CASH_LONGNAME', 'Paysite Cash');
define( '_CFG_PAYSITE_CASH_STATEMENT', 'Make payments with Paysite Cash');
define( '_CFG_PAYSITE_CASH_DESCRIPTION', 'Make payments with Paysite Cash');
define( '_CFG_PAYSITE_CASH_SITEID_NAME', 'Site ID');
define( '_CFG_PAYSITE_CASH_SITEID_DESC', 'Site number in Editor interface Pbiz');
define( '_CFG_PAYSITE_CASH_SECRET_NAME', 'Secret Word');
define( '_CFG_PAYSITE_CASH_SECRET_DESC', 'Choose a secret word or string that will internally secure the data transmitted and received from fraud.');

// Paybox France
define( '_CFG_PAYBOXFR_LONGNAME', 'Paybox');
define( '_CFG_PAYBOXFR_STATEMENT', 'Paybox Services: solutions de paiement sur Internet; Terminal de paiement virtuel');
define( '_CFG_PAYBOXFR_DESCRIPTION', 'Paybox Services: solutions de paiement sur Internet; Terminal de paiement virtuel');
define( '_CFG_PAYBOXFR_SITE_NAME', 'Site number');
define( '_CFG_PAYBOXFR_SITE_DESC', 'Site number (TPE) given by the bank.');
define( '_CFG_PAYBOXFR_RANK_NAME', 'Rank number');
define( '_CFG_PAYBOXFR_RANK_DESC', 'Rank number ("machine") given by the bank.');
define( '_CFG_PAYBOXFR_IDENTIFIANT_NAME', 'Paybox Identifiant');
define( '_CFG_PAYBOXFR_IDENTIFIANT_DESC', 'PAYBOX identifier, supplied by PAYBOX SERVICES at the time of registration.');
define( '_CFG_PAYBOXFR_PUBLICKEY_NAME', 'Public Key');
define( '_CFG_PAYBOXFR_PUBLICKEY_DESC', 'The public key to verify Paybox notifications (required!!).');
define( '_CFG_PAYBOXFR_PATH_NAME', 'Paybox Script Path');
define( '_CFG_PAYBOXFR_PATH_DESC', 'The path where your paybox script is located.');
define( '_CFG_PAYBOXFR_INFO_NAME', 'url http');
define( '_CFG_PAYBOXFR_INFO_DESC', 'You need to set the "url http" in your Paybox settings. This is required so that Paybox will notify the AEC about transactions. The URL you have to put in there is: "http://yoursite.com/index.php"');

// Airtoy
define( '_CFG_AIRTOY_LONGNAME', 'Airtoy');
define( '_CFG_AIRTOY_STATEMENT', 'Pay with Airtoy');
define( '_CFG_AIRTOY_DESCRIPTION', 'Pay with Airtoy');
define( '_CFG_AIRTOY_PHONE_NUMBER_NAME', 'Phone Number');
define( '_CFG_AIRTOY_PHONE_NUMBER_DESC', 'The phone number which the customer should send the SMS to.');
define( '_CFG_AIRTOY_RESPONSE_NAME', 'Response');
define( '_CFG_AIRTOY_RESPONSE_DESC', 'The response SMS the customer will receive (use ReWrite Engine as specified below)');
define( '_CFG_AIRTOY_SECRET_NAME', 'Secret Word');
define( '_CFG_AIRTOY_SECRET_DESC', 'Choose a secret word to make notifications a tad more secure against fraud.');
define( '_CFG_AIRTOY_PLAN_PARAMS_SMSCODE_PREFIX_NAME', 'SMS Code Prefix');
define( '_CFG_AIRTOY_PLAN_PARAMS_SMSCODE_PREFIX_DESC', 'The Prefix for the SMS code the user has to send to Airtoy. The Code will always come out as "Prefix-XXX", where XXX is the invoice id');
define( '_AEC_AIRTOY_PARAMS_EXPLAIN_NAME', 'Send SMS');
define( '_AEC_AIRTOY_PARAMS_EXPLAIN_DESC', "Send an SMS with the text \"%s\" (no quotation marks) to %s. You will then receive a code that you can enter below to clear the payment.");
define( '_AEC_AIRTOY_PARAMS_SMSCODE_NAME', 'SMS Code');
define( '_AEC_AIRTOY_PARAMS_SMSCODE_DESC', 'Enter the SMS Code you have received after sending the above message.');
define( '_AEC_AIRTOY_ERROR_NOCODE', 'You did not put in any code!');
define( '_AEC_AIRTOY_CODE_WRONG', 'The code you have put in was wrong.');

// PayPal WPP
define( '_CFG_PAYPAL_WPP_LONGNAME', 'PayPal WPP');
define( '_CFG_PAYPAL_WPP_STATEMENT', 'Make payments with PayPal - it\'s fast, free and secure!');
define( '_CFG_PAYPAL_WPP_DESCRIPTION', 'PayPal lets you send money to anyone with email. PayPal is free for consumers and works seamlessly with your existing credit card and checking account.');

define( '_CFG_PAYPAL_WPP_API_USER_NAME', 'API Username');
define( '_CFG_PAYPAL_WPP_API_USER_DESC', 'Your PayPal API Username');
define( '_CFG_PAYPAL_WPP_API_PASSWORD_NAME', 'API Password');
define( '_CFG_PAYPAL_WPP_API_PASSWORD_DESC', 'Your PayPal API Password');
define( '_CFG_PAYPAL_WPP_USE_CERTIFICATE_NAME', 'Use Certificate');
define( '_CFG_PAYPAL_WPP_USE_CERTIFICATE_DESC', 'Use Certificate Authentication instead of the standard signature');
define( '_CFG_PAYPAL_WPP_CERTIFICATE_PATH_NAME', 'Certificate Path');
define( '_CFG_PAYPAL_WPP_CERTIFICATE_PATH_DESC', 'The path to your PayPal Certificate');
define( '_CFG_PAYPAL_WPP_SIGNATURE_NAME', 'Signature');
define( '_CFG_PAYPAL_WPP_SIGNATURE_DESC', 'Your PayPal Signature');
define( '_CFG_PAYPAL_WPP_COUNTRY_NAME', 'Country');
define( '_CFG_PAYPAL_WPP_COUNTRY_DESC', 'Choose the country of your business');
define( '_CFG_PAYPAL_WPP_CANCEL_NOTE_NAME', 'Cancel Note');
define( '_CFG_PAYPAL_WPP_CANCEL_NOTE_DESC', 'The Cancel Note the user gets displayed when a subscription via PayPal WPP is cancelled');

// Moneybookers
define( '_CFG_MONEYBOOKERS_LONGNAME', 'Moneybookers');
define( '_CFG_MONEYBOOKERS_STATEMENT', 'Your wallet for the internet... to shop and send money worldwide');
define( '_CFG_MONEYBOOKERS_DESCRIPTION', 'Moneybookers enables any business or consumer with an email address to securely and cost-effectively send and receive payments online - in real-time!');

define( '_CFG_MONEYBOOKERS_PAY_TO_EMAIL_NAME', 'Merchant email');
define( '_CFG_MONEYBOOKERS_PAY_TO_EMAIL_DESC', 'Email address of the merchant\'s moneybookers.com account.');
define( '_CFG_MONEYBOOKERS_SECRET_WORD_NAME', 'Secret Word');
define( '_CFG_MONEYBOOKERS_SECRET_WORD_DESC', '<p>The uppercase MD5 value of the secret word submitted in the "Merchant Tools" section of the merchant\'s online Moneybookers account.</p><strong>Note: The secret word MUST be submitted in the "Merchant Tools" section in lowercase before the md5sig can be used. If you insert any uppercase symbols, they will automatically be converted to lower case. The only restriction on your secret word is the length which must not exceed 10 characters. Non-alphanumeric symbols can be used. If the "Merchant Tools" section is not shown in your account, please contact merchantservices@moneybookers.com</strong>');
define( '_CFG_MONEYBOOKERS_RECIPIENT_DESCRIPTION_NAME', 'Company Name');
define( '_CFG_MONEYBOOKERS_RECIPIENT_DESCRIPTION_DESC', 'A description of the recipient, which will be shown on the merchant gateway. If no value is submitted, the pay_to_email value will be as the recipient of the payment.');
define( '_CFG_MONEYBOOKERS_LOGO_URL_NAME', 'Logo URL');
define( '_CFG_MONEYBOOKERS_LOGO_URL_DESC', 'The URL of the logo which you would like to appear at the top of the merchant gateway. The logo must be accessible via HTTPS otherwise it will not be shown.');
define( '_CFG_MONEYBOOKERS_HIDE_LOGIN_NAME', 'Hide Login');
define( '_CFG_MONEYBOOKERS_HIDE_LOGIN_DESC', 'Merchants can show their customers the gateway page without the prominent login');
define( '_CFG_MONEYBOOKERS_CONFIRMATION_NOTE_NAME', 'Confirmation Note');
define( '_CFG_MONEYBOOKERS_CONFIRMATION_NOTE_DESC', 'Merchant may show to the customer on the confirmation screen - the end step of the process - a note, confirmation number, PIN or any other message. Line breaks (&lt;br /&gt;) may be used for longer messages.');

// Nochex
define( '_CFG_NOCHEX_LONGNAME', 'Nochex');
define( '_CFG_NOCHEX_STATEMENT', 'Nochex Online Payment Services');
define( '_CFG_NOCHEX_DESCRIPTION', 'Leading independent UK based online payment company, specialising in providing smaller online businesses with simple, accessible, easy to use, online payment services.');

define( '_CFG_NOCHEX_MERCHANT_ID_NAME', 'Merchant ID');
define( '_CFG_NOCHEX_MERCHANT_ID_DESC', 'Your Merchant ID');

// Payer.se
define( '_CFG_PAYER_LONGNAME', 'Payer');
define( '_CFG_PAYER_STATEMENT', 'Make payments with Payer!');
define( '_CFG_PAYER_DESCRIPTION', 'Payer payment provider.');

define( '_CFG_PAYER_DEBUGMODE_NAME', 'Debugmode');
define( '_CFG_PAYER_DEBUGMODE_DESC', 'Specify the Debug mode. Choose "silent" for no debugging information.');
define( '_CFG_PAYER_AGENTID_NAME', 'Agent ID');
define( '_CFG_PAYER_AGENTID_DESC', 'Your Agent ID');
define( '_CFG_PAYER_KEY1_NAME', 'Key 1 (A)');
define( '_CFG_PAYER_KEY1_DESC', 'Your First Key (Key A)');
define( '_CFG_PAYER_KEY2_NAME', 'Key 2 (B)');
define( '_CFG_PAYER_KEY2_DESC', 'Your Second Key (Key B)');
define( '_CFG_PAYER_PAYMENT_METHOD_NAME', 'Payment Methods');
define( '_CFG_PAYER_PAYMENT_METHOD_DESC', 'Choose which payment methods you want to allow for the transaction');

// Authorize.net CIM
define( '_CFG_AUTHORIZE_CIM_LONGNAME', 'Authorize.net CIM');
define( '_CFG_AUTHORIZE_CIM_STATEMENT', 'Make payments with Authorize.net');
define( '_CFG_AUTHORIZE_CIM_DESCRIPTION', 'Make payments with Authorize.net');
define( '_CFG_AUTHORIZE_CIM_LOGIN_NAME', 'API Login ID');
define( '_CFG_AUTHORIZE_CIM_LOGIN_DESC', 'Your API Login ID on Authorize.net.');
define( '_CFG_AUTHORIZE_CIM_TRANSACTION_KEY_NAME', 'Transaction Key');
define( '_CFG_AUTHORIZE_CIM_TRANSACTION_KEY_DESC', 'Your Transaction Key on Authorize.net.');
define( '_CFG_AUTHORIZE_CIM_PROMPTADDRESS_NAME', 'Prompt for Address');
define( '_CFG_AUTHORIZE_CIM_PROMPTADDRESS_DESC', 'Ask the user to put in an Address with the Billing Name.');
define( '_CFG_AUTHORIZE_CIM_PROMPTZIPONLY_NAME', 'Prompt for Zip only');
define( '_CFG_AUTHORIZE_CIM_PROMPTZIPONLY_DESC', 'Ask the user to put in a Zip code only.');

// ClickBank
define( '_CFG_CLICKBANK_LONGNAME',			'ClickBank' );
define( '_CFG_CLICKBANK_STATEMENT',			'Make payments with ClickBank!' );
define( '_CFG_CLICKBANK_DESCRIPTION', 'Make payments with ClickBank');
define( '_CFG_CLICKBANK_PUBLISHER_NAME', 'PUBLISHER');
define( '_CFG_CLICKBANK_PUBLISHER_DESC', 'Your ClickBank PUBLISHER account number.');
define( '_CFG_CLICKBANK_SECRET_KEY_NAME', 'Secret key');
define( '_CFG_CLICKBANK_SECRET_KEY_DESC', 'Your secret key is an essential element to the process because it is used for both encryption and decryption and is only known by you and ClickBank');
define( '_CFG_CLICKBANK_INFO_NAME', 'IMPORTANT NOTE! Thank You Page');
define( '_CFG_CLICKBANK_INFO_DESC', 'On your ClickBank Account Homepage, >>Accounts Settings<< tab, locate and click the >>My Products<< link. Set up the field >>Thank You Page URL<< with the URL "http://www.yoursite.com/index.php?option=com_acctexp&task=clickbanknotification". Replace "www.yoursite.com" with your own domain.');
define( '_CFG_CLICKBANK_PLAN_PARAMS_ITEM_NUMBER_NAME', 'Your Clickbank Item Number');
define( '_CFG_CLICKBANK_PLAN_PARAMS_ITEM_NUMBER_DESC', 'Put your item number of product/service you sell here.');

// NetDebit
define( '_CFG_NETDEBIT_LONGNAME',			'NetDebit' );
define( '_CFG_NETDEBIT_STATEMENT',			'Make payments with NetDebit!' );
define( '_CFG_NETDEBIT_DESCRIPTION', 'Make payments with NetDebit');
define( '_CFG_NETDEBIT_CONTENT_ID_NAME', 'Content ID');
define( '_CFG_NETDEBIT_CONTENT_ID_DESC', 'Your NetDebit Content ID.');
define( '_CFG_NETDEBIT_SID_NAME', 'SID');
define( '_CFG_NETDEBIT_SID_DESC', 'Your NetDebit SID.');
define( '_CFG_NETDEBIT_PID_NAME', 'PID');
define( '_CFG_NETDEBIT_PID_DESC', 'Your NetDebit PID.');
define( '_CFG_NETDEBIT_SECRET_NAME', 'Secret');
define( '_CFG_NETDEBIT_SECRET_DESC', 'Your Secret word to make communication with NetDebit secure.');
define( '_CFG_NETDEBIT_JAVASCRIPT_CHECKOUT_NAME', 'Javascript Checkout');
define( '_CFG_NETDEBIT_JAVASCRIPT_CHECKOUT_DESC', 'User Javascript Checkout instead of a HTML Button. This somewhat obsfuscates the button but might not have all the features when switched on. Will be automatically overwritten if more features are needed.');
define( '_CFG_NETDEBIT_TYPE_NAME', 'preselect Payment Type');
define( '_CFG_NETDEBIT_TYPE_DESC', 'Choose the preferrably selected payment option');
define( '_CFG_NETDEBIT_TYPE_LISTITEM_ELV', 'Debit');
define( '_CFG_NETDEBIT_TYPE_LISTITEM_CC', 'Credit Card');
define( '_CFG_NETDEBIT_PLAN_PARAMS_POSITION_NAME', 'Position');
define( '_CFG_NETDEBIT_PLAN_PARAMS_POSITION_DESC', 'Tarifposition');

// payOS
define( '_CFG_PAYOS_LONGNAME',			'payOS' );
define( '_CFG_PAYOS_STATEMENT',			'Make payments with payOS!' );
define( '_CFG_PAYOS_DESCRIPTION', 'Make payments with payOS');
define( '_CFG_PAYOS_WEBMASTER_ID_NAME', 'Webmaster ID');
define( '_CFG_PAYOS_WEBMASTER_ID_DESC', 'Your payOS Webmaster ID.');
define( '_CFG_PAYOS_CONTENT_ID_NAME', 'Content ID');
define( '_CFG_PAYOS_CONTENT_ID_DESC', 'Your payOS Content ID.');
define( '_CFG_PAYOS_SECRET_NAME', 'Secret');
define( '_CFG_PAYOS_SECRET_DESC', 'Your Secret word to make communication with payOS secure.');
define( '_CFG_PAYOS_TYPE_NAME', 'preselect Payment Type');
define( '_CFG_PAYOS_TYPE_DESC', 'Choose the preferrably selected payment option');
define( '_CFG_PAYOS_TYPE_LISTITEM_ELV', 'Debit');
define( '_CFG_PAYOS_TYPE_LISTITEM_CC', 'Credit Card');

// iATS
define( '_CFG_IATS_LONGNAME',			'iATS Ticketmaster' );
define( '_CFG_IATS_STATEMENT',			'Make payments with Ticketmaster!' );
define( '_CFG_IATS_DESCRIPTION', 'Make payments with Ticketmaster');
define( '_CFG_IATS_AGENT_CODE_NAME', 'Agent Code');
define( '_CFG_IATS_AGENT_CODE_DESC', 'Your Ticketmaster Agent Code.');
define( '_CFG_IATS_PASSWORD_NAME', 'Password');
define( '_CFG_IATS_PASSWORD_DESC', 'Your Ticketmaster Password.');
define( '_CFG_IATS_EXP_AMOUNT_NAME', 'Term Length - Amount');
define( '_CFG_IATS_EXP_AMOUNT_DESC', 'Overall length of a billing cycle in which individual billing periods take place. Amount of Units.');
define( '_CFG_IATS_EXP_UNIT_NAME', 'Term Length - Units');
define( '_CFG_IATS_EXP_UNIT_DESC', 'Overall length of a billing cycle in which individual billing periods take place. Select Unit.');
define( '_CFG_IATS_SERVER_TYPE_NAME', 'Server Type');
define( '_CFG_IATS_SERVER_TYPE_DESC', 'Select which type of iATS Server you want to use - US or UK.');

// Chase Paymentech Orbital XML
define( '_CFG_CHASE_PAYMENTECH_LONGNAME',			'Chase Paymentech' );
define( '_CFG_CHASE_PAYMENTECH_STATEMENT',			'Make payments with Chase Paymentech!' );
define( '_CFG_CHASE_PAYMENTECH_DESCRIPTION', 'Make payments with Chase Paymentech');
define( '_CFG_CHASE_PAYMENTECH_MERCHANT_ID_NAME', 'Merchant ID');
define( '_CFG_CHASE_PAYMENTECH_MERCHANT_ID_DESC', 'Your Chase Paymentech Merchant ID.');
define( '_CFG_CHASE_PAYMENTECH_TERMINAL_ID_NAME', 'Terminal ID');
define( '_CFG_CHASE_PAYMENTECH_TERMINAL_ID_DESC', 'Your Terminal ID.');
define( '_CFG_CHASE_PAYMENTECH_PROMPTADDRESS_NAME', 'Prompt for Address');
define( '_CFG_CHASE_PAYMENTECH_PROMPTADDRESS_DESC', 'Ask the user to put in an Address with the Billing Name.');
define( '_CFG_CHASE_PAYMENTECH_PROMPTZIPONLY_NAME', 'Prompt for Zip only');
define( '_CFG_CHASE_PAYMENTECH_PROMPTZIPONLY_DESC', 'Ask the user to put in a Zip code only.');

?>
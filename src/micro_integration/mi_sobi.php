<?php
/**
 * @version $Id: mi_sobi.php
 * @package AEC - Account Control Expiration - Subscription component for Joomla! OS CMS
 * @subpackage Micro Integrations - Sigsiu Online Business Index
 * @copyright 2006/2007 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @license GNU/GPL v.2 http://www.gnu.org/copyleft/gpl.html
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class mi_sobi extends MI
{
	function Settings( $params )
	{
		global $database;

        $settings = array();
		$settings['publish_all']		= array( 'list_yesno' );
		$settings['unpublish_all']		= array( 'list_yesno' );

		$settings = $this->autoduplicatesettings( $settings );

		$settings['rebuild']			= array( 'list_yesno' );

		$rewriteswitches				= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );
		$settings['rewriteInfo']		= array( 'fieldset', _AEC_MI_SET11_EMAIL, AECToolbox::rewriteEngineInfo( $rewriteswitches ) );

		return $settings;
	}

	function Defaults()
	{
		$defaults = array();
		$defaults['agent_fields']	= "user=[[user_id]]\ncb_id=[[user_id]]\nname=[[user_name]]\nemail=[[user_email]]\ncompany=\nneed_approval=1";
		$defaults['company_fields']	= "name=[[user_name]]\naddress=\nsuburb=\ncountry=\nstate=\npostcode=\ntelephone=\nfax=\nwebsite=\ncb_id=[[user_id]]\nemail=[[user_email]]";

		return $defaults;
	}

	function saveparams( $params )
	{
		global $mosConfig_absolute_path, $database;
		$newparams = $params;

		if ( $params['rebuild'] ) {
			$planlist = MicroIntegrationHandler::getPlansbyMI( $this->id );

			foreach ( $planlist as $planid ) {
				$plan = new SubscriptionPlan( $database );
				$plan->load( $planid );

				$userlist = SubscriptionPlanHandler::getPlanUserlist( $planid );
				foreach ( $userlist as $userid ) {
					$metaUser = new metaUser( $userid );

					$this->action( $params, $metaUser, null, $plan );
				}
			}

			$newparams['rebuild'] = 0;
		}

		return $newparams;
	}

	function relayAction( $params, $metaUser, $plan, $invoice, $area )
	{
		$agent = null;
		$company = null;

		if ( $params['unpublish_all'.$area] ) {
			$this->unpublishProperties( $params, $agent );
		}

		if ( $params['publish_all'.$area] ) {
			$this->publishProperties( $params, $agent );
		}

		if ( $company === false ) {
			return false;
		} else {
			return true;
		}
	}

	function publishItems( $params, $metaUser )
	{
		global $database;

		$query = 'UPDATE #__sobi2_item'
				. ' SET `published` = \'1\''
				. ' WHERE `owner` = \'' . $metaUser->userid . '\''
				;
		$database->setQuery( $query );
		if ( $database->query() ) {
			return true;
		} else {
			$this->setError( $database->getErrorMsg() );
			return false;
		}
	}

	function unpublishItems( $params, $metaUser )
	{
		global $database;

		$query = 'UPDATE #__sobi2_item'
				. ' SET `published` = \'0\''
				. ' WHERE `owner` = \'' . $metaUser->userid . '\''
				;
		$database->setQuery( $query );
		if ( $database->query() ) {
			return true;
		} else {
			$this->setError( $database->getErrorMsg() );
			return false;
		}
	}

}

?>
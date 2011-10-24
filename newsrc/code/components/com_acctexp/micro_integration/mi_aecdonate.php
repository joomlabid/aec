<?php
/**
 * @version $Id: mi_aecdonate.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - AEC Donations
 * @copyright 2006-2011 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_aecdonate
{
	function Info()
	{
		$info = array();
		$info['name'] = JText::_('AEC_MI_AECDONATE_NAME');
		$info['desc'] = JText::_('AEC_MI_AECDONATE_DESC');

		return $info;
	}

	function Settings()
	{
		$settings = array();

		$settings['min'] = array( 'inputB' );
		$settings['rec'] = array( 'inputB' );
		$settings['max'] = array( 'inputB' );

		return $settings;
	}

	function saveParams( $params )
	{
		foreach ( $params as $n => $v ) {
			if ( !empty( $v ) ) {
				if ( $n != 'rec' ) {
					$params[$n] = AECToolbox::correctAmount( $v );
				}
			}
		}

		return $params;
	}

	function getMIform( $request )
	{
		$settings = array();

		if ( !empty( $this->settings['rec'] ) ) {
			$settings['amt'] = array( 'inputC', JText::_('MI_MI_AECDONATE_USERSELECT_AMT_NAME'), JText::_('MI_MI_AECDONATE_USERSELECT_AMT_DESC'), $this->settings['rec'] );
		} else {
			$settings['amt'] = array( 'inputC', JText::_('MI_MI_AECDONATE_USERSELECT_AMT_NAME'), JText::_('MI_MI_AECDONATE_USERSELECT_AMT_DESC'), '' );
		}

		return $settings;
	}

	function verifyMIform( $request )
	{
		$return = array();

		if ( empty( $request->params['amt'] ) || ( $request->params['amt'] == "" ) ) {
			$return['error'] = JText::_("Please provide an amount");
		}

		return $return;
	}

	function invoice_item_cost( $request )
	{
		$this->modifyPrice( $request );

		return true;
	}

	function modifyPrice( $request )
	{
		if ( !isset( $request->params['amt'] ) ) {
			return null;
		}

		$price = AECToolbox::correctAmount( $request->params['amt'] );

		if ( !empty( $this->settings['max'] ) ) {
			if ( $price > $this->settings['max'] ) {
				$price = $this->settings['max'];
			}
		}

		if ( !empty( $this->settings['min'] ) ) {
			if ( $price < $this->settings['min'] ) {
				$price = $this->settings['min'];
			}
		}

		$price = AECToolbox::correctAmount( $price );

		$request->add['terms']->nextterm->setCost( $price );

		return null;
	}

}
?>

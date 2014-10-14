<?php
/**
 * @version $Id: mi_eventum.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - Eventum
 * @copyright 2006-2013 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class mi_eventum extends MI
{
	function Info()
	{
		$info = array();
		$info['name'] = 'Eventum MI';
		$info['desc'] = 'Eventum Help Desk Integration - WIP';

		return $info;
	}

	function Settings()
	{
		$settings = array();
		$settings['set_issue_level']	= array( 'inputE' );
		$settings['tags']				= array( 'inputE' );
		$settings['text']				= array( 'inputD' );
		$settings['level']				= array( 'list' );
		$settings['force_notify']		= array( 'toggle' );
		$settings['force_email']		= array( 'toggle' );
		$settings['params']				= array( 'inputD' );

		$settings = $this->autoduplicatesettings( $settings );

		$rewriteswitches			= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );

		$settings					= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		$levels[] = JHTML::_('select.option', 2, JText::_('AEC_NOTICE_NUMBER_2') );
		$levels[] = JHTML::_('select.option', 8, JText::_('AEC_NOTICE_NUMBER_8') );
		$levels[] = JHTML::_('select.option', 32, JText::_('AEC_NOTICE_NUMBER_32') );
		$levels[] = JHTML::_('select.option', 128, JText::_('AEC_NOTICE_NUMBER_128') );

		$settings['lists']['level'] = JHTML::_( 'select.genericlist', $levels, 'level', 'size="5"', 'value', 'text', $this->settings['level'] );
		$settings['lists']['level_exp'] = JHTML::_( 'select.genericlist', $levels, 'level_exp', 'size="5"', 'value', 'text', $this->settings['level_exp'] );
		$settings['lists']['level_pre_exp'] = JHTML::_( 'select.genericlist', $levels, 'level_pre_exp', 'size="5"', 'value', 'text', $this->settings['level_pre_exp'] );

		return $settings;
	}


	function relayAction( $request )
	{
		if ( !isset( $this->settings['short'.$request->area] ) ) {
			return null;
		}

		$eventum_userid = $this->getEventumUser( $request->metaUser->userid );

		$db = JFactory::getDBO();

		$rewriting = array( 'short', 'tags', 'text', 'params' );

		foreach ( $rewriting as $rw_name ) {
			$this->settings[$rw_name.$request->area] = AECToolbox::rewriteEngineRQ( $this->settings[$rw_name.$request->area], $request );
		}

		$log_entry = new EventLog();
		$log_entry->issue( $this->settings['short'.$request->area], $this->settings['tags'.$request->area], $this->settings['text'.$request->area], $this->settings['level'.$request->area], $this->settings['params'.$request->area], $this->settings['force_notify'.$request->area], $this->settings['force_email'.$request->area] );
	}

	function getEventum()
	{

	}

	function getEventumCustomFields()
	{

	}

	function getEventumFieldOptions()
	{

	}

	function getEventumUserid( $metaUser )
	{
		return $userid;
	}

	function updateIssueLevel( $eventum_userid, $level )
	{

	}

	function createIssue( $eventum_userid, $details )
	{

	}
}
?>

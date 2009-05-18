<?php
/**
 * @version $Id: mi_phpbb3.php 01 2007-08-11 13:29:29Z SBS $
 * @package AEC - Account Control Expiration - Subscription component for Joomla! OS CMS
 * @subpackage Micro Integrations - phpBB3
 * @copyright 2006/2007 Copyright (C) David Deutsch
 * @author Calum Polwart, Jon Goldman, David Deutsch & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/copyleft/gpl.html
 * Based on code from the mi_remository.php and mi_juga.php by David Deutsch.
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_phpbb3
{

	function Info()
	{
		$info = array();
		$info['name'] = _AEC_MI_NAME_PHPBB3;
		$info['desc'] = _AEC_MI_DESC_PHPBB3;

		return $info;
	}

	function Settings()
	{
		global $database;

		$query = 'SELECT `group_id`, `group_name`, `group_colour`'
			 	. ' FROM phpbb_groups'
			 	;
	 	$database->setQuery( $query );
	 	$groups = $database->loadObjectList();

		$sg = array();
		foreach ( $groups as $group ) {
			$sg[] = mosHTML::makeOption( $group->group_id, $group->group_name );
			$sg2[] = mosHTML::makeOption( $group->group_colour, $group->group_name );
		}

         // Explode the Groups to Exclude
         if ( !empty($this->settings['groups_exclude'] ) ) {
     		$selected_groups_exclude = array();

     		foreach ( $this->settings['groups_exclude'] as $group_exclude ) {
     			$selected_groups_exclude[]->value = $group_exclude;
     		}
     	} else {
     		$selected_groups_exclude			= '';
     	}

		$settings = array();

		$settings['lists']['group']				= mosHTML::selectList($sg, 'group', 'size="4"', 'value', 'text', $this->settings['group']);
		$settings['lists']['group_exp']			= mosHTML::selectList($sg, 'group_exp', 'size="4"', 'value', 'text', $this->settings['group_exp']);
		$settings['lists']['group_colour']		= mosHTML::selectList($sg2, 'group_colour', 'size="4"', 'value', 'text', $this->settings['group_colour']);
		$settings['lists']['group_colour_exp']	= mosHTML::selectList($sg2, 'group_colour_exp', 'size="4"', 'value', 'text', $this->settings['group_colour_exp']);

		$settings['lists']['groups_exclude']	= mosHTML::selectList( $sg, 'groups_exclude[]', 'size="10" multiple="true"', 'value', 'text', $selected_groups_exclude );

		$settings['set_group']				= array( 'list_yesno' );
		$settings['group']					= array( 'list' );
		$settings['apply_colour']			= array( 'list_yesno' );
		$settings['group_colour']			= array( 'list' );
		$settings['set_group_exp']			= array( 'list_yesno' );
		$settings['group_exp']				= array( 'list' );
		$settings['apply_colour_exp']		= array( 'list_yesno' );
		$settings['group_colour_exp']		= array( 'list' );
		$settings['groups_exclude']			= array( 'list' );
		$settings['set_groups_exclude']		= array( 'list_yesno' );
		$settings['set_clear_groups']		= array( 'list_yesno' );
		$settings['rebuild']				= array( 'list_yesno' );
		$settings['remove']					= array( 'list_yesno' );

		return $settings;
	}

	function expiration_action( $request )
	{
		global $database;

		if ( $this->settings['set_group_exp'] ) {
			$userid = $request->metaUser->userid;

			$bbuser = null;
			// Get user info from PHPBB3 User Record
			$query = 'SELECT `user_id`, `group_id`'
					. ' FROM phpbb_users'
					. ' WHERE LOWER(user_email) = \'' . strtolower( $request->metaUser->cmsUser->email ) . '\''
					;
			$database->setQuery( $query );
			$database->loadObject( $bbuser );

			// check PHPBB3 primary group not on excluded list
			if ( in_array( $bbuser->group_id, $this->settings['groups_exclude'] ) ) {
				$onExcludeList = true;
			} else {
				$onExcludeList = false;
			}

			// check PHPBB3 secondary groups not on excluded list as long as primary group isn't already
			if ( ( $this->settings['set_groups_exclude'] ) && ( !$onExcludeList ) ) {
				$secGroups = null;
				$query = 'SELECT `group_id`'
						. ' FROM phpbb_user_group'
						. ' WHERE `user_id` = \'' . $bbuser->user_id . '\''
						;
				$database->setQuery( $query );
				$database->loadObject( $secGroups );

			 	foreach ( $secGroups as $secGroup ) {
					if ( in_array( $secGroup, $this->settings['groups_exclude'] ) ) {
						$onExcludeList = true;
						break;
					}
				}
			}

			$queries = array();

			// If Not On Exclude List, apply expiration group & clear secondary groups (if set)
			if ( !$onExcludeList ) {
				// update PHPBB3 groups list
				$queries[] = 'UPDATE phpbb_user_group'
						. ' SET `group_id` = \'' . $this->settings['group_exp'] . '\''
						. ' WHERE `group_id` = \'' . $bbuser->group_id . '\''
						. ' AND `user_id` = \'' . $bbuser->user_id . '\''
						;

				if ( $this->settings['apply_colour_exp'] ) {
					$color = ', `user_colour` = \'' . $this->settings['group_colour_exp'] . '\'';
				} else {
					$color = '';
				}

				$queries[] = 'UPDATE phpbb_users'
							. ' SET `group_id` = \'' . $this->settings['group_exp'] . '\'' . $color
							. ' WHERE `user_id` = \'' . $bbuser->user_id . '\''
							;

				// Clear Secondary Groups (if flag set)
				if ( $this->settings['set_clear_groups'] ) {
					$queries[] = 'DELETE FROM phpbb_user_group'
							. ' WHERE `group_id` != \'' . $this->settings['group_exp'] . '\''
							. ' AND `user_id` = \'' . $bbuser->user_id . '\''
							;
				}
			}

			foreach ( $queries as $query ) {
				$database->setQuery( $query );
				$database->query();
			}
		}

		return true;
	}

	function action( $request )
	{
		global $database;

		if ( $this->settings['set_group'] ) {
			$bbuser = null;
			// get the user phpbb user id
			$query = 'SELECT `user_id`, `group_id`'
					. ' FROM phpbb_users'
					. ' WHERE LOWER(user_email) = \'' . strtolower( $request->metaUser->cmsUser->email ) . '\''
					;
			$database->setQuery( $query );
			$database->loadObject( $bbuser );

			// check PHPBB3 primary group not on excluded list
			if ( in_array( $bbuser->group_id, $this->settings['groups_exclude'] ) ) {
				$onExcludeList = true;
			} else {
				$onExcludeList = false;
			}

			// check PHPBB3 secondary groups not on excluded list as long as primary group isn't already
			if ( ( $this->settings['set_groups_exclude'] ) && ( !$onExcludeList ) ) {
				$secGroups = null;
				$query = 'SELECT `group_id`'
						. ' FROM phpbb_user_group'
						. ' WHERE `user_id` = \'' . $bbuser->user_id . '\''
						;
				$database->setQuery( $query );
				$database->loadObject( $secGroups );

			 	foreach ( $secGroups as $secGroup ) {
					if ( in_array( $secGroup, $this->settings['groups_exclude'] ) ) {
						$onExcludeList = true;
						break;
					}
				}
			}

			// If Not On Exclude List, apply expiration group & clear secondary groups (if set)
			if ( !$onExcludeList ) {
				// update PHPBB3 groups list
				$queries[] = 'UPDATE phpbb_user_group'
						. ' SET `group_id` = \'' . $this->settings['group'] . '\''
						. ' WHERE `group_id` = \'' . $bbuser->group_id . '\''
						. ' AND `user_id` = \'' . $bbuser->user_id . '\''
						;
				// update PHPBB3 primary group
				if ( $this->settings['apply_colour'] ) {
					$color = ', `user_colour` = \'' . $this->settings['group_colour'] . '\'';
				} else {
					$color = '';
				}

				$queries[] = 'UPDATE phpbb_users'
							. ' SET `group_id` = \'' . $this->settings['group'] . '\'' . $color
							. ' WHERE `user_id` = \'' . $bbuser->user_id . '\''
							;
			}

			foreach ( $queries as $query ) {
				$database->setQuery( $query );
				$database->query();
			}
		}

		return true;
	}
}

?>
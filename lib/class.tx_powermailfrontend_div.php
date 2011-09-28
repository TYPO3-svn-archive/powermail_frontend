<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alex Kellner <alexander.kellner@in2code.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib . 'class.tslib_pibase.php');

class tx_powermailfrontend_div extends tslib_pibase {

	public $extKey = 'powermail_frontend'; // Extension key
	public $prefixId = 'tx_powermailfrontend_pi1'; // prefix for piVars
	public $scriptRelPath = 'pi1/class.tx_powermailfrontend_list.php';	// Path to any script in pi1 for locallang

	/**
	* Function getTitle() gets title for any uid
	*
	* @param 	string 	$uid: field uid
	* @return 	string	title
	*/
	public function getTitle($uid) {

		$title = $uid;
		// SQL query
		if (is_numeric($this->minimize($uid))) { // only if uid4 given
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
				'title',
				'tx_powermail_fields',
				$where_clause = 'uid = ' . $this->minimize($uid),
				$groupBy = '',
				$orderBy = '',
				$limit = 1
			);
			if ($res !== false) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				$title = $row['title'];
			}
		}

		return $title;
	}
	
	/**
	* Function alternate() checks if a number is odd or not
	*
	* @param 	integer 	$int: a positive number
	* @return 	boolean
	*/
	public function alternate($int = 0) {
		if ($int % 2 != 0) { // odd or even
			return false; // return false
		} else { 
			return true; // return true
		}
	}
	
	/**
	* Function arraytwo2arrayone() changes: array('v1', array('v2')) to array('v1', 'v1_v2)
	*
	* @param 	array 	$array: array to change
	* @return 	array	New array
	*/
	public function arraytwo2arrayone($array) {
		$newarray = array();
		
		if (count($array) > 0 && is_array($array)) {
			foreach ($array as $k => $v) {
				if (!is_array($v)) { // first level
					
					$newarray[$k] = $v; // no change
				
				} else { // second level
					if (count($v) > 0) {
						
						foreach ($v as $k2 => $v2) {
							if (!is_array($v2)) $newarray[$k . '_' . $k2] = $v2; // change to first level
						}
					
					}
				}
			}
		}
		
		return $newarray;
	}
	
	/**
	* Function minimize() changes string uid3_0 => 3
	*
	* @param 	string 	$str: string with uid
	* @param 	boolean $uidreplace: if uid should be removed from string
	* @return 	string	uid
	*/
	public function minimize($str, $uidreplace = 1) {
		if ($uidreplace == 1) $str = str_replace('uid', '', $str); // uid23_1 to 23_1
		$strarray = explode('_', $str); // 23_1 to 23 and 1
		$uid = $strarray[0]; // 23 from 23_1
		
		if (!empty($uid)) return $uid;
	}
	
	/**
	* Function numberOfMails() returns number of powermails
	*
	* @param 	array 	$where: array with whole select string
	* @return 	integer	number of all mails
	*/
	public function numberOfMails($where) {
		$num = 0;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
			$where['select'],
			$where['from'],
			$where['where'],
			$where['groupby'],
			$where['orderby']
		);
		if ($res !== false) {
			$num = $GLOBALS['TYPO3_DB']->sql_num_rows($res); // numbers of all entries
		}
		return $num;
	}
	
	/**
	* Function sortLink() generates sort link
	*
	* @param 	string 	$content: html content
	* @param 	array 	$conf: TS configuration
	* @param 	array 	$cObj: content object
	* @param 	array 	$piVars: piVars from GET or POST request
	* @return 	string	link
	*/
	public function sortLink($content, $conf, $cObj, $piVars) {
		$this->conf = $conf;
		$this->cObj = $cObj;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		
		return preg_replace_callback ( // Automaticly fill locallangmarkers with fitting value of locallang.xml
			'#\#\#\#SORT_(.*)\#\#\##Uis', // regulare expression
			array($this, 'sortLinkCallback'), // open function
			$content // current content
		);
	}
	
	/**
	* Function sortLinkCallback() callback function to sort link generation 
	*
	* @param 	array 	$array: array from preg_replace_callback function
	* @return 	string	link
	*/
	public function sortLinkCallback($array) {
		$field = $array[1];
		$sort = 'asc';
		if ($this->piVars['sort'][strtolower($field)] == 'asc') $sort = 'desc'; // change if asc
		$label = $this->pi_getLL('label_sorting', 'Sort');
		$sorting = $this->pi_getLL('label_' . $sort, 'ascending');
		
		return $this->pi_linkTP_keepPIvars(sprintf($label, $field, $sorting), array('sort' => array(strtolower($field) => $sort)), 1); // return string with link
	}
	
	/**
	* Returns fieldtype to a uid (need function fieldDetails())
	*
	* @param 	string 	$uid	powermail field uid
	* @return 	string	fieldtype
	*/
	public function getFieldType($uid) {
		$tmp_uid = t3lib_div::trimExplode('_', $uid, 1); // split on _
		$uid = $tmp_uid[0]; // give me only the values before _ (34_1 to 34)
		$uid = intval(preg_replace('/[^0-9]/', '', $uid)); // keep only the numbers to get the uid of powermail field
		$arr = $this->fieldDetails($uid); // get values from database to this uid

		return $arr['f_type'];
	}
	
	/**
	* Get all Field details of any powermail field
	*
	* @param 	string 	$uid	Could be a number (33) or a string like ###UID33###
	* @return 	array	$row	Returns field array
	*/
	public function fieldDetails($uid) {
		$ret = array();
		if (strpos($uid, '_') === false) { // We don't want uids like ###UID33_0###
			$uid = intval(preg_replace('/[^0-9]/', '', $uid)); // keep only the numbers to get the uid of powermail field
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
				'tx_powermail_fieldsets.uid fs_uid, tx_powermail_fields.uid f_uid, tx_powermail_fieldsets.felder fs_fields, tx_powermail_fieldsets.title fs_title, tx_powermail_fields.title f_title, tx_powermail_fields.formtype f_type, tx_powermail_fields.flexform f_field, tt_content.tx_powermail_title c_title, tx_powermail_fields.fe_field f_fefield, tx_powermail_fields.description f_description',
				'tx_powermail_fieldsets LEFT JOIN tx_powermail_fields ON (tx_powermail_fieldsets.uid = tx_powermail_fields.fieldset) LEFT JOIN tt_content ON (tx_powermail_fieldsets.tt_content = tt_content.uid)',
				$where_clause = 'tx_powermail_fields.uid = ' . intval($uid),
				$groupBy = '',
				$orderBy = '',
				$limit = 1
			);
			if ($res !== false) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if (is_array($row) && count($row) > 0) {
					$ret = $row;
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		return $ret;
	}
	
	/**
	* Check if current logged in user is allowed to make changes
	*
	* @param 	string 	$uid	FE_USERS id of current logged in user
	* @param 	array 	$conf	$this->conf
	* @return 	string	$error	return Errormessage or void if no error
	*/
	public function feuserHasAccess($uid, $conf) {
		// config
		$this->conf = $conf;
		$this->pi_loadLL();
		$error = false; // no error at the beginning
		$usersArrayConf = t3lib_div::trimExplode(',', $conf['edit.']['feuser'], 1); // array with all feuserHasAccess users
		$groupArrayConf = t3lib_div::trimExplode(',', $conf['edit.']['fegroup'], 1); // array with all feuserHasAccess groups
		if (is_numeric(array_search('owner', $usersArrayConf))) $usersArrayConf[array_search('owner', $usersArrayConf)] = $this->userID($uid, 'fe_users'); // replace "owner" with uid of owner
		if (is_numeric(array_search('ownergroup', $groupArrayConf))) { // if one entry is "ownergroup"
			$tmp_groupArray = $this->userID($uid, 'fe_groups'); // replace "ownergroup" with uid of group where the owner is listed
			$groupArrayConf = array_merge((array) $groupArrayConf, (array) $tmp_groupArray); // add group array from user who has made the powermail_mail to the group array
		}
		$groupArray = t3lib_div::trimExplode(',', $GLOBALS['TSFE']->fe_user->user['usergroup'], 1); // array with all groups from currently logged in user
		
		// 1. check for admins
		if ($this->isAdmin($conf)) {
			return false;
		}
		
		// 2. check for editors
		if (!empty($conf['edit.']['feuser']) || !empty($conf['edit.']['fegroup'])) { // if min a user or a group is feuserHasAccess
			if ($GLOBALS['TSFE']->fe_user->user['uid'] > 0) { // if a FE User is logged in
				
				// 1. check group
				if (count(array_intersect($groupArray, $groupArrayConf)) == 0) { // if there is none of the groups feuserHasAccess
					$error = $this->pi_getLL('edit_error_forbiddenuser', 'Logged in user is not feuserHasAccess to make changes!'); // msg: user is not feuserHasAccess
				}
				
				// 2. check user
				if (in_array($GLOBALS['TSFE']->fe_user->user['uid'], $usersArrayConf)) { // if currently logged in user is feuserHasAccess
					$error = false; // overwrite any error entries
				}
				
			} else {
				$error = $this->pi_getLL('edit_error_nouser', 'No user logged in!');
			}
		} else {
			$error = $this->pi_getLL('edit_error_nothing', 'Please enable min. a user or a group to edit entries!');
		}
		
		return $error;
	}
	
	/**
	* Check if current logged in fe user is admin user
	*
	* @param 	array 	TypoScript configuration
	* @return 	boolean
	*/
	public function isAdmin($conf) {
		if ($conf['admin.']['feusergroup'] > 0 && t3lib_div::inList($GLOBALS['TSFE']->fe_user->user['usergroup'], $conf['admin.']['feusergroup'])) { // if admin group isset and admingroup is in usergroup of currently logged in fe_user
			return true; // admin
		} else {
			return false; // no admin
		}
	}
	
	/**
	* Return uid of feuser or feuser group from mail owner
	*
	* @param 	string 	$uid				Mail UID
	* @param 	string 	$table				Table name		('fe_users' or 'fe_groups')
	* @return	string	$return[$table]		UID of found value
	*/
	public function userID($uid, $table = 'fe_users') {
		$return = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
			'fe_users.uid fe_users_uid, fe_groups.uid fe_groups_uid',
			'tx_powermail_mails, fe_users, fe_groups, sys_refindex',
			$where_clause = 'sys_refindex.tablename = "fe_users" AND sys_refindex.ref_table = "fe_groups" AND fe_users.uid = sys_refindex.recuid AND fe_groups.uid = sys_refindex.ref_uid AND fe_users.uid = tx_powermail_mails.feuser AND tx_powermail_mails.uid = ' . intval($uid),
			$groupBy = '',
			$orderBy = '',
			$limit = 1000
		);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // One loop for every entry
				$return['fe_users'] = $row['fe_users_uid'];
				$return['fe_groups'][] = $row['fe_groups_uid'];
			}
		}
		
		if (!empty($return[$table])) {
			return $return[$table]; // if result return it
		}
		return 0;
	}
	
	/**
	* Cuts an array and returns only feuserHasAccess values
	*
	* @param 	array 	$vars			All variables from database to current powermail mail	array('uid3' => 'Alex')
	* @param 	string 	$feuserHasAccessList		Commaseparated string with feuserHasAccess uids 				(uid3,uid4)
	* @params	string	$fieldsmode		Defines mails / forms mode
	* @params	int		$powermailuid	Powermail Uid who contains the fields to edit
	* @return 	array	$vars			Return full array form input or only a part of it
	*/
	public function feuserHasAccessUID($vars, $feuserHasAccessList, $fieldsmode = 'mails', $powermailUid = 0) {
		$feuserHasAccessArray = t3lib_div::trimExplode(',', $feuserHasAccessList, 1); // split feuserHasAccess string to an array
		switch ($fieldsmode) {
			case 'mails':
			case '':
				if (count($feuserHasAccessArray) > 0) { // if there are some fields which are feuserHasAccess to show (if not show all)
					$new = array();
					foreach ($vars as $key => $value) { // one loop for every variable in db
						if (in_array($key, $feuserHasAccessArray)) { // if current key of db is in feuserHasAccess array
							$new[$key] = $value; // fill array
						}
					}
					$vars = $new; // overwrite array with new array
				}
				return $vars;

			case 'forms':
				$new = array();
				if (count($feuserHasAccessArray) > 0) { // if there are some fields which are feuserHasAccess to show (if not show all)
					foreach ($feuserHasAccessArray as $key) {
						if (!empty($vars[$key])) {
							$new[$key] = $vars[$key];
						} else {
							$new[$key] = '';
						}
					}
				} else if (intval($powermailUid) > 0) {
					// SQL query
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
						'tx_powermail_fields.uid',
						'tx_powermail_fields LEFT JOIN tx_powermail_fieldsets ON tx_powermail_fieldsets.uid = tx_powermail_fields.fieldset',
						$where_clause = "(tx_powermail_fields.formtype = 'text'
							OR tx_powermail_fields.formtype = 'radio'
							OR tx_powermail_fields.formtype = 'check'
							OR tx_powermail_fields.formtype = 'select'
							OR tx_powermail_fields.formtype = 'textarea'
							OR tx_powermail_fields.formtype = 'countryselect'
							OR tx_powermail_fields.formtype = 'date'
							OR tx_powermail_fields.formtype = 'datetime'
							OR tx_powermail_fields.formtype = 'file')
							AND tx_powermail_fieldsets.tt_content = " . $powermailUid . t3lib_BEfunc::BEenableFields('tx_powermail_fields') . t3lib_BEfunc::BEenableFields('tx_powermail_fieldsets'),
						$groupBy = '',
						$orderBy = 'tx_powermail_fields.fieldset, tx_powermail_fields.sorting',
						$limit = ''
					);

					if ($res !== false) { // If there is a result
						// 1. Collecting different field uids to an array
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
							if (!empty($vars['uid' . $row['uid']])) {
								$new['uid' . $row['uid']] = $vars['uid' . $row['uid']];
							} else {
								$new['uid' . $row['uid']] = '';
							}
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res);
					}

				}
				return $new;
		}
	}

	/**
	* Cuts an array and returns only allowed values
	*
	* @param 	array 	$vars		All variables from database to current powermail mail	array('uid3' => 'Alex')
	* @param 	string 	$allowed	Commaseparated string with allowed uids 				(uid3,uid4)
	* @return 	array	$vars		Return full array form input or only a part of it
	*/
	public function allowedUID($vars, $allowed) {
		$alwd_arr = t3lib_div::trimExplode(',', $allowed, 1); // split allowed string to an array
		if (count($alwd_arr) > 0) { // if there are some fields which are allowed to show (if not show all)
			$new = array();

			foreach ($vars as $key => $value) { // one loop for every variable in db
				if (in_array($key, $alwd_arr)) { // if current key of db is in allowed array
					$new[$key] = $value; // fill array
				}
			}
			$vars = $new; // overwrite array with new array
		}

		return $vars;
	}
	
	/**
	* Function addWhereClause() adds where clause for DB select (list, latest, abc filter)
	*
	* @return 	string		$where		Where clause
	*/
	public function addWhereClause($pObj) {
		$this->cObj = $pObj->cObj;
		$this->conf = $pObj->conf;
		$this->mode = ($pObj->mode != 'abc' ? $pObj->mode : 'search'); // set mode
		
		$where = '1'; // start where clause
		$where .= ' AND pid IN (' . $this->pi_getPidList($this->cObj->data['pages'], $this->cObj->data['recursive']) . ')'; // only in starting folder
		if ($this->conf[$this->mode . '.']['delta'] > 0) {
			$where .= ' AND crdate > ' . (time() - (intval($this->conf[$this->mode . '.']['delta']) * 86400)); // show only entries which are newer than before X days
		}
		if ($this->conf[$this->mode . '.']['showownonly'] == 1 && !$this->isAdmin($this->conf)) {
			$where .= ' AND feuser = ' . (($GLOBALS['TSFE']->fe_user->user['uid']) > 0 ? $GLOBALS['TSFE']->fe_user->user['uid'] : -1); // show only own entries (if not logged in, use a non-existing number to show no entries)
		}
		$where .= $this->cObj->enableFields('tx_powermail_mails'); // don't show hidden and deleted fields

		return $where;
	}
	
	/** 
	 * Delete entry if &tx_powermailfrontend_pi1[delete]= 
	 * 
	 * @param obj $pObj Parent object 
	 * @return boolean
	 */
	public function delEntry($pObj) {
		// only if something should be deleted and current user is feuserHasAccess to delete something
		if ($pObj->piVars['delete'] > 0 && !$this->feuserHasAccess($pObj->piVars['delete'], $pObj->conf)) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_powermail_mails',
				'uid=' . $pObj->piVars['delete'],
				array(
					'deleted' => 1,
					'tstamp' => time()
				)
			);
			return true; 
		}
		return false;
	}
	
	/**
	* Return powermail field type from Uid
	*
	* @param 	integer 	Field Uid
	* @return	string		Type
	*/
	public function getFieldTypeFromUid($uid) {
		if ($uid == '_all') {
			return 'text';
		}
		$uid = str_replace('uid', '', $uid);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
			'formtype',
			'tx_powermail_fields',
			$where_clause = 'uid = ' . intval($uid),
			$groupBy = '',
			$orderBy = '',
			$limit = 1
		);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row['formtype'];
		}
	}
	
	/**
	* Return powermail field options from Field Uid
	*
	* @param 	integer 	Field Uid
	* @param 	object	 	Parernt Object
	* @return	array		Options Array (label, value, selected)
	*/
	public function getFieldOptionsFromFieldUid($uid, $pObj) {
		$uid = str_replace('uid', '', $uid);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
			'flexform',
			'tx_powermail_fields',
			$where_clause = 'uid = ' . intval($uid),
			$groupBy = '',
			$orderBy = '',
			$limit = 1
		);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		}
		$arr = array();
		$tmp_options = $pObj->pi_getFFvalue(t3lib_div::xml2array($row['flexform']), 'options');
		$options = t3lib_div::trimExplode("\n", $tmp_options, 1);
		foreach ((array) $options as $key => $value) {
			$split = t3lib_div::trimExplode('|', $value, 0);
			$arr[$key]['label'] = $split[0];
			if (isset($split[1])) {
				$arr[$key]['value'] = $split[1];
			}
			if (isset($split[2])) {
				$arr[$key]['selected'] = $split[2];
			}
		}
		return $arr;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_div.php']);
}

?>
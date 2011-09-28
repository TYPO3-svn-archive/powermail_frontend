<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alexander Kellner <alexander.kellner@einpraegsam.net>
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

class user_powermailfe_be_fields {

	var $limit = 10000; // limit for select query
	
	function main(&$params, &$pObj)	{

		$ffPiVars = t3lib_div::xml2array($params['row']['pi_flexform'], 'piVars'); // current xml to array
		if (!is_array($ffPiVars)) {
			$ffPiVars = utf8_encode(t3lib_div::xml2array($params['row']['pi_flexform'],'piVars'));
		}

		if ($params['config']['itemsProcFunc_config']['mode'] == 'fields') {
			$params['config']['itemsProcFunc_config']['mode'] = ($ffPiVars['data']['mainconfig']['lDEF']['fieldsmode']['vDEF'] == 'mails' || $ffPiVars['data']['mainconfig']['lDEF']['fieldsmode']['vDEF'] == '') ? 'mailFields' : 'formFields';
		}

		switch ($params['config']['itemsProcFunc_config']['mode']) {
			case 'mailFields':
			case 'fieldsAndOff':
			case 'fieldsAndOverall':
				$selectOptions = array();
				$tree = t3lib_div::makeInstance('t3lib_queryGenerator'); // make instance for query generator class
				// Get pid where to search for powermails
				$pid_array = explode('|', $params['row']['pages']); // extract starting point
				$pid = $tree->getTreeList(str_replace(array('pages_'), '', $pid_array[0]), $params['row']['recursive'], 0, 1); // get list of pages from starting point recursive
				// SQL query
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
					'tx_powermail_mails.piVars',
					'tx_powermail_mails',
					$where_clause = (intval($pid) > 0 ? 'pid IN (' . $pid . ')' : '1') . t3lib_BEfunc::BEenableFields('tx_powermail_mails'),
					$groupBy = '',
					$orderBy = 'tx_powermail_mails.uid DESC',
					$limit = $this->limit
				);

				if ($res !== false) { // If there is a result
					// 1. Collecting different field uids to an array
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
						$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // converting umlauts
						$array = t3lib_div::xml2array($row['piVars'], 'piVars'); // current xml to array
						if (!is_array($array)) $array = utf8_encode(t3lib_div::xml2array($row['piVars'],'piVars')); // current xml to array

						if (is_array($array) && isset($array)) { // if array esists
							foreach ($array as $key => $value) { // one loop for every value
								if (is_numeric(str_replace('uid', '', $key))) { // if field is like uid34
									if (!in_array($key, $selectOptions)) {
										$selectOptions[] = $key; // add key to list if key don't exist in list
									}
								}
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					// Sorting array
					sort($selectOptions);
				}

				// Generate flexform array
				if (is_array($selectOptions) && isset($selectOptions)) { // if array exists
					for ($i = 0; $i < count($selectOptions); $i++) { // one loop for every value

						// Manipulate options
						$params['items'][$i]['0'] = $this->getFieldTitle($selectOptions[$i]).' ('.$selectOptions[$i].')'; // Option name
						$params['items'][$i]['1'] = $selectOptions[$i]; // Option value

					}
				}
				break;

			case 'powermailforms':
				// SQL query
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
					'tx_powermail_title, tt_content.pid, tt_content.uid, pages.title',
					'tt_content LEFT JOIN pages ON pages.uid = tt_content.pid',
					$where_clause = "CType='powermail_pi1' AND pages.deleted = 0 AND tt_content.deleted = 0 AND sys_language_uid = 0",
					$groupBy = '',
					$orderBy = 'pages.sorting, tx_powermail_title ASC',
					$limit = $this->limit
				);

				if ($res !== false) { // If there is a result
					// 1. Collecting different field uids to an array
					$params['items'][0]['0'] = $pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.edit.select'); // Option name
					$params['items'][0]['1'] = 0; // Option value
					$i = 1;
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
						$params['items'][$i]['0'] = $row['title'] . ' [' . $row['pid'] . '] -&gt; ' . $row['tx_powermail_title'] . ' [' . $row['uid'] . ']'; // Option name
						$params['items'][$i]['1'] = $row['uid']; // Option value
						$i ++;
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
				break;

			case 'formFields':

				$powermailUid = intval($ffPiVars['data']['mainconfig']['lDEF']['powermailuid']['vDEF']);

				// SQL query
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
					'tx_powermail_fields.title, tx_powermail_fields.uid',
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
					$limit = $this->limit
				);

				if ($res !== false) { // If there is a result
					// 1. Collecting different field uids to an array
					$i = 0;
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
						$params['items'][$i]['0'] = $row['title'] . ' (uid' . $row['uid'] . ')'; // Option name
						$params['items'][$i]['1'] = 'uid' . $row['uid']; // Option value
						$i ++;
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}

				break;
		}

		switch ($params['config']['itemsProcFunc_config']['mode']) {
			case 'fieldsAndOff':
				 // Add '[deactivated]'
				array_unshift($params['items'], array($pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.empty'), '')); // add first param with text and no value
				break;

			case 'fieldsAndOverall':
				// Add '[search in all fields]'
				array_unshift($params['items'], array($pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.searchAll'), '_all')); // add first param with text and * as value
				break;
		}
	}
	
	
	/** Function getFieldTitle() get the title of a powermail field uid
	 * @param	int		$uid of field
	 * @return	string	Fieldname
	 */
	private function getFieldTitle($uid) {
		$ret = $uid;
		$uid = str_replace('uid', '', $uid); // uid23 to 23
		// SQL query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'title',
			'tx_powermail_fields',
			$where_clause = 'uid=' . $uid,
			$groupBy = '',
			$orderBy = '',
			$limit = 1
		);
		if ($res !== false) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			if ($row['title']) {
				$ret = $row['title'];
			}
		}
		return $ret;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/be/class.user_powermailfe_be_fields.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/be/class.user_powermailfe_be_fields.php']);
}

?>

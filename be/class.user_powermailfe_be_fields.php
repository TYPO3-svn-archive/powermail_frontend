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
		// config
		$newarray = array(); //init
		$tree = t3lib_div::makeInstance('t3lib_queryGenerator'); // make instance for query generator class
		
		// Get pid where to search for powermails
		$pid_array = explode('|', $params['row']['pages']); // preflight for startingpoint
		$pid = $tree->getTreeList(str_replace(array('pages_'), '', $pid_array[0]), $params['row']['recursive'], 0, 1); // get list of pages from starting point recursive
		
		// SQL query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'tx_powermail_mails.piVars',
			'tx_powermail_mails',
			$where_clause = (intval($pid) > 0 ? 'pid IN ('.$pid.')' : '1') . t3lib_BEfunc::BEenableFields('tx_powermail_mails'),
			$groupBy = '',
			$orderBy = 'tx_powermail_mails.uid DESC',
			$limit = $this->limit
		);
		if ($res) { // If there is a result
		
			// 1. Collecting different field uids to an array
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
				$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // converting umlauts
				$array = t3lib_div::xml2array($row['piVars'], 'piVars'); // current xml to array
				if (!is_array($array)) $array = utf8_encode(t3lib_div::xml2array($row['piVars'],'piVars')); // current xml to array
				
				if (is_array($array) && isset($array)) { // if array esists
					foreach ($array as $key => $value) { // one loop for every value
						if (is_numeric(str_replace('uid', '', $key))) { // if field is like uid34
							if (!in_array($key, $newarray)) {
								$newarray[] = $key; // add key to list if key don't exist in list
							}
						}
					}
				}
				
			}
			
			// 2. Sorting array
			sort($newarray);
			
			// 3. Return to backend
			if (is_array($newarray) && isset($newarray)) { // if array esists
				for ($i=0; $i < count($newarray); $i++) { // one loop for every value
					
					// Manipulate options
					$params['items'][$i]['0'] = $this->getTitle($newarray[$i]).' ('.$newarray[$i].')'; // Option name
					$params['items'][$i]['1'] = $newarray[$i]; // Option value
					
				}
			}
		}
		
		// 4. Add the first option if needed
		if ($params['config']['itemsProcFunc_config']['mode'] == 'fieldsAndOff') { // First param should be '[deactivated]'
			array_unshift($params['items'], array($pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.empty'), '')); // add first param with text and no value
		} elseif ($params['config']['itemsProcFunc_config']['mode'] == 'fieldsAndOverall') { // First param should be '[search in all fields]'
			array_unshift($params['items'], array($pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.searchAll'), '_all')); // add first param with text and * as value
		}
			
	}
	
	
	// Function getTitle() gets title for any uid
	function getTitle($uid) {
		$uid = str_replace('uid', '', $uid); // uid23 to 23
		
		// SQL query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'title',
			'tx_powermail_fields',
			$where_clause = 'uid = '.$uid,
			$groupBy = '',
			$orderBy = '',
			$limit = 1
		);
		if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
		if ($row['title']) return $row['title'];
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/be/class.user_powermailfe_be_fields.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/be/class.user_powermailfe_be_fields.php']);
}

?>

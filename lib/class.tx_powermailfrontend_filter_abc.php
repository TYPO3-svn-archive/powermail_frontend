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

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_dynamicmarkers.php'); // file for dynamicmarker functions
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_div.php'); // load div class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_sessions.php'); // load sessions class


class tx_powermailfrontend_filter_abc extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_pi1.php';	// Path to this script relative to the extension dir.
	var $mode = 'abc'; // current mode
	var $filter;
	
	function main($conf, $piVars, $cObj, $query_pid = '', $query_cat = '') {
		
		// Config
		$this->conf = $conf;
		$this->cObj = $cObj;
		$this->piVars = $piVars;
		$this->query_cat = $query_cat;
		$this->query_pid = $query_pid;
		$this->pi_loadLL();
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class
		$this->tmpl = $this->markerArray = array(); $this->filter = ''; // init
		$this->tmpl['filter'][$this->mode] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['search']),'###POWERMAILFE_FILTER_ABC###'); // Load HTML Template
		$this->sessions = t3lib_div::makeInstance('tx_powermailfrontend_sessions'); // New object: session functions
		if (!empty($this->piVars['filter'])) {
			if(!empty($this->piVars['filter']['reset'])) {
				$this->sessions->deleteSession($this->conf, $this->cObj);
				unset($this->piVars['filter']['reset']);
				foreach ($this->piVars['filter'] as $filterKey => $filterValue) {
					$this->piVars['filter'][$filterKey] = '';
				}
			}
		} else {
			$this->piVars['filter'] = $this->sessions->getSession($this->conf, $this->cObj);
		}

		// let's go
		if ($this->conf['search.']['abc'] != '') { // if abc should be shown
			$this->markerArray['###POWERMAILFE_ABC_ALL###'] = $this->show_all(); // Link all
			$this->markerArray['###POWERMAILFE_ABC_ABC###'] = $this->show_abc(); // Link abc
			$this->markerArray['###POWERMAILFE_ABC_0-9###'] = $this->show_numbers(); // Link numbers
			
			$this->hook_pmfe_abc(); // hook
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['filter'][$this->mode], $this->markerArray); // substitute Marker in Template
			$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
			$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		}
		
		if (!empty($this->content)) return $this->content;
		
    }
	
	
	// Function show_all() generates link same page without piVars
	function show_all() {
		$content = '<span class="powermailfe_abc_letter_all' . ($this->piVars['list']['all'] || count($this->piVars) == 0 ? ' powermailfe_abc_letter_all_act' : '') . '">';
		$allLink = $this->pi_linkTP_keepPIvars($this->pi_getLL('powermailfe_ll_abclist_all', 'All'), array('filter' => array($this->conf['search.']['abc'] => '*')), 1, 1);
		if ($this->piVars['filter'][$this->conf['search.']['abc']] == '*' || empty($this->piVars['filter'][$this->conf['search.']['abc']])) {
			$allLink =  '<span class="powermailfe_abc_act">' . $allLink . '</span>';
		}
		$content .= $allLink;
		$content .= '</span>';

		if (!empty($content)) return $content;
	}
	
	
	// Function show_abc() to generate ABC list
	function show_abc() {
		$content = ''; // init
		$this->vararray = $curLetter = array();
		
			
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'tx_powermail_mails.piVars, uid',
			'tx_powermail_mails',
			$where_clause = $this->div->addWhereClause($this),
			$groupBy = 'tx_powermail_mails.uid',
			$orderBy = '',
			$limit = $this->conf['query.']['limit']
		);
		if ($res) { // If there is a result
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // One loop for every tx_powermail_mails entry
				$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // rename not allowed signs
				$this->piVars_array = t3lib_div::xml2array($row['piVars'], 'pivars'); // xml to array
				if (!is_array($this->piVars_array)) $this->piVars_array = utf8_encode(t3lib_div::xml2array($row['piVars'], 'pivars')); // xml to array
				
				$this->vararray[] = $this->piVars_array; // add current array
			}
		}
		
		for ($a=A; $a != AA; $a++) { // ABC loop
			$curLetter[$a] = 0; // default: for current letter no entry
			for ($i=0; $i<count($this->vararray); $i++) { // one loop for every mail
				foreach ($this->vararray[$i] as $key => $value) { // one loop for every var in current mail
					if ($this->conf['search.']['abc'] == $key) { // only if current key is selected in backend
						if (strtolower($this->vararray[$i][$key][0]) == strtolower($a)) $curLetter[$a] = 1; // entry for current letter
					}
				}
			}
			
			// Generate Return string
			$content .= '<span class="powermailfe_abc_letter">';
			if ($curLetter[$a]) { // If result (link with letter)
				$letterLink = $this->pi_linkTP_keepPIvars($a, array('filter' => array($this->conf['search.']['abc'] => htmlentities(strtolower($a)) )), 1, 1); // Generate link for each sign
				if ($this->piVars['filter'][$this->conf['search.']['abc']] == htmlentities(strtolower($a))) {
					$letterLink =  '<span class="powermailfe_abc_act">' . $letterLink . '</span>';
				}
				$content .= $letterLink;
			} else { // no result: letter only link
				$content .= $a; 
			} 
			$content .= '</span>'."\n";
		}
		
		if (!empty($content)) return $content;
	}
	
	
	// Function show_numbers() to generate numbers link
	function show_numbers() {
		$content = $curLetter = ''; // init
		$this->vararray = array(); // init
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'tx_powermail_mails.piVars, uid',
			'tx_powermail_mails',
			$where_clause = $this->div->addWhereClause($this),
			$groupBy = 'tx_powermail_mails.uid',
			$orderBy = '',
			$limit = $this->conf['query.']['limit']
		);
		if ($res) { // If there is a result
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // One loop for every tx_powermail_mails entry
				$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // rename not allowed signs
				$this->piVars_array = t3lib_div::xml2array($row['piVars'], 'pivars'); // xml to array
				if (!is_array($this->piVars_array)) $this->piVars_array = utf8_encode(t3lib_div::xml2array($row['piVars'], 'pivars')); // xml to array
				
				$this->vararray[] = $this->piVars_array; // add current array
			}
		}
		
		for ($a=0; $a < 10; $a++) { // ABC loop
			$curLetter = 0; // default: for current letter no entry
			for ($i=0; $i<count($this->vararray); $i++) { // one loop for every mail
				foreach ($this->vararray[$i] as $key => $value) { // one loop for every var in current mail
					if ($this->conf['search.']['abc'] == $key) { // only if current key is selected in backend
						if (strtolower($this->vararray[$i][$key][0]) == strtolower($a)) $curLetter = 1; // entry for current letter
					}
				}
			}
		}
			
		// Generate Return string
		$content .= '<span class="powermailfe_abc_letter_09">';
		if ($curLetter) { // If result (link with letter)
			$numberLink = $this->pi_linkTP_keepPIvars ($this->pi_getLL('powermailfe_ll_abclist_numbers', '0-9'), array('filter' => array($this->conf['search.']['abc'] => "@" )), 1, 1); // Generate link for 0-9
			if ($this->piVars['filter'][$this->conf['search.']['abc']] == '@') {
				$numberLink =  '<span class="powermailfe_abc_act">' . $numberLink . '</span>';
			}
			$content .= $numberLink;
		} else { // no result: letter only link
			$content .= $this->pi_getLL('powermailfe_ll_abclist_numbers', '0-9')."\n"; 
		} 
		$content .= '</span>'."\n";
		
		if (!empty($content)) return $content;
	}
	

	// Function hook_pmfe_abc() to change the main content 1
	function hook_pmfe_abc() {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_abc($this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	
	
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_filter_abc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_filter_abc.php']);
}

?>
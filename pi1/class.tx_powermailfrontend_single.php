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
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_markers.php'); // load marker class
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_dynamicmarkers.php'); // file for dynamicmarker functions
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_div.php'); // load div class

class tx_powermailfrontend_single extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_single.php';	// Path to this script relative to the extension dir.
	var $mode = 'single'; // current mode
	
	/**
	* Generate single view
	*
	* @param 	array 	$conf			TS and Flexform array
	* @param 	array 	$piVars			$this->piVars
	* @param 	array 	$cObj			Content Object
	* @return 	string	$content		Returns string with single view
	*/
	function main($conf, $piVars, $cObj) {
		// Config
    	$this->cObj = $cObj; // cObject
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->tmpl = $this->wrappedSubpartArray = array();
		$this->allowedfields = t3lib_div::trimExplode(',', $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'fields', $this->mode), 1); // get allowed fields from flexform
		$this->markers = t3lib_div::makeInstance('tx_powermailfrontend_markers'); // Create new instance for markers class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->tmpl[$this->mode]['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###POWERMAILFE_SINGLE###'); // Load HTML Template
		
		if ($this->piVars['show'] > 0 && empty($this->piVars['edit'])) { // only if GET param show was set
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
				'uid, piVars, crdate, recipient, subject_r, sender, senderIP',
				'tx_powermail_mails',
				$where_clause = 'tx_powermail_mails.uid = '.$this->piVars['show'].$this->cObj->enableFields('tx_powermail_mails'),
				$groupBy = '',
				$orderBy = '',
				$limit = 1
			);
			if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); // Result in array
			$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // rename not allowed signs
			$this->vars = t3lib_div::xml2array($row['piVars'], 'pivars'); // xml to array
			if (!is_array($this->vars)) $this->vars = utf8_encode(t3lib_div::xml2array($row['piVars'], 'pivars')); // xml to array
			unset($row['piVars']); // delete piVars from row array
			$this->vars = array_merge((array) $this->vars, $row); // set array
			$this->hook_pmfe_single($this->vars); // hook for array manipulation
					
			$this->markerArray = $this->markers->makeMarkers($this->conf, $this->vars, $this->piVars, $this->cObj, $this->mode); // markerArray fill
			$this->wrappedSubpartArray['###POWERMAILFE_SPECIAL_LISTLINK###'] = $this->cObj->typolinkWrap( array('parameter' => ($this->conf['list.']['pid'] > 0 ? $this->conf['list.']['pid'] : $GLOBALS['TSFE']->id), 'useCacheHash' => 1) ); // List Link
			
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->markerArray, array(), $this->wrappedSubpartArray); // Get html template
			$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
			if (!empty($this->content)) return $this->content; // return HTML
		}
		
    }
	

	// Function hook_pmfe_single() to change the main content 1
	function hook_pmfe_single(&$rowArray) {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_single($rowArray, $this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_single.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_single.php']);
}

?>
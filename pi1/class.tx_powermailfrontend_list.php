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

class tx_powermailfrontend_list extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_list.php';	// Path to this script relative to the extension dir.
	
	function main($conf, $piVars, $cObj) {
		// Config
    	$this->cObj = $cObj; // cObject
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->content = ''; $content_item = ''; // init
		$this->markers = t3lib_div::makeInstance('tx_powermailfrontend_markers'); // Create new instance for markers class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->allowedfields = t3lib_div::trimExplode(',',$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'fields', 'mainconfig'),1); // get allowed fields from flexform
		$this->tmpl['list']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['list']),'###POWERMAILFE_LIST###'); // Load HTML Template
		$this->tmpl['list']['item'] = $this->cObj->getSubpart($this->tmpl['list']['all'],'###ITEM###'); // work on subpart 2
		
		// Let's go
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'piVars',
			'tx_powermail_mails',
			$where_clause = 'pid IN ('.$this->pi_getPidList($this->cObj->data['pages'], $this->cObj->data['recursive']).')'.$this->cObj->enableFields('tx_powermail_mails'),
			$groupBy = '',
			$orderBy = $this->conf['list.']['orderby'],
			$limit = intval($this->conf['list.']['limit'])
		);
		if ($res) { // If there is a result
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // One loop for every tx_powermail_mails entry
				$this->markerArray = $this->markers->makeMarkers($this->conf, $row, $this->allowedfields, $this->piVars, $this->cObj); // markerArray fill
				$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['list']['item'], $this->markerArray);
			}
		}
		$subpartArray['###CONTENT###'] = $content_item;
		
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['list']['all'], array(), $subpartArray); // Get html template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		if (!empty($this->content)) return $this->content; // return HTML
		
    }
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_list.php']);
}

?>
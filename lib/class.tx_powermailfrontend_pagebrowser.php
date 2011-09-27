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
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_div.php'); // file for dynamicmarker functions


class tx_powermailfrontend_pagebrowser extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_pi1.php';	// Path to any pi1 script for locallang
	
	function main($conf, $piVars, $cObj, $pbarray) {

		if ($pbarray['numberOfMails'] == 0) {
			return '';
		}

		// Config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pbarray = $pbarray;
		$this->markerArray = array();
		$this->tmpl = array ('pagebrowser' => $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['pagebrowser']), '###POWERMAILFRONTEND_PAGEBROWSER###')); // Load HTML Template for pagebrowser
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class

		$this->markerArray['###CURRENT_MIN###'] = ($this->pbarray['pointer'] * $this->conf['list.']['perPage']) + 1; // Current page: From
		$this->markerArray['###CURRENT_MAX###'] = ($this->pbarray['pointer'] * $this->conf['list.']['perPage']) + $this->pbarray['numberOfMails_cur']; // Current page: up to
		$this->markerArray['###OVERALL###'] = $this->pbarray['numberOfMails']; // Overall addresses

		$this->markerArray['###POWERMAILFE_EXPORTCSVLINKTEXT###'] = $this->pi_getLL('powermailfe_ll_export_csv_label', 'Export items as CSV'); // edit label
		$this->wrappedSubpartArray['###POWERMAILFE_EXPORTCSVLINK###'] = array('<a href="/index.php?eID=tx_powermailfrontend_export&format=csv&uid=' . $this->cObj->data['uid'] . '">', '</a>'); // Export CSV Link
		$this->markerArray['###POWERMAILFE_EXPORTEXCELLINKTEXT###'] = $this->pi_getLL('powermailfe_ll_export_excel_label', 'Export items as EXCEL'); // edit label
		$this->wrappedSubpartArray['###POWERMAILFE_EXPORTEXCELLINK###'] = array('<a href="/index.php?eID=tx_powermailfrontend_export&format=xls&uid=' . $this->cObj->data['uid'] . '">', '</a>'); // Export Excel Link

		if ($this->conf['list.']['perPage'] < $this->pbarray['numberOfMails']) {
			if (t3lib_extMgm::isLoaded('pagebrowse', 0)) {
				$numberOfPages = intval($this->pbarray['numberOfMails'] / $this->conf['list.']['perPage']) + (($this->pbarray['numberOfMails'] % $this->conf['list.']['perPage']) == 0 ? 0 : 1);
				$pagebrowseConf = array (
					'includeLibs' => 'EXT:pagebrowse/pi1/class.tx_pagebrowse_pi1.php',
					'userFunc' => 'tx_pagebrowse_pi1->main',
					'numberOfPages' => $numberOfPages,
					'pageParameterName'  => $this->prefixId . '|pointer',
					'templateFile' => $this->conf['pagebrowse.']['templateFile'],
					'pagesBefore' => $this->conf['pagebrowse.']['pagesBefore'],
					'pagesAfter' => $this->conf['pagebrowse.']['pagesAfter'],
					'enableMorePages' => $this->conf['pagebrowse.']['enableMorePages'],
					'enableLessPages' => $this->conf['pagebrowse.']['enableLessPages'],
					'numberOfLinks' => $this->conf['pagebrowse.']['numberOfLinks']
				);
				$pagebrowseCObj = t3lib_div::makeInstance('tslib_cObj');
				$pagebrowseCObj->start(array(), '');
				$this->markerArray['###PAGELINKS###'] = $pagebrowseCObj->cObjGetSingle('USER', $pagebrowseConf);
			} else {
				$this->conf['pagebrowser.']['special.']['userFunc.'] = $this->pbarray; // config for pagebrowser userfunc
				$this->markerArray['###PAGELINKS###'] = $this->cObj->cObjGetSingle($this->conf['pagebrowser'], $this->conf['pagebrowser.']);
			}
		}
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['pagebrowser'], $this->markerArray, array(), $this->wrappedSubpartArray); // substitute Marker in Template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i","",$this->content); // Finally clear not filled markers
		return $this->content;
    }
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_pagebrowser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_pagebrowser.php']);
}

?>
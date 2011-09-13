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
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_dynamicmarkers.php'); // file for dynamicmarker functions
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_div.php'); // load div class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_filter_abc.php'); // load abc class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_filter_search.php'); // load searchfilter class

class tx_powermailfrontend_search extends tslib_pibase {

	public $extKey = 'powermail_frontend'; // Extension key
	public $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_powermailfrontend_single.php';	// Path to this script relative to the extension dir.
	public $mode = 'search'; // current mode
	
	/**
	 * Main function for the searchfields
	 *
	 * @param	array		TypoScript configuration
	 * @param	array		piVars
	 * @param	object		Content object
	 * @return	string		content
	 */
	public function main($conf, $piVars, $cObj) {
		// Config
    	$this->cObj = $cObj; // cObject
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->tmpl = $this->markerArray = array();
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->abc = t3lib_div::makeInstance('tx_powermailfrontend_filter_abc'); // New object: TYPO3 abc function
		$this->search = t3lib_div::makeInstance('tx_powermailfrontend_filter_search'); // New object: TYPO3 searchfilter function
		$this->tmpl[$this->mode]['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###POWERMAILFE_FILTER###'); // Load HTML Template
		
		// Let's go
		if (!$this->piVars['show'] && !$this->piVars['edit']) { // only if detaillink is not chosen
			if ($this->conf['search.']['abc']) {
				$this->markerArray['###POWERMAILFE_ABC###'] = $this->abc->main($this->conf, $this->piVars, $this->cObj); // ABC result
			}
			if ($this->conf['search.']['search']) {
				$this->markerArray['###POWERMAILFE_SEARCH###'] = $this->search->main($this->conf, $this->piVars, $this->cObj); // Add some search fields
			}
			
			$this->hook_pmfe_search();
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->markerArray); // Get html template
			$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
			if (!empty($this->content)) {
				return $this->content; // return HTML
			}
		}
		
    }
	
	/**
	 * Function hook_pmfe_search() to change the main content 1
	 *
	 * @return	void
	 */
	private function hook_pmfe_search() {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_search($this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_search.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_search.php']);
}

?>
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
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_markers.php'); // load marker class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_dynamicmarkers.php'); // file for dynamicmarker functions
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_div.php'); // load div class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_pagebrowser.php'); // load pagebrowser class

class tx_powermailfrontend_list extends tslib_pibase {

	public $extKey = 'powermail_frontend'; // Extension key
	public $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_powermailfrontend_list.php';	// Path to this script relative to the extension dir.
	public $mode = 'list'; // if mode is not set, take list view
	
	/**
	* Generate list and latest view
	*
	* @param 	array 	$conf			TS and Flexform array
	* @param 	array 	$piVars			$this->piVars
	* @param 	array 	$cObj			Content Object
	* @return 	string	$content		Returns string with list view
	*/
	public function main($conf, $piVars, $cObj) {
		// Config
    	$this->cObj = $cObj; // cObject
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->outerMarkerArray = $this->wrappedSubpartArray = $this->wrappedSubpartArrayOuter = $rowArray = $piVars_array = array(); 
		$this->content = $content_item = $row = $res = '';
		$this->markers = t3lib_div::makeInstance('tx_powermailfrontend_markers'); // Create new instance for markers class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class
		$this->pagebrowser = t3lib_div::makeInstance('tx_powermailfrontend_pagebrowser'); // Create new instance for pagebrowser class
		$this->tmpl[$this->mode]['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]),'###POWERMAILFE_' . strtoupper($this->mode) . '###'); // Load HTML Template
		$this->tmpl[$this->mode]['item'] = $this->cObj->getSubpart($this->tmpl[$this->mode]['all'], '###ITEM_' . strtoupper($this->mode) . '###'); // work on subpart 2
		
		// Let's go
		$this->del = $this->div->delEntry($this); // delete entry if needed
		if (empty($this->piVars['show']) && empty($this->piVars['edit'])) { // don't show list in single mode
				
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
				$this->where['select'] = 'uid, piVars, crdate, recipient, subject_r, sender, senderIP, uploadPath',
				$this->where['from'] = 'tx_powermail_mails',
				$this->where['where'] = $this->div->addWhereClause($this),
				$this->where['groupby'] = '',
				$this->where['orderby'] = $this->conf['query.']['orderby'],
				$this->where['limit'] = $this->conf['query.']['limit']
			);
			
			// 1. write xml values to an array
			if ($res) { // If there is a result
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // One loop for every tx_powermail_mails entry
					$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // rename not allowed signs
					$piVars_array = t3lib_div::xml2array($row['piVars'], 'pivars'); // xml to array
					if (!is_array($piVars_array)) $piVars_array = utf8_encode(t3lib_div::xml2array($row['piVars'], 'pivars')); // xml to array
					unset($row['piVars']); // delete piVars from row array
					$rowArray[] = array_merge((array) $piVars_array, (array) $row); // set array
				}
			}
			// 2. sort array and split array in parts for pagebrowser
			$rowArray = $this->div->sortArray($rowArray, $this->conf, $this->mode, $this->piVars); // sort and filter array as wanted (e.g. settings via constants)
			$this->overall = count($rowArray); // overall numbers of items
			$rowArray = array_chunk($rowArray, $this->conf[$this->mode . '.']['perPage']); // split array in parts for pagebrowser
			$this->hook_pmfe_list($rowArray); // hook for array manipulation
			
			// 3. substitute markerArray in loop
			for ($i = 0; $i < count($rowArray[($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0)]); $i++) { // One loop for every tx_powermail_mails entry
				$this->markerArray = $this->markers->makeMarkers($this->conf, $rowArray[($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0)][$i], $this->piVars, $this->cObj, $this->mode); // markerArray fill
				$this->markerArray['###ALTERNATE###'] = ($this->div->alternate($i) ? 'odd' : 'even'); // odd or even
				$this->markerArray['###UID###'] = $rowArray[($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0)][$i]['uid']; // UID of current mail

				if ( // min one feuser or min one group should be enabled AND current entry is allowed to be edited from current user
					(!empty($conf['edit.']['feuser']) || !empty($conf['edit.']['fegroup'])) && 
					!$this->div->allowed($rowArray[($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0)][$i]['uid'], $this->conf)
				) 
				{
					$this->markerArray['###POWERMAILFE_EDITLINKTEXT###'] = $this->pi_getLL('edit_label', 'Edit entry'); // edit label
					$this->wrappedSubpartArray['###POWERMAILFE_SPECIAL_EDITLINK###'] = $this->cObj->typolinkWrap( array('parameter' => ($this->conf['edit.']['pid'] > 0 ? $this->conf['edit.']['pid'] : $GLOBALS['TSFE']->id), 'additionalParams' => '&' . $this->prefixId . '[edit]=' . $rowArray[($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0)][$i]['uid'], 'useCacheHash' => 1) ); // Edit Link
				} else { // Edit is not allowed
					$this->markerArray['###POWERMAILFE_EDITLINKTEXT###'] = ''; // clear label
					$this->wrappedSubpartArray['###POWERMAILFE_SPECIAL_EDITLINK###'] = array(); // clear typolinkwrap
				}
				
				$this->wrappedSubpartArray['###POWERMAILFE_SPECIAL_DETAILLINK###'] = $this->cObj->typolinkWrap( array('parameter' => ($this->conf['single.']['pid'] > 0 ? $this->conf['single.']['pid'] : $GLOBALS['TSFE']->id), 'additionalParams' => '&' . $this->prefixId . '[show]=' . $rowArray[($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0)][$i]['uid'] . ($this->piVars['pointer'] > 0 ? '&' .$this->prefixId . '[pointer]=' . $this->piVars['pointer'] : ''), 'useCacheHash' => 1) ); // Detail Link
				
				$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['item'], $this->markerArray, array(), $this->wrappedSubpartArray);
			}
			
			$subpartArray['###CONTENT_' . strtoupper($this->mode) . '###'] = $content_item;
			$this->num = $i; // whole numbers of entries in current page
			$this->additionalMarker(); // fill additional markers
			
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->outerMarkerArray, $subpartArray, $this->wrappedSubpartArrayOuter); // Get html template
			$this->content = $this->div->sortLink($this->content, $this->conf, $this->cObj, $this->piVars); // substitute Sort markers
			$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
			if (!empty($this->content)) return $this->content; // return HTML
		}
		
    }
	
	
	/**
	* Function additionalMarker() to add some more markers
	*
	* @return 	void
	*/
	private function additionalMarker() {
		$this->outerMarkerArray['###POWERMAILFE_PAGEBROWSER###'] = $this->pagebrowser->main($this->conf, $this->piVars, $this->cObj, array('overall' => $this->overall, 'overall_cur' => $this->num, 'pointer' => ($this->piVars['pointer'] > 0 ? $this->piVars['pointer'] : 0), 'perPage' => $this->conf[$this->mode . '.']['perPage'])); // Show pagebrowser
		if ($this->del) { // if something was deleted and only then
			$this->outerMarkerArray['###POWERMAILFE_DELETEMSG###'] = $this->cObj->cObjGetSingle($this->conf['edit.']['deletemsg'], $this->conf['edit.']['deletemsg.']); // show delete message
		}
	}
	
	
	/**
	* Add a hook to change output of list or latest view
	*
	* @return 	void
	*/
	private function hook_pmfe_list(&$rowArray) {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_list($rowArray, $this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_list.php']);
}

?>
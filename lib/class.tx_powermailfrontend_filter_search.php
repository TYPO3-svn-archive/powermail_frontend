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
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_sessions.php'); // load sessions class

class tx_powermailfrontend_filter_search extends tslib_pibase {

	public $extKey = 'powermail_frontend'; // Extension key
	public $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_powermailfrontend_pi1.php';	// Path to this script relative to the extension dir.
	public $mode = 'searchfilter'; // current mode
	
	/**
	 * Main function for a default searchfields
	 *
	 * @param	array		TypoScript configuration
	 * @param	array		piVars
	 * @param	object		Content object
	 * @param	string		Query pid
	 * @param	string		Query cat
	 * @return	string		content
	 */
	public function main($conf, $piVars, $cObj, $query_pid = '', $query_cat = '') {
		// Config
		$this->conf = $conf;
		$this->cObj = $cObj;
		$this->piVars = $piVars;
		$this->query_cat = $query_cat;
		$this->query_pid = $query_pid;
		$this->pi_loadLL();
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class
		$this->tmpl = $this->outerArray = $this->subpartArray = $this->fieldMarkerArray = $this->innerFieldMarkerArray = $this->subpartArray2 = array();
		$content_item = $this->filter = ''; // init
		$this->tmpl['filter'][$this->mode] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['search']), '###POWERMAILFE_FILTER_SEARCH###'); // Load HTML Template
		$this->tmpl['filter']['item'] = $this->cObj->getSubpart($this->tmpl['filter'][$this->mode], '###ITEM###'); // work on subpart 1
		$this->searchfields = t3lib_div::trimExplode(',', $this->conf['search.']['search'], 1); // array with all needed search fields
		$this->sessions = t3lib_div::makeInstance('tx_powermailfrontend_sessions'); // New object: session functions

		$this->filter = $this->piVars['filter'];
		if (!empty($this->filter)) {
			// a new filter value was added
			if(empty($this->filter['reset'])) {
				$this->sessions->setSession($this->conf, $this->piVars, $this->cObj, false);
			} else {
				// filter reset
				$this->sessions->deleteSession($this->conf, $this->cObj);
				unset($this->filter);
			}
		} else {
			$this->filter = $this->sessions->getSession($this->conf, $this->cObj, 'filter');
		}

		// let's go
		if (count($this->searchfields) > 0) { // if there should min. 1 searchfield be added
			$this->outerArray['###POWERMAILFE_SEARCH_ACTION###'] = htmlentities($this->pi_linkTP_keepPIvars_url(array(), 1, 1)); // target url for form
			
			for ($i = 0; $i < count($this->searchfields); $i++) { // one loop for every needed field
				if ($this->searchfields[$i] != '_all') { // search a field
					$this->markerArray['###POWERMAILFE_SEARCH_LABEL###'] = $this->pi_getLL('label_' . $this->searchfields[$i], $this->div->getTitle($this->searchfields[$i]));
					$this->fieldMarkerArray['###POWERMAILFE_SEARCH_NAME###'] = $this->searchfields[$i]; // uid4
				} else { // search all fields
					$this->markerArray['###POWERMAILFE_SEARCH_LABEL###'] = $this->pi_getLL('label_searchallfields', 'Search in all fields'); // label for search in all fields
					$this->fieldMarkerArray['###POWERMAILFE_SEARCH_NAME###'] = '_all'; // search in all fields
				}
				if (!empty($this->filter[$this->searchfields[$i]])) {
					$this->fieldMarkerArray['###POWERMAILFE_SEARCH_VALUE###'] = $this->filter[$this->searchfields[$i]]; // value
				} else {
					$this->fieldMarkerArray['###POWERMAILFE_SEARCH_VALUE###'] = '';
				}
				$this->markerArray['###POWERMAILFE_SEARCH_NAME###'] = $this->fieldMarkerArray['###POWERMAILFE_SEARCH_NAME###'];
				
				$type = $this->div->getFieldTypeFromUid($this->searchfields[$i]); // get type
				$this->tmpl['filter']['field'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['search']), '###POWERMAILFE_FILTER_SEARCH_FIELD_' . strtoupper($type) . '###');
				if ($type == 'select') {
					$options = $this->div->getFieldOptionsFromFieldUid($this->searchfields[$i], $this); // get options
					$this->tmpl['filter']['field_item'] = $this->cObj->getSubpart($this->tmpl['filter']['field'], '###ITEM###'); // work on subpart 1
					$content_item2 = '';
					foreach ((array) $options as $values) {
						$this->innerFieldMarkerArray['###POWERMAILFE_SEARCH_LABEL###'] = $values['label'];
						if (isset($values['value'])) {
							$this->innerFieldMarkerArray['###POWERMAILFE_SEARCH_VALUE###'] = $values['value'];
						} else {
							$this->innerFieldMarkerArray['###POWERMAILFE_SEARCH_VALUE###'] = $values['label'];
						}
						if (
							($this->filter[$this->searchfields[$i]] == $values['value'] || $this->filter[$this->searchfields[$i]] == $values['label'])
							&& $this->filter[$this->searchfields[$i]] != ''
						) {
							$this->innerFieldMarkerArray['###POWERMAILFE_SEARCH_SELECTED###'] = ' selected="selected"';
						} elseif (isset($values['selected']) && !isset($this->filter[$this->searchfields[$i]])) {
							$this->innerFieldMarkerArray['###POWERMAILFE_SEARCH_SELECTED###'] = ' selected="selected"';
						} else {
							$this->innerFieldMarkerArray['###POWERMAILFE_SEARCH_SELECTED###'] = '';
						}
						$content_item2 .= $this->cObj->substituteMarkerArrayCached($this->tmpl['filter']['field_item'], $this->innerFieldMarkerArray);
					}
					$this->subpartArray2['###CONTENT###'] = $content_item2;
				}
				$this->markerArray['###FIELD###'] = $this->cObj->substituteMarkerArrayCached($this->tmpl['filter']['field'], $this->fieldMarkerArray, $this->subpartArray2);
				
				$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['filter']['item'], $this->markerArray); // add all markers of this loop to the variable
			}
			$this->subpartArray['###CONTENT###'] = $content_item; // work on subpart 2
			
			$this->hook_pmfe_searchfilter(); // hook

			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['filter'][$this->mode], $this->outerArray, $this->subpartArray); // substitute Marker in Template
			$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
			$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
			
			if (!empty($this->content)) return $this->content;
		}
		
		
    }
	
	/**
	 * Function hook_pmfe_abc() to change the main content 1
	 *
	 * @return	void
	 */
	private function hook_pmfe_searchfilter() {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_searchfilter($this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_filter_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_filter_search.php']);
}

?>
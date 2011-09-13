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
require_once(t3lib_extMgm::extPath('powermail_frontend').'lib/class.tx_powermailfrontend_div.php'); // load div class

class tx_powermailfrontend_markers extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1'; // Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_list.php';	// Path to any script in pi1 for locallang
	
	
	// Function makeMarkers() makes markers from row (uid => ###WTDIRECTORY_UID###)
	// $what should contains 'detail' or 'list' to load the right html template
	// $conf contains TYPO3 conf array
	// $row contains db values as an array
	// $allowedArray contains allowed fields (from flexform)
	function makeMarkers($conf, $row, $allowedArray = array(), $piVars, $cObj) {
	
		// config
		global $TSFE;
    	$this->cObj = $TSFE->cObj; // cObject
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->conf = $conf;
		$this->piVars = $piVars;
		$markerArray = array(); $this->tmpl = array();
		$this->tmpl['all']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($conf['template.']['all']),'###POWERMAILFE_ALL###'); // Load HTML Template: ALL (works on subpart ###POWERMAILFE_ALL###)
		$this->tmpl['all']['item'] = $this->cObj->getSubpart($this->tmpl['all']['all'],"###ITEM###"); // Load HTML Template: ALL (works on subpart ###ITEM###)
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class
		
		// Let's go and get the xml from the db
		$piVars_array = t3lib_div::xml2array($row['piVars'],'pivars'); // xml to array
		if (!is_array($piVars_array)) $piVars_array = utf8_encode(t3lib_div::xml2array($row['piVars'],'pivars')); // xml to array
		
		// 1. Fill automatic markers
		if (is_array($allowedArray) && isset($allowedArray)) { // if allowedarray is set
			foreach ($allowedArray as $key => $value) { // one loop for every value to show
				
				if (!is_array($piVars_array[$value])) { // first level (e.g. textfields)
					$markerArrayAll = array(); // clear array at the beginning
					if (($this->conf['emptylabels.']['hide'] == 1 && $piVars_array[$value] != '') || $this->conf['emptylabels.']['hide'] != 1) $markerArrayAll['###POWERMAILFE_LABEL###'] .= $this->div->getTitle($value); // add label
					$markerArrayAll['###POWERMAILFE_VALUE###'] .= $piVars_array[$value]; // add value
					$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['all']['item'], $markerArrayAll); // Add
				
				} else { // second level (checkboxes, multiple selector fields)
					if (isset($piVars_array[$value])) {
						foreach ($piVars_array[$value] as $key2 => $value2) { // one loop for every value to show
							$markerArrayAll = array(); // clear array at the beginning
							if (($this->conf['emptylabels.']['hide'] == 1 && $value2 != '') || $this->conf['emptylabels.']['hide'] != 1) $markerArrayAll['###POWERMAILFE_LABEL###'] .= $this->div->getTitle($value); // add label
							$markerArrayAll['###POWERMAILFE_VALUE###'] .= $value2; // add value
							$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['all']['item'], $markerArrayAll); // Add
						}
					}
				}
			}
			$subpartArray['###CONTENT###'] = $content_item; // ###POWERMAILFE_ALL###
			$markerArray['###POWERMAILFE_ALL###'] = $this->cObj->substituteMarkerArrayCached($this->tmpl['all']['all'], array(), $subpartArray); // Fill ###POWERMAILFE_ALL###
		}
		
		// 2. Fill individual markers (like ###UID33### and ###LABEL_UID33###)
		if(!empty($piVars_array) && is_array($piVars_array)) { // If array from xml is set
			foreach ($piVars_array as $key => $value) { // one loop for every field in xml
				// values (first and second level)
				if (!is_array($value)) { // first level
					if (is_numeric(str_replace('uid','',$key))) $markerArray['###'.strtoupper($key).'###'] = $value; // uid3 => ###UID3###
				}
				elseif (is_array($value) && isset($value)) { // second level (checkboxes maybe)
					$i=1; // init
					foreach ($value as $key2 => $value2) { // one loop for every e.g. checkbox in second level
						$markerArray['###'.strtoupper($key).'###'] .= $value2.', '; // complete checkbox fields to one marker
						$markerArray['###'.strtoupper($key).'_'.$i.'###'] = $value2; // uid3_1 => ###UID3_1###
						$i++; // increase counter
					}
				}
				
				// label
				if (is_numeric(str_replace('uid','',$key))) $markerArray['###LABEL_'.strtoupper($key).'###'] = $this->div->getTitle($key); // uid3 => ###UID3###
			}
		}
		
		//print_r($markerArray);
		if(!empty($markerArray)) return $markerArray;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_markers.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_markers.php']);
}

?>

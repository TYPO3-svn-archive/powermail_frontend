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
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/class.tx_powermailfrontend_div.php'); // load div class

class tx_powermailfrontend_markers extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1'; // Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_list.php';	// Path to any script in pi1 for locallang

	
	/**
	* Generate markerArray for list or single view
	*
	* @param 	array 	$conf			TS and Flexform array
	* @param 	array 	$piVars_array	Array from XML to current mail
	* @param 	array 	$piVars			$this->piVars
	* @param 	array 	$cObj			Content Object
	* @param 	string 	$what			Mode could be 'list' or 'single'
	* @return 	array	$markerArray	Returns array with markers array('###UID33###' => 'Alex')
	*/
	function makeMarkers($conf, $piVars_array, $piVars, $cObj, $what = 'list') {
	
		// config
		global $TSFE;
        $this->cObj = $TSFE->cObj; // local cObject
		//$this->cObj = $cObj;
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class
		$this->markerArray = array();
		$this->tmpl = array();
		$i = 0;
		$content_item = '';
		$this->allowedArray = t3lib_div::trimExplode(',', $this->conf[$what . '.']['fields'], 1);
		$this->allowedArray = $this->div->addFieldUidsFromOtherLanguages($this->allowedArray);

		$this->tmpl['all']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($conf['template.']['all']), '###POWERMAILFE_ALL###'); // Load HTML Template: ALL (works on subpart ###POWERMAILFE_ALL###)
		$this->tmpl['all']['item'] = $this->cObj->getSubpart($this->tmpl['all']['all'], '###ITEM###'); // Load HTML Template: ALL (works on subpart ###ITEM###)
		
		// Let's go
		$piVars_array = $this->div->arraytwo2arrayone($piVars_array); // changes: array('v1', array('v2')) to array('v1', 'v1_v2)
		$this->cObj->start($piVars_array, 'tx_powermail_mails'); // enable .field in typoscript

		if(!empty($piVars_array) && is_array($piVars_array)) { // If array from xml is set
			foreach ($piVars_array as $key => $value) { // one loop for every field in xml

				$fieldLabel = $this->div->getTitle($key, $GLOBALS['TSFE']->sys_language_uid);
				$fieldType = $this->div->getFieldType($key);
				
				// 1. Fill automatic markers
				if (in_array($this->div->minimize($key, 0), $this->allowedArray) || (count($this->allowedArray) == 0) && is_numeric(str_replace(array('uid', '_'), '', $key))) { // if current uid allowed in flexform or show all uids
					$this->markerArrayAll = array(); // clear array at the beginning
					$this->markerArrayAll['###POWERMAILFE_LABEL###'] .= $fieldLabel; // add label
					$this->markerArrayAll['###POWERMAILFE_KEY###'] .= $key; // add key
					$this->markerArrayAll['###POWERMAILFE_ALTERNATE###'] .= ($this->div->alternate($i) ? 'odd' : 'even'); // odd or even

					if ($value != '') { // if there is a value
						$ts_array = array (
							'type' => $fieldType, // fieldtype
							'uid' => $key, // uid
							'value' => $value, // value
							'label' => $fieldLabel // label
						);
						$this->cObj->start($ts_array, 'tx_powermail_fields'); // enable .field in typoscript
						if ($this->cObj->cObjGetSingle($this->conf[$what . '.'][$key], $this->conf[$what . '.'][$key . '.']) != '') { // if ts for current field available (e.g. uid23 = TEXT ...)
							$this->markerArrayAll['###POWERMAILFE_VALUE###'] .= $this->cObj->cObjGetSingle($this->conf[$what . '.'][$key], $this->conf[$what . '.'][$key . '.']); // value
						} else { // no ts for current field, take default TS
							$this->markerArrayAll['###POWERMAILFE_VALUE###'] .= $this->cObj->cObjGetSingle($this->conf[$what . '.']['fieldValue'], $this->conf[$what . '.']['fieldValue.']); // add value
							if ($fieldType == 'file') {
								if ($this->conf[$what . '.']['fieldValue.']['file.']['link'] == 1) {
									if ($piVars_array['uploadPath'] != '' && 1 == 0) {
										$fileLink = $this->cObj->typolinkWrap( array('parameter' => $piVars_array['uploadPath'] . $this->markerArrayAll['###POWERMAILFE_VALUE###']));
									} else {
										$fieldDetails = $this->div->fieldDetails($key);
										if ($this->conf[$what . '.']['fieldValue.']['file.']['useTitleAsUploadSubFolderName'] == 1) {
											$subFolder =  $fieldDetails['c_title'] . '/';
										} else {
											$subFolder = '';
										}
										$fileLink = $this->cObj->typolinkWrap( array('parameter' => $this->conf[$what . '.']['fieldValue.']['file.']['uploadfolder'] . $subFolder . $this->markerArrayAll['###POWERMAILFE_VALUE###']));
									}
									$this->markerArrayAll['###POWERMAILFE_VALUE###'] = $fileLink[0] . $this->markerArrayAll['###POWERMAILFE_VALUE###'] . $fileLink[1];
								}
							}
						}
					}

					// adding marker to string
					if ($this->markerArrayAll['###POWERMAILFE_VALUE###'] != '' || (($what == 'list' && $this->conf['list.']['hideEmpty'] != 1) || ($what == 'latest' && $this->conf['latest.']['hideEmpty'] != 1) || ($what == 'single' && $this->conf['single.']['hideEmpty'] != 1))) {
						$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['all']['item'], $this->markerArrayAll); // Add
					}
					$i++; // increase counter
				}
				
				
				// 2. Fill individual markers (like ###UID33### and ###LABEL_UID33###)
				if (is_numeric($this->div->minimize($key))) { // if uid3 or uid3_4 and NOT file
					$this->markerArray['###LABEL_' . strtoupper($key) . '###'] = $fieldLabel; // label::uid3 => ###LABEL_UID3###
				}
				if ($this->cObj->cObjGetSingle($this->conf[$what . '.'][$key], $this->conf[$what . '.'][$key . '.']) != '') { // if ts for current field available
					
					$this->markerArray['###' . strtoupper($key) . '###'] .= $this->cObj->cObjGetSingle($this->conf[$what . '.'][$key], $this->conf[$what . '.'][$key . '.']); // value
				
				} else { // no ts for current field
					
					if ($value != '') { // if there is a value
						$ts_array = array (
							'type' => $fieldType, // fieldtype
							'uid' => $key, // uid
							'value' => $value, // value
							'label' => $fieldLabel // label
						);
						$this->cObj->start($ts_array, 'tx_powermail_fields'); // enable .field in typoscript
						$this->markerArray['###' . strtoupper($key) . '###'] .= $this->cObj->cObjGetSingle($this->conf[$what . '.']['fieldValue'], $this->conf[$what . '.']['fieldValue.']); // add value
						#$this->markerArray['###' . strtoupper($key) . '###'] = $value . (strtolower($key) != 'uid' ? '<!-- NO TS -->' : ''); // uid3 => ###UID3###
					}
					
				}
			}
			$subpartArray['###CONTENT###'] = $content_item; // ###POWERMAILFE_ALL###
			
			
			$this->markerArray['###POWERMAILFE_ALL###'] = $this->cObj->substituteMarkerArrayCached($this->tmpl['all']['all'], array(), $subpartArray); // Fill ###POWERMAILFE_ALL###
		}
		
		if (!empty($this->markerArray)) return $this->markerArray;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_markers.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_markers.php']);
}

?>

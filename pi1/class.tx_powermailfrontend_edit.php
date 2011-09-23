<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Alexander Kellner <alexander.kellner@einpraegsam.net>
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
require_once(t3lib_extMgm::extPath('powermail') . 'pi1/class.tx_powermail_html.php'); // get html and field function class
require_once(t3lib_extMgm::extPath('powermail') . 'lib/class.tx_powermail_functions_div.php'); // file for div functions

class tx_powermailfrontend_edit extends tslib_pibase {

	var $extKey = 'powermail_frontend'; // Extension key
	var $prefixId = 'tx_powermailfrontend_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_edit.php';	// Path to this script relative to the extension dir.
	var $mode = 'edit'; // current mode
	var $dbInsert = 1; // disable db insert for testing only
	
	function main($conf, $piVars, $cObj) {
		// Config
    	$this->cObj = $cObj; // cObject
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->tmpl = $this->wrappedSubpartArray = $this->innerMarkerArray = $this->markerArray = array(); $content_item = '';
		$this->allowedfields = t3lib_div::trimExplode(',', $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'fields', $this->mode), 1); // get allowed fields from flexform
		$this->markers = t3lib_div::makeInstance('tx_powermailfrontend_markers'); // Create new instance for markers class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermailfrontend_dynamicmarkers'); // New object: TYPO3 dynamicmarker function
		$this->div = t3lib_div::makeInstance('tx_powermailfrontend_div'); // Create new instance for div class
		$this->fieldgenerator = t3lib_div::makeInstance('tx_powermail_html'); // get methods of powermail field generator
		$this->sec = t3lib_div::makeInstance('tx_powermail_functions_div'); // get methods of powermail for sec function
		$this->tmpl[$this->mode]['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###POWERMAILFE_' . strtoupper($this->mode) . '###'); // Load HTML Template
		$this->tmpl[$this->mode]['item'] = $this->cObj->getSubpart($this->tmpl[$this->mode]['all'],'###ITEM_' . strtoupper($this->mode) . '###'); // work on subpart 2
		
		if ($this->piVars['edit'] > 0) { // only if GET param show was set
			
			if (empty($this->piVars['change']['uid'])) { // no values to save, so show form
			
				if (!$this->div->allowed($this->piVars['edit'], $this->conf)) { // current user is allowed to make changes
					
					// Get XML to current entry
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
						'uid, piVars, crdate, recipient, subject_r, sender, senderIP',
						'tx_powermail_mails',
						$where_clause = 'tx_powermail_mails.uid = ' . $this->piVars['edit'] . $this->cObj->enableFields('tx_powermail_mails'),
						$groupBy = '',
						$orderBy = '',
						$limit = 1
					);
					if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // rename not allowed signs
					$this->vars = t3lib_div::xml2array($row['piVars'], 'pivars'); // xml to array
					if (!is_array($this->vars)) $this->vars = utf8_encode(t3lib_div::xml2array($row['piVars'], 'pivars')); // xml to array

					$this->vars = $this->div->allowedUID($this->vars, $this->conf['edit.']['fields'], $this->conf['edit.']['fieldsmode'], $this->conf['edit.']['powermailuid']);

					// save values in in session
					$GLOBALS['TSFE']->fe_user->setKey('ses', 'powermail_'.($this->cObj->data['_LOCALIZED_UID'] > 0 ? $this->cObj->data['_LOCALIZED_UID'] : $this->cObj->data['uid']), $this->vars); // Generate Session with piVars array
					$GLOBALS['TSFE']->storeSessionData(); // Save session
					
					// one loop for every field
					//t3lib_div::debug($this->vars);
					foreach ((array) $this->vars as $key => $value) { // one loop for every field in xml
						if($key != 'FILE') {
							//$this->conf['edit.']['field.']['checkboxJS'] = 1;
							$this->innerMarkerArray['###POWERMAILFE_FIELDS###'] = $this->fieldgenerator->main($this->conf['edit.'], array($key => $value), $this->cObj, $this->div->fieldDetails($key), array(), 0); // Get HTML code for each field
							$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['item'], $this->innerMarkerArray);
						}
					}
					$subpartArray['###CONTENT_'.strtoupper($this->mode).'###'] = $content_item;
					
					// Fill further markers
					$this->markerArray['###ACTION###'] = $this->pi_linkTP_keepPIvars_url(array(), 1); // current URL
					$this->markerArray['###UID###'] = $this->piVars['edit']; // uid of current mail
					$linkConf = array(
						'returnLast' => 'url', 
						'parameter' => ($this->conf['list.']['pid'] > 0 ? $this->conf['list.']['pid'] : $GLOBALS['TSFE']->id), 
						'additionalParams' => '&' . $this->prefixId . '[delete]=' . $this->piVars['edit'] . ((intval($piVars['pointer']) > 0) ? '&' . $this->prefixId . '[pointer]=' . intval($piVars['pointer']) : '')
					);
					$this->markerArray['###URLDELETE###'] = t3lib_div::makeRedirectUrl($this->cObj->typolink('x', $linkConf));
					$this->wrappedSubpartArray['###POWERMAILFE_SPECIAL_LISTLINK###'] = $this->cObj->typolinkWrap( array('parameter' => ($this->conf['list.']['pid'] > 0 ? $this->conf['list.']['pid'] : $GLOBALS['TSFE']->id) . '#powermailfe_listitem_' . $row['uid'], 'additionalParams' => ((intval($piVars['pointer']) > 0) ? '&' . $this->prefixId . '[pointer]=' . intval($piVars['pointer']) : ''), 'useCacheHash' => 1) ); // List Link
					
					// end
					$this->hook_pmfe_edit_form(); // hook for array manipulation
					$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->markerArray, $subpartArray, $this->wrappedSubpartArray); // Get html template
					$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
					if (!empty($this->content)) return $this->content; // return HTML
				
				} else {
					return $this->div->allowed($this->piVars['edit'], $this->conf); // return error message
				}
			
			} else { // saving values to database
			
				// 1. Get vars from GET and clean them
				$this->uid = intval($this->piVars['change']['uid']); // uid of entry
				unset($this->piVars['change']['uid']); // delete uid from array
				$this->newvars = t3lib_div::_GP('tx_powermail_pi1');
				$this->newvars = $this->sec->sec($this->newvars); // overwrite piVars from doorman class
				
				// 2. Get values from Database
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
					'uid, piVars, crdate, recipient, subject_r, sender, senderIP',
					'tx_powermail_mails',
					$where_clause = 'tx_powermail_mails.uid = ' . $this->uid . $this->cObj->enableFields('tx_powermail_mails'),
					$groupBy = '',
					$orderBy = '',
					$limit = 1
				);
				if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); // Result in array
				$row['piVars'] = t3lib_div::convUmlauts($row['piVars']); // rename not allowed signs
				$this->vars = t3lib_div::xml2array($row['piVars'], 'pivars'); // xml to array
				if (!is_array($this->vars)) $this->vars = utf8_encode(t3lib_div::xml2array($row['piVars'], 'pivars')); // xml to array
				
				// 3. overwrite values with new values
				$this->vars = array_merge((array) $this->vars, (array) $this->newvars); // overwrite old with new values
				$this->xml = t3lib_div::array2xml($this->vars, '', 0, 'piVars'); // change back to xml
				$this->hook_pmfe_edit_save(); // hook for manipulation
				if ($this->dbInsert) $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_powermail_mails', 'uid = '.$this->uid, array('piVars' => $this->xml)); // update entry in database
				else t3lib_div::debug($this->vars, 'This values should be stored');
				
				$this->markerArray['###POWERMAILFE_SUCCESS_MESSAGE###'] = $this->pi_getLL('edit_updateSuccess', 'Success!');
				// build link to list page
				$listLinkConf = array(
					'parameter' => ($this->conf['list.']['pid'] > 0 ? $this->conf['list.']['pid'] : $GLOBALS['TSFE']->id) . '#powermailfe_listitem_' . $row['uid'],
					'useCacheHash' => 1
				);
				if (intval($piVars['pointer']) > 0) {
					$listLinkConf['additionalParams'] = '&' . $this->prefixId . '[pointer]=' . intval($piVars['pointer']);
				}
				$this->wrappedSubpartArray['###POWERMAILFE_SPECIAL_LISTLINK###'] = $this->cObj->typolinkWrap($listLinkConf); // List Link

				$this->tmpl[$this->mode]['success'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###POWERMAILFE_' . strtoupper($this->mode) . '_SUCCESS###'); // Load HTML Template

				$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['success'], $this->markerArray, NULL, $this->wrappedSubpartArray); // Get html template
				$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers

				return $this->content;
			}
			
		}
		
    }
	

	// Function hook_pmfe_edit_form() to change the form
	function hook_pmfe_edit_form() {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode]['form'])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode]['form'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_edit_form($this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	

	// Function hook_pmfe_edit_save() to change the update
	function hook_pmfe_edit_save() {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode]['save'])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->mode]['save'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$_procObj->pmfe_edit_save($this->xml, $this->conf, $this->piVars, $this); // Get new marker Array from other extensions
			}
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_edit.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_edit.php']);
}

?>
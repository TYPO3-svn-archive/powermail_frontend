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
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'pi1/class.tx_powermailfrontend_list.php'); // load list class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'pi1/class.tx_powermailfrontend_single.php'); // load single class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'pi1/class.tx_powermailfrontend_search.php'); // load search class
require_once(t3lib_extMgm::extPath('powermail_frontend') . 'pi1/class.tx_powermailfrontend_edit.php'); // load edit class
if (t3lib_extMgm::isLoaded('wt_doorman', 0)) {
	require_once(t3lib_extMgm::extPath('wt_doorman') . 'class.tx_wtdoorman_security.php'); // load security class
}

/**
 * Plugin 'powermail_frontend' for the 'powermail_frontend' extension.
 *
 * @author	Alex Kellner <alexander.kellner@in2code.de>
 * @package	TYPO3
 * @subpackage	tx_powermailfrontend_pi1
 */
class tx_powermailfrontend_pi1 extends tslib_pibase {
	public $prefixId      = 'tx_powermailfrontend_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_powermailfrontend_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'powermail_frontend';	// The extension key.
	public $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf)	{
		$this->secure(); // Security options for piVars
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->content = ''; // init
		$this->config(); // Enable flexform values in config
		
		if ($this->check()) { // if check is ok
			
			// let's go
			$mode = t3lib_div::trimExplode(',', $this->conf['mainconfig.']['mode'], 1); // split mode
			if (count($mode) > 0) { // only if mode is set
				for ($i=0; $i < count($mode); $i++) { // One loop for every selected mode
					
					switch ($mode[$i]) { // mode
						case 'list': // if list mode is selected
							$this->list = t3lib_div::makeInstance('tx_powermailfrontend_list'); // Create new instance for list class
							$this->list->mode = 'list'; // set mode
							$this->content .= $this->list->main($this->conf, $this->piVars, $this->cObj); // use list class
							break;
							
						case 'latest': // if latest mode is selected
							$this->list = t3lib_div::makeInstance('tx_powermailfrontend_list'); // Create new instance for list class
							$this->list->mode = 'latest'; // set mode
							$this->content .= $this->list->main($this->conf, $this->piVars, $this->cObj); // use list class
							break;
							
						case 'single': // if single mode is selected
							$this->single = t3lib_div::makeInstance('tx_powermailfrontend_single'); // Create new instance for single class
							$this->content .= $this->single->main($this->conf, $this->piVars, $this->cObj); // use single class
							break;
							
						case 'search': // if category mode is selected
							$this->search = t3lib_div::makeInstance('tx_powermailfrontend_search'); // Create new instance for search class
							$this->content .= $this->search->main($this->conf, $this->piVars, $this->cObj); // use search class
							break;
							
						case 'edit': // if edit mode is selected
							$this->edit = t3lib_div::makeInstance('tx_powermailfrontend_edit'); // Create new instance for edit class
							$this->content .= $this->edit->main($this->conf, $this->piVars, $this->cObj); // use edit class
							break;
					}
					
				}
			}
			
		} else {
			$this->content = $this->check(); // return some errormessages
		}
	
		return $this->pi_wrapInBaseClass($this->content);
	}
	
	/**
	 * Function secure() uses wt_doorman to clear piVars
	 *
	 * @return	void
	 */
	private function secure() {
		if (!class_exists('tx_wtdoorman_security')) {
			die ('Extension wt_doorman not found!');
		}
		
		$this->sec = t3lib_div::makeInstance('tx_wtdoorman_security'); // Create new instance for security class
		$this->sec->secParams = array ( // Allowed piVars type (int, text, alphanum, "value")
			'pointer' => 'int',
			'show' => 'int',
			'edit' => 'int',
			'filter' => array (
				'*' => 'text'
				//'*' => 'alphanum ++ @-_\.', // alphanum extended with '@', '-', '.' and '_'
			),
			'delete' => 'int',
			'deleteFile' => 'int',
			'change' => array (
				'*' => 'text'
			),
			'sort' => array (
				'*' => 'alphanum'
			),
			'export' => '"csv", "excel"'
		);
		$this->piVars = $this->sec->sec($this->piVars); // overwrite piVars piVars from doorman class
	}
	
	/**
	 * Function check() is checking for some extension requirements
	 *
	 * @return	void
	 */
	private function check() {
		$error = ''; // init
		
		if (intval($this->cObj->data['pages']) === 0) { // if startingpoint is not set
			$error = '<b>' . $this->pi_getLL('pi1_error', 'powermail_frontend error: No starting point was set in backend!') . '</b><br />'; // errormessage
		}
		
		if (empty($error)) {
			return true; // if there are no errors, return true
		} else {
			return $error; // else return error msg
		}
	}
	
	/**
	 * Enables flexform values in $conf
	 *
	 * @return	void
	 */
	private function config() {
		// 1. add flexform values to $this->conf
		if (is_array($this->cObj->data['pi_flexform']['data'])) { // if there are flexform values
			foreach ($this->cObj->data['pi_flexform']['data'] as $key => $value) { // every flexform category
				if (count($this->cObj->data['pi_flexform']['data'][$key]['lDEF']) > 0) { // if there are flexform values
					foreach ($this->cObj->data['pi_flexform']['data'][$key]['lDEF'] as $key2 => $value2) { // every flexform option
						if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key2, $key)) { // if value exists in flexform
							$this->conf[$key.'.'][$key2] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key2, $key); // overwrite $this->conf
						}
					}
				}
			}
		}
		
		// 2. add hook for conf manipulation
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['setup'])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['setup'] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->setup($this->conf, $this->piVars, $this); // Enable setup manipulation
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_pi1.php']);
}

?>
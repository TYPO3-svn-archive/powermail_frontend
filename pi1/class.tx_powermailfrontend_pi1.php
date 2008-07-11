<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alex Kellner <alexander.kellner@einpraegsam.net>
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
require_once(t3lib_extMgm::extPath('powermail_frontend').'pi1/class.tx_powermailfrontend_list.php'); // load list class


/**
 * Plugin 'powermail_frontend' for the 'powermail_frontend' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	tx_powermailfrontend
 */
class tx_powermailfrontend_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_powermailfrontend_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_powermailfrontend_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'powermail_frontend';	// The extension key.
	var $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf = $conf;
		if (is_array($this->cObj->data['pi_flexform'])) $this->conf = array_merge($this->conf, $this->cObj->data['pi_flexform']); // add flexform arry to conf array
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->listIt = t3lib_div::makeInstance('tx_powermailfrontend_list'); // Create new instance for list class
		
		if ($this->cObj->data['pages'] > 0) { // if startingpoint is set
			
			$this->content = $this->listIt->main($this->conf, $this->piVars, $this->cObj); // use list class
			
		} else $this->content = '<b>'.$this->pi_getLL('pi1_error', 'powermail_frontend error: No startingpoint was set in backend!').'</b>'; // errormessage
	
		return $this->pi_wrapInBaseClass($this->content);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/pi1/class.tx_powermailfrontend_pi1.php']);
}

?>
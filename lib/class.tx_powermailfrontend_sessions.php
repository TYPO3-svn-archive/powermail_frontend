<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Alexander Grein <ag@mediaessenz.eu>
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

class tx_powermailfrontend_sessions extends tslib_pibase {

	public $extKey = 'powermail_frontend'; // Extension key
	public $prefixId = 'tx_powermailfrontend_pi1'; // Same as class name
	public $scriptRelPath = 'pi1/class.tx_powermailfrontend_pi1.php'; // Path to this script relative to the extension dir.

	/**
	 * setSession function for store search filter parameters to session
	 *
	 * @param	array		$conf: TypoScript configuration
	 * @param	array		$piVars: piVars
	 * @param	object		$cObj: Content object
	 * @param	boolean		$overwrite session vars
	 * @return	void
	 */
	public function setSession($conf, $piVars, $cObj, $overwrite = true) {
		// conf
		$this->conf = $conf;
		$this->cObj = $cObj;

		// start
		if (isset($piVars)) { // Only if piVars are existing
			// get old values before overwriting
			if (!$overwrite) { // get old values so, it can be set again
				$oldPiVars = $this->getSession($this->conf, $this->cObj); // Get Old piVars from Session (without not allowed piVars)
				if (isset($oldPiVars) && is_array($oldPiVars)) $piVars = array_merge($oldPiVars, $piVars); // Add old piVars to new piVars
			}

			$piVars = $this->urldecodeArrayRecursive($piVars);

			// Set Session (overwrite all values)
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_' . ($this->cObj->data['_LOCALIZED_UID'] > 0 ? $this->cObj->data['_LOCALIZED_UID']
					: $this->cObj->data['uid']), $piVars); // Generate Session with piVars array
			$GLOBALS['TSFE']->storeSessionData(); // Save session
		}
	}

	/** Function getSession() to get all saved session data in an array
	 *
	 * @param	array	$conf: Typoscript configuration
	 * @param	object	$cObj: Content object
	 * @return	array	piVars from session
	 */
	public function getSession($conf, $cObj) {
		// conf
		$this->conf = $conf;
		$this->cObj = $cObj;

		// start
		$piVars = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_' . ($this->cObj->data['_LOCALIZED_UID'] > 0 ? $this->cObj->data['_LOCALIZED_UID']
				: $this->cObj->data['uid'])); // Get piVars from Session

		if (isset($piVars)) return $piVars;
	}


	/**
	 * Apply the function "urldecode" on all values in the incomming array.
	 * Is the proceeded value always an array, this method will be called recursive.
	 *
	 * @param	array	$dataArray
	 * @return	array	$urldecoded data
	 */
	protected function urldecodeArrayRecursive(array $dataArray) {
		$urldecodedData = array();

		foreach ($dataArray as $key => $val) {
			if (is_array($val)) {
				$this->urldecodeArrayRecursive($val);
			} else {
				$val = rawurldecode($val);
			}
			$urldecodedData[$key] = $val;
		}
		return $urldecodedData;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_sessions.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_sessions.php']);
}

?>
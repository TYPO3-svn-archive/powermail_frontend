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

class tx_powermailfrontend_mails_filter extends tslib_pibase {

	private $mailsArray, $confArray, $viewMode, $filterPiVars;
	
	/*
	* Function sortArray() sorts piVars array
	*
	* @param 	array 	$array: whole array to sort
	* @param 	array 	$conf: TS configuration
	* @param 	string 	$mode: list, detail, latest
	* @param 	array 	$piVars: piVars from GET or POST request
	* @return 	array	new array
	*/
	public function sortArray() {
		$this->sort = $this->confArray[$this->viewMode . '.']['orderby']; // sorting
		if (!empty($this->piVars['sort'])) { // sort from piVars
			foreach ((array) $this->piVars['sort'] as $key => $value) { // one loop for every sorting entry
				$this->sort = strtolower($key . ' ' . $value);
				break; // take first entry and stop loop
			}
		}
		if ($this->confArray[$this->viewMode . '.']['orderby']) {
			usort($this->mailsArray, array('tx_powermailfrontend_mails_filter', 'cmp')); // sorting
		}
	}

	/**
	* Function cmp is a compare function for usort
	*
	* @param 	array 	$a: first array
	* @param 	array 	$b: second array
	* @return 	boolean
	*/
	private function cmp($a, $b) {
		// config
		$conf_orderby = t3lib_div::trimExplode(' ', $this->sort, 1); // split on space

		// let's go
		$return = strcmp($a[$conf_orderby[0]], $b[$conf_orderby[0]]); // give me 0 or 1 or -1
		if (strtolower($conf_orderby[1]) == 'desc' && $return !== 0) $return *= -1; // if DESC than change 1 to -1 OR -1 to 1

		return $return;
	}

	/**
	 * array_search with recursive searching, optional partial matches and optional search by key
	 *
	 * @param string	$needle
	 * @param array		$haystack
	 * @param bool 		$partial_matches
	 * @param bool 		$search_keys
	 * @return bool|int|string
	 */
	private function arrayFindRecursive($needle, $haystack, $partial_matches = false, $search_keys = false) {
        if (!is_array($haystack)) {
			return ($partial_matches && strpos(strtolower($haystack), strtolower($needle)) !== false);
		}
        foreach ($haystack as $key => $value) {
			$key = strtolower($key);
			$value = strtolower($value);
            $what = ($search_keys) ? $key : $value;
            if ($needle === $what) return $key;
            else if ($partial_matches && strpos($what, $needle) !== false) return $key;
            else if (is_array($value) && $this->arrayFindRecursive($needle, $value, $partial_matches, $search_keys) !== false) return $key;
        }
        return false;
    }

	/**
	* changes filter
	*
	* @return 	void
	*/
	public function filterArray() {
		// config
		$mailsToInclude = array();
		$newArray = array();

		// if limit set, cut array
		if ($this->limit > 0) {
			$this->mailsArray = array_slice($this->mailsArray, 0, $this->limit); // give me only the first X entries of the array
		}

		// if new mode is set, clear old values
		if (!empty($this->confArray[$this->viewMode . '.']['new'])) { // if new mode was set
			for ($i = 0; $i < count($this->mailsArray); $i++) { // one loop for every list item
				if ($this->mailsArray[$i][$this->confArray[$this->viewMode . '.']['new']] > time()) { // if this value is greater than today
					$newArray[] = $this->mailsArray[$i];
				}
			}
			$this->mailsArray = array(); // clear old array
			$this->mailsArray = $newArray; // define old array
		}

		// remove empty or "*" filter values and convert all search stings to lowercase
		$this->filterPiVars = array_filter($this->filterPiVars);
		if (!empty($this->filterPiVars) && count($this->filterPiVars) > 0) {
			foreach ($this->filterPiVars as $key => $value) { // one loop for every filter
				if ($value == '*') {
					unset($this->filterPiVars[$key]);
				} else {
					$this->filterPiVars[$key] = strtolower($this->filterPiVars[$key]);
				}
			}
		}

		// filter mail array
		if (!empty($this->filterPiVars) && count($this->filterPiVars) > 0) { // if there are some filter

			// delete all empty piVars to speedup filter loop
			$mailsToCheckArray = array();
			foreach ($this->mailsArray as $mailToFilter) {
				$mailsToCheckArray[] = array_filter($mailToFilter);
			}

			if (!empty($this->filterPiVars['_all'])) {
				// search over all fields
				foreach ($mailsToCheckArray as $mailToCheckKey => $mailToCheckValueArray) { // one loop for every mail
					if ($this->arrayFindRecursive($this->filterPiVars['_all'], $mailToCheckValueArray, true, false) !== false) {
						$mailsToInclude[$mailToCheckKey] = 1; // Match, so include
					}
				}
			}

			foreach ($mailsToCheckArray as $mailToCheckKey => $mailToCheckValueArray) { // one loop for every mail
				foreach ($this->filterPiVars as $filterKey => $filterValue) { // one loop for every filter
					if ($filterKey != '_all' && array_key_exists($filterKey, $mailToCheckValueArray)) {
						foreach ($mailToCheckValueArray as $mailKey => $mailValue) { // one loop for every piVar
							if ($filterKey == $mailKey) { // if current uid is the uid which should be filtered
								if ($filterValue[0] == '@' && is_numeric($mailValue[0])) { // filter with beginning number
									$mailsToInclude[$mailToCheckKey] = 1;
								} else {
									if (strlen($filterValue) == 1 && strtolower($mailValue[0]) == $filterValue) { // only one letter given
										$mailsToInclude[$mailToCheckKey] = 1;
									} else if ($this->arrayFindRecursive($filterValue, $mailValue, true, false) !== false) { // search word given
										$mailsToInclude[$mailToCheckKey] = 1;
									}
								}
							}
						}
					}
				}
			}

			// remove not matching mails
			$this->mailsArray = array_intersect_key($this->mailsArray, $mailsToInclude);

			if ($this->confArray['search.']['filterAnd'] == 1) {
				foreach ($this->mailsArray as $searchResultIndex => $searchResult) {
					$and = true;
					foreach ($this->filterPiVars as $filterName => $filterValue) { // one loop for every filter
						if ($filterName == '_all') {
							$and = false;
							foreach ($searchResult as $resultKey => $resultValue) {
								if ((strpos(strtolower($searchResult[$resultKey]), $filterValue) !== false) || (strlen($filterValue) == 1 && strpos(strtolower($searchResult[$resultKey]), $filterValue) == 0)) {
									$and = true;
									break;
								}
							}
						} else {
							if ((strpos(strtolower($searchResult[$filterName]), $filterValue) === false) || (strlen($filterValue) == 1 && strpos(strtolower($searchResult[$filterName]), $filterValue) != 0)) {
								$and = false;
								break;
							}
						}
					}
					if (!$and) {
						unset($this->mailsArray[$searchResultIndex]);
					}
				}
			}
		}
	}

	public function getResult() {
		return $this->mailsArray;
	}

	public function writeMailUidsToSession() {
		$sessionArray = array();
		$mailUidsArray = array();
		$this->sessions = t3lib_div::makeInstance('tx_powermailfrontend_sessions'); // New object: session functions
		foreach ($this->mailsArray as $mail) {
			$mailUidsArray[] .= $mail['uid'];
		}
		$sessionArray['exportUids'] = implode(',', $mailUidsArray);
		$this->sessions->setSession($this->confArray, $sessionArray, $this->cObj, false);
	}

	public function __construct($mailsArray, $confArray, $viewMode, $cObj, $filterPiVars) {
		$this->mailsArray = $mailsArray;
		$this->confArray = $confArray;
		$this->viewMode = $viewMode;
		$this->cObj = $cObj;
		$this->filterPiVars = $filterPiVars;
		$this->limit = $this->confArray[$this->viewMode . '.']['limit'];
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_mails_filter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/class.tx_powermailfrontend_mails_filter.php']);
}
?>
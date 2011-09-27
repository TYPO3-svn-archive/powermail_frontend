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
			usort($this->mailArray, array('tx_powermailfrontend_mails_filter', 'cmp')); // sorting
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
	* changes filter
	*
	* @return 	void
	*/
	public function filterArray() {
		// config
		$del_listitem = $newArray = $removeFilterPiVars = array();
		$j = 0;
		$emptyFormSubmited = true;


		// let's go
		// 1. if limit set, cut array
		if ($this->limit > 0) {
			$this->mailArray = array_slice($this->mailArray, 0, $this->limit); // give me only the first X entries of the array
		}

		// 2. if new mode is set, clear old values
		if (!empty($this->confArray[$this->viewMode . '.']['new'])) { // if new mode was set
			for ($i = 0; $i < count($this->mailArray); $i++) { // one loop for every list item
				if ($this->mailArray[$i][$this->confArray[$this->viewMode . '.']['new']] > time()) { // if this value is greater than today
					$newArray[] = $this->mailArray[$i];
				}
			}
			$this->mailArray = array(); // clear old array
			$this->mailArray = $newArray; // define old array
		}

		if (!empty($this->filterPiVars) && count($this->filterPiVars) > 0) {
			foreach ($this->filterPiVars as $key => $value) { // one loop for every filter
				if (!empty($value)) {
					$emptyFormSubmited = false;
				}
				if($value[0] == '*') {
					$removeFilterPiVars[] .= $key;
				}
			}
		} else {
			$emptyFormSubmited = false;
		}

		if (!$emptyFormSubmited) { // if no filter parameter is found in piVars, get filter values from session
			//$this->sessions = t3lib_div::makeInstance('tx_powermailfrontend_sessions'); // New object: session functions
			//$this->filterPiVars = $this->sessions->getSession($this->confArray, $this->cObj, 'filter');
		}

		if (count($removeFilterPiVars) > 0) {
			foreach($removeFilterPiVars as $filterPiVar) {
				unset($this->filterPiVars[$filterPiVar]);
			}
			//$this->sessions->setSession($this->confArray, $this->filterPiVars, $this->cObj, true);
		}

		// 3. filter array
		if (!empty($this->filterPiVars) && count($this->filterPiVars) > 0) { // if there are some filter
			foreach ($this->filterPiVars as $key => $value) { // one loop for every filter
				if (!empty($value)) {

					for ($i = 0; $i < count($this->mailArray); $i++) { // one loop for every list item

						if ($key != '_all') { // search in a specified field
							$del_listitem[$i] = 0; // not to delete is default
							if (!array_key_exists($key, $this->mailArray[$i])) {
								$del_listitem[$i] = 1; // in current list item is not the field which should be filtered - so delete
							}
							if ($del_listitem[$i] === 0) { // only if this listitem should not be deleted
								foreach ($this->mailArray[$i] as $key2 => $value2) { // one loop for every piVar in current list item
									if ($key == $key2) { // if current uid is the uid which should be filtered
										switch (strtolower($value[0])) {
											case '@': // filter with beginning number
												if (!is_numeric($value2[0])) {
													$del_listitem[$i] = 1; // this value don't start with a number
												}
												break;

											case 'a': // filter with beginning letter
											case 'b':
											case 'c':
											case 'd':
											case 'e':
											case 'f':
											case 'g':
											case 'h':
											case 'i':
											case 'j':
											case 'k':
											case 'l':
											case 'm':
											case 'n':
											case 'o':
											case 'p':
											case 'q':
											case 'r':
											case 's':
											case 't':
											case 'u':
											case 'v':
											case 'w':
											case 'x':
											case 'y':
											case 'z':
											default:
												if (strlen($value) == 1) { // only one letter given
													if (strtolower($value2[0]) != $value) {
														$del_listitem[$i] = 1; // this value don't start with the right letter
													}
												} else { // searchword given
													if (is_array($value2)) { // second level
														$tmp_del = 1;
														foreach ($value2 as $key4 => $value4) {
															if (stristr(strtolower($value4), strtolower($value))) {
																$tmp_del = 0;
																break;
															}
														}
														if ($tmp_del) {
															$del_listitem[$i] = 1; // this var is not in the value
														}
													} else { // first level
														if (strpos(strtolower($value2), strtolower($value)) === false) {
															$del_listitem[$i] = 1; // this var is not in the value
														}
													}
												}
												break;
										}
									}
								}
							}

						} else { // numberOfMails search
							$del_listitem[$i] = 1; // delete is default
							foreach ($this->mailArray[$i] as $key2 => $value2) { // one loop for every piVar in current list item
								if (strpos(strtolower($value2), strtolower($value)) !== false) $del_listitem[$i] = 0; // Match, so don't delete
							}
						}

					}
				}
			}

			$newArray = array(); // clear temp array
			foreach ($this->mailArray as $key3 => $value3) { // finally sort it
				if (!$del_listitem[$j]) {
					$newArray[] = $value3; // fill into a new array
				}

				$j++; // increase counter
			}

			if ($this->confArray['search.']['filterAnd'] == 1) {
				foreach ($newArray as $searchResultIndex => $searchResult) {
					$and = true;
					foreach ($this->filterPiVars as $filterName => $filterValue) { // one loop for every filter
						if ($filterValue != '') {
							if ($filterName == '_all') {
								$and = false;
								foreach ($searchResult as $resultKey => $resultValue) {
									if ((strpos(strtolower($searchResult[$resultKey]), strtolower($filterValue)) !== false) || (strlen($filterValue) == 1 && strpos(strtolower($searchResult[$resultKey]), strtolower($filterValue)) == 0)) {
										$and = true;
										break;
									}
								}
							} else {
								if ((strpos(strtolower($searchResult[$filterName]), strtolower($filterValue)) === false) || (strlen($filterValue) == 1 && strpos(strtolower($searchResult[$filterName]), strtolower($filterValue)) != 0)) {
									$and = false;
									break;
								}
							}
						}
					}
					if (!$and) {
						unset($newArray[$searchResultIndex]);
					}
				}
			}

			//unset($this->mailArray); // delete old array
			$this->mailArray = $newArray; // fill into old array
		}
	}

	public function getResult() {
		return $this->mailArray;
	}

	public function writeMailUidsToSession() {
		$sessionArray = array();
		$mailUidsArray = array();
		$this->sessions = t3lib_div::makeInstance('tx_powermailfrontend_sessions'); // New object: session functions
		foreach ($this->mailArray as $mail) {
			$mailUidsArray[] .= $mail['uid'];
		}
		$sessionArray['exportUids'] = implode(',', $mailUidsArray);
		$this->sessions->setSession($this->confArray, $sessionArray, $this->cObj, false);
		//t3lib_div::debug($this->cObj);
	}

	public function __construct($mailsArray, $confArray, $viewMode, $cObj, $filterPiVars) {
		$this->mailArray = $mailsArray;
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
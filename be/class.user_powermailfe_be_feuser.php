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

class user_powermailfe_be_feuser {

	var $limit = 10000; // limit for select query
	
	function main(&$params, &$pObj)	{
		// config
		$mode = $params['config']['itemsProcFunc_config']['mode']; // mode from xml
		
		if ($mode == 'feuser') { // show only feusers
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
				'fe_users.uid, fe_users.username, fe_groups.title',
				'fe_users, fe_groups, sys_refindex',
				$where_clause = 'sys_refindex.tablename = "fe_users" AND sys_refindex.ref_table = "fe_groups" AND fe_users.uid=sys_refindex.recuid AND fe_groups.uid=sys_refindex.ref_uid' . t3lib_BEfunc::BEenableFields('fe_users'),
				$groupBy = 'fe_users.uid',
				$orderBy = 'fe_users.uid',
				$limit = $this->limit
			);
			if ($res) { // If there is a result
				$i=0;
				$params['items'][$i]['0'] = $pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.feuser_owner'); // Option label
				$params['items'][$i]['1'] = 'owner'; // Option value
				
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
					$i++; // increase counter
					$params['items'][$i]['0'] = $row['username']; // Option label
					$params['items'][$i]['1'] = $row['uid']; // Option value
				}
			}
			
		} elseif ($mode == 'fegroup') { // show groups
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
				'fe_groups.title, fe_groups.uid',
				'fe_users, fe_groups, sys_refindex',
				$where_clause = 'sys_refindex.tablename = "fe_users" AND sys_refindex.ref_table = "fe_groups" AND fe_users.uid=sys_refindex.recuid AND fe_groups.uid=sys_refindex.ref_uid' . t3lib_BEfunc::BEenableFields('fe_users'),
				$groupBy = 'fe_groups.uid',
				$orderBy = 'fe_groups.uid',
				$limit = $this->limit
			);
			if ($res) { // If there is a result
				$i=0;
				$params['items'][$i]['0'] = $pObj->sL('LLL:EXT:powermail_frontend/locallang_db.xml:pi_flexform.fegroup_owner'); // Option label
				$params['items'][$i]['1'] = 'ownergroup'; // Option value
				
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // one loop for every db entry
					$i++; // increase counter
					$params['items'][$i]['0'] = $row['title']; // Option label
					$params['items'][$i]['1'] = $row['uid']; // Option value
				}
			}
		}

			
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/be/class.user_powermailfe_be_feuser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/be/class.user_powermailfe_be_feuser.php']);
}

?>

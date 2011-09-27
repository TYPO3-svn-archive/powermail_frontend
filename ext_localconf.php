<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_powermailfrontend_pi1.php', '_pi1', 'list_type', 0);
include_once(t3lib_extMgm::extPath('powermail_frontend') . 'lib/user_powermailfrontendOnCurrentPage.php'); // Conditions for JS including

$TYPO3_CONF_VARS['FE']['eID_include']['tx_powermailfrontend_export'] = 'EXT:powermail_frontend/lib/class.tx_powermailfrontend_export.php';

?>
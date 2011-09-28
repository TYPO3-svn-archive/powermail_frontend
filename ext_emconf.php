<?php

########################################################################
# Extension Manager/Repository config file for ext "powermail_frontend".
#
# Auto generated 28-09-2011 14:30
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'powermail_frontend',
	'description' => 'Full database plugin (List-, Detail-, Latestview) of powermail entries. Frontend edit possible. Extend powermail to an eventlist or a guestbook, e.g.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.7.0',
	'dependencies' => 'wt_doorman,powermail',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Alex Kellner',
	'author_email' => 'alexander.kellner@in2code.de',
	'author_company' => 'in2code',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.4.6-0.0.0',
			'wt_doorman' => '1.3.0-0.0.0',
			'powermail' => '1.6.5-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'pagebrowse' => '1.3.0-0.0.0',
		),
	),
	'_md5_values_when_last_written' => 'a:42:{s:12:"ext_icon.gif";s:4:"34d9";s:17:"ext_localconf.php";s:4:"d03c";s:14:"ext_tables.php";s:4:"806a";s:13:"locallang.xml";s:4:"4a7c";s:16:"locallang_db.xml";s:4:"6536";s:39:"be/class.user_powermailfe_be_feuser.php";s:4:"4055";s:39:"be/class.user_powermailfe_be_fields.php";s:4:"ccb5";s:22:"be/flexform_ds_pi1.xml";s:4:"59c8";s:14:"doc/manual.sxw";s:4:"2208";s:38:"lib/class.tx_powermailfrontend_div.php";s:4:"76e8";s:49:"lib/class.tx_powermailfrontend_dynamicmarkers.php";s:4:"aa58";s:41:"lib/class.tx_powermailfrontend_export.php";s:4:"0216";s:45:"lib/class.tx_powermailfrontend_filter_abc.php";s:4:"6da8";s:48:"lib/class.tx_powermailfrontend_filter_search.php";s:4:"e3ed";s:47:"lib/class.tx_powermailfrontend_mails_filter.php";s:4:"e8f7";s:42:"lib/class.tx_powermailfrontend_markers.php";s:4:"52d2";s:46:"lib/class.tx_powermailfrontend_pagebrowser.php";s:4:"2b77";s:43:"lib/class.tx_powermailfrontend_sessions.php";s:4:"7133";s:43:"lib/user_powermailfrontendOnCurrentPage.php";s:4:"a966";s:42:"lib/user_powermailfrontend_pagebrowser.php";s:4:"c020";s:14:"pi1/ce_wiz.gif";s:4:"85a0";s:39:"pi1/class.tx_powermailfrontend_edit.php";s:4:"aeed";s:39:"pi1/class.tx_powermailfrontend_list.php";s:4:"e966";s:38:"pi1/class.tx_powermailfrontend_pi1.php";s:4:"4a09";s:46:"pi1/class.tx_powermailfrontend_pi1_wizicon.php";s:4:"3e3a";s:41:"pi1/class.tx_powermailfrontend_search.php";s:4:"ff67";s:41:"pi1/class.tx_powermailfrontend_single.php";s:4:"77e4";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"1b39";s:36:"res/css/powermail_frontend_basic.css";s:4:"132d";s:38:"res/less/powermail_frontend_basic.less";s:4:"ed9d";s:26:"static/css_basic/setup.txt";s:4:"c975";s:24:"static/pi1/constants.txt";s:4:"dc52";s:20:"static/pi1/setup.txt";s:4:"3736";s:23:"templates/tmpl_all.html";s:4:"1aa9";s:24:"templates/tmpl_edit.html";s:4:"8748";s:26:"templates/tmpl_latest.html";s:4:"b38f";s:24:"templates/tmpl_list.html";s:4:"8e0d";s:31:"templates/tmpl_pagebrowser.html";s:4:"ea6a";s:42:"templates/tmpl_pagebrowser_pagebrowse.html";s:4:"4c6c";s:26:"templates/tmpl_search.html";s:4:"780d";s:26:"templates/tmpl_single.html";s:4:"7098";}',
	'suggests' => array(
	),
);

?>
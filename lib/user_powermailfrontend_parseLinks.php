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

/**
 * user_powermailfrontend_parseLinks() parse content and transform e.g. [url="http:://www.test.de"]Test[/url] to a real link <a href="http:://www.test.de">Test</a>
 *
 * @param	string		$content: The PlugIn content
 * @param	array		$conf: The PlugIn configuration
 * @return	string      The content that is displayed on the website
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

class user_powermailfrontend_parseLinks extends tslib_pibase {

	var $cObj; // The backReference to the mother cObj object set at call time

	function user_parseLinks($content = '', $conf = array()) {

		$target = '';
		$aTagParams = '';

		if (!empty($conf['target'])) {
			$target = ' target="' . $conf['target'] . '"';
		}

		if (!empty($conf['target'])) {
			$aTagParams = ' ' . $conf['ATagParams'];
		}

		if (TYPO3_MODE != 'FE') { // only in Frontend
			return false;
		}
		$bbcode = array(
			'[url=&quot;', '[/url]',
			'[mail=&quot;', '[/mail]',
			'&quot;]'
		);
		$htmlcode = array(
			'<a' . $target . $aTagParams . ' href="', '</a>',
			'<a href="mailto:', '</a>',
			'">'
		);
        return nl2br(str_replace($bbcode, $htmlcode, $content));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/user_powermailfrontend_parseLinks.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_frontend/lib/user_powermailfrontend_parseLinks.php']);
}
?>
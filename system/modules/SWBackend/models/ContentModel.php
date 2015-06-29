<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */



/**
 * Reads and writes content elements
 *
 * @package   Models
 * @author    Leo Feyer <https://github.com/leofeyer>
 * @copyright Leo Feyer 2005-2014
 */
class ContentModel extends \Contao\ContentModel {

	public static function findByPidAndTable($intPid, $strParentTable, array $arrOptions=array()) {
		$t = static::$strTable;

		$arrColumns = array("$t.pid=? AND $t.ptable=?");
		if ($strParentTable == 'tl_article')
			$arrColumns = array("$t.pid=? AND ($t.ptable=? OR $t.ptable='')");

		if (!$arrOptions['order'])
			$arrOptions['order'] = "$t.sorting";

		return static::findBy($arrColumns, array($intPid, $strParentTable), $arrOptions);
	}
}

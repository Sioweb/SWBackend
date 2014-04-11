<?php

/**
* Contao Open Source CMS
*  
* @file sw_files.php
* @class tl_files
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class sw_files extends tl_files
{
	public function saveMeta($strValue, DataContainer $dc)
	{
		$arrValue = deserialize($strValue);
		if($arrValue)
			foreach($arrValue as $vKey => $values)
			{
				$arrDiff = array_diff_key($values,$GLOBALS['TL_CONFIG']['siowebFilemanager']);
				$arrKeys = array();
				$strDiff = '';
				if($arrDiff)
				{
					$arrKeys = array_merge($GLOBALS['TL_CONFIG']['siowebFilemanager'],$arrDiff);
					$strDiff = implode(',',array_keys($arrKeys));
				}

				if($strDiff)
					\Config::getInstance()->update('$GLOBALS[\'TL_CONFIG\'][\'siowebFilemanager\']',$strDiff);
				if($arrKeys)
					$GLOBALS['TL_CONFIG']['siowebFilemanager'] = $arrKeys;
			}
		return $strValue;
	}
}
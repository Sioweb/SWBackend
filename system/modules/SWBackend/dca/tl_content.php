<?php

/**
* Contao Open Source CMS
* 
* @file tl_content.php
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/



$GLOBALS['TL_DCA']['tl_content']['palettes']['sw_separator'] = '{type_legend},type,cssID';

\BackendUser::getInstance()->authenticate();
if(\BackendUser::getInstance()->useDragNDropUploader)
	foreach($GLOBALS['TL_DCA']['tl_content']['palettes'] as $pKey => &$palette)
		$palette = str_replace('singleSRC','multiSRC',$palette);
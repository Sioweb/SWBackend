<?php

/**
* Contao Open Source CMS
*  
* @file config.php
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.glossar
* @copyright Sascha Weidner, Sioweb
*/


if(TL_MODE == 'BE')
{
	require_once TL_ROOT . '/system/modules/SWBackend/config/icons/replacer.php';
	$GLOBALS['TL_HOOKS']['initializeSystem'][]  = array('SWBackend', 'sw_initialize');
}
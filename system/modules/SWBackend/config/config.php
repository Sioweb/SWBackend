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


/**
 * Back end modules
 */
$GLOBALS['TL_CTE']['texts']['sw_separator'] = 'ContentSeparator';
$GLOBALS['TL_WRAPPERS']['separator'][] = 'sw_separator';

$GLOBALS['BE_MOD']['content']['article']['tables'][] ='tl_page';

$GLOBALS['SWBackend']['fileTree'] = false;

if(TL_MODE == 'BE') 
{
	$GLOBALS['TL_HOOKS']['initializeSystem'][]  = array('Backend', 'sw_initialize');
	$GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('Backend', 'changeNavigation');
	$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Backend', 'extendFileTree');

	$GLOBALS['TL_JAVASCRIPT'][] = 'assets/sioweb/sioweb.min.js?sioweb=true&amp;request_token='.$_SESSION['REQUEST_TOKEN'];
	$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/SWBackend/assets/sioweb.js';
	$GLOBALS['TL_CSS'][] = 'system/modules/SWBackend/assets/sioweb.css';
	require_once TL_ROOT . '/system/modules/SWBackend/config/icons/replacer.php';
}

if($_POST['action'] == 'dragNdrop')
	$GLOBALS['TL_HOOKS']['executePostActions'][] = array('Backend','dragNdropUpload');

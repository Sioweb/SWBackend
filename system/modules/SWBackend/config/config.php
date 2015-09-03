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
  $GLOBALS['TL_HOOKS']['initializeSystem'][]  = array('SWBackend', 'sw_initialize');

$GLOBALS['TL_CTE']['texts']['sw_separator'] = 'ContentSeparator';
$GLOBALS['TL_WRAPPERS']['separator'][] = 'sw_separator';

// print_r($_SESSION['BE_DATA']['showHideArticles']);

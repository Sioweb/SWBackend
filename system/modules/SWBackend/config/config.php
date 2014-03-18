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
	$GLOBALS['TL_HOOKS']['initializeSystem'][]  = array('Backend', 'sw_initialize');
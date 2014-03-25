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

$GLOBALS['TL_DCA']['tl_content']['fields']['uploadSRC'] = $GLOBALS['TL_DCA']['tl_content']['fields']['singleSRC'];
$GLOBALS['TL_DCA']['tl_content']['fields']['uploadSRC']['eval']['files'] = false;
$GLOBALS['TL_DCA']['tl_content']['fields']['uploadSRC']['eval']['filesOnly'] = false;
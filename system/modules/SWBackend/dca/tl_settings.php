<?php

/**
* Contao Open Source CMS
* 
* @file tl_settings.php
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sascha
*/


/**
 * System configuration
 */
$semicolon = substr($GLOBALS['TL_DCA']['tl_settings']['palettes']['default'], -1, 1);
if($semicolon != ';')
	$semicolon = ';';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_settings']['palettes']['default'].$semicolon.'{sioweb_theme_settings},useSiowebTheme,doNotUseTheme,navigation_signet';

$GLOBALS['TL_DCA']['tl_settings']['fields']['useSiowebTheme'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['useSiowebTheme'],
	'default'                 => 1,
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_settings']['fields']['navigation_signet'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['navigation_signet'],
	'exclude'                 => true,
	'inputType'               => 'fileTree',
	'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
	'sql'                     => "binary(16) NULL"
);
$GLOBALS['TL_DCA']['tl_settings']['fields']['doNotUseTheme'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['doNotUseTheme'],
	'default'                 => 1,
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);
<?php

/**
* Contao Open Source CMS
* 
* @file tl_user.php
* @class tl_user
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/


$GLOBALS['TL_DCA']['tl_user']['list']['operations']['su']['button_callback'][0] = 'sw_user';

foreach($GLOBALS['TL_DCA']['tl_user']['palettes'] as $pKey => &$palette)
	$palette = str_replace('backendTheme','backendTheme,doNotUseTheme,useDragNDropUploader',$palette);

$GLOBALS['TL_DCA']['tl_user']['fields']['doNotUseTheme'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['doNotUseTheme'],
	'default'                 => 1,
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"

);$GLOBALS['TL_DCA']['tl_user']['fields']['useDragNDropUploader'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['useDragNDropUploader'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

class sw_user extends tl_user {

	function switchUser($row, $href, $label, $title, $icon) 
	{
		$link = parent::switchUser($row, $href, $label, $title, $icon);
		return preg_replace('/<a /','<a class="switchUser" ',$link);
	}
}
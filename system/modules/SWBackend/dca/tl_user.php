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

class sw_user extends tl_user {

	function switchUser($row, $href, $label, $title, $icon) 
	{
		$link = parent::switchUser($row, $href, $label, $title, $icon);
		return preg_replace('/<a /','<a class="switchUser" ',$link);
	}
}
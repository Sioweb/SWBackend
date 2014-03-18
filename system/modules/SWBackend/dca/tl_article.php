<?php

/**
* Contao Open Source CMS
* 
* @file tl_article.php
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/


unset($GLOBALS['TL_DCA']['tl_article']['list']['global_operations']['all']);

$GLOBALS['TL_DCA']['tl_article']['list']['global_operations']['allPages'] = array(
	'label'               => &$GLOBALS['TL_LANG']['MSC']['allPages'],
	'href'                => 'act=select&amp;use=tl_page',
	'class'               => 'header_edit_all',
	'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
);
$GLOBALS['TL_DCA']['tl_article']['list']['global_operations']['allArticles'] = array(
	'label'               => &$GLOBALS['TL_LANG']['MSC']['allArticles'],
	'href'                => 'act=select&amp;use=tl_article',
	'class'               => 'header_edit_all',
	'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
);
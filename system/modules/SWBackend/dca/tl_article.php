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

$GLOBALS['TL_DCA']['tl_article']['list']['global_operations']['toggleArticle'] = array(
  'label'               => &$GLOBALS['TL_LANG']['MSC']['toggleArticle'],
  'href'                => 'pta=all',
  'class'               => 'header_toggle',
  'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
);
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

$GLOBALS['TL_DCA']['tl_article']['list']['operations']['toggle']['button_callback'][0] = 'sw_article';

class sw_article extends tl_article
{ 
  public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
  {
    $Icon = parent::toggleIcon($row, $href, $label, $title, $icon, $attributes);
    $Icon = preg_replace('/toggleVisibility\(([a-zA-Z0-9$]+)\,([a-zA-Z0-9]+)\)/', "toggleVisibility($1,$2,'tl_article')",$Icon);
    return $Icon;
  }
}
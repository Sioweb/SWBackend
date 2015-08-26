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
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_settings']['palettes']['default'].$semicolon.'{sioweb_theme_settings},navigation_signet,useSiowebLoginTheme,showSignetForLogin,siowebFilemanager';

$GLOBALS['TL_DCA']['tl_settings']['fields']['useSiowebLoginTheme'] = array
(
  'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['useSiowebLoginTheme'],
  'default'                 => 1,
  'exclude'                 => true,
  'inputType'               => 'checkbox',
  'eval'                    => array('tl_class'=>'w50'),
  'sql'                     => "char(1) NOT NULL default '1'"
);
$GLOBALS['TL_DCA']['tl_settings']['fields']['showSignetForLogin'] = array
(
  'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['showSignetForLogin'],
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
);
$GLOBALS['TL_DCA']['tl_settings']['fields']['doNotUseTheme'] = array(
  'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['doNotUseTheme'],
  'default'                 => 1,
  'exclude'                 => true,
  'inputType'               => 'checkbox',
  'eval'                    => array('tl_class'=>'w50'),
  'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_settings']['fields']['siowebFilemanager'] = array(
  'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['siowebFilemanager'],
  'default'                 => 'title,link,caption',
  'exclude'                 => true,
  'inputType'               => 'text',
  'eval'                    => array('tl_class'=>'w50','nospace'=>true),
  'sql'                     => "text NULL"
);

class sw_settings {
  public function save_signet($strValue, DataContainer $dc)
  {
    return \String::binToUuid($strValue);
  }
}
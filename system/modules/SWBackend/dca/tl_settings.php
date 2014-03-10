<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * System configuration
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = '{backend_settings},navigation_signet;'.$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'];

$GLOBALS['TL_DCA']['tl_settings']['fields']['navigation_signet'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['navigation_signet'],
	'exclude'                 => true,
	'inputType'               => 'fileTree',
	'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
	'sql'                     => "binary(16) NULL"
);
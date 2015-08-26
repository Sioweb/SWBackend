<?php

/**
* Contao Open Source CMS
*  
* @file autoload.php
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
  'sioweb\contao\extensions\backend'
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
  // Classes
  'sioweb\contao\extensions\backend\Sioweb'       => 'system/modules/SWBackend/classes/Sioweb.php',
  'sioweb\contao\extensions\backend\SWBackend'      => 'system/modules/SWBackend/classes/SWBackend.php',

  //Library
  'Controller'                      => 'system/modules/SWBackend/library/sioweb/Controller.php',

  // Elements
  'sioweb\contao\extensions\backend\ContentImage'     => 'system/modules/SWBackend/elements/ContentImage.php',
  'sioweb\contao\extensions\backend\ContentGallery'   => 'system/modules/SWBackend/elements/ContentGallery.php',

  // Elements
  'sioweb\contao\extensions\backend\ContentSeparator'   => 'system/modules/SWBackend/elements/ContentSeparator.php',
));


\TemplateLoader::addFiles(array(
  'ce_separator'    => 'system/modules/SWBackend/templates/elements',
  'be_panel'      => 'system/modules/SWBackend/templates/backend/panel',
  'be_panel_default'  => 'system/modules/SWBackend/templates/backend/panel',

  'be_tree'     => 'system/modules/SWBackend/templates/backend/tree',
  'be_tree_buttons' => 'system/modules/SWBackend/templates/backend/tree',
  'be_tree_default' => 'system/modules/SWBackend/templates/backend/tree',
  'be_tree_childs'  => 'system/modules/SWBackend/templates/backend/tree',
  'be_tree_pages'   => 'system/modules/SWBackend/templates/backend/tree',
));
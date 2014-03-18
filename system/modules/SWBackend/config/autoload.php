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
	// Elements
	'sioweb\contao\extensions\backend\ContentSeparator'		=> 'system/modules/SWBackend/elements/ContentSeparator.php',

	// Classes
	'Backend'												=> 'system/modules/SWBackend/classes/Backend.php',
	
	// Drivers
	'sioweb\contao\extensions\backend\DC_Table'				=> 'system/modules/SWBackend/drivers/DC_Table.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_main'       	   => 'system/modules/SWBackend/templates/backend',
	'be_login'			   => 'system/modules/SWBackend/templates/backend',
	'be_maintenance'	   => 'system/modules/SWBackend/templates/backend',
	'dc_article'		   => 'system/modules/SWBackend/templates/drivers',
	'ce_separator'	  	   => 'system/modules/SWBackend/templates/elements',
));

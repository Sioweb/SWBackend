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
	'Backend'												=> 'system/modules/SWBackend/classes/Backend.php',
	'sioweb\contao\extensions\backend\SWBackend'			=> 'system/modules/SWBackend/classes/SWBackend.php',
	// Elements
	'sioweb\contao\extensions\backend\ContentSeparator'		=> 'system/modules/SWBackend/elements/ContentSeparator.php',
));

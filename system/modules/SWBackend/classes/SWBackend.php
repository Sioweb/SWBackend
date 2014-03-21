<?php

namespace sioweb\contao\extensions\backend;
use Contao;

/**
* Contao Open Source CMS
*  
* @file SWBackend.php
* @class SWBackend
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class SWBackend extends \Controller
{
	public function sw_initialize()
	{
		\BackendUser::getInstance()->authenticate();
		if((\BackendUser::getInstance()->id && \BackendUser::getInstance()->backendTheme != 'sioweb' && (!\BackendUser::getInstance()->doNotUseTheme && !$GLOBALS['TL_CONFIG']['doNotUseTheme'])) || (!\BackendUser::getInstance()->id && !$GLOBALS['TL_CONFIG']['useSiowebTheme']))
			return;

		define('TL_FILES_URL','');
		define('TL_ASSETS_URL','');
		
		\ClassLoader::addClasses(array(
			// Classes
			'Backend'												=> 'system/modules/SWBackend/classes/Backend.php',
			'sioweb\contao\extensions\backend\DC_Table'				=> 'system/modules/SWBackend/drivers/DC_Table.php',
			'sioweb\contao\extensions\backend\DC_Folder'			=> 'system/modules/SWBackend/drivers/DC_Folder.php',
		));

		if(!\BackendUser::getInstance()->doNotUseTheme && !$GLOBALS['TL_CONFIG']['doNotUseTheme'])
			\TemplateLoader::addFiles(array(
				'be_main'			=> 'system/modules/SWBackend/templates/backend',
				'be_login'			=> 'system/modules/SWBackend/templates/backend',
				'be_maintenance'	=> 'system/modules/SWBackend/templates/backend',
				'dc_article'		=> 'system/modules/SWBackend/templates/drivers',
			));

		if($GLOBALS['TL_CONFIG']['navigation_signet'])
		{
			$File = \FilesModel::findBy('uuid',$GLOBALS['TL_CONFIG']['navigation_signet']);
			if($File)
			{
				$arrImage = array(
					'singleSRC' => $File->path,
					'alt' => '',
					'size' => 'a:3:{i:0;s:0:"";i:1;s:0:"";i:2;s:12:"proportional";}',
					'imagemargin' => 'a:5:{s:6:"bottom";s:0:"";s:4:"left";s:0:"";s:5:"right";s:0:"";s:3:"top";s:0:"";s:4:"unit";s:0:"";}',
					'floating' => '',
					'caption' => '',
					'fullsize' => '',
					'imageUrl' => ''
				);
				$obj = new \stdClass();
				$this->addImageToTemplate($obj,$arrImage);
				
				$GLOBALS['TL_CONFIG']['navigation_signet_transformed'] = (array)$obj;
			}
		}

		/* Config.php */
		$GLOBALS['BE_MOD']['content']['article']['tables'][] ='tl_page';
		$GLOBALS['SWBackend']['fileTree'] = false;

		$GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('Backend', 'changeNavigation');
		$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Backend', 'extendFileTree');

		$GLOBALS['TL_JAVASCRIPT'][] = 'assets/sioweb/sioweb.min.js?sioweb=true&amp;request_token='.$_SESSION['REQUEST_TOKEN'];
		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/SWBackend/assets/sioweb.js';
		$GLOBALS['TL_CSS'][] = 'system/modules/SWBackend/assets/sioweb.css';

		if(\Input::post('action') == 'dragNdrop')
			$GLOBALS['TL_HOOKS']['executePostActions'][] = array('Backend','dragNdropUpload');
		/* !config.php */

	}
}

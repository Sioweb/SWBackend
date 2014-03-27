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

class SWBackend extends Sioweb
{
	public function sw_initialize()
	{
		$this->getBackendUser();
		if((\BackendUser::getInstance()->id && \BackendUser::getInstance()->backendTheme != 'sioweb' && !\BackendUser::getInstance()->doNotUseTheme) || (!\BackendUser::getInstance()->id && !$GLOBALS['TL_CONFIG']['useSiowebTheme']))
			return;

		if(\Input::get('do') != 'settings')
			$this->fileManagerSettings();

		define('TL_FILES_URL','');
		define('TL_ASSETS_URL','');

		\ClassLoader::addClasses(array(
			// Classes
			'Backend'												=> 'system/modules/SWBackend/classes/Backend.php',
			'sioweb\contao\extensions\backend\DC_Table'				=> 'system/modules/SWBackend/drivers/DC_Table.php',
			'sioweb\contao\extensions\backend\DC_Folder'			=> 'system/modules/SWBackend/drivers/DC_Folder.php',

		));

		if(!\BackendUser::getInstance()->doNotUseTheme && !$GLOBALS['TL_CONFIG']['doNotUseTheme'])
		{
			$this->doNotUseTheme();
		}
		else
		{
			\TemplateLoader::addFiles(array(
				'be_main'			=> 'system/modules/SWBackend/templates/noTheme/backend',
			));
			$GLOBALS['TL_CSS'][] = 'system/modules/SWBackend/assets/main.css';
		}

		$this->loadSignet();

		/* Config.php */
		$GLOBALS['BE_MOD']['content']['article']['tables'][] ='tl_page';
		$GLOBALS['SWBackend']['fileTree'] = false;

		if(!\BackendUser::getInstance()->doNotUseTheme && !$GLOBALS['TL_CONFIG']['doNotUseTheme'])
		{
			$GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('Backend', 'changeNavigation');
			$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Backend', 'extendFileTree');
		}
		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/SWBackend/assets/core.js';
		$GLOBALS['TL_JAVASCRIPT'][] = 'assets/sioweb/sioweb-0.8.5.js';
		if(\BackendUser::getInstance()->useDragNDropUploader != false)
			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/SWBackend/assets/dragAndDrop.js';
		$GLOBALS['TL_CSS'][] = 'system/modules/SWBackend/assets/sioweb.css';

		if(\Input::post('FORM_SUBMIT') === 'tl_upload' && \Input::post('isAjaxRequest') === '1')
		{
			$GLOBALS['TL_HOOKS']['postUpload'][] = array('Backend','dragNdropUpload');
		}
		/* !config.php */

	}

	private function doNotUseTheme()
	{
		\TemplateLoader::addFiles(array(
			'be_main'			=> 'system/modules/SWBackend/templates/backend',
			'be_login'			=> 'system/modules/SWBackend/templates/backend',
			'be_maintenance'	=> 'system/modules/SWBackend/templates/backend',
			'dc_article'		=> 'system/modules/SWBackend/templates/drivers',
		));

		if(\BackendUser::getInstance()->useDragNDropUploader != false)
		{
			\ClassLoader::addClasses(array(
				// Widgets
				'FileTree' => 'system/modules/SWBackend/widgets/FileTree.php'
			));
		}
		if(\BackendUser::getInstance()->useSiowebFilemanager != false)
		{
			$this->useFilemanager();
		}
	}

	protected function fileManagerSettings()
	{
		if(!$GLOBALS['TL_CONFIG']['siowebFilemanager'])
		{
			$FileManager = 'title,link,caption';
			$this->Config->update('$GLOBALS[\'TL_CONFIG\'][\'siowebFilemanager\']',$FileManager);
		}
		else
			$FileManager = $GLOBALS['TL_CONFIG']['siowebFilemanager'];

		$arrFM = array();
		$FileManager = explode(',',str_replace(' ','',$FileManager));
		foreach($FileManager as $fKey => $field)
			$arrFM[$field] = '';

		$GLOBALS['TL_CONFIG']['siowebFilemanager'] = $arrFM;
		return $arrFM;
	}

	private function useFilemanager()
	{
		\TemplateLoader::addFiles(array(
			'be_filemanager' => 'system/modules/SWBackend/templates/backend',
		));

		\ClassLoader::addClasses(array(
			// Widgets
			'MetaWizard' => 'system/modules/SWBackend/widgets/MetaWizard.php',

			//Classes
			'sioweb\contao\extensions\backend\FileManager' => 'system/modules/SWBackend/classes/FileManager.php',
		));
		
		$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Backend', 'loadTlFiles');

		$GLOBALS['BE_MOD']['system']['files']['createField'] = array('FileManager', 'createField');
	}

	private function loadSignet()
	{
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
	}
}
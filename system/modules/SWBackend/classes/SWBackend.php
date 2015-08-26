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

class SWBackend extends Sioweb {
  protected $BackendUser = false;
  protected $isSiowebTheme = false;

  public function sw_initialize() {
    $this->tl_version = (VERSION < 3.3?'3.1/':'');
    $this->tl_version = (VERSION < 3.4 && VERSION >= 3.3?'3.3/':'');
    if(strpos(\Environment::get('phpSelf'),'install.php') !== false)
      return;

    $GLOBALS['TL_HOOKS']['executePostActions'][] = array('Backend','swExecutePostActions');

    $this->getBackendUser();
    $this->BackendUser = \BackendUser::getInstance();
    #if((\BackendUser::getInstance()->id && \BackendUser::getInstance()->backendTheme != 'sioweb' && !\BackendUser::getInstance()->doNotUseTheme) || (!\BackendUser::getInstance()->id && !$GLOBALS['TL_CONFIG']['useSiowebTheme']))
    # return;
    $this->isSiowebTheme = ($this->BackendUser->backendTheme == 'sioweb');

    $this->loadBasics();

    if($this->BackendUser->mergeSitesAndArticles)
      $this->mergeSitesAndArticles();

    if($this->BackendUser->showSignetInNavi || $GLOBALS['TL_CONFIG']['showSignetForLogin'])
      $this->loadSignet();

    if($this->BackendUser->useDragNDropUploader && \Input::get('do') != 'settings')
      $this->loadDragNDropUploader();

    if($this->BackendUser->useSiowebFilemanager && \Input::get('do') != 'settings') {
      $this->fileManagerSettings();
      $this->useFilemanager();
    }

    if($this->BackendUser->useFastTheme)
      $GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('Sioweb', 'changeNavigation');
  }

  private function loadBasics() {
    define('TL_FILES_URL','');
    define('TL_ASSETS_URL','');

    \ClassLoader::addClasses(array(
      // Classes
      'Backend' => 'system/modules/SWBackend/classes/Backend.php',
    ));

    \TemplateLoader::addFiles(array(
      'be_maintenance'  => 'system/modules/SWBackend/templates/backend',
      'dc_article'    => 'system/modules/SWBackend/templates/drivers',
    ));

    if($GLOBALS['TL_CONFIG']['useSiowebLoginTheme'])
      \TemplateLoader::addFiles(array(
        'be_login'      => 'system/modules/SWBackend/templates/backend',
      ));

    if(
      (
        $this->BackendUser->showSignetInNavi ||
        $this->BackendUser->useFastTheme ||
        $this->BackendUser->useDragNDropUploader
      ) && !$this->isSiowebTheme
    ) {
      \TemplateLoader::addFiles(array(
        'be_main'     => 'system/modules/SWBackend/templates/'.$this->tl_version.'noTheme/backend',
      ));
      $GLOBALS['TL_CSS'][] = 'system/modules/SWBackend/assets/main.css';
    }
    
    if($this->isSiowebTheme) {
      \TemplateLoader::addFiles(array(
        'be_main' => 'system/modules/SWBackend/templates/backend',
      ));
    }

    $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/SWBackend/assets/core.js';
    $GLOBALS['TL_JAVASCRIPT'][] = 'assets/sioweb/sioweb-0.8.5.js';

    $GLOBALS['TL_CSS'][] = 'system/modules/SWBackend/assets/sioweb.css';
  }

  private function loadDragNDropUploader() {
    \ClassLoader::addClasses(array(
      // Widgets
      'FileTree' => 'system/modules/SWBackend/widgets/'.(VERSION < 3.3?'3.1/':'').'FileTree.php'
    ));
    $GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Sioweb', 'extendFileTree');
    $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/SWBackend/assets/dragAndDrop.js';
  }

  private function mergeSitesAndArticles() {
    $GLOBALS['BE_MOD']['content']['article']['tables'][] ='tl_page';
    $GLOBALS['SWBackend']['fileTree'] = false;

    \ClassLoader::addClasses(array(
      // Classes
      'sioweb\contao\extensions\backend\DC_File'        => 'system/modules/SWBackend/drivers/DC_File.php',
      'sioweb\contao\extensions\backend\DC_Table'       => 'system/modules/SWBackend/drivers/DC_Table.php',
      'sioweb\contao\extensions\backend\DC_Folder'      => 'system/modules/SWBackend/drivers/DC_Folder.php',
    ));
  }

  protected function fileManagerSettings() {
    if(!$GLOBALS['TL_CONFIG']['siowebFilemanager']) {
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

  private function useFilemanager() {
    \TemplateLoader::addFiles(array(
      'be_filemanager' => 'system/modules/SWBackend/templates/backend',
    ));

    \ClassLoader::addClasses(array(
      // Widgets
      'MetaWizard' => 'system/modules/SWBackend/widgets/MetaWizard.php',

      //Classes
      'sioweb\contao\extensions\backend\FileManager' => 'system/modules/SWBackend/classes/FileManager.php',
    ));
    
    $GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Sioweb', 'loadTlFiles');

    $GLOBALS['BE_MOD']['system']['files']['createField'] = array('FileManager', 'createField');
  }

  private function loadSignet() {
    if($GLOBALS['TL_CONFIG']['navigation_signet']) {
      $File = \FilesModel::findByUuid($GLOBALS['TL_CONFIG']['navigation_signet']);
      if($File) {
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
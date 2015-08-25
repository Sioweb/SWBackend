<?php

namespace sioweb\contao\extensions\backend;
use Contao;

/**
* Contao Open Source CMS
*  
* @file FileManager.php
* @class FileManager
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class FileManager extends \Sioweb
{
  public function createField()
  {
    $this->getBackendUser();
    $fileManager = new \BackendTemplate('be_filemanager');

    if(\BackendUser::getInstance()->useSiowebFilemanager != false)
      $fileManager->fields = $GLOBALS['TL_CONFIG']['siowebFilemanager'];

    $fileManager->field = \Input::get('id');

    return $fileManager->parse();
  }
}
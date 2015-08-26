<?php


/**
* Contao Open Source CMS
*  
* @file Controller.php
* @class Controller
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class Controller extends \Contao\Controller
{

  public static function addImageToTemplate($objTemplate, $arrItem, $intMaxWidth=null, $strLightboxId=null)
  {
    parent::addImageToTemplate($objTemplate, $arrItem, $intMaxWidth, $strLightboxId);
    #echo '<pre>'.print_r($arrItem,1).'</pre>';
  }
}

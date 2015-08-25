<?php

namespace sioweb\contao\extensions\backend;
use contao;

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

class ContentImage extends \ContentElement
{

  /**
   * Template
   * @var string
   */
  protected $strTemplate = 'ce_image';


  /**
   * Return if the image does not exist
   * @return string
   */
  public function generate()
  {
    if ($this->singleSRC == '')
    {
      return '';
    }

    $objFile = \FilesModel::findByUuid($this->singleSRC);

    if ($objFile === null)
    {
      if (!\Validator::isUuid($this->singleSRC))
      {
        return '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
      }

      return '';
    }

    if (!is_file(TL_ROOT . '/' . $objFile->path))
    {
      return '';
    }

    foreach((array)deserialize($objFile->meta) as $lKey => $lang)
      if($lKey == $GLOBALS['TL_LANGUAGE'])
        foreach ($lang as $mKey => $meta) {
          $this->$mKey = $meta;
        }

    $this->singleSRC = $objFile->path;
    return parent::generate();
  }


  /**
   * Generate the content element
   */
  protected function compile()
  {
    $this->addImageToTemplate($this->Template, $this->arrData);
  }
}

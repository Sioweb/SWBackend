<?php

namespace sioweb\contao\extensions\backend;
use Contao;

/**
* Contao Open Source CMS
*/

/**
 * @file SWAjax.php
 * @class SWAjax
 * @author Sascha Weidner
 * @version 3.0.0
 * @package sioweb.contao.extensions.backend
 * @copyright Sascha Weidner, Sioweb
*/

class SWAjax {
  public function executePostActions($strAction = false) {
    switch($strAction) {
      case 'toggleNextArticles':
      echo 'Hallo';
      break;
    }
  }
}
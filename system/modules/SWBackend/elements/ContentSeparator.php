<?php

namespace sioweb\contao\extensions\backend;

/**
* Contao Open Source CMS
* 
* @file ContentSeparator.php
* @class ContentSeparator
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class ContentSeparator extends \ContentElement
{
	protected $strTemplate = 'ce_separator';

	public function generate()
	{
		return parent::generate();
	}
	protected function compile() {

	}
}
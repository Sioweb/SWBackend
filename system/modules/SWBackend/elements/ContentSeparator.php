<?php

/*
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 */

namespace sioweb\contao\extensions\backend;
use Contao;

/**
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
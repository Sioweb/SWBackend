<?php

namespace sioweb\contao\extensions\backend;
use Contao;

/**
* Contao Open Source CMS
*  
* @file Sioweb.php
* @class Sioweb
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

if(!class_exists('Sioweb'))
{
class Sioweb extends \Controller
{
	protected function getBackendUser()
	{
		\BackendUser::getInstance()->authenticate();
	}
}
}
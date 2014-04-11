<?php

/**
* Contao Open Source CMS
*  
* @file Backend.php
* @class Backend
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class Backend extends \Contao\Backend
{
	/**
	 * Open a back end module and return it as HTML
	 * @param string
	 * @return string
	 */
	protected function getBackendModule($module)
	{
		$arrModule = array();

		foreach ($GLOBALS['BE_MOD'] as &$arrGroup)
		{
			if (isset($arrGroup[$module]))
			{
				$arrModule =& $arrGroup[$module];
				break;
			}
		}

		$arrInactiveModules = \ModuleLoader::getDisabled();

		// Check whether the module is active
		if (is_array($arrInactiveModules) && in_array($module, $arrInactiveModules))
		{
			$this->log('Attempt to access the inactive back end module "' . $module . '"', __METHOD__, TL_ACCESS);
			$this->redirect('contao/main.php?act=error');
		}

		$this->import('BackendUser', 'User');

		// Dynamically add the "personal data" module (see #4193)
		if (\Input::get('do') == 'login')
		{
			$arrModule = array('tables'=>array('tl_user'), 'callback'=>'ModuleUser');
		}

		// Check whether the current user has access to the current module
		elseif ($module != 'undo' && !$this->User->isAdmin && !$this->User->hasAccess($module, 'modules'))
		{
			$this->log('Back end module "' . $module . '" was not allowed for user "' . $this->User->username . '"', __METHOD__, TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$strTable = \Input::get('table') ?: $arrModule['tables'][0];
		if(\Input::get('use') && \Input::get('table') == '')
			$strTable = \Input::get('use');
		$id = ((!\Input::get('act') && \Input::get('id')) || (\Input::get('use') && \Input::get('table') == '')) ? \Input::get('id') : $this->Session->get('CURRENT_ID');

		/*
		$strTable = \Input::get('table') ?: $arrModule['tables'][0];
		$id = (!\Input::get('act') && \Input::get('id')) ? \Input::get('id') : $this->Session->get('CURRENT_ID');
		/**/

		// Store the current ID in the current session
		if ($id != $this->Session->get('CURRENT_ID'))
		{
			$this->Session->set('CURRENT_ID', $id);
			#$this->reload();
		}
		define('CURRENT_ID', (\Input::get('table') ? $id : \Input::get('id')));
		$this->Template->headline = $GLOBALS['TL_LANG']['MOD'][$module][0];

		// Add the module style sheet
		if (isset($arrModule['stylesheet']))
		{
			foreach ((array) $arrModule['stylesheet'] as $stylesheet)
			{
				$GLOBALS['TL_CSS'][] = $stylesheet;
			}
		}

		// Add module javascript
		if (isset($arrModule['javascript']))
		{
			foreach ((array) $arrModule['javascript'] as $javascript)
			{
				$GLOBALS['TL_JAVASCRIPT'][] = $javascript;
			}
		}

		$dc = null;

		// Redirect if the current table does not belong to the current module
		if ($strTable != '')
		{
			if (!in_array($strTable, (array)$arrModule['tables']))
			{
				$this->log('Table "' . $strTable . '" is not allowed in module "' . $module . '"', __METHOD__, TL_ERROR);
				$this->redirect('contao/main.php?act=error');
			}

			// Load the language and DCA file
			\System::loadLanguageFile($strTable);
			$this->loadDataContainer($strTable);

			// Include all excluded fields which are allowed for the current user
			if ($GLOBALS['TL_DCA'][$strTable]['fields'])
			{
				foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $k=>$v)
				{
					if ($v['exclude'])
					{
						if ($this->User->hasAccess($strTable.'::'.$k, 'alexf'))
						{
							if ($strTable == 'tl_user_group')
							{
								$GLOBALS['TL_DCA'][$strTable]['fields'][$k]['orig_exclude'] = $GLOBALS['TL_DCA'][$strTable]['fields'][$k]['exclude'];
							}

							$GLOBALS['TL_DCA'][$strTable]['fields'][$k]['exclude'] = false;
						}
					}
				}
			}

			// Fabricate a new data container object
			if ($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] == '')
			{
				$this->log('Missing data container for table "' . $strTable . '"', __METHOD__, TL_ERROR);
				trigger_error('Could not create a data container object', E_USER_ERROR);
			}

			$dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
			$dc = new $dataContainer($strTable, $arrModule);
		}

		// AJAX request
		if ($_POST && \Environment::get('isAjaxRequest'))
		{
			$this->objAjax->executePostActions($dc);
		}

		// Trigger the module callback
		elseif (class_exists($arrModule['callback']))
		{
			$objCallback = new $arrModule['callback']($dc);
			$this->Template->main .= $objCallback->generate();
		}

		// Custom action (if key is not defined in config.php the default action will be called)
		elseif (\Input::get('key') && isset($arrModule[\Input::get('key')]))
		{
			$objCallback = new $arrModule[\Input::get('key')][0]();
			$this->Template->main .= $objCallback->$arrModule[\Input::get('key')][1]($dc);

			// Add the name of the parent element
			if (isset($_GET['table']) && in_array(\Input::get('table'), $arrModule['tables']) && \Input::get('table') != $arrModule['tables'][0])
			{
				if ($GLOBALS['TL_DCA'][$strTable]['config']['ptable'] != '')
				{
					$objRow = $this->Database->prepare("SELECT * FROM " . $GLOBALS['TL_DCA'][$strTable]['config']['ptable'] . " WHERE id=?")
											 ->limit(1)
											 ->execute(CURRENT_ID);

					if ($objRow->title != '')
					{
						$this->Template->headline .= ' » ' . $objRow->title;
					}
					elseif ($objRow->name != '')
					{
						$this->Template->headline .= ' » ' . $objRow->name;
					}
				}
			}

			// Add the name of the submodule
			$this->Template->headline .= ' » ' . sprintf($GLOBALS['TL_LANG'][$strTable][\Input::get('key')][1], \Input::get('id'));
		}

		// Default action
		elseif (is_object($dc))
		{
			$act = \Input::get('act');

			if ($act == '' || $act == 'paste' || $act == 'select')
			{
				$act = ($dc instanceof \listable) ? 'showAll' : 'edit';
			}

			switch ($act)
			{
				case 'delete':
				case 'show':
				case 'showAll':
				case 'undo':
					if (!$dc instanceof \listable)
					{
						$this->log('Data container ' . $strTable . ' is not listable', __METHOD__, TL_ERROR);
						trigger_error('The current data container is not listable', E_USER_ERROR);
					}
					break;

				case 'create':
				case 'cut':
				case 'cutAll':
				case 'copy':
				case 'copyAll':
				case 'move':
				case 'edit':
					if (!$dc instanceof \editable)
					{
						$this->log('Data container ' . $strTable . ' is not editable', __METHOD__, TL_ERROR);
						trigger_error('The current data container is not editable', E_USER_ERROR);
					}
					break;
			}

			// Correctly add the theme name in the style sheets module
			if (strncmp(\Input::get('table'), 'tl_style', 8) === 0)
			{
				if (\Input::get('table') == 'tl_style_sheet' || !isset($_GET['act']))
				{
					$objRow = $this->Database->prepare("SELECT name FROM tl_theme WHERE id=(SELECT pid FROM tl_style_sheet WHERE id=?)")
											 ->limit(1)
											 ->execute(\Input::get('id'));

					$this->Template->headline .= ' » ' . $objRow->name;
					$this->Template->headline .= ' » ' . $GLOBALS['TL_LANG']['MOD']['tl_style'];

					if (\Input::get('table') == 'tl_style')
					{
						$objRow = $this->Database->prepare("SELECT name FROM tl_style_sheet WHERE id=?")
												 ->limit(1)
												 ->execute(CURRENT_ID);

						$this->Template->headline .= ' » ' . $objRow->name;
					}
				}
				elseif (\Input::get('table') == 'tl_style')
				{
					$objRow = $this->Database->prepare("SELECT name FROM tl_theme WHERE id=(SELECT pid FROM tl_style_sheet WHERE id=(SELECT pid FROM tl_style WHERE id=?))")
											 ->limit(1)
											 ->execute(\Input::get('id'));

					$this->Template->headline .= ' » ' . $objRow->name;
					$this->Template->headline .= ' » ' . $GLOBALS['TL_LANG']['MOD']['tl_style'];

					$objRow = $this->Database->prepare("SELECT name FROM tl_style_sheet WHERE id=?")
											 ->limit(1)
											 ->execute(CURRENT_ID);

					$this->Template->headline .= ' » ' . $objRow->name;
				}
			}
			else
			{
				// Add the name of the parent element
				if (\Input::get('table') && in_array(\Input::get('table'), $arrModule['tables']) && \Input::get('table') != $arrModule['tables'][0])
				{
					if ($GLOBALS['TL_DCA'][$strTable]['config']['ptable'] != '')
					{
						$objRow = $this->Database->prepare("SELECT * FROM " . $GLOBALS['TL_DCA'][$strTable]['config']['ptable'] . " WHERE id=?")
												 ->limit(1)
												 ->execute(CURRENT_ID);

						if ($objRow->title != '')
						{
							$this->Template->headline .= ' » ' . $objRow->title;
						}
						elseif ($objRow->name != '')
						{
							$this->Template->headline .= ' » ' . $objRow->name;
						}
					}
				}

				// Add the name of the submodule
				if (\Input::get('table') && isset($GLOBALS['TL_LANG']['MOD'][\Input::get('table')]))
				{
					$this->Template->headline .= ' » ' . $GLOBALS['TL_LANG']['MOD'][\Input::get('table')];
				}
			}

			// Add the current action
			if (\Input::get('act') == 'editAll')
			{
				$this->Template->headline .= ' » ' . $GLOBALS['TL_LANG']['MSC']['all'][0];
			}
			elseif (\Input::get('act') == 'overrideAll')
			{
				$this->Template->headline .= ' » ' . $GLOBALS['TL_LANG']['MSC']['all_override'][0];
			}
			elseif (is_array($GLOBALS['TL_LANG'][$strTable][$act]) && \Input::get('id'))
			{
				if (\Input::get('do') == 'files')
				{
					$this->Template->headline .= ' » ' . \Input::get('id');
				}
				else
				{
					$this->Template->headline .= ' » ' . sprintf($GLOBALS['TL_LANG'][$strTable][$act][1], \Input::get('id'));
				}
			}

			return $dc->$act();
		}

		return null;
	}

	public function dragNdropUpload($arrUploaded)
	{
		$arrPath = array();
		foreach($arrUploaded as $fKey => $file)
		{
			$file = pathinfo($file);
			$arrPath[] = $file['dirname'].'/'.$file['basename'];
		}
		$fileObj = \FilesModel::findMultipleByPaths($arrPath);
		if($fileObj)
		{
			$arrFiles = array();
			while($fileObj->next())
			{
				$objFile = new \File($fileObj->path, true);
				$Image = \Image::getHtml(\Image::get($fileObj->path, 80, 60, 'center_center'), '', 'class="gimage"');
				$fileObj->uuid = \String::binToUuid($fileObj->uuid);
				$fileObj->img = $Image;

			}
			echo json_encode(array('fieldName'=>\Input::post('fieldName'), 'images'=>$fileObj->fetchAll()));
		}
	}

	/**
	 * Compile buttons from the table configuration array and return them as HTML
	 * @param array
	 * @param string
	 * @param array
	 * @param boolean
	 * @param array
	 * @param integer
	 * @param integer
	 * @return string
	 */
	protected function generateIcon($arrRow, $strTable, $do, $act)
	{
		if(!$act)
			return '';

		$v = is_array($GLOBALS['TL_DCA'][$strTable]['list']['operations'][$act]) ? $GLOBALS['TL_DCA'][$strTable]['list']['operations'][$act] : array($GLOBALS['TL_DCA'][$strTable]['list']['operations'][$act]);
		$id = specialchars(rawurldecode($arrRow['id']));

		$label = $v['label'][0] ?: $act;
		$title = sprintf($v['label'][1] ?: $act, $id);
		$attributes = ($v['attributes'] != '') ? ' ' . ltrim(sprintf($v['attributes'], $id, $id)) : '';

		$return = '';
		// Add the key as CSS class
		if (strpos($attributes, 'class="') !== false)
			$attributes = str_replace('class="', 'class="' . $act . ' ', $attributes);
		else
			$attributes = ' class="' . $act . '"' . $attributes;


		$v['href'] = ($v['href'] ? 'do='.$do.'&amp;'.$v['href'].'&amp;id='.$arrRow['id'].
			(strpos($v['href'],'act=') === false ? '&amp;act=' : '' ) .
			(strpos($v['href'],'table=') === false ? '&amp;table=' : '' ) .
			(strpos($v['href'],'use=') === false ? '&amp;use=' : '' )
			: 'do='.$de.'&amp;act='.$act.'&amp;id='.$arrRow['id']);

		// Call a custom function instead of using the default button
		if (is_array($v['button_callback']))
		{
			$this->import($v['button_callback'][0]);

			return trim($this->$v['button_callback'][0]->$v['button_callback'][1]($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strTable));
		}
		elseif (is_callable($v['button_callback']))
		{
			return trim($v['button_callback']($arrRow, 'do='.$do.'&amp;'.$v['href'], $label, $title, $v['icon'], $attributes, $strTable));
		}

		if ($k != 'move' && $v != 'move')
		{
			if ($k == 'show')
				return trim('<a href="'.$this->addToUrl($v['href'].'&amp;popup=1',1).'" title="'.specialchars($title).'" onclick="Backend.openModalIframe({\'width\':765,\'title\':\''.specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG'][$strTable]['show'][1], $arrRow['id']))).'\',\'url\':this.href});return false"'.$attributes.'>'.\Image::getHtml($v['icon'], $label).'</a> ');
			else
				return trim('<a href="'.$this->addToUrl($v['href'],1).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($v['icon'], $label).'</a>');
		}
	}
}
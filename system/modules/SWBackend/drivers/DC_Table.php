<?php

/*
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 */

namespace sioweb\contao\extensions\backend;

/**
* @class DC_Table
* @file DC_Table.php
* @author Sascha Weidner
* @version 3.1.0
* @package kd.contao.extensions.backend
* @copyright Sascha Weidner, Sioweb
*/

class DC_Table extends \Contao\DC_Table
{
	/**
	 * Initialize the object
	 * @param string
	 * @param array
	 * @throws \Exception
	 */
	public function __construct($strTable, $arrModule=array())
	{
		// Die Tabelle wird manchmal überschrieben ... es ist 23.42 Uhr und es war irgendwas mit kopieren und deaktivieren...
		$use = \Input::get('use');
		if(\Input::get('table') == '' && $use != '' && $strTable != $use)
		{
			#$GLOBALS['TL_DCA'][$use]['list']['label']['format'] = $GLOBALS['TL_DCA'][$strTable]['list']['label']['format'];
			$strTable = $use;
		}
		parent::__construct($strTable, $arrModule);
	}

	/** /

	public static function getReferer($blnEncodeAmpersands=false, $strTable=null)
	{
		return parent::getReferer($blnEncodeAmpersands, $strTable);
	}
	
	/**/
	protected function reviseTable()
	{
		$table = $this->strTable;
		parent::reviseTable();
		if(\Input::get('do') == 'article')
		{
			$this->strTable = 'tl_page';
			parent::reviseTable();
			$this->strTable = $table;
		}
	}
	/**/


	/**
	 * Build the sort panel and return it as string
	 * @return string
	 * Überschrieben um ein DIV anzuhängen...
	 */
	protected function panel()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] == '')
		{
			return '';
		}

		$intFilterPanel = 0;
		$arrPanels = array();

		foreach (trimsplit(';', $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout']) as $strPanel)
		{
			$panels = '';
			$arrSubPanels = trimsplit(',', $strPanel);

			foreach ($arrSubPanels as $strSubPanel)
			{
				$panel = '';

				// Regular panels
				if ($strSubPanel == 'search' || $strSubPanel == 'limit' || $strSubPanel == 'sort')
				{
					$panel = $this->{$strSubPanel . 'Menu'}();
				}

				// Multiple filter subpanels can be defined to split the fields across panels
				elseif ($strSubPanel == 'filter')
				{
					$panel = $this->{$strSubPanel . 'Menu'}(++$intFilterPanel);
				}

				// Call the panel_callback
				else
				{
					$arrCallback = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panel_callback'][$strSubPanel];

					if (is_array($arrCallback))
					{
						$this->import($arrCallback[0]);
						$panel = $this->$arrCallback[0]->$arrCallback[1]($this);
					}
					elseif (is_callable($arrCallback))
					{
						$panel = $arrCallback($this);
					}
				}

				// Add the panel if it is not empty
				if ($panel != '')
				{
					$panels = $panel . $panels;
				}
			}

			// Add the group if it is not empty
			if ($panels != '')
			{
				$arrPanels[] = $panels;
			}
		}

		if (empty($arrPanels))
		{
			return '';
		}

		if (\Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			$this->reload();
		}

		$return = '';
		$intTotal = count($arrPanels);
		$intLast = $intTotal - 1;

		for ($i=0; $i<$intTotal; $i++)
		{
			$submit = '';

			if ($i == $intLast)
			{
				$submit = '

<div class="tl_submit_panel tl_subpanel">
<input type="image" name="filter" id="filter" src="' . TL_FILES_URL . 'system/themes/' . \Backend::getTheme() . '/images/reload.gif" class="tl_img_submit" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '" alt="' . specialchars($GLOBALS['TL_LANG']['MSC']['apply']) . '">
</div>';
			}

			$return .= '
<div class="tl_panel">' . $submit . $arrPanels[$i] . '

<div class="clear"></div>

</div>';
		}

		$return = '
<form action="'.ampersand(\Environment::get('request'), true).'" class="tl_form sw_'.$this->strTable.'" method="post">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_filters">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
' . $return . '
</div>
<div class="tl_opener">'.$GLOBALS['TL_LANG']['MSC']['open_panels'].'</div>
</form>
';

		return $return;
	}
	
	/* ARTIKEL-ANSICHT */
	
	


	/**
	 * List all records of the current table as tree and return them as HTML string
	 * @return string
	 * FUCK SPAGETTICODE D:
	 *
	 * useTable = Tabelle die in Clipboard etc. verwendet werden soll.
	 */
	protected function treeView()
	{
		$table = $this->strTable;
		$useTable = $this->strTable;
		
		$treeClass = 'tl_tree';
		if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6)
		{
			$table = $this->ptable;
			$treeClass = 'tl_tree_xtnd';

			\System::loadLanguageFile($table);
			$this->loadDataContainer($table);
		}

		$session = $this->Session->getData();

		// Toggle the nodes
		if (\Input::get('ptg') == 'all')
		{
			$node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->strTable.'_'.$table.'_tree' : $this->strTable.'_tree';

			// Expand tree
			if (!is_array($session[$node]) || empty($session[$node]) || current($session[$node]) != 1)
			{
				$session[$node] = array();
				$objNodes = $this->Database->execute("SELECT DISTINCT pid FROM " . $table . " WHERE pid>0");

				while ($objNodes->next())
				{
					$session[$node][$objNodes->pid] = 1;
				}
			}

			// Collapse tree
			else
			{
				$session[$node] = array();
			}

			$this->Session->setData($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', \Environment::get('request')));
		}

		// Return if a mandatory field (id, pid, sorting) is missing
		if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && (!$this->Database->fieldExists('id', $table) || !$this->Database->fieldExists('pid', $table) || !$this->Database->fieldExists('sorting', $table)))
		{
			return '
<p class="tl_empty">strTable "'.$table.'" can not be shown as tree!</p>';
		}

		// Return if there is no parent table
		if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6 && !strlen($this->ptable))
		{
			return '
<p class="tl_empty">Table "'.$table.'" can not be shown as extended tree!</p>';
		}

		$blnClipboard = false;
		$arrClipboard = $this->Session->get('CLIPBOARD');

		#echo '<pre>'.print_r($arrClipboard,1).'</pre>';

		// Check the clipboard
		if (!empty($arrClipboard[$useTable]))
		{
			$blnClipboard = true;
			$arrClipboard = $arrClipboard[$useTable];
		}

		#echo '<pre>'.print_r($arrClipboard,1).'</pre>';

		// Load the fonts to display the paste hint
		$GLOBALS['TL_CONFIG']['loadGoogleFonts'] = $blnClipboard;

		$label = $GLOBALS['TL_DCA'][$useTable]['config']['label'];
		$icon = $GLOBALS['TL_DCA'][$useTable]['list']['sorting']['icon'] ?: 'pagemounts.gif';
		$label = \Image::getHtml($icon).' <label>'.$label.'</label>';

		// Begin buttons container
		$return = '
<div id="tl_buttons">'.((\Input::get('act') == 'select') ? '
<a href="'.$this->getReferer(true).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']) ? '
<a href="contao/main.php?'.$GLOBALS['TL_DCA'][$this->strTable]['config']['backlink'].'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a> ' : '')) . ((\Input::get('act') != 'select' && !$blnClipboard && !$GLOBALS['TL_DCA'][$this->strTable]['config']['closed']) ? '
<a href="'.$this->addToUrl('act=paste&amp;mode=create&amp;use=tl_page').'" class="header_new" title="'.specialchars($GLOBALS['TL_LANG']['tl_page']['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['tl_page']['new'][0].'</a>' .
'<a href="'.$this->addToUrl('act=paste&amp;mode=create&amp;use=tl_article').'" class="header_new" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG'][$this->strTable]['new'][0].'</a> ' : '') . 

((\Input::get('act') != 'select' && !$blnClipboard) ? $this->generateGlobalButtons() : '') . 
($blnClipboard ? '<a href="'.$this->addToUrl('clipboard=1').'" class="header_clipboard" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']).'" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['clearClipboard'].'</a> ' : '') . '
</div>' . \Message::generate(true);

		$tree = '';
		$blnHasSorting = $this->Database->fieldExists('sorting', $table);
		$blnNoRecursion = false;

		// Limit the results by modifying $this->root
		if ($session['search'][$this->strTable]['value'] != '')
		{
			$for = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? 'pid' : 'id';

			if ($session['search'][$this->strTable]['field'] == 'id')
			{
				$objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE id=?")
										  ->execute($session['search'][$this->strTable]['value']);
			}
			else
			{
				$objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE CAST(".$session['search'][$this->strTable]['field']." AS CHAR) REGEXP ? GROUP BY $for")
										  ->execute($session['search'][$this->strTable]['value']);
			}

			if ($objRoot->numRows < 1)
			{
				$this->root = array();
			}
			else
			{
				// Respect existing limitations (root IDs)
				if (is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']))
				{
					$arrRoot = array();

					while ($objRoot->next())
					{
						if (count(array_intersect($this->root, $this->Database->getParentRecords($objRoot->$for, $table))) > 0)
						{
							$arrRoot[] = $objRoot->$for;
						}
					}

					$this->root = $arrRoot;
				}
				else
				{
					$blnNoRecursion = true;
					$this->root = $objRoot->fetchEach($for);
				}
			}
		}

		// Call a recursive function that builds the tree
		for ($i=0, $c=count($this->root); $i<$c; $i++)
		{
			$tree .= $this->generateTree($table, $this->root[$i], array('p'=>$this->root[($i-1)], 'n'=>$this->root[($i+1)]), $blnHasSorting, -20, ($blnClipboard ? $arrClipboard : false), ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $blnClipboard && $this->root[$i] == $arrClipboard['id']), false, $blnNoRecursion);
		}

		// Return if there are no records
		if ($tree == '' && \Input::get('act') != 'paste')
		{
			return $return . '
<p class="tl_empty">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>';
		}

		$return .= ((\Input::get('act') == 'select') ? '

<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="tl_form" method="post">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_select">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').($blnClipboard ? '

<div id="paste_hint">
  <p>'.$GLOBALS['TL_LANG']['MSC']['selectNewPosition'].'</p>
</div>' : '').'

<div class="tl_listing_container tree_view" id="tl_listing">'.(isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['breadcrumb']) ? $GLOBALS['TL_DCA'][$table]['list']['sorting']['breadcrumb'] : '').((\Input::get('act') == 'select') ? '

<div class="tl_select_trigger">
<label for="tl_select_trigger" class="tl_select_label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox">
</div>' : '').'

<ul class="tl_listing '. $treeClass .'">
  <li class="tl_folder_top"><div class="tl_left">'.$label.'</div> <div class="tl_right">';

		$_buttons = '&nbsp;';

		// Show paste button only if there are no root records specified
		if (\Input::get('act') != 'select' && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $blnClipboard && ((!count($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']) && $GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] !== false) || $GLOBALS['TL_DCA'][$table]['list']['sorting']['rootPaste']))
		{
			// Call paste_button_callback (&$dc, $row, $table, $cr, $childs, $previous, $next)
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']))
			{
				$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

				$this->import($strClass);
				$_buttons = $this->$strClass->$strMethod($this, array('id'=>0), $table, false, $arrClipboard);
			}
			else
			{
				$imagePasteInto = \Image::getHtml('pasteinto.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]);
				$_buttons = '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid=0'.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
			}
		}

		// End table
		$return .= $_buttons . '</div><div style="clear:both"></div></li>'.$tree.'
</ul>

</div>';

		// Close the form
		if (\Input::get('act') == 'select')
		{
			$callbacks = '';

			// Call the buttons_callback
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
				{
					$this->import($callback[0]);
					$callbacks .= $this->$callback[0]->$callback[1]($this);
				}
			}

			$return .= '

<div class="tl_formbody_submit" style="text-align:right">

<div class="tl_submit_container">' . (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ? '
  <input type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['deleteSelected']).'"> ' : '') . '
  <input type="submit" name="cut" id="cut" class="tl_submit" accesskey="x" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['moveSelected']).'">
  <input type="submit" name="copy" id="copy" class="tl_submit" accesskey="c" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['copySelected']).'"> ' . (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ? '
  <input type="submit" name="override" id="override" class="tl_submit" accesskey="v" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['overrideSelected']).'">
  <input type="submit" name="edit" id="edit" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['editSelected']).'"> ' : '') . $callbacks . '
</div>

</div>
</div>
</form>';
		}

		return $return;
	}



	/**
	 * Recursively generate the tree and return it as HTML string
	 * @param string
	 * @param integer
	 * @param array
	 * @param boolean
	 * @param integer
	 * @param array
	 * @param boolean
	 * @param boolean
	 * @param boolean
	 * @return string
	 */
	protected function generateTree($table, $id, $arrPrevNext, $blnHasSorting, $intMargin=0, $arrClipboard=null, $blnCircularReference=false, $protectedPage=false, $blnNoRecursion=false)
	{
		static $session;

		$session = $this->Session->getData();
		$node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->strTable.'_'.$table.'_tree' : $this->strTable.'_tree';

		// Toggle nodes
		if (\Input::get('ptg'))
		{
			$session[$node][\Input::get('ptg')] = (isset($session[$node][\Input::get('ptg')]) && $session[$node][\Input::get('ptg')] == 1) ? 0 : 1;
			$this->Session->setData($session);

			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', \Environment::get('request')));
		}

		$objRow = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
								 ->limit(1)
								 ->execute($id);

		// Return if there is no result
		if ($objRow->numRows < 1)
		{
			$this->Session->setData($session);
			return '';
		}

		$return = '';
		$intSpacing = 20;
		$childs = array();

		// Add the ID to the list of current IDs
		if ($this->strTable == $table)
		{
			$this->current[] = $objRow->id;
		}

		// Check whether there are child records
		if (!$blnNoRecursion)
		{
			if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $this->strTable != $table)
			{
				$objChilds = $this->Database->prepare("SELECT id FROM " . $table . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))
											->execute($id);

				if ($objChilds->numRows)
				{
					$childs = $objChilds->fetchEach('id');
				}
			}
		}

		$blnProtected = false;

		// Check whether the page is protected
		if ($table == 'tl_page')
		{
			$blnProtected = ($objRow->protected || $protectedPage) ? true : false;
		}

		$session[$node][$id] = (is_int($session[$node][$id])) ? $session[$node][$id] : 0;
		$mouseover = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $table == $this->strTable) ? ' onmouseover="Theme.hoverDiv(this,1)" onmouseout="Theme.hoverDiv(this,0)" onclick="Theme.toggleSelect(this)"' : '';
	
		$RowType = ((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $objRow->type == 'root') || $table != $this->strTable) ? 'tl_folder' : 'tl_file');
		$return .= "\n  " . '<li class="'.$RowType.' click2edit"'.$mouseover.'><div class="tl_left" style="padding-left:'.($intMargin + $intSpacing + (empty($childs) ? 20 : 0)).'px">';

		// Calculate label and add a toggle button
		$args = array();
		$showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];
		$level = ($intMargin / $intSpacing + 1);

		if (!empty($childs))
		{
			$img = ($session[$node][$id] == 1) ? 'folMinus.gif' : 'folPlus.gif';
			$alt = ($session[$node][$id] == 1) ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
			$return .= '<a href="'.$this->addToUrl('ptg='.$id).'" title="'.specialchars($alt).'" onclick="Backend.getScrollOffset();return AjaxRequest.toggleStructure(this,\''.$node.'_'.$id.'\','.$level.','.$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'].')">'.\Image::getHtml($img, '', 'style="margin-right:2px"').'</a>';
		}

		foreach ($showFields as $k=>$v)
		{
			// Decrypt the value
			if ($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['encrypt'])
			{
				$objRow->$v = \Encryption::decrypt(deserialize($objRow->$v));
			}

			if (strpos($v, ':') !== false)
			{
				list($strKey, $strTable) = explode(':', $v);
				list($strTable, $strField) = explode('.', $strTable);

				$objRef = $this->Database->prepare("SELECT " . $strField . " FROM " . $strTable . " WHERE id=?")
										 ->limit(1)
										 ->execute($objRow->$strKey);

				$args[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (in_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
			{
				$args[$k] = \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $objRow->$v);
			}
			elseif ($GLOBALS['TL_DCA'][$table]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['multiple'])
			{
				$args[$k] = ($objRow->$v != '') ? (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0] : $v) : '';
			}
			else
			{
				$args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$objRow->$v] ?: $objRow->$v;
			}
		}

		$label = vsprintf(((strlen($GLOBALS['TL_DCA'][$table]['list']['label']['format'])) ? $GLOBALS['TL_DCA'][$table]['list']['label']['format'] : '%s'), $args);

		// Shorten the label if it is too long
		if ($GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] > 0 && $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] < utf8_strlen(strip_tags($label)))
		{
			$label = trim(\String::substrHtml($label, $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'])) . ' …';
		}

		$label = preg_replace('/\(\) ?|\[\] ?|\{\} ?|<> ?/', '', $label);

		// Call the label_callback ($row, $label, $this)
		if (is_array($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']))
		{
			$strClass = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][0];
			$strMethod = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][1];

			$this->import($strClass);
			$return .= $this->$strClass->$strMethod($objRow->row(), $label, $this, '', false, $blnProtected);
		}
		elseif (is_callable($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']))
		{
			$return .= $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']($objRow->row(), $label, $this, '', false, $blnProtected);
		}
		else
		{
			$return .= \Image::getHtml('iconPLAIN.gif', '') . ' ' . $label;
		}

		$return .= '</div> <div class="tl_right">';
		$previous = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['pp'] : $arrPrevNext['p'];
		$next = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['nn'] : $arrPrevNext['n'];
		$_buttons = '';

		// use=tl_tabelle Damit ich in do=article bleiben und die Tabelle use=tabelle nuten kann.
		// Regular buttons ($row, $table, $root, $blnCircularReference, $childs, $previous, $next)
		foreach($GLOBALS['TL_DCA'][$table]['list']['operations'] as $oKey => &$operation)
			if(in_array($oKey,array('edit','editHeader','toggle')))
				$operation['href'] = 'use='.$table.'&amp;do='.end((explode('_',$table))).'&amp;'.str_replace('use='.$table.'&amp;do='.end((explode('_',$table))).'&amp;','',$operation['href']);
			else 
				$operation['href'] = 'use='.$table.'&amp;'.str_replace('use='.$table.'&amp;','',$operation['href']);
		
		// BTW Ich hab keine Ahnung mehr wann ich $this->strTable und wann $table brauche ... 
		$_buttons .= (\Input::get('act') == 'select' && $table == \Input::get('use')) ? '<input type="checkbox" name="IDS[]" id="ids_'.$id.'" class="tl_tree_checkbox" value="'.$id.'">' : $this->generateButtons($objRow->row(), ($this->strTable == \Input::get('use') ? $this->strTable : $table), $this->root, $blnCircularReference, $childs, $previous, $next);
		
		// Paste buttons
		if ($arrClipboard !== false && \Input::get('act') != 'select')
		{
			$_buttons .= ' ';

			// Call paste_button_callback(&$dc, $row, $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next)
			if (is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback']))
			{
				$strClass = $GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback'][1];

				$this->import($strClass);
				if($table == \Input::get('use') || $table == 'tl_page')
				$_buttons .= $this->$strClass->$strMethod($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, ($table != \Input::get('use') && $table == 'tl_page'));
			}
			elseif (is_callable($GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback']))
			{
				$_buttons .= $GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback']($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next, ($table != \Input::get('use') && $table == 'tl_page'));
			}
			else
			{
				$imagePasteAfter = \Image::getHtml('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $id));
				$imagePasteInto = \Image::getHtml('pasteinto.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $id));

				// Regular tree (on cut: disable buttons of the page all its childs to avoid circular references)
				if ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] == 5)
				{
					$_buttons .= ($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id'])) || (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && !$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['rootPaste'] && in_array($id, $this->root))) ? \Image::getHtml('pasteafter_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
					$_buttons .= ($arrClipboard['mode'] == 'paste' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? \Image::getHtml('pasteinto_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
				}

				// Extended tree
				else
				{
					$_buttons .= ($this->strTable == $table) ? (($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? \Image::getHtml('pasteafter_.gif') : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ') : '';
					$_buttons .= ($this->strTable != $table) ? '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ' : '';
				}
			}
		}

		$return .= ($_buttons ?: '&nbsp;') . '</div><div style="clear:both"></div></li>';

		// ARTIKEL
		if ($table != $this->strTable)
		{
			$objChilds = $this->Database->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))
							 			->execute($id);

			if ($objChilds->numRows)
			{
				$ids = $objChilds->fetchEach('id');

				for ($j=0, $c=count($ids); $j<$c; $j++)
				{
					$return .= $this->generateTree($this->strTable, $ids[$j], array('pp'=>$ids[($j-1)], 'nn'=>$ids[($j+1)]), $blnHasSorting, ($intMargin + $intSpacing), $arrClipboard, false, ($j<(count($ids)-1) || !empty($childs)));
				}
			}
		}

		// Begin a new submenu Tatsächliche Seiten :)
		if (!$blnNoRecursion)
		{
			if (!empty($childs) && $session[$node][$id] == 1)
			{
				$return .= '<li class="parent" id="'.$node.'_'.$id.'"><ul class="level_'.$level.'">';
			}

			// Add the records of the parent table
			if ($session[$node][$id] == 1)
			{
				if (is_array($childs))
				{
					for ($k=0, $c=count($childs); $k<$c; $k++)
					{
						$return .= $this->generateTree($table, $childs[$k], array('p'=>$childs[($k-1)], 'n'=>$childs[($k+1)]), $blnHasSorting, ($intMargin + $intSpacing), $arrClipboard, ((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $childs[$k] == $arrClipboard['id']) || $blnCircularReference) ? true : false), ($blnProtected || $protectedPage));
					}
				}
			}

			// Close the submenu
			if (!empty($childs) && $session[$node][$id] == 1)
			{
				$return .= '</ul></li>';
			}
		}

		$this->Session->setData($session);
		return $return;
	}


	/**
	 * List all records of a particular table
	 * @return string
	 */
	public function showAll()
	{
		$return = '';
		$this->limit = '';
		$this->bid = 'tl_buttons';
		$table = $this->strTable;
		if(\Input::get('use') !== '' && \Input::get('table') == '')
			$table = \Input::get('use');

		// Clean up old tl_undo and tl_log entries
		if ($table == 'tl_undo' && strlen($GLOBALS['TL_CONFIG']['undoPeriod']))
		{
			$this->Database->prepare("DELETE FROM tl_undo WHERE tstamp<?")
						   ->execute(intval(time() - $GLOBALS['TL_CONFIG']['undoPeriod']));
		}
		elseif ($table == 'tl_log' && strlen($GLOBALS['TL_CONFIG']['logPeriod']))
		{
			$this->Database->prepare("DELETE FROM tl_log WHERE tstamp<?")
						   ->execute(intval(time() - $GLOBALS['TL_CONFIG']['logPeriod']));
		}

		$this->reviseTable();

		// Add to clipboard
		if (\Input::get('act') == 'paste')
		{
			$arrClipboard = $this->Session->get('CLIPBOARD');
			$arrClipboard[$table] = array
			(
				'id' => \Input::get('id'),
				'childs' => \Input::get('childs'),
				'mode' => \Input::get('mode')
			);

			$this->Session->set('CLIPBOARD', $arrClipboard);
		}

		// Custom filter
		if (!empty($GLOBALS['TL_DCA'][$table]['list']['sorting']['filter']) && is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['filter']))
		{
			foreach ($GLOBALS['TL_DCA'][$table]['list']['sorting']['filter'] as $filter)
			{
				$this->procedure[] = $filter[0];
				$this->values[] = $filter[1];
			}
		}

		// Render view
		if ($this->treeView)
		{
			$return .= $this->panel();
			$return .= $this->treeView();
		}
		else
		{
			if (\Input::get('table') && $this->ptable && $this->Database->fieldExists('pid', $table))
			{
				$this->procedure[] = 'pid=?';
				$this->values[] = CURRENT_ID;
			}

			$return .= $this->panel();
			$return .= ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] == 4) ? $this->parentView() : $this->listView();

			// Add another panel at the end of the page
			if (strpos($GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'], 'limit') !== false && ($strLimit = $this->limitMenu(true)) != false)
			{
				$return .= '

<form action="'.ampersand(\Environment::get('request'), true).'" class="tl_form" method="post">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_filters_limit">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<div class="tl_panel_bottom">

<div class="tl_submit_panel tl_subpanel">
<input type="image" name="btfilter" id="btfilter" src="' . TL_FILES_URL . 'system/themes/' . \Backend::getTheme() . '/images/reload.gif" class="tl_img_submit" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '" alt="' . specialchars($GLOBALS['TL_LANG']['MSC']['apply']) . '">
</div>' . $strLimit . '

<div class="clear"></div>

</div>

</div>
</form>
';
			}
		}

		// Store the current IDs
		$session = $this->Session->getData();
		$session['CURRENT']['IDS'] = $this->current;
		$this->Session->setData($session);

		return $return;
	}

}

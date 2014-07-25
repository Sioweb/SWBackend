<?php

namespace sioweb\contao\extensions\backend;

/**
* Contao Open Source CMS
* 
* @class DC_Table
* @file DC_Table.php
* @author Sascha Weidner
* @version 3.1.0
* @package sw.contao.extensions.backend
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
		$this->import('BackendUser','User');


		if(\Input::get('do') == 'article')
		{
			// Die Tabelle wird manchmal überschrieben ... es ist 23.42 Uhr und es war irgendwas mit kopieren und deaktivieren...
			$use = \Input::get('use');
			if(\Input::get('table') == '' && $use != '' && $strTable != $use)
			{
				#$GLOBALS['TL_DCA'][$use]['list']['label']['format'] = $GLOBALS['TL_DCA'][$strTable]['list']['label']['format'];
				$strTable = $use;
			}
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
		if($this->User->backendTheme != 'sioweb')
			return parent::panel();
		if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] == '')
			return '';

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
					$panels = $panel . $panels;
			}

			// Add the group if it is not empty
			if ($panels != '')
				$arrPanels[] = $panels;
		}

		if (empty($arrPanels))
			return '';

		if (\Input::post('FORM_SUBMIT') == 'tl_filters')
			$this->reload();

		$intTotal = count($arrPanels);
		$intLast = $intTotal - 1;

		$Panel = new \BackendTemplate('be_panel');
		$Panel->request_token = REQUEST_TOKEN;
		$Panel->table = $this->strTable;
		$Panel->action = ampersand(\Environment::get('request'), true);

		$arrFilters = array();
		for ($i=0; $i<$intTotal; $i++){
			$submit = '';

			$Filter = new \BackendTemplate('be_panel_default');
			$Filter->theme = \Backend::getTheme();
			if ($i == $intLast)
				$Filter->intLast = true;
			$Filter->panel = $arrPanels[$i];
			$arrFilters[] = $Filter->parse();
		}
		$Panel->filters = $arrFilters;
		return $Panel->parse();
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
		if(\Input::get('do') != 'article')
			return parent::treeView();

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


		$tplTree = new \BackendTemplate('be_tree');
		$tplTree->action = ampersand(\Environment::get('request'), true);
		$tplTree->blnClipboard = $blnClipboard;
		$tplTree->table = $table;
		$tplTree->label = $label;
		$tplTree->treeClass = $treeClass;

		/* Globale Buttons, Alles aufklappen, Mehere bearbeiten ... */
		$tplTreeButtons = new \BackendTemplate('be_tree_buttons');
		$tplTreeButtons->referer = $this->getReferer(true);
		$tplTreeButtons->table = $this->strTable;
		$tplTreeButtons->globalButtons = $this->generateGlobalButtons();
		$tplTreeButtons->blnClipboard = $blnClipboard;
		$tplTreeButtons->message = \Message::generate(true);

		// Begin buttons container
		$return = $tplTreeButtons->parse();

		$tree = '';
		$blnHasSorting = $this->Database->fieldExists('sorting', $table);
		$blnNoRecursion = false;

		// Limit the results by modifying $this->root
		if ($session['search'][$this->strTable]['value'] != '') {
			$for = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? 'pid' : 'id';

			if ($session['search'][$this->strTable]['field'] == 'id')
				$objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE id=?")->execute($session['search'][$this->strTable]['value']);
			else
				$objRoot = $this->Database->prepare("SELECT $for FROM {$this->strTable} WHERE CAST(".$session['search'][$this->strTable]['field']." AS CHAR) REGEXP ? GROUP BY $for")->execute($session['search'][$this->strTable]['value']);

			if ($objRoot->numRows < 1)
				$this->root = array();
			else {
				// Respect existing limitations (root IDs)
				if (is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['root'])) {
					$arrRoot = array();
					while ($objRoot->next())
						if (count(array_intersect($this->root, $this->Database->getParentRecords($objRoot->$for, $table))) > 0)
							$arrRoot[] = $objRoot->$for;
					$this->root = $arrRoot;
				}
				else {
					$blnNoRecursion = true;
					$this->root = $objRoot->fetchEach($for);
				}
			}
		}

		// Call a recursive function that builds the tree
		for ($i=0, $c=count($this->root); $i<$c; $i++)
			$tree .= $this->generateTree($table, $this->root[$i], array('p'=>$this->root[($i-1)], 'n'=>$this->root[($i+1)]), $blnHasSorting, -20, ($blnClipboard ? $arrClipboard : false), ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $blnClipboard && $this->root[$i] == $arrClipboard['id']), false, $blnNoRecursion);

		// Return if there are no records
		if ($tree == '' && \Input::get('act') != 'paste')
			return $return . '<p class="tl_empty">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>';

		$tplTree->tree = $tree;

		$_buttons = '&nbsp;';
		// Show paste button only if there are no root records specified
		if (\Input::get('act') != 'select' && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $blnClipboard && ((!count($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']) && $GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] !== false) || $GLOBALS['TL_DCA'][$table]['list']['sorting']['rootPaste'])) {
			// Call paste_button_callback (&$dc, $row, $table, $cr, $childs, $previous, $next)
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'])) {
				$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

				$this->import($strClass);
				$_buttons = $this->$strClass->$strMethod($this, array('id'=>0), $table, false, $arrClipboard);
			}
			else {
				$imagePasteInto = \Image::getHtml('pasteinto.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]);
				$_buttons = '<a class="'.$arrClipboard['mode'].'" href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid=0'.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][0]).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
			}
		}
		$tplTree->buttons = $_buttons;

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
					$tplTree->callbacks .= $this->$callback[0]->$callback[1]($this);
				}
			}
		}

		$return .= $tplTree->parse();

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

		$tplTree = new \BackendTemplate('be_tree_default');
		$tplTree->intMargin = $intMargin;

		if(\Input::get('do') != 'article')
			return parent::generateTree($table, $id, $arrPrevNext, $blnHasSorting, $intMargin, $arrClipboard, $blnCircularReference, $protectedPage, $blnNoRecursion);

		$session = $this->Session->getData();
		$node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->strTable.'_'.$table.'_tree' : $this->strTable.'_tree';

		// Toggle nodes
		if (\Input::get('ptg')) {
			$session[$node][\Input::get('ptg')] = (isset($session[$node][\Input::get('ptg')]) && $session[$node][\Input::get('ptg')] == 1) ? 0 : 1;
			$this->Session->setData($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', \Environment::get('request')));
		}

		$objRow = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")->limit(1)->execute($id);

		// Return if there is no result
		if ($objRow->numRows < 1)
		{
			$this->Session->setData($session);
			return '';
		}

		$return = '';
		$tplTree->intSpacing = 20;
		$childs = array();

		// Add the ID to the list of current IDs
		if ($this->strTable == $table)
			$this->current[] = $objRow->id;

		// Check whether there are child records
		if (!$blnNoRecursion) {
			if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $this->strTable != $table) {
				$objChilds = $this->Database->prepare("SELECT id FROM " . $table . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))->execute($id);

				if ($objChilds->numRows)
					$childs = $objChilds->fetchEach('id');
			}
		}

		$tplTree->childs = $childs;

		$blnProtected = false;

		// Check whether the page is protected
		if ($table == 'tl_page')
			$blnProtected = ($objRow->protected || $protectedPage) ? true : false;

		$session[$node][$id] = (is_int($session[$node][$id])) ? $session[$node][$id] : 0;
		$tplTree->mouseover = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 || $table == $this->strTable);
		$tplTree->RowType = ((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $objRow->type == 'root') || $table != $this->strTable) ? 'tl_folder' : 'tl_file');
		
		// Calculate label and add a toggle button
		$args = array();
		$showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];
		$level = ($intMargin / $tplTree->intSpacing + 1);


		$tplTreeChilds = new \BackendTemplate('be_tree_childs');
		$tplTreeChilds->level = $level;
		$tplTreeChilds->node = $node;
		$tplTreeChilds->id = $id;
		$tplTreeChilds->table = $this->strTable;
		if ($childs) {
			$tplTreeChilds->img = ($session[$node][$id] == 1) ? 'folMinus.gif' : 'folPlus.gif';
			$tplTreeChilds->alt = ($session[$node][$id] == 1) ? ($session[$node][$id] == 1) ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'] : '';
			$tplTree->childs = $tplTreeChilds->parse();
		}
		elseif($this->strTable != $table) {
			// Check whether there are child records
			if (!$blnNoRecursion) {
				$objChilds = $this->Database->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))->execute($id);

				if ($objChilds->numRows)
					$childs = $objChilds->fetchEach('id');
			}
			$tplTreeChilds->noRequest = true;
			$img = ($session['showHideArticles'][$id] == 1) ? 'closeArticles.png' : 'openArticles.png';
			$tplTreeChilds->img = 'system/modules/SWBackend/assets/'.$img;
			$tplTreeChilds->alt = $session[$node][$id] == 1 ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
			if($childs) {
				$tplTree->childs = $tplTreeChilds->parse();
				$tplClass = 'hasArticles';
				if($session['showHideArticles'][$id] == 1)
					$tplClass .= ' open';
				$tplTree->hasArticles = $tplClass;
			}
		}

		if($session['showHideArticles'][$objRow->pid] == 1)
			$tplTree->hasArticles = 'open';
		


		foreach ($showFields as $k=>$v) {
			// Decrypt the value
			if ($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['encrypt'])
				$objRow->$v = \Encryption::decrypt(deserialize($objRow->$v));

			if (strpos($v, ':') !== false) {
				list($strKey, $strTable) = explode(':', $v);
				list($strTable, $strField) = explode('.', $strTable);

				$objRef = $this->Database->prepare("SELECT " . $strField . " FROM " . $strTable . " WHERE id=?")
										 ->limit(1)
										 ->execute($objRow->$strKey);

				$args[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (in_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
				$args[$k] = \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $objRow->$v);
			elseif ($GLOBALS['TL_DCA'][$table]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['multiple'])
				$args[$k] = ($objRow->$v != '') ? (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['label'][0] : $v) : '';
			else
				$args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$objRow->$v] ?: $objRow->$v;
		}

		$label = vsprintf(((strlen($GLOBALS['TL_DCA'][$table]['list']['label']['format'])) ? $GLOBALS['TL_DCA'][$table]['list']['label']['format'] : '%s'), $args);

		// Shorten the label if it is too long
		if ($GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] > 0 && $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'] < utf8_strlen(strip_tags($label)))
			$label = trim(\String::substrHtml($label, $GLOBALS['TL_DCA'][$table]['list']['label']['maxCharacters'])) . ' …';

		$label = preg_replace('/\(\) ?|\[\] ?|\{\} ?|<> ?/', '', $label);

		// Call the label_callback ($row, $label, $this)
		if (is_array($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'])){
			$strClass = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][0];
			$strMethod = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'][1];

			$this->import($strClass);
			$tplTree->label = $this->$strClass->$strMethod($objRow->row(), $label, $this, '', false, $blnProtected);
		}
		elseif (is_callable($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']))
			$tplTree->label = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']($objRow->row(), $label, $this, '', false, $blnProtected);
		else
			$tplTree->label = \Image::getHtml('iconPLAIN.gif', '') . ' ' . $label;


		$previous = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['pp'] : $arrPrevNext['p'];
		$next = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $arrPrevNext['nn'] : $arrPrevNext['n'];
		$_button = $_buttons = '';

		// use=tl_tabelle Damit ich in do=article bleiben und die Tabelle use=tabelle nuten kann.
		// Regular buttons ($row, $table, $root, $blnCircularReference, $childs, $previous, $next)
		foreach($GLOBALS['TL_DCA'][$table]['list']['operations'] as $oKey => &$operation)
			if(in_array($oKey,array('edit','editHeader','toggle')))
				$operation['href'] = 'use='.$table.'&amp;do='.end((explode('_',$table))).'&amp;'.str_replace('use='.$table.'&amp;do='.end((explode('_',$table))).'&amp;','',$operation['href']);
			else 
				$operation['href'] = 'use='.$table.'&amp;'.str_replace('use='.$table.'&amp;','',$operation['href']);
		
		// BTW Ich hab keine Ahnung mehr wann ich $this->strTable und wann $table brauche ... 
		$_button .= (\Input::get('act') == 'select' && $table == \Input::get('use')) ? '<input type="checkbox" name="IDS[]" id="ids_'.$id.'" class="tl_tree_checkbox" value="'.$id.'">' : $this->generateButtons($objRow->row(), ($this->strTable == \Input::get('use') ? $this->strTable : $table), $this->root, $blnCircularReference, $childs, $previous, $next);
		
		$_button = preg_replace('/(<a(.+?(?<!class="))class="([^"]+)"([^>]*)>(<img.+?(?<!src=")src=".+?(?<!images\/)images+\/([a-z]+)\.[^"]+"[^<]+>)<\/a>)\s*/','<a class="$3 $3_$6" $2$4>$5</a>',$_button);
		
		$_buttons .= $_button;
		$_button = '';

		// Paste buttons
		if ($arrClipboard !== false && \Input::get('act') != 'select') {
			$_buttons .= ' ';

			// Call paste_button_callback(&$dc, $row, $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next)
			if (is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback'])) {
				$strClass = $GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback'][1];

				$this->import($strClass);
				if($table == \Input::get('use') || $table == 'tl_page') {
					$_button = $this->$strClass->$strMethod($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, ($table != \Input::get('use') && $table == 'tl_page'));
					$foundMode = preg_match_all('/(mode=([^\&]+)[^"]+"\s*)/',$_button,$results);
					if($foundMode)
						$_button = preg_replace_callback('/(mode=([^\&]+)[^"]+"\s*)/',array($this,'addPasteClasses'),$_button);
					$_buttons .= $_button;
				}
			}
			elseif (is_callable($GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback'])) {
				$_button = $GLOBALS['TL_DCA'][$table]['list']['sorting']['paste_button_callback']($this, $objRow->row(), $table, $blnCircularReference, $arrClipboard, $childs, $previous, $next, ($table != \Input::get('use') && $table == 'tl_page'));
				$foundMode = preg_match_all('/(mode=([^\&]+)[^"]+"\s*)/',$_button,$results);
				if($foundMode)
					$_button = preg_replace_callback('/(mode=([^\&]+)[^"]+"\s*)/',array($this,'addPasteClasses'),$_button);
				$_buttons .= $_button;
			}
			else {
				$imagePasteAfter = \Image::getHtml('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $id));
				$imagePasteInto = \Image::getHtml('pasteinto.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $id));

				// Regular tree (on cut: disable buttons of the page all its childs to avoid circular references)
				if ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] == 5) {
					$_buttons .= ($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id'])) || (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']) && !$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['rootPaste'] && in_array($id, $this->root))) ? \Image::getHtml('pasteafter_.gif').' ' : '<a class="'.$arrClipboard['mode'].'" href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
					$_buttons .= ($arrClipboard['mode'] == 'paste' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? \Image::getHtml('pasteinto_.gif').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" class="'.$arrClipboard['mode'].'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ';
				}

				// Extended tree
				else {
					$_buttons .= ($this->strTable == $table) ? (($arrClipboard['mode'] == 'cut' && ($blnCircularReference || $arrClipboard['id'] == $id) || $arrClipboard['mode'] == 'cutAll' && ($blnCircularReference || in_array($id, $arrClipboard['id']))) ? \Image::getHtml('pasteafter_.gif') : '<a class="'.$arrClipboard['mode'].'" href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ') : '';
					$_buttons .= ($this->strTable != $table) ? '<a class="'.$arrClipboard['mode'].'"  href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$id.(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteinto'][1], $id)).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ' : '';
				}
			}
		}
		$tplTree->buttons = $_buttons;


		// ARTIKEL
		if ($table != $this->strTable)
		{
			$objChilds = $this->Database->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting" : ''))
							 			->execute($id);
			$arrChilds = array();
			if ($objChilds->numRows)
			{
				$ids = $objChilds->fetchEach('id');

				for ($j=0, $c=count($ids); $j<$c; $j++)
				{
					$arrChilds[] = $this->generateTree($this->strTable, $ids[$j], array('pp'=>$ids[($j-1)], 'nn'=>$ids[($j+1)]), $blnHasSorting, ($intMargin + $tplTree->intSpacing), $arrClipboard, false, ($j<(count($ids)-1) || !empty($childs)));
				}
			}
			$tplTree->subitems = $arrChilds;
		}


		// Begin a new submenu Tatsächliche Seiten :)
		if (!$blnNoRecursion)
		{
			$tplTreePages = new \BackendTemplate('be_tree_pages');
			if (!empty($childs) && $session[$node][$id] == 1)
				$tplTreePages->childs = true;
			$tplTreePages->node = $node;
			$tplTreePages->id = $id;
			$tplTreePages->level = $level;

			// Add the records of the parent table
			$arrPages = array();
			if ($session[$node][$id] == 1)
				if (is_array($childs))
					for ($k=0, $c=count($childs); $k<$c; $k++)
						$arrPages[] = $this->generateTree($table, $childs[$k], array('p'=>$childs[($k-1)], 'n'=>$childs[($k+1)]), $blnHasSorting, ($intMargin + $tplTree->intSpacing), $arrClipboard, ((($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 5 && $childs[$k] == $arrClipboard['id']) || $blnCircularReference) ? true : false), ($blnProtected || $protectedPage));
			$tplTreePages->pages = $arrPages;

			$tplTree->pages = $tplTreePages->parse();
		}

		$this->Session->setData($session);
		return $tplTree->parse();
	}

	private function addPasteClasses($matches=array())
	{
		return $matches[1].' class="paste'.($matches[2] != 1 ? 'In' : 'After').'"';
	}


	/**
 	 * Show header of the parent table and list all records of the current table
	 * @return string
	 */
	protected function parentView()
	{

		if(\Input::get('do') != 'article')
			return parent::parentView();

		$blnClipboard = false;
		$arrClipboard = $this->Session->get('CLIPBOARD');
		$table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] == 6) ? $this->ptable : $this->strTable;
		$blnHasSorting = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'][0] == 'sorting';
		$blnMultiboard = false;

		// Check clipboard
		if (!empty($arrClipboard[$table]))
		{
			$blnClipboard = true;
			$arrClipboard = $arrClipboard[$table];

			if (is_array($arrClipboard['id']))
			{
				$blnMultiboard = true;
			}
		}

		// Load the fonts to display the paste hint
		$GLOBALS['TL_CONFIG']['loadGoogleFonts'] = $blnClipboard;

		// Load the language file and data container array of the parent table
		\System::loadLanguageFile($this->ptable);
		$this->loadDataContainer($this->ptable);

		$return = '
<div id="tl_buttons">' . (\Input::get('nb') ? '&nbsp;' : '
<a class="header_back" href="'.$this->getReferer(true, $this->ptable).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>') . ' ' . (!$blnClipboard ? ((\Input::get('act') != 'select') ? ((!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable']) ? '
<a href="'.$this->addToUrl(($blnHasSorting ? 'act=paste&amp;mode=create' : 'act=create&amp;mode=2&amp;pid='.$this->intId)).'" class="header_new" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG'][$this->strTable]['new'][0].'</a> ' : '') . $this->generateGlobalButtons() : '') : '<a href="'.$this->addToUrl('clipboard=1').'" class="header_clipboard" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']).'" accesskey="x">'.$GLOBALS['TL_LANG']['MSC']['clearClipboard'].'</a> ') . '
</div>' . \Message::generate(true);

		// Get all details of the parent record
		$objParent = $this->Database->prepare("SELECT * FROM " . $this->ptable . " WHERE id=?")
									->limit(1)
									->execute(CURRENT_ID);

		if ($objParent->numRows < 1)
		{
			return $return;
		}

		$return .= ((\Input::get('act') == 'select') ? '

<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_select" class="tl_form" method="post" novalidate>
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_select">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' : '').($blnClipboard ? '

<div id="paste_hint">
  <p>'.$GLOBALS['TL_LANG']['MSC']['selectNewPosition'].'</p>
</div>' : '').'

<div class="tl_listing_container parent_view">

<div class="tl_header click2edit" onmouseover="Theme.hoverDiv(this,1)" onmouseout="Theme.hoverDiv(this,0)" onclick="Theme.toggleSelect(this)">';

		// List all records of the child table
		if (!\Input::get('act') || \Input::get('act') == 'paste' || \Input::get('act') == 'select')
		{
			// Header
			$imagePasteNew = \Image::getHtml('new.gif', $GLOBALS['TL_LANG'][$this->strTable]['pastenew'][0]);
			$imagePasteAfter = \Image::getHtml('pasteafter.gif', $GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][0]);
			$imageEditHeader = \Image::getHtml('edit.gif', $GLOBALS['TL_LANG'][$this->strTable]['editheader'][0]);
			$strEditHeader = ($this->ptable != '') ? $GLOBALS['TL_LANG'][$this->ptable]['edit'][0] : $GLOBALS['TL_LANG'][$this->strTable]['editheader'][1];

			$return .= '
<div class="tl_content_right">'.((\Input::get('act') == 'select') ? '
<label for="tl_select_trigger" class="tl_select_label">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox">' : (!$GLOBALS['TL_DCA'][$this->ptable]['config']['notEditable'] ? '
<a href="'.preg_replace('/&(amp;)?table=[^& ]*/i', (($this->ptable != '') ? '&amp;table='.$this->ptable : ''), $this->addToUrl('act=edit')).'" class="edit" title="'.specialchars($strEditHeader).'">'.$imageEditHeader.'</a>' : '') . 
(($blnHasSorting && !$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable']) ? ' <a class="pasteNew" href="'.$this->addToUrl('act=create&amp;mode=2&amp;pid='.$objParent->id.'&amp;id='.$this->intId).'" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pastenew'][0]).'">'.$imagePasteNew.'</a>' : '') . 
($blnClipboard ? ' <a class="pasteIn" href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$objParent->id . (!$blnMultiboard ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][0]).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a>' : '')) . '
</div>';

			// Format header fields
			$add = array();
			$headerFields = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['headerFields'];

			foreach ($headerFields as $v)
			{
				$_v = deserialize($objParent->$v);

				if (is_array($_v))
				{
					$_v = implode(', ', $_v);
				}
				elseif ($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['multiple'])
				{
					$_v = ($_v != '') ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
				}
				elseif ($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['rgxp'] == 'date')
				{
					$_v = $_v ? \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $_v) : '-';
				}
				elseif ($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['rgxp'] == 'time')
				{
					$_v = $_v ? \Date::parse($GLOBALS['TL_CONFIG']['timeFormat'], $_v) : '-';
				}
				elseif ($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['rgxp'] == 'datim')
				{
					$_v = $_v ? \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $_v) : '-';
				}
				elseif ($v == 'tstamp')
				{
					$objMaxTstamp = $this->Database->prepare("SELECT MAX(tstamp) AS tstamp FROM " . $this->strTable . " WHERE pid=?")
												   ->execute($objParent->id);

					if (!$objMaxTstamp->tstamp)
					{
						$objMaxTstamp->tstamp = $objParent->tstamp;
					}

					$_v = \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], max($objParent->tstamp, $objMaxTstamp->tstamp));
				}
				elseif (isset($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['foreignKey']))
				{
					$arrForeignKey = explode('.', $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['foreignKey'], 2);

					$objLabel = $this->Database->prepare("SELECT " . $arrForeignKey[1] . " AS value FROM " . $arrForeignKey[0] . " WHERE id=?")
											   ->limit(1)
											   ->execute($_v);

					if ($objLabel->numRows)
					{
						$_v = $objLabel->value;
					}
				}
				elseif (is_array($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v]))
				{
					$_v = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v][0];
				}
				elseif (isset($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v]))
				{
					$_v = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['reference'][$_v];
				}
				elseif ($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options']))
				{
					$_v = $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['options'][$_v];
				}

				// Add the sorting field
				if ($_v != '')
				{
					$key = isset($GLOBALS['TL_LANG'][$this->ptable][$v][0]) ? $GLOBALS['TL_LANG'][$this->ptable][$v][0] : $v;
					$add[$key] = $_v;
				}
			}

			// Trigger the header_callback (see #3417)
			if (is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback']))
			{
				$strClass = $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'][1];

				$this->import($strClass);
				$add = $this->$strClass->$strMethod($add, $this);
			}
			elseif (is_callable($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback']))
			{
				$add = $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback']($add, $this);
			}

			// Output the header data
			$return .= '

<table class="tl_header_table">';

			foreach ($add as $k=>$v)
			{
				if (is_array($v))
				{
					$v = $v[0];
				}

				$return .= '
  <tr>
    <td><span class="tl_label">'.$k.':</span> </td>
    <td>'.$v.'</td>
  </tr>';
			}

			$return .= '
</table>
</div>';

			$orderBy = array();
			$firstOrderBy = array();

			// Add all records of the current table
			$query = "SELECT * FROM " . $this->strTable;

			if (is_array($this->orderBy) && strlen($this->orderBy[0]))
			{
				$orderBy = $this->orderBy;
				$firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

				// Order by the foreign key
				if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['foreignKey']))
				{
					$key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['foreignKey'], 2);
					$query = "SELECT *, (SELECT ". $key[1] ." FROM ". $key[0] ." WHERE ". $this->strTable .".". $firstOrderBy ."=". $key[0] .".id) AS foreignKey FROM " . $this->strTable;
					$orderBy[0] = 'foreignKey';
				}
			}
			elseif (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields']))
			{
				$orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
				$firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);
			}

			// Support empty ptable fields (backwards compatibility)
			if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'])
			{
				$this->procedure[] = ($this->ptable == 'tl_article') ? "(ptable=? OR ptable='')" : "ptable=?";
				$this->values[] = $this->ptable;
			}

			// WHERE
			if (!empty($this->procedure))
			{
				$query .= " WHERE " . implode(' AND ', $this->procedure);
			}
			if (!empty($this->root) && is_array($this->root))
			{
				$query .= (!empty($this->procedure) ? " AND " : " WHERE ") . "id IN(" . implode(',', array_map('intval', $this->root)) . ")";
			}

			// ORDER BY
			if (!empty($orderBy) && is_array($orderBy))
			{
				$query .= " ORDER BY " . implode(', ', $orderBy);
			}

			$objOrderByStmt = $this->Database->prepare($query);

			// LIMIT
			if (strlen($this->limit))
			{
				$arrLimit = explode(',', $this->limit);
				$objOrderByStmt->limit($arrLimit[1], $arrLimit[0]);
			}

			$objOrderBy = $objOrderByStmt->execute($this->values);

			if ($objOrderBy->numRows < 1)
			{
				return $return . '
<p class="tl_empty_parent_view">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>

</div>';
			}

			// Call the child_record_callback
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']) || is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']))
			{
				$strGroup = '';
				$blnIndent = false;
				$intWrapLevel = 0;
				$row = $objOrderBy->fetchAllAssoc();

				// Make items sortable
				if ($blnHasSorting)
				{
					$return .= '

<ul id="ul_' . CURRENT_ID . '">';
				}

				for ($i=0, $c=count($row); $i<$c; $i++)
				{
					$this->current[] = $row[$i]['id'];
					$imagePasteAfter = \Image::getHtml('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $row[$i]['id']));
					$imagePasteNew = \Image::getHtml('new.gif', sprintf($GLOBALS['TL_LANG'][$this->strTable]['pastenew'][1], $row[$i]['id']));

					// Decrypt encrypted value
					foreach ($row[$i] as $k=>$v)
					{
						if ($GLOBALS['TL_DCA'][$table]['fields'][$k]['eval']['encrypt'])
						{
							$row[$i][$k] = \Encryption::decrypt(deserialize($v));
						}
					}

					// Make items sortable
					if ($blnHasSorting)
					{
						$return .= '
<li id="li_' . $row[$i]['id'] . '">';
					}

					// Add the group header
					if (!$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['disableGrouping'] && $firstOrderBy != 'sorting')
					{
						$sortingMode = (count($orderBy) == 1 && $firstOrderBy == $orderBy[0] && $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] != '' && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] == '') ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'];
						$remoteNew = $this->formatCurrentValue($firstOrderBy, $row[$i][$firstOrderBy], $sortingMode);
						$group = $this->formatGroupHeader($firstOrderBy, $remoteNew, $sortingMode, $row);

						if ($group != $strGroup)
						{
							$return .= "\n\n" . '<div class="tl_content_header">'.$group.'</div>';
							$strGroup = $group;
						}
					}

					$blnWrapperStart = in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['start']);
					$blnWrapperSeparator = in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['separator']);
					$blnWrapperStop = in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['stop']);

					// Closing wrappers
					if ($blnWrapperStop)
					{
						if (--$intWrapLevel < 1)
						{
							$blnIndent = false;
						}
					}

					$return .= '

<div class="tl_content'.($blnWrapperStart ? ' wrapper_start' : '').($blnWrapperSeparator ? ' wrapper_separator' : '').($blnWrapperStop ? ' wrapper_stop' : '').($blnIndent ? ' indent' : '').(($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_class'] != '') ? ' ' . $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_class'] : '').(($i%2 == 0) ? ' even' : ' odd').' click2edit" onmouseover="Theme.hoverDiv(this,1)" onmouseout="Theme.hoverDiv(this,0)" onclick="Theme.toggleSelect(this)">
<div class="tl_content_right">';

					// Opening wrappers
					if ($blnWrapperStart)
					{
						if (++$intWrapLevel > 0)
						{
							$blnIndent = true;
						}
					}

					// Edit multiple
					if (\Input::get('act') == 'select')
					{
						$return .= '<input type="checkbox" name="IDS[]" id="ids_'.$row[$i]['id'].'" class="tl_tree_checkbox" value="'.$row[$i]['id'].'">';
					}

					// Regular buttons
					else
					{
						$return .= $this->generateButtons($row[$i], $this->strTable, $this->root, false, null, $row[($i-1)]['id'], $row[($i+1)]['id']);

						// Sortable table
						if ($blnHasSorting)
						{
							// Create new button
							if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] && !$GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'])
							{
								$return .= ' <a class="pasteNew" href="'.$this->addToUrl('act=create&amp;mode=1&amp;pid='.$row[$i]['id'].'&amp;id='.$objParent->id).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pastenew'][1], $row[$i]['id'])).'">'.$imagePasteNew.'</a>';
							}

							// Prevent circular references
							if ($blnClipboard && $arrClipboard['mode'] == 'cut' && $row[$i]['id'] == $arrClipboard['id'] || $blnMultiboard && $arrClipboard['mode'] == 'cutAll' && in_array($row[$i]['id'], $arrClipboard['id']))
							{
								$return .= ' <span class="pasteAfter">' . \Image::getHtml('pasteafter_.gif').'</span>';
							}

							// Copy/move multiple
							elseif ($blnMultiboard)
							{
								$return .= ' <a class="pasteAfter" href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row[$i]['id']).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $row[$i]['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a>';
							}

							// Paste buttons
							elseif ($blnClipboard)
							{
								$return .= ' <a class="pasteAfter" href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row[$i]['id'].'&amp;id='.$arrClipboard['id']).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$this->strTable]['pasteafter'][1], $row[$i]['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a>';
							}

							// Drag handle
							if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'])
							{
								$return .= ' <span class="drag">' . \Image::getHtml('drag.gif', '', 'class="drag-handle" title="' . sprintf($GLOBALS['TL_LANG'][$this->strTable]['cut'][1], $row[$i]['id']) . '"').'</span>';
							}
						}
					}

					if (is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']))
					{
						$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'][0];
						$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'][1];

						$this->import($strClass);
						$return .= '</div>'.$this->$strClass->$strMethod($row[$i]).'</div>';
					}
					elseif (is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']))
					{
						$return .= '</div>'.$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']($row[$i]).'</div>';
					}

					// Make items sortable
					if ($blnHasSorting)
					{
						$return .= '

</li>';
					}
				}
			}
		}

		// Make items sortable
		if ($blnHasSorting)
		{
			$return .= '
</ul>

<script>
  Backend.makeParentViewSortable("ul_' . CURRENT_ID . '");
</script>';
		}

		$return .= '

</div>';

		// Close form
		if (\Input::get('act') == 'select')
		{
			// Submit buttons
			$arrButtons = array();

			if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'])
			{
				$arrButtons['delete'] = '<input type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\''.$GLOBALS['TL_LANG']['MSC']['delAllConfirm'].'\')" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['deleteSelected']).'">';
			}

			if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'])
			{
				$arrButtons['cut'] = '<input type="submit" name="cut" id="cut" class="tl_submit" accesskey="x" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['moveSelected']).'">';
			}

			if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'])
			{
				$arrButtons['copy'] = '<input type="submit" name="copy" id="copy" class="tl_submit" accesskey="c" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['copySelected']).'">';
			}

			if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'])
			{
				$arrButtons['override'] = '<input type="submit" name="override" id="override" class="tl_submit" accesskey="v" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['overrideSelected']).'">';
				$arrButtons['edit'] = '<input type="submit" name="edit" id="edit" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['editSelected']).'">';
			}

			// Call the buttons_callback (see #4691)
			if (is_array($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback']))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] as $callback)
				{
					if (is_array($callback))
					{
						$this->import($callback[0]);
						$arrButtons = $this->$callback[0]->$callback[1]($arrButtons, $this);
					}
					elseif (is_callable($callback))
					{
						$arrButtons = $callback($arrButtons, $this);
					}
				}
			}

			$return .= '<div class="tl_formbody_submit" style="text-align:right">';

if(\Input::get('act') != 'select' && \Input::get('popup') !== '1')
	$return .= '<div class="tl_submit_container">' . implode(' ', $arrButtons) . '</div>';

$return .= '</div>
</div>
</form>';
		}

		return $return;
	}


	/**
	 * List all records of a particular table
	 * @return string
	 */
	public function showAll()
	{
		if(\Input::get('do') != 'article')
			return parent::showAll();
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

<?php

/**
* Contao Open Source CMS
* 
* @file tl_page.php
* @class sw_page
* @author Sascha Weidner
* @version 3.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sascha Weidner, Sascha
*/
$GLOBALS['TL_DCA']['tl_page']['config']['headline'] = 'title';
$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['paste_button_callback'] = array('sw_page','pastePage');
unset($GLOBALS['TL_DCA']['tl_page']['list']['operations']['articles']);

class sw_page extends tl_page
{
	/**
	 * Return the paste page button
	 * @param \DataContainer
	 * @param array
	 * @param string
	 * @param boolean
	 * @param array
	 * @return string
	 */
	public function pastePage(DataContainer $dc, $row, $table, $cr, $arrClipboard=null, $isArticle = false)
	{
		$disablePA = false;
		$disablePI = false;

		// Disable all buttons if there is a circular reference
		if ($arrClipboard !== false && ($arrClipboard['mode'] == 'cut' && ($cr == 1 || $arrClipboard['id'] == $row['id']) || $arrClipboard['mode'] == 'cutAll' && ($cr == 1 || in_array($row['id'], $arrClipboard['id']))))
		{
			$disablePA = true;
			$disablePI = true;
		}

		// Prevent adding non-root pages on top-level
		if (Input::get('mode') != 'create' && $row['pid'] == 0)
		{
			$objPage = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
									  ->limit(1)
									  ->execute(Input::get('id'));

			if ($objPage->type != 'root')
			{
				$disablePA = true;

				if ($row['id'] == 0)
				{
					$disablePI = true;
				}
			}
		}

		// Check permissions if the user is not an administrator
		if (!$this->User->isAdmin)
		{
			// Disable "paste into" button if there is no permission 2 (move) or 1 (create) for the current page
			if (!$disablePI)
			{
				if (!$this->User->isAllowed(2, $row) || (Input::get('mode') == 'create' && !$this->User->isAllowed(1, $row)))
				{
					$disablePI = true;
				}
			}

			$objPage = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
									  ->limit(1)
									  ->execute($row['pid']);

			// Disable "paste after" button if there is no permission 2 (move) or 1 (create) for the parent page
			if (!$disablePA && $objPage->numRows)
			{
				if (!$this->User->isAllowed(2, $objPage->row()) || (Input::get('mode') == 'create' && !$this->User->isAllowed(1, $objPage->row())))
				{
					$disablePA = true;
				}
			}

			// Disable "paste after" button if the parent page is a root page and the user is not an administrator
			if (!$disablePA && ($row['pid'] < 1 || in_array($row['id'], $dc->rootIds)))
			{
				$disablePA = true;
			}
		}

		$return = '';

		// Return the buttons
		$imagePasteAfter = Image::getHtml('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
		$imagePasteInto = Image::getHtml('pasteinto.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

		if ($row['id'] > 0 && !$isArticle)
		{
			$return = $disablePA ? Image::getHtml('pasteafter_.gif').' ' : '<a href="'.$this->addToUrl('do=page&amp;act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
		}
		
		$do = 'page';
		if($isArticle)
			$do = 'article';
		$return .= ($disablePI ? Image::getHtml('pasteinto_.gif').' ' : '<a href="'.$this->addToUrl('do='.$do.'&amp;act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
		return $return;
	}
}
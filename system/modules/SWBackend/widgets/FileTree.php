<?php


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

class FileTree extends \Contao\FileTree
{
	/**
	 * Load the database object
	 * @param array
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);
	}

	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		$arrSet = array();
		$arrValues = array();
		$blnHasOrder = ($this->strOrderField != '' && is_array($this->{$this->strOrderField}));

		if (!empty($this->varValue)) // Can be an array
		{
			$objFiles = \FilesModel::findMultipleByUuids((array)$this->varValue);
			$allowedDownload = trimsplit(',', strtolower($GLOBALS['TL_CONFIG']['allowedDownload']));

			if ($objFiles !== null)
			{
				while ($objFiles->next())
				{
					// File system and database seem not in sync
					if (!file_exists(TL_ROOT . '/' . $objFiles->path))
					{
						continue;
					}

					$arrSet[] = $objFiles->uuid;

					// Show files and folders
					if (!$this->blnIsGallery && !$this->blnIsDownloads)
					{
						if ($objFiles->type == 'folder')
						{
							$arrValues[$objFiles->uuid] = \Image::getHtml('folderC.gif') . ' ' . $objFiles->path;
						}
						else
						{
							$objFile = new \File($objFiles->path, true);
							$strInfo = $objFiles->path . ' <span class="tl_gray">(' . $this->getReadableSize($objFile->size) . ($objFile->isGdImage ? ', ' . $objFile->width . 'x' . $objFile->height . ' px' : '') . ')</span>';

							if ($objFile->isGdImage)
							{
								$arrValues[$objFiles->uuid] = \Image::getHtml(\Image::get($objFiles->path, 80, 60, 'center_center'), '', 'class="gimage" title="' . specialchars($strInfo) . '"');
							}
							else
							{
								$arrValues[$objFiles->uuid] = \Image::getHtml($objFile->icon) . ' ' . $strInfo;
							}
						}
					}

					// Show a sortable list of files only
					else
					{
						if ($objFiles->type == 'folder')
						{
							$objSubfiles = \FilesModel::findByPid($objFiles->uuid);

							if ($objSubfiles === null)
							{
								continue;
							}

							while ($objSubfiles->next())
							{
								// Skip subfolders
								if ($objSubfiles->type == 'folder')
								{
									continue;
								}

								$objFile = new \File($objSubfiles->path, true);
								$strInfo = $objSubfiles->path . ' <span class="tl_gray">(' . $this->getReadableSize($objFile->size) . ($objFile->isGdImage ? ', ' . $objFile->width . 'x' . $objFile->height . ' px' : '') . ')</span>';

								if ($this->blnIsGallery)
								{
									// Only show images
									if ($objFile->isGdImage)
									{
										$arrValues[$objSubfiles->uuid] = \Image::getHtml(\Image::get($objSubfiles->path, 80, 60, 'center_center'), '', 'class="gimage" title="' . specialchars($strInfo) . '"');
									}
								}
								else
								{
									// Only show allowed download types
									if (in_array($objFile->extension, $allowedDownload) && !preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
									{
										$arrValues[$objSubfiles->uuid] = \Image::getHtml($objFile->icon) . ' ' . $strInfo;
									}
								}
							}
						}
						else
						{
							$objFile = new \File($objFiles->path, true);
							$strInfo = $objFiles->path . ' <span class="tl_gray">(' . $this->getReadableSize($objFile->size) . ($objFile->isGdImage ? ', ' . $objFile->width . 'x' . $objFile->height . ' px' : '') . ')</span>';

							if ($this->blnIsGallery)
							{
								// Only show images
								if ($objFile->isGdImage)
								{
									$arrValues[$objFiles->uuid] = \Image::getHtml(\Image::get($objFiles->path, 80, 60, 'center_center'), '', 'class="gimage" title="' . specialchars($strInfo) . '"');
								}
							}
							else
							{
								// Only show allowed download types
								if (in_array($objFile->extension, $allowedDownload) && !preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
								{
									$arrValues[$objFiles->uuid] = \Image::getHtml($objFile->icon) . ' ' . $objFiles->path;
								}
							}
						}
					}
				}
			}

			// Apply a custom sort order
			if ($blnHasOrder)
			{
				$arrNew = array();

				foreach ($this->{$this->strOrderField} as $i)
				{
					if (isset($arrValues[$i]))
					{
						$arrNew[$i] = $arrValues[$i];
						unset($arrValues[$i]);
					}
				}

				if (!empty($arrValues))
				{
					foreach ($arrValues as $k=>$v)
					{
						$arrNew[$k] = $v;
					}
				}

				$arrValues = $arrNew;
				unset($arrNew);
			}
		}

		// Load the fonts for the drag hint (see #4838)
		$GLOBALS['TL_CONFIG']['loadGoogleFonts'] = true;

		// Convert the binary UUIDs
		$strSet = implode(',', array_map('String::binToUuid', $arrSet));
		$strOrder = $blnHasOrder ? implode(',', array_map('String::binToUuid', $this->{$this->strOrderField})) : '';

		$return = '<input type="hidden" name="'.$this->strName.'" id="ctrl_'.$this->strId.'" value="'.$strSet.'">' . ($blnHasOrder ? '
  <input type="hidden" name="'.$this->strOrderName.'" id="ctrl_'.$this->strOrderId.'" value="'.$strOrder.'">' : '') . '
  <div class="selector_container">' . (($blnHasOrder && count($arrValues)) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '') . '
    <ul id="sort_'.$this->strId.'" class="'.trim(($blnHasOrder ? 'sortable ' : '').($this->blnIsGallery ? 'sgallery' : '')).'">';

		foreach ($arrValues as $k=>$v)
		{
			$return .= '<li data-id="'.\String::binToUuid($k).'"><span onclick="Backend.removeMultiSrcThumbnails(this,\'ctrl_'.$this->strId.'\',\'ctrl_'.$this->strOrderId.'\');return false;" class="sortable_delete"></span>'.$v.'</li>';
		}

		$return .= '</ul>
    <p><a href="contao/file.php?do='.\Input::get('do').'&amp;table='.$this->strTable.'&amp;field='.$this->strField.'&amp;act=show&amp;id='.\Input::get('id').'&amp;value='.$strSet.'&amp;rt='.REQUEST_TOKEN.'" class="tl_submit" onclick="Backend.getScrollOffset();Backend.openModalSelector({\'width\':765,\'title\':\''.specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['filepicker'])).'\',\'url\':this.href,\'id\':\''.$this->strId.'\'});return false">'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</a></p>' . ($blnHasOrder ? '
    <script>Backend.makeMultiSrcSortable("sort_'.$this->strId.'", "ctrl_'.$this->strOrderId.'")</script>' : '') . '
  </div>';

		if (!\Environment::get('isAjaxRequest'))
		{
			$return = '<div>' . $return . '</div>';
		}

		return $return;
	}
}

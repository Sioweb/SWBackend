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

class MetaWizard extends \Contao\MetaWizard
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
		// Make sure there is at least an empty array
		if (!is_array($this->varValue) || empty($this->varValue))
		{
			$this->import('BackendUser', 'User');
			$this->varValue = array($this->User->language=>array()); // see #4188
		}

		$count = 0;
		$languages = $this->getLanguages();
		$return = '';
		$taken = array();

		// Add the existing entries
		if (!empty($this->varValue))
		{
			$return = '<ul id="ctrl_'.$this->strId.'" class="tl_metawizard">';

			// Add the input fields
			foreach ($this->varValue as $lang=>$meta)
			{
				$return .= '<li class="'.(($count%2 == 0) ? 'even' : 'odd').'" data-language="' . $lang . '">';
				$return .= '<span class="lang">' . $languages[$lang] . ' ' . \Image::getHtml('delete.gif', '', 'class="tl_metawizard_img" onclick="Backend.metaDelete(this)" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['aw_delete']).'"') . '</span>';
				
				$return .= '<ul class="meta_rows">';
	    			foreach(array_merge((array)$GLOBALS['TL_CONFIG']['siowebFilemanager'],$meta) as $mode => $value)
	    			{
	    				$return .= '<li>';
							$return .= '<label onclick="Backend.changeOptionWizardField(this,\''.$mode.'\');return false;" for="ctrl_'.$mode.'_'.$count.'">'.($GLOBALS['TL_LANG']['Sioweb'][$mode] ? $GLOBALS['TL_LANG']['Sioweb'][$mode].' ('.$mode.')' : $mode).'</label> <input type="text" name="'.$this->strId.'['.$lang.']['.$mode.']" id="ctrl_'.$mode.'_'.$count.'" class="tl_text" value="'.specialchars($value).'">';
							
							$return .= '<div class="tl_right">';
								$return .= '<a href="'.$this->addToUrl('&amp;cmd_' . $this->strField.'=delete&amp;cid='.$count.'&amp;id='.$this->currentRecord).'" class="delete" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['ow_delete']).'" onclick="Backend.siowebOptionsWizard(this,\'delete\',\'ctrl_'.$this->strId.'\');return false">';
								$return .= \Image::getHtml($button.'.gif', $GLOBALS['TL_LANG']['MSC']['ow_delete']);
								$return .= '</a> ';
							$return .= '</div>';
						$return .= '</li>';
					}

					$return .= '<li>';
						$return .= '<span class="add" onclick="Backend.siowebOptionsWizard(this,\'add\',\'ctrl_'.$this->strId.'\');return false">'.$GLOBALS['TL_LANG']['Sioweb']['addNewField'].'</span>';
					$return .= '</li>';
				$return .= '</ul>';

				$return .= '</li>';

				$taken[] = $lang;
				++$count;
			}

			$return .= '</ul>';
		}

		$options = array('<option value="">-</option>');

		// Add the remaining languages
		foreach ($languages as $k=>$v)
		{
			$options[] = '<option value="' . $k . '"' . (in_array($k, $taken) ? ' disabled' : '') . '>' . $v . '</option>';
		}

		$return .= '
  <div class="tl_metawizard_new">
    <select name="'.$this->strId.'[language]" class="tl_select tl_chosen" onchange="Backend.toggleAddLanguageButton(this)">'.implode('', $options).'</select> <input type="button" class="tl_submit" disabled value="'.specialchars($GLOBALS['TL_LANG']['MSC']['aw_new']).'" onclick="Backend.metaWizard(this,\'ctrl_'.$this->strId.'\')">
  </div>';

		return $return;
	}
}

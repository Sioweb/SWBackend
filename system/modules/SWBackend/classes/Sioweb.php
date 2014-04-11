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

	public function extendFileTree($strName)
	{
		if(!$GLOBALS['SWBackend']['fileTree'])
			foreach($GLOBALS['TL_DCA'][$strName]['fields'] as $fKey => $field)
				if($field['inputType'] == 'fileTree')
					if($GLOBALS['TL_DCA'][$strName]['fields'][$fKey]['eval'])
						$GLOBALS['TL_DCA'][$strName]['fields'][$fKey]['eval']['tl_class'] .= ' dragNdrop';
					else
						$GLOBALS['TL_DCA'][$strName]['fields'][$fKey]['eval'] = array('tl_class' => 'dragNdrop');
		$GLOBALS['SWBackend']['fileTree'] = true;
	}


	public function loadTlFiles($strName)
	{
		if($strName == 'tl_files')
		{
			if(is_array($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['save_callback']))
				$GLOBALS['TL_DCA']['tl_files']['fields']['meta']['save_callback'][] = array('sw_files','saveMeta');
			else
				$GLOBALS['TL_DCA']['tl_files']['fields']['meta']['save_callback'] = array(array('sw_files','saveMeta'));
		}
	}
	

	public function changeNavigation($arrModules, $blnShowAll)
	{
		foreach($arrModules as $tKey => $type)
			if($type['modules'])
			foreach($type['modules'] as $mKey => $modul)
			{
				// Seitenstruktur killen - ist in der DCA noch nötig.
				if($mKey == 'page')
					unset($arrModules[$tKey]['modules'][$mKey]);

				if($mKey == 'themes')
				{
					$this->loadDataContainer($modul['tables'][0]);
					$Globals = $GLOBALS['TL_DCA'][$modul['tables'][0]]['list']['global_operations'];
					$Operations = $GLOBALS['TL_DCA'][$modul['tables'][0]]['list'];
					/**/

					$arrModules[$tKey]['modules'][$mKey]['tl_globaloperations'][] = array(
						'key'=>'add',
						'title'=>'Add',
						'label'=>'Add',
						'class'=>'header_new',
						'href'=>$this->addToUrl('do='.$mKey.'&amp;act=create')
					);
					if($Globals)
						foreach($Globals as $oKey => $operation)
							if(!in_array($oKey,array('all','toggleNodes')))
								$arrModules[$tKey]['modules'][$mKey]['tl_globaloperations'][] = array_merge($operation,array(
									'key'=>$oKey,
									'title'=>$operation['label'][0],
									'label'=>$operation['label'][1],
									'href'=>$this->addToUrl($operation['href'])
								));
					$Theme = \ThemeModel::findAll();
					if($Theme)
					{
						while($Theme->next())
						{
							$arrModules[$tKey]['modules'][$mKey]['tl_buttons'][$Theme->id]['theme'] = array(
								'title' => $Theme->name
							);
							foreach($Operations['operations'] as $oKey => $operation)
							{
								if($oKey == 'show')
									continue;

								$BackendUser = \BackendUser::getInstance();
								
								if($BackendUser->backendTheme == 'sioweb')
									$arrModules[$tKey]['modules'][$mKey]['tl_buttons'][$Theme->id]['buttons'][] = array(
										'title'=>$Theme->title,
										'label'=>$Theme->title,
										'attributes'=>($oKey!='delete' ? '' : ' onclick="return confirm(\'Theme '.$Theme->name.' wirklich löschen?\')"'),
										'class'=>$oKey,
										'href'=>($operation['href'] ? $this->addToUrl('do='.$mKey.'&amp;'.$operation['href'].'&amp;id='.$Theme->id.
											(strpos($operation['href'],'act=') === false ? '&amp;act=&amp;' : '' ) .
											(strpos($operation['href'],'table=') === false ? '&amp;table=' : '' ) .
											(strpos($operation['href'],'use=') === false ? '&amp;use=' : '' )) 
											: $this->addToUrl('do='.$mKey.'&amp;act='.$oKey.'&amp;id='.$Theme->id))
									);
								else
								{
									$arrModules[$tKey]['modules'][$mKey]['tl_buttons'][$Theme->id]['buttons'][] = $this->generateIcon($Theme->row(), 'tl_theme', $mKey, $oKey);
								}
							}
						}
					}
					/** /
					if($Operations)
						foreach($Operations as $oKey => $operation)
							if(!in_array($oKey,array('edit','editHeader','cut','delete','show')))
								$arrModules[$tKey]['modules'][$mKey]['tl_buttons'][] = array_merge($operation,array(
									'key'=>$oKey,
									'title'=>$operation['label'][0],
									'label'=>$operation['label'][1],
									'href'=>$this->addToUrl($operation['href'])
								));
						echo '<pre>'.print_r($Operations,1).'</pre>';
					/**/
				}
			}
			#echo '<pre>'.print_r($arrModules,1).'</pre>'; 
		return $arrModules;
	}
}
}
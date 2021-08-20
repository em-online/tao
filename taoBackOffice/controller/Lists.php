<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2002-2008 (original work) Public Research Centre Henri Tudor & University of Luxembourg (under the project TAO & TAO2);
 *               2008-2010 (update and modification) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *               2013-2018 (update and modification) Open Assessment Technologies SA;
 *
 */

namespace oat\taoBackOffice\controller;

use common_exception_BadRequest;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\helpers\Template;
use oat\tao\model\TaoOntology;
use \tao_helpers_Scriptloader;
use \tao_actions_form_List;
use \tao_helpers_Uri;
use oat\taoBackOffice\model\lists\ListService;

/**
 * This controller provide the actions to manage the lists of data
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package taoBackOffice
 *
 */
class Lists extends \tao_actions_CommonModule
{
    use OntologyAwareTrait;
    
	/**
	 * Show the list of users
	 * @return void
	 */
	public function index()
	{
        tao_helpers_Scriptloader::addCssFile(Template::css('lists.css', 'tao'));
        $this->defaultData();

		$myAdderFormContainer = new tao_actions_form_List();
		$myForm = $myAdderFormContainer->getForm();

		if($myForm->isSubmited()){
			if($myForm->isValid()){
				$values = $myForm->getValues();
				$newList = $this->getListService()->createList($values['label']);
				$i = 0;
				while($i < $values['size']){
					$this->getListService()->createListElement($newList, __('element'). ' '.($i + 1));
					$i++;
				}
			}
		} else {
			$myForm->getElement('label')->setValue(__('List').' '.(count($this->getListService()->getLists()) + 1));
		}
		$this->setData('form', $myForm->render());

		$this->setData('lists', $this->getListData());
		$this->setView('Lists/index.tpl');
	}

	/**
	 * Returns all lists with all values for all lists
	 * @return array
	 */
	private function getListData()
	{
	    $listService = $this->getListService();
	    $lists = array();
	    foreach($listService->getLists() as $listClass){
	        $elements = array();
	        foreach($listService->getListElements($listClass) as $index => $listElement){
	            $elements[$index] = array(
	                'uri'		=> tao_helpers_Uri::encode($listElement->getUri()),
	                'label'		=> $listElement->getLabel()
	            );
	            ksort($elements);
	        }
	        $lists[] = array(
	            'uri'		=> tao_helpers_Uri::encode($listClass->getUri()),
	            'label'		=> $listClass->getLabel(),
	            // The Language list should not be editable.
	            // @todo Make two different kind of lists: system list that are not editable and usual list.
	            'editable'	=> $listClass->isSubClassOf($this->getClass(TaoOntology::CLASS_URI_LIST)) && $listClass->getUri() !== \tao_models_classes_LanguageService::CLASS_URI_LANGUAGES,
	            'elements'	=> $elements
	        );
	    }
	    return $lists;
	}

	/**
	 * get the JSON data to populate the tree widget
     *
     * @throws \common_exception_Error
     * @throws common_exception_BadRequest
	 */
	public function getListsData()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}
		$data = array();
		foreach($this->getListService()->getLists() as $listClass){
			array_push($data, $this->getListService()->toTree($listClass));
		}
        $this->returnJson(array(
			'data' 		=> __('Lists'),
			'attributes' => array('class' => 'node-root'),
			'children' 	=> $data,
			'state'		=> 'open'
		));
	}

	/**
	 * get the elements in JSON of the list in parameter
     * @throws common_exception_BadRequest
     * @return void
	 */
	public function getListElements()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}
		$data = array();
		if($this->hasRequestParameter('listUri')){
			$list = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('listUri')));
			if(!is_null($list)){
				foreach($this->getListService()->getListELements($list, true) as  $listElement){
					$data[tao_helpers_Uri::encode($listElement->getUri())] = $listElement->getLabel();
				}
			}
		}
        $this->returnJson($data);
	}


	/**
	 * Save a list and it's elements
     *
     * @throws common_exception_BadRequest
	 * @return void
	 */
	public function saveLists()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}
		$saved = false;

		if($this->hasRequestParameter('uri')){

			$listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('uri')));
			if(!is_null($listClass)) {
			    // use $_POST instead of getRequestParameters to prevent html encoding
				$listClass->setLabel($_POST['label']);

				$setLevel = false;
				$levelProperty = $this->getProperty(TaoOntology::CLASS_URI_LIST);
				foreach($listClass->getProperties(true) as $property){
					if($property->getUri() == $levelProperty->getUri()){
						$setLevel = true;
						break;
					}
				}

				$elements = $this->getListService()->getListElements($listClass);
				// use $_POST instead of getRequestParameters to prevent html encoding
				foreach($_POST as $key => $value){
					if(preg_match("/^list\-element_/", $key)){
						$key = str_replace('list-element_', '', $key);
						$l = strpos($key, '_');
						$level = substr($key, 0, $l);
						$uri = tao_helpers_Uri::decode(substr($key, $l + 1));

						$found = false;
						foreach($elements as $element){
							if($element->getUri() == $uri && !empty($uri)){
								$found = true;
								$element->setLabel($value);
								if($setLevel){
									$element->editPropertyValues($levelProperty, $level);
								}
								break;
							}
						}
						if(!$found){
							$element = $this->getListService()->createListElement($listClass, $value);
							if($setLevel){
								$element->setPropertyValue($levelProperty, $level);
							}
						}
					}
				}
				$saved = true;
			}
		}
        $this->returnJson(array('saved' => $saved));
	}

	/**
	 * Create a list or a list element
     * @throws common_exception_BadRequest
	 * @return void
	 */
	public function create()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}

		$response = array();
		if($this->getRequestParameter('classUri')){

			if($this->getRequestParameter('type') == 'class' && $this->getRequestParameter('classUri') == 'root'){
				$listClass = $this->getListService()->createList();
				if(!is_null($listClass)){
					$response['label']	= $listClass->getLabel();
					$response['uri'] 	= tao_helpers_Uri::encode($listClass->getUri());
				}
			}

			if($this->getRequestParameter('type') == 'instance'){
				$listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('classUri')));
				if(!is_null($listClass)){
					$listElt = $this->getListService()->createListElement($listClass);
					if(!is_null($listElt)){
						$response['label']	= $listElt->getLabel();
						$response['uri'] 	= tao_helpers_Uri::encode($listElt->getUri());
					}
				}
			}

		}
        $this->returnJson($response);
	}

	/**
	 * Rename a list node: change the label of a resource
	 * Render the json response with the renamed status
     * @throws common_exception_BadRequest
	 * @return void
	 */
	public function rename()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}

		$data = array('renamed'	=> false);

		if($this->hasRequestParameter('uri') && $this->hasRequestParameter('newName')){

			if($this->hasRequestParameter('classUri')){
				$listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('classUri')));
				$listElt = $this->getListService()->getListElement($listClass, tao_helpers_Uri::decode($this->getRequestParameter('uri')));
				if(!is_null($listElt)){
					$listElt->setLabel($this->getRequestParameter('newName'));
					if($listElt->getLabel() == $this->getRequestParameter('newName')){
						$data['renamed'] = true;
					}
				}
			}
			else{
				$listClass = $this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('uri')));
				if(!is_null($listClass)){
					$listClass->setLabel($this->getRequestParameter('newName'));
					if($listClass->getLabel() == $this->getRequestParameter('newName')){
						$data['renamed'] = true;
					}
				}
			}
		}
        $this->returnJson($data);
	}

	/**
	 * Removee the list in parameter
     * @throws common_exception_BadRequest
	 * @return void
	 */
	public function removeList()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}
		$deleted = false;

		if($this->hasRequestParameter('uri')){
			$deleted = $this->getListService()->removeList(
				$this->getListService()->getList(tao_helpers_Uri::decode($this->getRequestParameter('uri')))
			);
		}
        $this->returnJson(array('deleted' => $deleted));
	}

	/**
	 * Remove the list element in parameter
     * @throws common_exception_BadRequest
	 * @return void
	 */
	public function removeListElement()
    {
		if(!$this->isXmlHttpRequest()){
			throw new common_exception_BadRequest('wrong request mode');
		}
		$deleted = false;

		if($this->hasRequestParameter('uri')){
			$deleted = $this->getListService()->removeListElement(
				$this->getResource(tao_helpers_Uri::decode($this->getRequestParameter('uri')))
			);
		}
		$this->returnJson(array('deleted' => $deleted));
	}

    /**
     * @return ListService
     */
	protected function getListService()
    {
        return ListService::singleton();
    }
}

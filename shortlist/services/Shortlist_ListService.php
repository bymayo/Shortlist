<?php

namespace Craft;

class Shortlist_ListService extends BaseApplicationComponent
{

    public function getListOrCreate($listId)
    {
    	if($listId === false) {
    		// Try to get the user's default list
    		$defaultList = $this->getDefaultList();
    		if(is_null($defaultList)) {
    			// create a new list
    			return $this->createList();
    		}

    		return $defaultList;
    	}

    	die('get specific list by id');
    }

    public function getDefaultList()
    {
    	// @todo we could check the global settings and setup default defined lists here maybe?
	   	$lists =  Shortlist_ListRecord::model()->findAllByAttributes(array('ownerId' => craft()->shortlist->user->id), array('order' => 'dateUpdated DESC'));

	   	if(empty($lists)) return null; // No default list defined

	   	// More than one default
	   	// Fix this now. Just make the most recently updated the default
	   	$list = null;
	   	if(count($lists) > 1) {
	   		// the first is the most recent, skip it
	   		$list = array_shift($lists);

	   		// Now reset all the other lists to be non-default
	   		$listIds = array();
	   		foreach($lists as $unsetList) {
	   			$listIds[] = $unsetList->id;
	   		}

	   		$this->makeUndefault($listIds);
	   	} else {
	   		$list = current($lists);
	   	}


	   	return $list;
    }


    private function makeUndefault($listIds = array())
    {
    	if(!is_array($listIds)) {
    		$listIds[] = $listIds;
    	}

    	if(empty($listIds)) return;

		// Do a direct query so we don't hit the dateUpdated record value
		$sql = 'UPDATE '.DBHelper::addTablePrefix('shortlist_list').' s SET s.default = false WHERE s.id IN ('.implode(', ', $listIds).')';
		$query = craft()->db->createCommand($sql)->execute();

		return;
    }

    public function createList($makeDefault = true)
    {
    	$listModel = new Shortlist_ListModel();
    	// Get the defaults // @todo
    	$listModel->name = 'default_listname';
    	$listModel->title = 'New List';
		$listModel->shareSlug = strtolower(StringHelper::randomString(18));
		$listModel->slug = 'default-slug';
		$listModel->userSlug = 'someuser-slug';
		$listModel->default = $makeDefault;
		$listModel->ownerId = craft()->shortlist->user->id;
		$listModel->ownerType = craft()->shortlist->user->type;

		if($listModel->validate()) {
	        // Create the element
	        if (craft()->elements->saveElement($listModel, false))
	        {
	            $record = new Shortlist_ListRecord();
	            $record->setAttributes($listModel->getAttributes());
	            $record->id = $listModel->id;
	            $record->insert();
	        } else {
	        	$listModel->addError('general', 'There was a problem creating the list');
	        }

	        craft()->search->indexElementAttributes($listModel);

	        return $listModel;

		} else {
			if(!empty(craft()->shortlist_list->errors)) {
				foreach(craft()->shortlist_list->errors as $error) {
					$listModel->addError('general', $error);
				}
			} else {
				$listModel->addError('general', 'There was a problem with creating the list');
			}
			die('<pre>'.print_R($listModel,1));
			die('invalid');
		}

		return null;

    }


}


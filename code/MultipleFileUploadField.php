<?php

/**
 * Defines an Uploadify form field capable of managing a relationship to multiple files
 * @package Uploadify
 * @author Aaron Carlino
 */

class MultipleFileUploadField extends UploadifyField
{
	
	/**
	 * @var array Adds a new action for sorting the files
	 */
	static $allowed_actions = array (
		'dosort' => 'CMS_ACCESS_CMSMain',	
	);
	

	/**
	 * @var array Overrides the "multi" setting
	 */
	static $defaults = array (
		'multi' => true,
		'deleteEnabled' => false
	);

	
	/**
	 * Sets the value of this form field to a set of File IDs
	 *
	 * @param mixed $value If an array, use that as a list of the IDs, if not, extract it from the DataObject
	 * @param mixed $data The DataObject being updated by the form
	 */
	public function setValue($value = null, $data = null) {
		if(!is_array($value)) {
			if(!$value && $data && $data instanceof DataObject && $data->hasMethod($this->name)) {
				$funcName = $this->name;
				if($obj = $data->$funcName()) {
					if($obj instanceof DataObjectSet) {
						$value = $obj->column('ID');
					}
				}
			}
		}
		parent::setValue($value, $data);
	}

	
	/**
	 * Update the sort order of the attached files
	 * @see SortableDataObject
	 */
	public function dosort() {
		if(!Permission::check("CMS_ACCESS_CMSMain"))
			return;
		if(isset($_REQUEST['file']) && is_array($_REQUEST['file'])) {
	    	foreach($_REQUEST['file'] as $sort => $id) {
	          $obj = DataObject::get_by_id("File", $id);
	          $obj->SortOrder = $sort;
	          $obj->write();
	    	}
    	}	
	}
	
	public function importlist(SS_HTTPRequest $request) {
		if($id = $request->requestVar('FolderID')) {
			if(is_numeric($id)) {
				$files = DataObject::get("File", "\"ParentID\" = $id AND \"File\".\"ClassName\" != 'Folder'");
				if($files && $this->form) {
					if($record = $this->form->getRecord()) {
						if($relation_name = $this->getForeignRelationName($record)) {
							foreach($files as $f) {
								if($f->$relation_name) {
									$f->Disabled = true;
								}						
							}
						}
					}
				}
				return $this->customise(array(
					'Files' => $files
				))->renderWith('ImportList');
			}
		}
	}
	
	

	/**
	 * Refresh the list of attached files
	 *
	 * @return SSViewer
	 */
	public function refresh() {
		ContentNegotiator::disable();
		$count = 0;
		$before = is_array($this->Value()) ? sizeof($this->Value()) : 0;
		if(isset($_REQUEST['FileIDs'])) {
			$ids = explode(",",$_REQUEST['FileIDs']);
			if(is_array($ids)) {
				$this->setValue($ids);
				$count = sizeof($ids) - $before;
			}
		}	
		return Convert::array2json(array(
			'html' => $this->renderWith('AttachedFiles'),
			'success' => sprintf(_t('Uploadify.SUCCESSFULADDMULTI','Added files successfully.'), $count)
		));
	}
	
	/**
	 * Handles the removal of a file from the attached files. Right now this doesn't do anything
	 * because files are not actually deleted from the file system or database for this option.
	 *
	 * @return null
	 */
	public function removefile() {
		if((isset($_REQUEST['FileID']))
			&& ($form = $this->form)
			&& ($rec = $form->getRecord())
			&& ($key = $this->getForeignRelationName($rec))
			&& ($file_class = $this->getFileClass($rec))
			&& ($file = DataObject::get_by_id($file_class, (int) $_REQUEST['FileID']))) {
				
				$currentComponentSet = $rec->{$this->name}();
				$currentComponentSet->remove($file);
				$currentComponentSet->write();
			return;
		}
	}
	

	 
	/**
	 * Load the requirements and return a formfield to the template. Ensure "multi" is on.
	 *
	 * @return UploadifyField
	 */
	public function FieldHolder() {
		$f = parent::FieldHolder();
		if($this->Sortable()) {
			Requirements::javascript('dataobject_manager/javascript/dom_jquery_ui.js');
			Requirements::css('dataobject_manager/css/dom_jquery_ui.css');
		}
		$this->setVar('multi',true);
		return $f;
	}
	

	/**
	 * Gets the list of attached files
	 *
	 * @return DataObjectSet
	 */
	public function Files() {
		if($val = $this->Value()) {
			if(is_array($val)) {
				$list = implode(',', $val);
				$class = $this->baseFileClass;
				if($files = DataObject::get($class, "\"{$class}\".\"ID\" IN (".Convert::raw2sql($list).")")) {
					$ret = new DataObjectSet();
					foreach($files as $file) {
						if(is_subclass_of($file->ClassName, "Image") || $file->ClassName == "Image") {
							$image = ($file->ClassName != "Image") ? $file->newClassInstance("Image") : $file;
							if($thumb = $image->CroppedImage(32,32)) {
								$image->Thumb = $thumb->URL;						
							}
							$ret->push($image);
						}
						else {
							$file->Thumb = $file->Icon();
							$ret->push($file);
						}
					}
					return $ret;
				}
			}
		}
		return false;
	}
	

	/**
	 * Template accessor to determine if sorting is enabled
	 *
	 * @return boolean
	 */
	public function Sortable() {
		if(!self::$backend) return false;
		return class_exists("SortableDataObject") && SortableDataObject::is_sortable_class("File");
	}
	

	/**
	 * Saves the data in this form field into a database record.
	 *
	 * @param DataObject $record The record being updated by the form
	 */
	public function saveInto(DataObject $record) {
		// Can't do has_many without a parent id
		if(!$record->isInDB()) {
			$record->write();
		}
		if(!$file_class = $this->getFileClass($record)) {
			return false;
		}
		if(isset($_REQUEST[$this->name]) && is_array($_REQUEST[$this->name])) {
			if($relation_name = $this->getForeignRelationName($record)) {
				// Null out all the existing relations and reset.
				$currentComponentSet = $record->{$this->name}();
				$currentComponentSet->removeAll();
				// Assign all the new relations (may have already existed)
				foreach($_REQUEST[$this->name] as $id) {
					if($file = DataObject::get_by_id($this->baseFileClass, $id)) {
						$new = ($file_class != $this->baseFileClass) ? $file->newClassInstance($file_class) : $file;
						$new->write();
						$currentComponentSet->add($new);
					}
				}
			}
		}		
	}
	
	/**
	 * Returns file class for the $record for field $this->name if has_many or many_many
	 * 
	 * @param 	DataObject $record 	The record to search
	 * @return 	string|boolean		File class or false
	 */
	public function getFileClass (DataObject $record) {
		if(!$file_class = $record->has_many($this->name)) {
			if (!$many_class = $record->many_many($this->name)) {
				return false;
			}
			// set child class. 
			$file_class = $many_class[1];
		}
		return $file_class;
	}
	
	/**
	 * Gets the foreign key from the child File class that relates to the $has_many or $many_many
	 * on the parent record
	 *
	 * @param DataObject $record The record to search
	 * @return string
	 */
	public function getForeignRelationName(DataObject $record) {
		
		if ($many_info = $record->many_many($this->name)) {
			// return parent field
			return $many_info[2];
		} elseif ($file_class = $record->has_many($this->name)) {
			$class = $record->class;
			$relation_name = false;
			while($class != "DataObject") {
				if($relation_name = singleton($file_class)->getReverseAssociation($class)) {
					break;
				}
				$class = get_parent_class($class);					
			}
			if(!$relation_name) {
				user_error("Could not find has_one or belongs many_many relation ship on $file_class", E_USER_ERROR);
			}

			return $relation_name .= "ID";
		}
		return false;
	}
}
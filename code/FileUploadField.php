<?php

/**
 * Defines an Uploadify form field capable of managing a single file relationship 	
 * @package Uploadify
 * @author Aaron Carlino
 */
class FileUploadField extends UploadifyField
{

	/**
	 * @var array Override the {@see $defaults} array to force single file
	 */
	static $defaults = array (
		'multi' => false
	);
	
	
	/**
	 * Sets the value of the form field. 
	 *
	 * @param mixed $value If numeric, get the file by ID, otherwise, introspect the $data object
	 * @param DataObject $data The record associated with the parent form
	 */
	public function setValue($value = null, $data = null) {
		if(!is_numeric($value)) {
			if($id = Controller::curr()->getRequest()->requestVar($this->Name()."ID")) {
				$value = $id;
			}
			elseif(!$value && $data && $data instanceof DataObject && $data->hasMethod($this->name)) {
				$funcName = $this->name;
				if($obj = $data->$funcName()) {
					if($obj instanceof File || $obj instanceof S3File) {
						$value = $obj->ID;
					}
				}
			}
		}
		parent::setValue($value, $data);
	}


	/**
	 * Refresh the attached files box. This method may receive a list of IDs,
	 * but it will only accept the last one in the list. 
	 *
	 * @param SS_HTTPRequest $request
	 * @return SSViewer
	 */
	public function refresh(SS_HTTPRequest $request) {
		ContentNegotiator::disable();
		if($id = $request->requestVar('FileIDs')) {
			if(!is_numeric($id)) {
				$arr = explode(',',$id);
				if(is_array($arr)) {
					$id = end($arr);
				}
			}
			$this->setValue($id);
			$name = null;
			if(is_numeric($id)) {
				if($file = DataObject::get_by_id($this->baseFileClass, Convert::raw2sql($id))) {
					$name = $file->Name;
				}
			}
		}	
		return Convert::array2json(array(
			'html' => $this->renderWith('AttachedFiles'),
			'success' => sprintf(_t('Uploadify.SUCCESSFULADDSINGLE','Added file "%s" successfully.'), $name)
		));
	}
	
	
	/**
	 * Handles the removal of a file from the attached files. Right now this doesn't do anything
	 * because files are not actually deleted from the file system or database for this option.
	 *
	 * @return null
	 */
	public function removefile() {
		if($form = $this->form) {
			if($rec = $form->getRecord()) {
				$rec->{$this->Name().'ID'} = 0;
				$rec->write();
				return;
			}
		}
	}
	


	/**
	 * Load the requirements and return a formfield to the template. Ensure "multi" is off.
	 *
	 * @return UploadifyField
	 */
	public function FieldHolder() {
		$f = parent::FieldHolder();
		$this->setVar('multi',false);
		return $f;
	}


	/**
	 * Gets all the attached files. This should only return one file, but we return
	 * a {@link DataObjectSet} in order to maintain a single template
	 *
	 * @return DataObjectSet
	 */
	public function Files() {
		if($val = $this->Value()) {
			$class = $this->baseFileClass;
			if($files = DataObject::get($class, "\"{$class}\".\"ID\" IN (".Convert::raw2sql($val).")")) {
				$ret = new DataObjectSet();
				foreach($files as $file) {
					if(is_subclass_of($file->ClassName, "Image") || $file->ClassName == "Image") {
						$image = ($file->ClassName != "Image") ? $file->newClassInstance("Image") : $file;
						if($thumb = $image->CroppedImage(50,50)) {
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
		return false;
	}
	

	/**
	 * Saves the form data into a record. The {@see $name} property of the object is used
	 * to determine the foreign key on the record, e.g. "SomeFileID".
	 *
	 * @param DataObject $record The record associated with the parent form
	 */
	public function saveInto(DataObject $record) {
		if(isset($_REQUEST[$this->name."ID"])) {
			$file_id = (int) $_REQUEST[$this->name."ID"];
			if($file_class = $record->has_one($this->name)) {
				if($f = DataObject::get_by_id($this->baseFileClass, $file_id)) {
					if($f->ClassName != $file_class) {
						$file = $f->newClassInstance($file_class);
						$file->write();
					}
				}
			}
			$record->{$this->name . 'ID'} = $_REQUEST[$this->name."ID"];
		}
	}

}
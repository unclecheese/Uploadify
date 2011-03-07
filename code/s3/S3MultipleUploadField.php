<?php

/**
 * This class is almost a perfect replica of {@see S3UploadField}, only
 * it subclasses {@see MultipleFileUploadField} instead of {@see FileUploadField}.
 * It seems totally redundant to have to do this, but because PHP does not support
 * multiple inheritance, there is no realistic way for this class to inherit both the
 * features of multiple uploads and S3.
 *
 * @package Uploadify
 * @subpackage S3
 * @author Aaron Carlino
 */
class S3MultipleUploadField extends MultipleFileUploadField {
	
	/**
	 * @var array An array of default settings that will be merged with parent classes.
	 */
	static $defaults = array (
		's3bucket' => null,
		's3script' => null
	);
	
	/**
	 * @var array Controller actions allowed on this form field.
	 */
	static $allowed_actions = array (
		'uploads3'
	);
	
	
	/**
	 * @var string The most fundamental file class used for uploading
	 */
	public $baseFileClass = "S3File";
	
	
	/**
	 * @var string The template that will render for this field
	 */
	public $template = "S3Upload";
	
	
	/**
	 * @var boolean The user should never be allowed to select folders for S3 files
	 */
	public $allowFolderSelection = false;
	


	/**
	 * Overload some settings to make sure the right script is used.
	 *
	 * @return UploadifyField
	 */	
	public function FieldHolder() {
		if(!$this->getSetting('s3script')) {
			$this->setVar('script', urlencode(Director::baseURL().Director::makeRelative($this->Link('uploads3'))));
		}
		else {
			$this->setVar('script', $this->getSetting('s3script'));
		}
		if(!$this->Backend()) {
			$this->template = "UploadifyField";
		}
		return parent::FieldHolder();;
	}
	

	/**
	 * Handles uploading to the S3 server
	 *
	 * @return int
	 */	
	public function uploads3() {
		if (isset($_FILES["Filedata"]) && is_uploaded_file($_FILES["Filedata"]["tmp_name"])) {
			$ext = strtolower(end(explode('.', $_FILES['Filedata']['name'])));
			$class = $this->baseFileClass;
			$file = new $class();
			if($this->getSetting('s3bucket')) {
				$file->setUploadBucket($this->getSetting('s3bucket'));
			}
			$file->loadUploaded($_FILES['Filedata']);	
			$file->write();			
			echo $file->ID;
		}	
	}
	
	
	/**
	 * Prevent the user from enabling upload folder selection
	 */
	public function allowFolderSelection() {
		$this->allowFolderSelection = false;
	}

}
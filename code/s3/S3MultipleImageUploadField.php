<?php

/**
 * Defines an Uploadify form field capable of managing a relationship to multiple images
 * @package Uploadify
 * @author Aaron Carlino
 */
class S3MultipleImageUploadField extends MultipleFileUploadField
{
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
	public $baseFileClass = "S3Image";
	
	
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
	 * Force the object to accept only images.
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
		$this->imagesOnly();
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
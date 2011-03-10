<?php

/**
 * Defines an Uploadify form field capable of managing a single image relationship 	
 * @package Uploadify
 * @author Aaron Carlino
 */

class ImageUploadField extends FileUploadField
{
	/**
	 * Force the object to accept only images.
	 *
	 * @return UploadifyField
	 */
	public function FieldHolder() {
		$this->imagesOnly();
		return parent::FieldHolder();
	}
}
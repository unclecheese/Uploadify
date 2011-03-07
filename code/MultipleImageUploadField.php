<?php

/**
 * Defines an Uploadify form field capable of managing a relationship to multiple images
 * @package Uploadify
 * @author Aaron Carlino
 */
class MultipleImageUploadField extends MultipleFileUploadField
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
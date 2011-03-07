<?php

/**
 * A pseudo-file class that represents data stored on an Amazon S3 server.
 * A lot of the member functions and properties are derived from the {@link File}
 * class to maintain consistency in the framework.
 *
 * @package Uploadify
 * @subpackage S3
 * @author Aaron Carlino
 */
class S3File extends DataObject {

	static $db = array(
		"Name" => "Varchar(255)",
		"Bucket" => "Varchar(255)",
		"URL" => "Varchar(255)"
	);
	
	static $has_one = array(
		"Owner" => "Member"
	);
	
	
	/**
	 * @var string The access key for authentication to the S3 API
	 */
	protected static $access_key = null;
	

	/**
	 * @var string The secret key for authentication to the S3 API
	 */
	protected static $secret_key = null;
	
	
	/**
	 * @var string The default bucket where uploads should go.
	 */	
	public static $default_bucket = "ss-uploads";
	
	
	/**
	 * @var boolean If true, append a unique id to the filenames
	 */	
	public static $unique_id = true;


	/**
	 * @var S3 The member S3 object that will talk to the S3 server.
	 */	
	protected $S3 = null;


	/**
	 * @var string A custom upload bucket to overload the default
	 */	
	protected $uploadBucket = null;
	
	
	/**
	 * @var string A custom filename
	 */	
	public $fileName = null;
	
	
	/**
	 * Set the two API keys needed to connect to S3
	 *
	 * @param string $access The access key
	 * @param string $secret The secret key
	 */
	public static function set_auth($access, $secret) {
		self::$access_key = $access;
		self::$secret_key = $secret;
	}
	
	
	/**
	 * Globally sets the default bucket where all uploads should go
	 *
	 * @param string $bucket The name of the bucket
	 */
	public static function set_default_bucket($bucket) {
		self::$default_bucket = $bucket;
	}
	
	
	/**
	 * Turn off automatic unique id generation. Unique IDs are used to quell name
	 * collisions.
	 */
	public static function disable_unique_ids() {
		self::$unique_id = false;
	}
	
	
	/**
	 * Constructor for the S3File object. Assigns a member S3 object.
	 *
	 * @param array $record The database record associated with this object
	 * @param boolean $isSingleton Set true if this object was instantiated as a singleton
	 */
	public function __construct($record = null, $isSingleton = false) {
		parent::__construct($record, $isSingleton);
		$this->S3 = new S3(self::$access_key, self::$secret_key);
	}
	
	
	/**
	 * Sets a custom upload bucket for this instance. Overrides {@see self::$default_bucket}
	 *
	 * @param string $bucket The name of the bucket to use
	 */
	public function setUploadBucket($bucket) {
		$this->uploadBucket = $bucket;
	}
	
	public function canDelete() {
		return Permission::check('CMS_ACCESS_CMSMain');
	}
	
	
	/**
	 * Gets the current upload bucket, custom or default.
	 *
	 * @return string
	 */
	public function getUploadBucket() {
		return $this->uploadBucket ? $this->uploadBucket : self::$default_bucket;
	}
	
	
	/**
	 * Given an array of filedata from the request, load up the meta data for the File
	 * and send it off to S3
	 *
	 * @param array $filedata The file data from the request
	 * @return boolean
	 */
	public function loadUploaded($filedata) {
		if(!is_array($filedata) || !isset($filedata['tmp_name'])) 
			return false;
		
		$fileTempName = $filedata['tmp_name'];
		$fileName = $filedata['name'];
		if(!$this->fileName) {
			$fileName = ereg_replace(' +','-',trim($fileName));
			$fileName = ereg_replace('[^A-Za-z0-9.+_\-]','',$fileName);
			if(self::$unique_id) {
				$ext = File::get_file_extension($fileName);
				$base = basename($fileName,".{$ext}");
				$this->Name = uniqid($base).".{$ext}";
			}
		}
		else {
			$this->Name = $this->fileName . "." . File::get_file_extension($fileName);
		}

		$bucket = $this->getUploadBucket();
		$this->S3->putBucket($bucket, S3::ACL_PUBLIC_READ);

		if ($this->S3->putObjectFile($fileTempName, $bucket, $this->Name, S3::ACL_PUBLIC_READ)) { 
			$this->Bucket = $bucket;
			$this->URL = "http://{$bucket}.s3.amazonaws.com/{$this->Name}";
		}
		
		return false;
	}
	
	
	/**
	 * Getter for the "Filename" field. This is stored as a field for File, but here
	 * it is done dynamically.
	 *
	 * @return string
	 */
	public function Filename() {
		return basename($this->URL);
	}
	
	
	/**
	 * Return the URL of an icon for the file type. Borrowed from {@see File}
	 *
	 * @return string
	 */
	public function Icon() {
		$ext = File::get_file_extension($this->Name);
		if(!Director::fileExists(SAPPHIRE_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = $this->appCategory();
		}

		if(!Director::fileExists(SAPPHIRE_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = "generic";
		}

		return SAPPHIRE_DIR . "/images/app_icons/{$ext}_32.gif";
	}
	
	
	/**
	 * Gets the category of this file type. Borrowed from {@see File}
	 *
	 * @return string
	 */
	public function appCategory() {
		$ext = File::get_file_extension($this->Name);
		switch($ext) {
			case "aif": case "au": case "mid": case "midi": case "mp3": case "ra": case "ram": case "rm":
			case "mp3": case "wav": case "m4a": case "snd": case "aifc": case "aiff": case "wma": case "apl":
			case "avr": case "cda": case "mp4": case "ogg":
				return "audio";
			
			case "mpeg": case "mpg": case "m1v": case "mp2": case "mpa": case "mpe": case "ifo": case "vob":
			case "avi": case "wmv": case "asf": case "m2v": case "qt":
				return "mov";
			
			case "arc": case "rar": case "tar": case "gz": case "tgz": case "bz2": case "dmg": case "jar":
			case "ace": case "arj": case "bz": case "cab":
				return "zip";
				
			case "bmp": case "gif": case "jpg": case "jpeg": case "pcx": case "tif": case "png": case "alpha":
			case "als": case "cel": case "icon": case "ico": case "ps":
				return "image";
		}
	}
	
	
	/**
	 * Capture the owner ID before the file gets written.
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->OwnerID = Member::currentUserID();
	}

}
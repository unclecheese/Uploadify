<?php

/**
 * Defines the Uploadify form field. This class is subclassed into {@see FileUploadField}
 * and {@see MultipleFileUploadField}. This abstract class contains all of the properties
 * and methods that are shared between the two
 *	
 * @package Uploadify
 * @author Aaron Carlino
 */
abstract class UploadifyField extends FormField
{
	
	/**
	 * @var array The allowed actions for the form field as a controller
	 */
	public static $allowed_actions = array (
		'upload',
		'refresh',
		'removefile',
		'deletefile' => 'CMS_ACCESS_CMSMain',
		'import' => 'CMS_ACCESS_CMSMain',
		'importlist' => 'CMS_ACCESS_CMSMain',
		'newfolder' => 'CMS_ACCESS_CMSMain'
	);


	/**
 	 * @var array Defines all of the default values that define the uploader.
 	 * These settings can be overridden with {@see setVar} at the object level
 	 */
	public static $defaults = array (
		'script' => null,
		'refreshlink' => null,
		'uploader' => null,
		'scriptAccess' => 'sameDomain',
		'queueSizeLimit' => '999',
		'auto' => true,
		'fileExt' => '*.*',
		'fileDesc' => ' ',
		'cancelImg' => null,
		'image_class' => 'Image',
		'file_class' => 'File',
		'buttonText' => 'Browse...',
		'queueID' => false,
		'wmode' => 'transparent',
		'hideButton' => true,
		'upload_on_submit' => false,
		'sortable' => false,
		'sizeLimit' => null
	);
	

	/**
 	 * @var boolean Stores whether the formfield is used in the backend
 	 */
	protected static $backend = false;	
	
	
	/**
	 * @var boolean Puts Uploadify in debug mode to show all of the parameters on the template
	 */
	protected static $debug = false;
	
	
	protected static $authenticate = true;


	/**
 	 * @var array The extensions that determine an Image file type {@see $image_class}
 	 */
	public static $image_extensions = array('jpg','jpeg','gif','png');


	/**
 	 * @var array The file types that are allowed in the uploader
 	 */
	public $fileTypes = array ();


	/**
 	 * @var array Extra request parameters to send with the upload request
 	 */
	public $extraParams = array();


	/**
	 * @var array Additional configuration settings for this instance
 	 */		
	public $configuration = array ();
	

	/**
	 * @var boolean Allow the selection of the file destination (CMS only)
	 */
	protected $allowFolderSelection = true;
	

	/**
	 * @var boolean Allow the user to choose an existing file
	 */
	public $allowImport = true;
	
	
	public $columnCount = 2;
	
	public $allowDelete = true;
	

	/**
	 * @var string The default upload folder
	 */
	public $uploadFolder = "Uploads";
	

	/**
	 * @var string Template to render the formfield
	 */
	public $template = "UploadifyField";
	
	
	/**
	 * @var string The most fundamental type of File to retrieve. This is
	 			   overridden by the S3 features, which use non-file objects
	 */
	public $baseFileClass = "File";


	/**
	 * Sets a default setting of the class definition that will apply to all
	 * instances, unless overridden by {@see setVar} in an individual instance
	 *
	 * @param string The var to set
	 * @param mixed The value to set
	 */
	public static function set_var($var, $value) {
		self::$defaults[$var] = $value;
	}
	
	
	/**
	 * Put Uploadify in debug mode. (Will render all the parameters on the template)
	 */
	public static function show_debug() {
		self::$debug = true;	
	}
		

	/**
	 * Convert a shorthand byte value from a PHP configuration directive to an integer value
	 * @param string $value
	 * @return int
	 */
	public static function convert_bytes($value) {
	    if ( is_numeric( $value ) ) {
	        return $value;
	    } 
	    else {
	        $value_length = strlen( $value );
	        $qty = substr( $value, 0, $value_length - 1 );
	        $unit = strtolower( substr( $value, $value_length - 1 ) );
	        switch ( $unit ) {
	            case 'k':
	                $qty *= 1024;
	                break;
	            case 'm':
	                $qty *= 1048576;
	                break;
	            case 'g':
	                $qty *= 1073741824;
	                break;
	        }
	        return $qty;
	    }
	}
	
	/**
	 * Cleans up a directory path so that it doesn't contain /assets/
	 *
	 * @param string $dirname The path of the directory
	 * @return string
	 */
	public static function relative_asset_dir($dirname) {
		return preg_replace('|^'.ASSETS_DIR.'/|', '', $dirname);
	}
	
	
	public static function disable_authentication() {
		self::$authenticate = false;
	}
	

	/**
	 * The constructor for the Uploadify field. Sets some more default settings that require
	 * logic, e.g. upload_max_filesize.
	 *
	 * @param string $name The name of the field. For single files, omit the "ID" and use 
	 *					   just the relation name
	 * @param string $title The label for the field
	 * @param array $configuration Some extra confuguration settings to add {@see setVar}
	 * @param Form $form The parent form to this field
	 */
	public function __construct($name, $title = null, $configuration = array(), $form = null) {
		parent::__construct($name, $title, null, $form);
		// A little hack to make things easier in the CMS
		$controller = Director::urlParam('Controller');
		if(is_subclass_of($controller,"LeftAndMain") || is_subclass_of($controller,"ModelAdmin_CollectionController") || $controller == "ModelAdmin_CollectionController" || is_subclass_of($controller,"ModelAdmin_RecordController") || $controller == "ModelAdmin_RecordController") {
			self::$backend = true;
		}

		$this->setVar('sizeLimit', self::convert_bytes(ini_get('upload_max_filesize')));
		$this->setVar('buttonText', _t('Uploadify.BUTTONTEXT','Browse...'));
		$this->addParam('PHPSESSID', session_id());	
		$this->setVar('queueID', 'UploadifyFieldQueue_'.$this->Name());
		if($this->Backend()) {
			$this->template .= "Backend";
		}
		foreach($configuration as $key => $val) {
			$this->setVar($key, $val);
		}
	}


	/**
	 * Gets a configuration setting for this form field. If one is not defined for
	 * the instance, fall back on the static {@see $defaults} array.
	 *
	 * @param string $setting The setting to get
	 * @return mixed The value of the setting
	 */
	public function getSetting($setting) {
		if(isset($this->configuration[$setting]))
			return $this->configuration[$setting];
		$vars = Object::combined_static(get_class($this), "defaults");
		return isset($vars[$setting]) ? $vars[$setting] : false;
	}
		

	/**
	 * Sets a configuration setting for the instance. Will override the static
	 * {@see $defaults} array.
	 *
	 * @param string $setting The setting to configure
	 * @param mixed $value The value to set
	 */
	public function setVar($setting, $value) {
		$this->configuration[$setting] = $value;
	}


	/**
	 * Sets the allowed file types for this instance. Case-insensitive. Omit
	 * any *., and use the extension only, e.g. "jpg".
	 *
	 * @param array $array An array of file tyes
	 * @param string $desc The description for this set of file types, e.g. "Images only"
	 */
	public function setFileTypes($array, $desc = " ") {
		foreach($array as $type) {
			$this->fileTypes[] = strtolower($type);
			$this->fileTypes[] = strtoupper($type);
			$this->setVar('fileDesc', $desc);
		}
	}
	
	public function setUploadFolder($dir) {
		$this->uploadFolder = $dir;
	}
	
	public function setDeleteEnabled($bool = true) {
		$this->allowDelete = $bool;
	}
	
	public function setColumnCount($count) {
		$this->columnCount = $count;
	}
	
	public function getUploadFolder() {
		if($this->uploadFolder) {
			return self::relative_asset_dir($this->uploadFolder);
		}
		return "Uploads";
	}

	 
	/**
	 * The main upload handler. Takes the $_FILES data from the request and stores a File
	 * record {@see $defaults['file_class']}. Returns the ID of this new file to the 
	 * Javascript handler, for insertion into the parent form.
	 * Note: This handler may require authentication, and that may not be possible
	 * if the PHP setting "session_use_only_cookies" is on.
	 *
	 * @return int
	 */
	public function upload() {
		if(isset($_FILES["Filedata"]) && is_uploaded_file($_FILES["Filedata"]["tmp_name"])) {
			$upload_folder = $this->getUploadFolder();
			if($this->Backend()) {
				if(isset($_REQUEST['FolderID'])) {
					if($folder = DataObject::get_by_id("Folder", Convert::raw2sql($_REQUEST['FolderID']))) {
						$upload_folder = self::relative_asset_dir($folder->Filename);
					}
				}
			}
			$ext = strtolower(end(explode('.', $_FILES['Filedata']['name'])));
			$class = in_array($ext, self::$image_extensions) ? $this->getSetting('image_class') : $this->getSetting('file_class');
			$file = new $class();
			$u = new Upload();
			$u->loadIntoFile($_FILES['Filedata'], $file, $upload_folder);
			$file->write();
			echo $file->ID;
		} 
		else {
			echo ' '; // return something or SWFUpload won't fire uploadSuccess
		}
	}
	
	

	/**
	 * An AJAX endpoint for handling the creation of ta new folder (backend only)
	 *
	 * @param SS_HTTPRequest $request
	 * @return SSViewer
	 */
	public function newfolder(SS_HTTPRequest $request) {
		if(!$this->Backend()) {
			return;
		}
		if($request->requestVar('FolderID') && is_numeric($request->requestVar('FolderID'))) {
			if($folder = DataObject::get_by_id("Folder", Convert::raw2sql($request->requestVar('FolderID')))) {
				$upload_folder = $folder;
				if($request->requestVar('NewFolder')) {
					$new_name = trim($request->requestVar('NewFolder'),"/");
					$clean_path = self::relative_asset_dir($upload_folder->Filename);
					$new_folder = Folder::findOrMake($clean_path.$new_name);
					$upload_folder = $new_folder;
				}
				return $this->customise(array(
					'FolderDropdown' => $this->FolderDropdown($upload_folder->ID),
					'CurrentUploadFolder' => $upload_folder
				))->renderWith('FolderSelection');				
			}
		}
	}
	
	

	/**
	 * Deletes a file from the attached files. Will remove the record and the file from the filesystem
	 */
	public function deletefile() {
		if(!Permission::check("CMS_ACCESS_CMSMain"))
			return;
		if(isset($_REQUEST['FileID']) && is_numeric($_REQUEST['FileID'])) {
			if($file = DataObject::get_by_id($this->baseFileClass, $_REQUEST['FileID'])) {
				if($file->canDelete()) {
					$file->delete();
				}
			}
		}
	}


	/**
	 * Handles the retrieval of files to import from a given folder.
	 *
	 * @param SS_HTTPRequest $request
	 * @return SSViewer
	 */
	public function importlist(SS_HTTPRequest $request) {
		if($id = $request->requestVar('FolderID')) {
			if(is_numeric($id)) {
				$files = DataObject::get("File", "\"ParentID\" = $id AND \"File\".\"ClassName\" != 'Folder'");
				return $this->customise(array(
					'Files' => $files
				))->renderWith('ImportList');
			}
		}
	}
	

	/**
	 * Determines whether a user can see the folder selection interface
	 *
	 * @return boolean
	 */
	public function CanSelectFolder() {
		return $this->allowFolderSelection;
	}
	
	
	public function ColumnCount() {
		return $this->columnCount;
	}
	

	/**
	 * Builds out the metadata for the class attribute of the file input tag. This
	 * is later parsed by the jQuery metadata plugin.
	 *
	 * @return string
	 */
	public function Metadata() {
		$ret = array();
		$vars = Object::combined_static(get_class($this), "defaults");		
		foreach($vars as $setting => $value)
			$ret[] = "$setting : '".$this->getSetting($setting)."'";
		$data = implode(",", $ret);
		if(!empty($this->extraParams)) {
			$data .= ", scriptData : { ";
			$extras = array();
			foreach($this->extraParams as $key => $val) {
				$extras[] = "'$key' : '$val'";
			}
			$params = implode(",", $extras);
			$data .= $params . "}";
		}
		return $data;
	}
	

	/**
	 * Loads all the requirements and returns an Uploadify field to the template
	 *
	 * @return UploadifyField
	 */
	public function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript("uploadify/javascript/swfobject.js");
		Requirements::javascript("uploadify/javascript/uploadify.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-metadata/jquery.metadata.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-livequery/jquery.livequery.js");
		Requirements::javascript("uploadify/javascript/uploadify_init.js");
		Requirements::themedCSS("uploadify");

		$this->Message = $this->XML_val('Message');
		$this->MessageType = $this->XML_val('MessageType');

		$this->loadFileTypes();

		if($this->form) {
			if(!$this->getSetting('script')) {
				if(!self::$authenticate && $this->Backend()) {
					$this->setVar('script', urlencode(Controller::join_links(
						Director::baseURL(),
						"UploadifyUploader",
						"?uploadFolder=".$this->getUploadFolder().
						"&imageClass=".$this->getSetting('image_class').
						"&fileClass=".$this->getSetting('file_class')
					)));
				}
				else {
					$this->setVar('script',urlencode(Director::baseURL().Director::makeRelative($this->Link('upload'))));		
					// long script strings cause IO error on Apple/Mac Flash platforms\
					// so parse out complextablefield
					$script = urlencode(Director::baseURL().Director::makeRelative($this->Link('upload')));
					if($pos = strpos($script,'%3Fctf')) {
						$script = substr($script,0,$pos);
					}
					$this->setVar('script',$script);
				}
			}
			if(!$this->getSetting('refreshlink')) {
				$this->setVar('refreshlink', Director::baseURL().Director::makeRelative($this->Link('refresh')));
			}
		}
		if(!$this->getSetting('uploader')) {
			$this->setVar('uploader',Director::baseURL().'uploadify/javascript/uploadify.swf');
		}
		if(!$this->getSetting('cancelImg')) {
			$this->setVar('cancelImg',Director::baseURL().'uploadify/images/cancel.png');
		}

		return $this->renderWith($this->template);
	}
	
	
	/**
	 * Returns the folder selection dropdown to the template
	 *
	 * @param int $id The ID of the folder that is selected
	 * @return DropdownField
	 */
	public function FolderDropdown($id = null) {
		if(!$id) {
			$id = $this->CurrentUploadFolder()->ID;
		}
		$class = (class_exists("SimpleTreeDropdownField")) ? "SimpleTreeDropdownField" : "DropdownField";
		$group = new FieldGroup(
			$d = new $class("UploadFolderID_{$this->id()}", '', "Folder", $id, "Filename"),
			new LiteralField("slash{$this->id()}"," / "),
			new TextField("NewFolder_{$this->id()}", ""),
			$a = new FormAction("ok_{$this->id()}", _t('Uploadify.CHANGEFOLDERACTION','Change'))
		);
		$a->useButtonTag = true;
		$a->addExtraClass("{'url' : '".$this->Link('newfolder')."' }");
		$d->setValue($id);
		return $group;		
	}
	

	/**
	 * Creates a dropdown for selection of folders for choosing an existing file
	 *
	 * @return DropdownField
	 */
	public function ImportDropdown() {
		$class = (class_exists("SimpleTreeDropdownField")) ? "SimpleTreeDropdownField" : "DropdownField";
		$d = new $class("ImportFolderID_{$this->id()}", _t('Uploadify.CHOOSEIMPORTFOLDER','Choose a folder'), "Folder", null, "Filename");
		$d->setEmptyString('-- ' . _t('Uploadify.PLEASESELECT','Select a folder') . ' --');
		$d->addExtraClass("{'url' : '".$this->Link('importlist')."' }");
		return $d;
	}

	
	/**
	 * A quick template accessor to determine if this uploader allows multiple files
	 *
	 * @return boolean
	 */
	public function Multi() {
		return $this->getSetting('multi');
	}

	/**
	 * A quick template accessor to determine if delete link is enabled
	 *
	 * @return boolean
	 */
	public function DeleteEnabled() {
		return $this->allowDelete;
	}

	/**
	 * A template accessor to determine if we're in the backend.
	 * @todo: This isn't very clean. The backend should use a different object, so we're
	 * not always checking this.
	 *
	 * @return 
	 */
	public function Backend() {
		return self::$backend;
	}
	

	/**
	 * Determine the "current" upload folder, e.g. If the {@see $uploadFolder} is not defined,
	 * then get the folder of the last file uploaded
	 *
	 * @return Folder
	 */
	public function CurrentUploadFolder() {
		if($this->allowFolderSelection && $this->getUploadFolder() == "Uploads") {		
			if($result = $this->Files()) {
				if($result instanceof File) {
					return $result->Parent();
				}
				elseif($result instanceof DataObjectSet) {
					return $result->First()->Parent();
				}
			}
		}
		return Folder::findOrMake($this->getUploadFolder());
	}
	

	/**
	 * The text for the browse button
	 *
	 * @return string
	 */
	public function ButtonText() {
		return $this->getSetting('buttonText');
	}
	

	/**
	 * Link to the import action
	 *
	 * @return string
	 */
	public function ImportLink() {
		return $this->getSetting('importLink');
	}
	
	
	/**
	 * Template accessor to determine if debug is enabled.
	 *
	 * @return boolean
	 */
	public function DebugMode() {
		return self::$debug;
	}
	
	
	/**
	 * Compile the metadata into a readable list of key/value pairs.
	 *
	 * @return string
	 */
	public function DebugOutput() {
		$data = Convert::json2array("{".$this->Metadata()."}");
		$ret = "";
		foreach($data as $k => $v) {
			$ret .= "<div><strong>$k</strong>: $v</div>";
		}
		return $ret;
	}


	/**
	 * Adds an extra parameter to the Flash request
	 *
	 * @param string $key The name of the parameter
	 * @param mixed $value The value of the parameter
	 */
	public function addParam($key, $value) {
		$this->extraParams[$key] = $value;
	}
	

	/**
	 * A shortcut to setting the uploader to accept only images.
	 */
	public function imagesOnly() {
		$this->setFileTypes(self::$image_extensions, _t('Uploadify.IMAGES','Images'));
	}
	
	
	/**
	 * Parses the {@see $fileTypes} array and creates a string suitable for the Javascript
	 * object that will create this field.
	 */
	protected function loadFileTypes() {
		if(!empty($this->fileTypes)) {
			$this->setVar('fileExt','*.'.implode(';*.',$this->fileTypes));
		}
	}
	

	/**
	 * Turns on folder selection. Nulls out the {@see $uploadFolder} property
	 */
	public function allowFolderSelection() {
		$this->allowFolderSelection = true;
	}


	/**
	 * Turns on folder selection. Nulls out the {@see $uploadFolder} property
	 */
	public function removeFolderSelection() {
		$this->allowFolderSelection = false;
	}
	

	/**
	 * Activate the file upload on submit of the form.
	 */
	public function uploadOnSubmit() {
		$this->setVar('auto',false);
		$this->setVar('upload_on_submit', true);

	}
		
	
}
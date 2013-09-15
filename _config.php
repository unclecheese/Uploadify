<?php
define('UPLOADIFY_DIR', basename(dirname(__FILE__)));
if(isset($_REQUEST['PHPSESSID'])) {
	Session::start($_REQUEST['PHPSESSID']);
}
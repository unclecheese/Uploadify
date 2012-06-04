<?php

if(isset($_REQUEST['PHPSESSID'])) {
	Session::start($_REQUEST['PHPSESSID']);
}
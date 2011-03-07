<?php

if(isset($_POST['PHPSESSID'])) {
	Session::start($_POST['PHPSESSID']);
}
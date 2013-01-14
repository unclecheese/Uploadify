<?php

if ($_SERVER['HTTP_USER_AGENT'] == 'Shockwave Flash' and isset($_POST['PHPSESSID'])) {
	Session::start($_POST['PHPSESSID']);
}

<?php
	require 'Facebook/FacebookSession.php';
	require 'Facebook/FacebookJavaScriptLoginHelper.php';
	FacebookSession::setDefaultApplication("823148504363911", "2e6875c6ad028b5a7f1099016b4f535f");
	$helper = new FacebookJavaScriptLoginHelper();
	var_dump($helper);
	echo 'Hello';
?>

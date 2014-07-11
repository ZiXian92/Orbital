<?php
	require_once "dropbox-sdk/Dropbox/autoload.php";
	use \Dropbox as dbx;
	#mail("zixian1992@hotmail.com", "Hello", "Mizuki Nana desu~", "From: admin@relivethemoment.host-ed.me\r\n");

	$accessToken = file_get_contents("accessToken.txt");
	$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0")
	$accountInfo = $dbxClient->getAccountInfo();
	print_r $accountInfo;
?>

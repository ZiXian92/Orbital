<?php
	require_once "dropbox-sdk/Dropbox/autoload.php";
	use \Dropbox as dbx;
	#mail("zixian1992@hotmail.com", "Hello", "Mizuki Nana desu~", "From: admin@relivethemoment.host-ed.me\r\n");

	/*function getWebAuth(){
		$appInfo = dbx\AppInfo::loadFromJsonFile("app-info.json");
		$clientIdentifier = "localhost/1.0";
		$redirectUri = "https://localhost/dropbox-auth-finish";
		$csrfTokenStore = new dbx\ArrayEntryStore($_SESSION, 'dropbox-auth-csrf-token');
		return new dbx\WebAuth($appInfo, $clientIdentifier, $redirectUri, $csrfTokenStore);
	}*/

	$accessToken = file_get_contents("accessToken.txt");
	$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");
	#$accountInfo = $dbxClient->getAccountInfo();
	#print_r($accountInfo);
	#$f = fopen("blank.txt", "r");
	#$res = $dbxClient->uploadFile("/blank.txt", dbx\WriteMode::add(), $f);
	#fclose($f);
	try{
		$res = $dbxClient->delete("/blank.txt");
		print_r($res);
	}
	catch(Exception $e){}
	finally{
		echo "File no longer exists.";
	}
?>

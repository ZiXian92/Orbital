<?php
	require_once "dropbox-sdk/Dropbox/autoload.php";
	use \Dropbox as dbx;
	#mail("zixian1992@hotmail.com", "Hello", "Mizuki Nana desu~", "From: admin@relivethemoment.host-ed.me\r\n");

	function getWebAuth(){
		$appInfo = dbx\AppInfo::loadFromJsonFile("app-info.json");
		$clientIdentifier = "localhost/1.0";
		$redirectUri = "https://localhost/dropbox-auth-finish";
		$csrfTokenStore = new dbx\ArrayEntryStore($_SESSION, 'dropbox-auth-csrf-token');
		return new dbx\WebAuth($appInfo, $clientIdentifier, $redirectUri, $csrfTokenStore);
	}

	$authorizeUrl = getWebAuth()->start();
	header("Location: ".$authorizeUrl);

	list($accessToken, $userId, $urlState) = getWebAuth()->finish($_GET);
	assert($urlState===null);

	file_put_contents("accessToken.txt", $accessToken);
?>

<?php
	/* Handles entry view and delete requests */

	require 'model.php';
	require_once 'dropbox-sdk/Dropbox/autoload.php';
	use \Dropbox as dbx;

	session_start();
	
	/* Block out all unauthorised execution of this script */
	if(!isset($_SESSION['user_id'])){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		header("Location: http://".$_SERVER['HTTP_HOST']);
		exit(0);
	}

	/* On passing the logged in check, authenticates if the user is
	 * dealing with his/her own entry and not someone else's.
	 */
	$model = new Model();

	#var_dump($model->authenticate_entry_request($_SESSION['user_id'], $_GET['id']));
	#exit(0);

	if($model->authenticate_entry_request($_SESSION['user_id'], $_GET['id'])){
		/* Gets the file path since the file will be
		 * dealt with anyway.
		 */
		$file = $model->get_entry_file($_GET['id']);

	/* Loads Dropbox access token */
		$accessToken = file_get_contents("accessToken.txt");

	/* Creates a new Dropbox client to access API */
		$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");

		/* Applies the appropriate action based on the request */
		switch($_GET['action']){
			case "view": header("Content-type: application/pdf");
				header("Content-disposition: inline; filename=\"entry.pdf\"");
				$f = fopen("/tmp/".$file, "wb");

		/* Displays the file if it exists in the storage */
				if($dbxClient->getFile("/".$file, $f)!=null){
					readfile("/tmp/".$file);
					fclose($f);
					unlink("/tmp/".$file);
					exit(0);
				}
				file_put_contents("message.txt", "File does not exist.");
				break;
			case "delete": unlink($file);
					$model->remove_entry($_GET['id']);
					break;
		}
	}

	/* Redirects to home page */
	header("Location: https://".$_SERVER['HTTP_HOST']);
?>

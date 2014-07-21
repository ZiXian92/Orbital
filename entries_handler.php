<?php
	/* Handles entry view and delete requests
	 * Incoming URLs should have URI of the format
	 * /entries_handler/action/entry_id
	 */

	require 'model.php';
	require_once 'dropbox-sdk/Dropbox/autoload.php';
	use \Dropbox as dbx;

	#Displays the entry identified by id in the browser.
	#Use only GET request.
	function view_entry($id){
		$model = new Model();
		if($_SERVER['REQUEST_METHOD']!='GET'){
			http_response_code(400);
			return;
		}
		if($model->authenticate_entry_request($_SESSION['user_id'], $id)){
			#Sets the header to display PDF file in browser
			header("Content-type: application/pdf");
			header("Content-disposition: inline; filename=\"entry.pdf\"");
			#Gets the access token to access Dropbox API
			$accessToken = file_get_contents("accessToken.txt");

			#Creates Dropbox client to access files
			$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");
			#Gets the file path
			$file = $model->get_entry_file($id);

			#Downloads the PDF file into /tmp folder
			$f = fopen("/tmp/".$file, "wb");
			if($dbxClient->getFile("/".$file, $f)!=null){
				readfile("/tmp/".$file);
				fclose($f);
				unlink("/tmp/".$file);
			}
			#Redirects to 'Not Found' page if entry does not exist
			else
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
		else
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404'); 
	}

	#Deletes an entry identified by id.
	#Only accepts POST request.
	function delete_entry($id){
		if($_SERVER['REQUEST_METHOD']!='POST'){
			http_response_code(400);
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
			return;
		}

		$model = new Model();
		if($model->authenticate_entry_request($_SESSION['user_id'], $id)){
			#Gets the access token to access Dropbox API
			$accessToken = file_get_contents("accessToken.txt");

			#Creates Dropbox client to access files
			$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");
			#Gets the file path
			$file = $model->get_entry_file($id);
			try{
				$dbxClient->delete("/".$file);
			}
			catch(Exception $e){}
			$model->remove_entry($id);
		}
		else
			http_response_code(400);
	}

	session_start();
	
	/* Block out all unauthorised execution of this script */
	if(!isset($_SESSION['user_id'])){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		header("Location: http://".$_SERVER['HTTP_HOST']);
		exit(0);
	}

	#Gets the URL elements
	$url_elem = explode('/', $_SERVER['REQUEST_URI']);

	#To deal with cases when the number of parameters is insufficient
	try{
		$action = strip_tags((string)$url_elem[2]);
		$id = strip_tags((string)$url_elem[3]);
		switch($action){
			case 'view': view_entry($id);
				break;
			case 'delete': delete_entry($id);
				break;
			default: http_response_code(400);
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
	}
	catch(Exception $e){
		http_response_code(400);
		if($_SERVER['REQUEST_METHOD']=='GET')
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
	}
	exit();

	/* On passing the logged in check, authenticates if the user is
	 * dealing with his/her own entry and not someone else's.
	 */
	$model = new Model();

	if($model->authenticate_entry_request($_SESSION['user_id'], $_GET['id'])){
		/* Gets the access token to access Dropbox API */
		$accessToken = file_get_contents("accessToken.txt");

		/* Creates Dropbox client to access files */
		$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");
		/* Gets the file path since the file will be
		 * dealt with anyway.
		 */
		$file = $model->get_entry_file($_GET['id']);

		/* Applies the appropriate action based on the request */
		switch($_GET['action']){
			case "view": header("Content-type: application/pdf");
				header("Content-disposition: inline; filename=\"entry.pdf\"");
				/* Downloads the PDF file into /tmp folder */
				$f = fopen("/tmp/".$file, "wb");
				if($dbxClient->getFile("/".$file, $f)!=null){
					readfile("/tmp/".$file);
					fclose($f);
					unlink("/tmp/".$file);
					exit(0);
				}
				file_put_contents("message.txt", "Entry does not exist. Please delete t from the records.");
				#readfile($file);
				break;
			case "delete": #unlink($file);
				try{
					$dbxClient->delete("/".$file);
				}
				catch(Exception $e){}
				$model->remove_entry($_GET['id']);
				break;
		}
	}

	/* Redirects to home page */
	header("Location: https://".$_SERVER['HTTP_HOST']);
?>

<?php
	/* Handles entry view and delete requests */

	require 'model.php';

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

	if($model->authenticate_entry_request($_SESSION['user_id'], $_GET['id'])){
		/* Gets the file path since the file will be
		 * dealt with anyway.
		 */
		$file = $model->get_entry_file($_GET['id']);

		/* Applies the appropriate action based on the request */
		switch($_GET['action']){
			case "view": header("Content-type: application/pdf");
					header("Content-disposition: inline; filename=\"entry.pdf\"");
					readfile($file);
					exit(0);
			case "delete": unlink($file);
					$model->remove_entry($_GET['id']);
					break;
		}
	}

	/* Redirects to home page */
	header("Location: https://".$_SERVER['HTTP_HOST']);
?>

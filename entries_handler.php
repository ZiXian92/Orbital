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

	$model = new Model();

	if($model->authenticate_entry_request($_SESSION['user_id'], $_GET['id'])){
		$file = $model->get_entry_file($_GET['id']);
		switch($_GET['action']){
			case "view": break;
			case "delete": unlink($file);
					$model->remove_entry($_GET['id']);
					break;
		}
	}

	header("Location: http://".$_SERVER['HTTP_HOST']);
?>

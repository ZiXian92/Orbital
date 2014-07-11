<?php
	/* admin.php
	 * Performs all the functionalities available to only the administrator.
	 * Includes: Listing all entries of any selected user
	 *	     Deletion of any valid entry
	 */

	require "model.php";
	require "view.php";
	require_once 'dropbox-sdk/Dropbox/autoload.php';
	use \Dropbox as dbx;

	session_start();

	/* If the user is not logged in or the user is not the administrator,
	 * re-direct to home page
	 */
	if(!isset($_SESSION['user_id']) || $_SESSION['user_id']!=0){
		if($_SERVER['HTTPS']=="on")
			header("Location: https://".$_SERVER['HTTP_HOST']);
		else
			header("Location: http://".$_SERVER['HTTP_HOST']);
		exit(0);
	}

	$model = new Model();

	/* Check for and remove any tags in the query string parameters */
	$action = strip_tags($_GET['action']);
	$id = strip_tags($_GET['id']);

	/* Apply the action only if the ID to act on is valid */
	if($model->contains_id($id)){
		switch($action){
			case "view": $arr = array();
					$model->set_template($arr, "User ".(string)$id);
					$arr['content'] = $model->list_entries_by_id($id);
					$view = new View($arr);
					$view->render();
					exit(0);
			case "delete": $accessToken = file_get_contents("accessToken.txt");
					$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");
					$model->remove_user($id, $dbxClient);
					break;
		}
	}
	header("Location: https://".$_SERVER['HTTP_HOST']);
	exit(0);
?>

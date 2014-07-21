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

	#Displays the list of entries authored by user with the given ID.
	#Throws exception for invalid ID.
	function view_user($id){
		$id = (int)strip_tags((string)$id);
		$model = new Model();
		if($model->contains_id($id)){
			$arr = $model->get_page_params('User '.(string)$id);
			$arr['title'] = 'User '.(string)$id;
			$arr['content'] = $model->list_entries_by_id($id);
			$view = new View($arr);
			$view->render();
		}
		else
			throw new Exception('Invalid user ID');
	}

	#Deletes all records of user with the given ID.
	#Throws exception for invalid ID.
	function delete_user($id){
		$id = (int)strip_tags((string)$id);
		$model = new Model();
		if($model->contains_id($id)){
			$accessToken = file_get_contents('accessToken.txt');
			$dbxClient = new dbx\Client($accessToken, "relivethatmoment/1.0");
			$model->remove_user($id, $dbxClient);
		}
		else
			throw new Exception('Invalid user ID');
	}

	session_start();

	/* If the user is not logged in or the user is not the administrator,
	 * re-direct to home page
	 */
	if(!isset($_SESSION['user_id']) || $_SESSION['user_id']!=0){
		if(isset($_SESSION['user_id']))
			header("Location: https://".$_SERVER['HTTP_HOST']);
		else
			header("Location: http://".$_SERVER['HTTP_HOST']);
		exit(0);
	}

	$url_elem = explode('/', $_SERVER['REQUEST_URI']);
	try{
		$action = $url_elem[2];
		$id = (int)$url_elem[3];

		#Apply the appropriate function for the action
		switch($action){
			case "view": view_user($id);
				break;
			case "delete": delete_user($id);
				break;
			default: throw new Exception('Invalid action');
		}
	}
	catch(Exception $e){
		http_response_code(400);
		if($_SERVER['REQUEST_METHOD']!='POST')
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
	}
?>

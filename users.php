<?php
	/* Handles all user account-related requests, such as signup,
	 * login, logout, and reset password.
	 * To-Do: Update $_GET['error'] is the appropriate error messages
	 * in each of the sections.
	 */

	require "model.php";

	/* Checks if the supplied email address is valid */
	function is_valid_email($email){
		if(preg_match("/^[a-zA-Z0-9_]+@[a-z]+\.com$/", $email))
			return true;
		return false;
	}

	$model = new Model();

	/* Executes request to sign up as new user */
	if(isset($_GET['action']) && $_GET['action']=="signup"){
		/* If email is not currently
		 * used by another user, add the user to database.
		 * Then, redirects to log in page.
		 * Validity of email address is done on browser
		 * using Javascript.
		 */
		if(is_valid_email($_POST['email']) && !$model->contains_email($_POST['email'])){
			$model->add_user($model->get_user_id(), $_POST['name'], $_POST['passwd'], $_POST['email']);
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=signedup";
		}

		/* Else, return to signup page */
		else
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
	}

	/* Handles login requests */
	elseif(isset($_GET['action']) && $_GET['action']=="login"){
		/* If login credentials are correct, start a new session.
		 * Sets the user's ID and username as session variables.
		 * Else, returns the user to the login page.
		 */
		if($model->is_valid_user($_POST['email'], $_POST['passwd'])){
			session_start();
			$user = $model->get_user($_POST['email'], $_POST['passwd']);
			$_SESSION['user_id'] = $user['ID'];
			$_SESSION['username'] = $user['USERNAME'];
			$url = "http://".$_SERVER['HTTP_HOST'];
		}
		else{
			$_GET['error'] = "Incorrect email or password";
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=login";
		}
	}

	/* Handles logout requests */
	elseif(isset($_GET['action']) && $_GET['action']=="logout"){
		session_start();
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=loggedout";
	}

	/* Redirects to home page if user tries to access this script directly
	 * from URL.
	 */
	else
		$url = "http://".$_SERVER['HTTP_HOST'];

	unset($_GET['action']);
	header("Location: ".$url);
	exit(0);
?>

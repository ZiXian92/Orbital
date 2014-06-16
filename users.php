<?php
	/* Handles all user account-related requests, such as signup,
	 * login, logout, change and forgot password.
	 * To-Do: Handle forgot password.
	 */

	require "model.php";

	/* Checks if the supplied email address is valid */
	function is_valid_email($email){
		$format = "/^[a-zA-Z0-9]+[a-zA-Z0-9_]*@[a-z]+\.com$/";
		return preg_match($format, $email);
	}

	/* Checks if the given password is valid */
	function is_valid_passwd($passwd){
		$format = "/^[a-zA-Z0-9]{10}$/";
		return preg_match($format, $passwd);
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
			if(is_valid_passwd((string)$_POST['passwd']) && $_POST['passwd']===$_POST['re-passwd']){
				$model->add_user($model->get_user_id(), (string)$_POST['name'], (string)$_POST['passwd'], $_POST['email']);
				$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signedup";
			}
			else{
				file_put_contents("message.txt", "Invalid password or the 2 passwords do not match.\nPassword should contain only 10 alphanumeric characters.");
				$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
			}
		}

		/* Else, return to signup page */
		else{
			file_put_contents("message.txt", "Invalid email or email is used by another user.");
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
		}
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
			file_put_contents("message.txt", "Incorrect email or password");
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=login";
		}
	}

	/* Handles password change request */
	elseif(isset($_GET['action']) && $_GET['action']=="changepasswd"){
		session_start();
		if(!isset($_SESSION['user_id'])){
			$url = "http://".$_SERVER['HTTP_HOST'];
			$_SESSION = array();
			session_destroy();
			setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		}
		else{
			if($model->get_password_by_id($_SESSION['user_id'])==
				SHA1($_POST['old_passwd'])){
				$model->set_new_password($_SESSION['user_id'], $_POST['new_passwd']);
				file_put_contents("message.txt", "Success!");
			}
			else{
				file_put_contents("message.txt", "Failed to change password.");
			}
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=change_passwd";
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

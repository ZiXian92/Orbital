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

	/* Checks if the given password is valid
	 * Password must contain strictly 10 alphanumberic characters
	 */
	function is_valid_passwd($passwd){
		$format = "/^[a-zA-Z0-9]{10}$/";
		return preg_match($format, $passwd);
	}

	session_start();

	$model = new Model();

	/* Handles exception of users entering users.php into URL */
	if(!isset($_GET['action'])){
		if(isset($_SESSION['user_id']))
			$url = "https://";
		else
			$url = "http://";
		$url.=$_SERVER['HTTP_HOST'];
	}

	/* Handles logout requests */
	elseif($_GET['action']=="logout"){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=loggedout";
	}

	/* Subsequent blocks should only be executed if the method is POST */
	elseif($_SERVER['REQUEST_METHOD']!="POST"){
		if(isset($_SESSION['user_id']))
			$url = "https://";
		else
			$url = "http://";
		$url.=$_SERVER['HTTP_HOST'];
	}

	/* Executes request to sign up as new user */
	elseif($_GET['action']=="signup"){
		$name = strip_tags((string)$_POST['name']);
		$email = strip_tags((string)$_POST['email']);
		$passwd = strip_tags((string)$_POST['passwd']);
		$passwd2 = strip_tags((string)$_POST['re-passwd']);

		/* Checks if $name is still valid after removing tags */
		if(strlen($name)==0){
			file_put_contents("message.txt", "Invalid username.");
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
		}

		/* Checks if the username is already taken */
		elseif($model->contains_username($name)){
			file_put_contents("message.txt", "This username is already taken.");
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
		}

		/* If email is not currently used by another user,
		 * add the user to database.
		 */
		elseif(!is_valid_email($email) || $model->contains_email($email)){
			file_put_contents("message.txt", "Invalid email or email is used by another user.");
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
		}

		/* Validates password */
		elseif(!is_valid_passwd($passwd) || !($passwd===$passwd2)){
			file_put_contents("message.txt", "Invalid password or the 2 passwords do not match.<br/>Password should contain only 10 alphanumeric characters.");
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
		}
		//On successful registration
		else{
			//Generate activation code
			$code = md5(uniqid(rand(), true));
			$model->add_user($model->get_user_id(), $name, $passwd, $email, $code);
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=signedup";
			$subject = "Account Activation";
			$message = "Thank you for signing up. To get started, please click on the link below to activate your account.\nhttps://".$_SERVER['HTTP_HOST']."/activate.php?x=".urlencode($email)."&y=".$code;
			header("Location: ".$url);

			//Sends email with activation link to the user
			mail($email, $subject, $message, "From: admin@".$_SERVER['HTTP_HOST']);
			exit(0);
		}
	}

	/* Handles login requests */
	elseif($_GET['action']=="login"){
		$email = strip_tags((string)$_POST['email']);
		$passwd = strip_tags((string)$_POST['passwd']);

		/* If login credentials are correct, start a new session.
		 * Sets the user's ID and username as session variables.
		 * Else, returns the user to the login page.
		 */
		if($model->is_valid_user($email, $passwd)){
			$user = $model->get_user($email, $passwd);
			$_SESSION['user_id'] = $user['ID'];
			$_SESSION['username'] = $user['USERNAME'];
			$url = "https://".$_SERVER['HTTP_HOST'];
		}
		else{
			file_put_contents("message.txt", "Incorrect email or password");
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=login";
		}
	}

	/* Handles password change request.
	 * Code is executed only if the user is logged in.
	 */
	elseif($_GET['action']=="changepasswd"){
		$old_passwd = strip_tags((string)$_POST['old_passwd']);
		$new_passwd = strip_tags((string)$_POST['new_passwd']);
		$new_passwd2 = strip_tags((string)$_POST['re-new_passwd']);
		if(isset($_SESSION['user_id'])){
			if(!is_valid_passwd($old_passwd) ||
			$model->get_password_by_id($_SESSION['user_id'])!=
			SHA1($old_passwd))
				file_put_contents("message.txt", "Incorrect current password.");
			elseif(!is_valid_passwd($new_passwd))
				file_put_contents("message.txt", "Invalid new password.");
			elseif($new_passwd!=$new_passwd2)
				file_put_contents("message.txt", "The 2 new passwords do not match.");
			else{
				$model->set_new_password($_SESSION['user_id'], $new_passwd);
				file_put_contents("message.txt", "Success!");
			}
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=change_passwd";
		}
		else
			$url = "http://".$_SERVER['HTTP_HOST'];
	}

	/* Destroys the session if user is not logged in */
	if(!isset($_SESSION['user_id'])){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
	}

	unset($_GET['action']);
	header("Location: ".$url);
	exit(0);
?>

<?php
	/* Handles all user account-related requests, such as signup,
	 * login, logout, change and forgot password.
	 * To-Do: Touch up password reset and signup portions once emailing is
	 * resolved.
	 * Modify account activation to allow for manual activation by
	 * administrator.
	 */

	require "model.php";
	DEFINE('USER', 'zixian');
	DEFINE('PASS', 'Nana7Nana');

	/* Checks if the supplied email address is valid */
	function is_valid_email($email){
		$format = "/^[a-zA-Z0-9]+[a-zA-Z0-9_]*@.+\..+$/";
		return preg_match($format, $email);
	}

	/* Checks if the given password is valid
	 * Password must contain strictly 10 alphanumberic characters
	 */
	function is_valid_passwd($passwd){
		$format = "/^[a-zA-Z0-9]{10}$/";
		return preg_match($format, $passwd);
	}

	/* Ensure that the rest of the script is accessed via HTTPS */
	if(empty($_SERVER['HTTPS'])){
		header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		exit(0);
	}

	session_start();

	$model = new Model();

	$req_url = 'https://api.sendgrid.com/';
	$params = array(
		'api_user' => USER,
		'api_key' => PASS,
		'from' => 'donotreply@localhost.com'
	);

	/* Handles exception of users entering users.php into URL */
	if(!isset($_GET['action'])){
		if(isset($_SESSION['user_id']))
			$url = "https://";
		else
			$url = "http://";
		$url.=$_SERVER['HTTP_HOST'];
	}

	/* Handles logout requests
	 * Logout is done by erasing all the session variables and destroying
	 * the cookie witht he session ID
	 */
	elseif($_GET['action']=="logout"){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		$url = "http://".$_SERVER['HTTP_HOST']."/loggedout";
	}

	/* Activates the appropriate account if the email and
	 * activation code matches. Else, redirects to page not found
	 */
	elseif($_GET['action']=="activate"){
		/* If activation script is called manually by user */
		if(isset($_GET['x']) && isset($_GET['y'])){
			$email = urldecode(strip_tags($_GET['x']));
			$code = strip_tags($_GET['y']);
			if($model->activate($email, $code)){
				file_put_contents("message.txt", "Account activated! Please proceed to log in.");
				$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=login";
			}
			else
				$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=404";
		}

		/* If activation script is called by administrator*/
		elseif($_SESSION['user_id']==0 && isset($_GET['id'])){
			$id = (int)strip_tags((string)$_GET['id']);
			$model->admin_activate($id);
			$url = "https://".$_SERVER['HTTP_HOST'];
		}
		else
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=404";
	}

	/* Handles password reset requests */
	elseif($_GET['action']=="reset_passwd"){
		/* Initialise $name and $email, whether the information
		 * is submitted by user through form or directly by
		 * admin's link
		 */
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$name = strip_tags($_POST['name']);
			$email = strip_tags($_POST['email']);
		}
		elseif($_SESSION['user_id']==0 && isset($_GET['email'])){
			$name = strip_tags($_GET['name']);
			$email = strip_tags(urldecode($_GET['email']));
		}
		/* Prevent any prankster attempt */
		else{
			header("Location: https://".$_SERVER['HTTP_HOST']);
			exit(0);
		}

		/* Attempts to reset the password given the name and email
		 * and retrieves the new password on successful attempt
		 */
		$passwd = $model->reset_password($name, $email);
		if($passwd){
			# For non-admin users
			if(!isset($_SESSION['user_id']))
				file_put_contents("message.txt", "Password successfully reset. Please check your email for your new password. Please change your password upon logging in. If you do not receive any email, please contact the site administrator regarding your password.");

			/* Sets the POSTFIELDS for sending email */
			$params['to'] = $email;
			$params['subject'] = 'Password Reset';
			$params['text'] = 'Your password has been reset. Your new password is '.$passwd.'. Please change your password upon logging in.';
			/*
			# Forms the mail request URL
			$request = $req_url."api/mail.send.json";

			# Initialise the request
			$session = curl_init($request);

			# Use POST method to API
			curl_setopt($session, CURL_OPT_POST, true);

			# Supplies the POST parameters
			curl_setopt($session, CURLOPT_POSTFIELDS, $params);

			# Do not return header but return response
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

			#Sends the email
			curl_exec($session);
			curl_close($session);*/
		}
		else
			file_put_contents("message.txt", "Your request could not be processed. Either the Username or Email is incorrect or your account is not activated.");
		# If password reset is done through form
		if($_SERVER['REQUEST_METHOD']=="POST")
			$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=reset_passwd";
		# If password reset is done by administrator
		else
			$url = "https://".$_SERVER['HTTP_HOST'];
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
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}

		/* Checks if the username is already taken */
		elseif($model->contains_username($name)){
			file_put_contents("message.txt", "This username is already taken.");
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}

		/* If email is not currently used by another user,
		 * add the user to database.
		 */
		elseif(!is_valid_email($email) || $model->contains_email($email)){
			file_put_contents("message.txt", "Invalid email or email is used by another user.");
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}

		/* Validates password */
		elseif(!is_valid_passwd($passwd) || !($passwd===$passwd2)){
			file_put_contents("message.txt", "Invalid password or the 2 passwords do not match.<br/>Password should contain only 10 alphanumeric characters.");
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}
		//On successful registration
		else{
			//Generate activation code
			$code = md5(uniqid(rand(), true));
			$model->add_user($model->get_user_id(), $name, $passwd, $email, $code);
			$url = "https://".$_SERVER['HTTP_HOST']."/signedup";
			# Forms the activation link
			$activate_code = 'https://'.$_SERVER['HTTP_HOST']."/users.php?action=activate&x=".urlencode($email)."&y=".$code;

			# Sets the remaining parameters for sending email
			$params['to'] = $email;
			$params['subject'] = 'Account Activation';
			$params['html'] = '<p>Thank you for signing up wth Relive That Moment. Click <a href="'.$activate_code.'">here</a> to activate your account.</p>';

			# Forms the mail request URL
			$request = $req_url."api/mail.send.json";

			# Initialise the request
			$session = curl_init($request);

			# Use POST method to API
			curl_setopt($session, CURL_OPT_POST, true);

			# Supplies the POST parameters
			curl_setopt($session, CURLOPT_POSTFIELDS, $params);

			# Do not return header but return response
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

			#Sends the email
			curl_exec($session);
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
			#file_put_contents("message.txt", "Incorrect email or password or account is not activated.");
			$url = "https://".$_SERVER['HTTP_HOST']."/login";
		}
	}

	/* Handles password change request.
	 * Code is executed only if the user is logged in.
	 */
	elseif($_GET['action']=="changepasswd"){
		$old_passwd = strip_tags((string)$_POST['old_passwd']);
		$new_passwd = strip_tags((string)$_POST['new_passwd']);
		$new_passwd2 = strip_tags((string)$_POST['re-new_passwd']);
		/*if(isset($_SESSION['user_id'])){
			if(!is_valid_passwd($old_passwd) ||
			$model->get_password_by_id($_SESSION['user_id'])!=
			SHA1($old_passwd))
				#file_put_contents("message.txt", "Incorrect current password.");
			elseif(!is_valid_passwd($new_passwd))
				#file_put_contents("message.txt", "Invalid new password.");
			elseif($new_passwd!=$new_passwd2)
				#file_put_contents("message.txt", "The 2 new passwords do not match.");
			else{
				$model->set_new_password($_SESSION['user_id'], $new_passwd);
				#file_put_contents("message.txt", "Success!");
			}
			$url = "https://".$_SERVER['HTTP_HOST']."/change_passwd";
		}
		else
			$url = "http://".$_SERVER['HTTP_HOST'];*/
	}

	/* Destroys the session if user is not logged in */
	if(!isset($_SESSION['user_id'])){
		$_SESSION = array();
		#session_destroy();
		#setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
	}

	unset($_GET['action']);
	header("Location: ".$url);
	exit(0);
?>

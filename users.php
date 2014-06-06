<?php
	require "model.php";

	function is_valid_email($email, $model){
		if(preg_match("/^[a-zA-Z0-9_]+@[a-z]+\.com$/", $email))
			return true;
		return false;
	}

	$model = new Model();

	/* Executes request to sign up as new user */
	if(isset($_GET['action']) && $_GET['action']=="signup"){
		/* If email is not currently
		 * used by another user, add the user to database.
		 * Then, automatically log the user in.
		 * Validity of email address is done on browser
		 * using Javascript.
		 */
		if($model->contains_email($_POST['email'])){
			$model->add_user($model->get_user_id(), $POST['name'], $POST['passwd'], $_POST['email']);
			$url = "http://".$_SERVER['HTTP_HOST']."/users.php?action=login";
		}

		/* Else, return to signup page */
		else
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=signup";
		unset($_GET['action']);
		header("Location: ".$url);
		exit(0);
	}

	/* Handles login requests */
	elseif(isset($_GET['action']) && $_GET['action']=="login"){
		/* If login credentials are correct, send cookies to identify
		 * user as logged in and redirect to the user's home page.
		 * Else, returns the user to the login page.
		 */
		if($model->is_valid_user($_POST['email'], $_POST['passwd'])){
			//set cookies
			$url = "http://".$_SERVER['HTTP_HOST'];
		}
		else
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=login";
		unset($_GET['action']);
		header("Location: ".$url);
		exit(0);
	}

	/* Handles logout requests */
	elseif(isset($_GET['action']) && $_GET['action']){
		//delete cookies from user side
		$url = "http://".$_SERVER['HTTP_HOST'];
		unset($_GET['action']);
		header("Location: ".$url);
		exit(0);
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

<?php
	/* Handles all user account-related requests, such as signup,
	 * login, logout, change and forgot password.
	 * Incoming URLs have request URI of the form
	 * /users/action or /users/action/params
	 * Requests of the form /users is dealt with by redirecting to index.php
	 * in .htaccess file.
	 * The case of request URI being /users/ is checked after
	 * processing the incoming request URI.
	 */

	require "model.php";
	DEFINE('USER', 'zixian');
	DEFINE('PASS', 'Nana7Nana');

	#Checks if user with the supplied credentials exists in database
	#Request is successful only if called by POST method and JSON string
	#containing email and password is supplied.
	function validate_login(){
		$req_headers = getallheaders();
		if($_SERVER['REQUEST_METHOD']=='POST' &&
		$req_headers['Content-Type']=='application/json; charset=UTF-8'){
			$req_params = json_decode(file_get_contents('php://input'), true);
			$model = new Model();
			try{
				$email = strip_tags($req_params['email']);
				$passwd = strip_tags($req_params['password']);
				if($model->is_valid_user($email, $passwd))
					echo 'Login successful';
				else
					echo 'Incorrect email and/or password.';
			}
			catch(Exception $e){
				http_response_code(400);
			}
		}
		else
			http_response_code(400);
	}

	#Checks the login credentials and logs the user in if is a valid user.
	#If not a valid user, which should not happen since validate_login
	#is executed before submitting form, redirects back to login page.
	#Reject access to URI /users/login.
	function login(){
		if($_SERVER['REQUEST_METHOD']!='POST'){
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
			exit();
		}

		$model = new Model();

		#Remove any tags to prevent XSS attacks.
		$email = strip_tags((string)$_POST['email']);
		$passwd = strip_tags((string)$_POST['passwd']);

		#Sets the session variables if login is successful
		if($model->is_valid_user($email, $passwd)){
			$user = $model->get_user($email, $passwd);
			$_SESSION['user_id'] = $user['ID'];
			$_SESSION['username'] = $user['USERNAME'];
			header('Location: https://'.$_SERVER['HTTP_HOST']);
		}
		else
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/login');
	}

	#Logs the user out
	function logout(){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		header('Location: http://'.$_SERVER['HTTP_HOST'].'/loggedout');
	}

	#Checks if the given username is already taken.
	#Only accessible by POST method and parameters must be sent in
	#JSON format.
	function checkUsername(){
		$req_headers = getallheaders();
		if($_SERVER['REQUEST_MTHOD']='POST' && $req_headers['Content-Type']=='application/json; charset=UTF-8'){
			$req_params = json_decode(file_get_contents('php://input'), true);
			try{
				$name = strip_tags($req_params['name']);
				$model = new Model();
				if($model->contains_username($name))
					echo 'This username is already taken.';
				else
					echo 'Ok';
			}
			catch(Exception $e){
				http_response_code(400);
			}
		}
		else
			http_response_code(400);
	}

	#Checks if the given email is taken by another user.
	#Only accessible by POST request using JSON format.
	function checkEmail(){
		$req_headers = getallheaders();
		if($_SERVER['REQUEST_METHOD']=='POST' && $req_headers['Content-Type']=='application/json; charset=UTF-8'){
			$req_params = json_decode(file_get_contents('php://input'), true);
			try{
				$email = strip_tags($req_params['email']);
				$model = new Model();
				if($model->contains_email($email))
					echo 'This email address is already used by another user.';
				else
					echo 'Ok';
			}
			catch(Exception $e){
				http_response_code(400);
			}
		}
		else
			http_response_code(400);
	}

	#Registers a new user.
	#Request must be a submission of form with fields name, email,
	#passwd and re-passwd.
	function signup(){
		#Rejects any invalid requests
		if($_SERVER['REQUEST_METHOD']!='POST' || !isset($_POST['name'])
		|| !isset($_POST['email']) || !isset($_POST['passwd']) ||
		!isset($_POST['re-passwd'])){
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/signup');
			exit();
		}

		$name = strip_tags($_POST['name']);
		$email = strip_tags($_POST['email']);
		$password = strip_tags($_POST['passwd']);
		$model = new Model();

		#Prevents other sites from calling this function with
		#the exact required form but did not check the form fields.
		if(empty($name) || $model->contains_username($name) || 
		empty($email) || $model->contains_email($email)){
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/signup');
			exit();
		}

		#Prepare URL and parameters for SendGrid API call
		$req_url = 'https://api.sendgrid.com/';
		$params = array(
			'api_user' => USER,
			'api_key' => PASS,
			'from' => 'donotreply@localhost.com'
		);

		#Generate activation code
		$code = md5(uniqid(rand(), true));
		$model->add_user($model->get_user_id(), $name, $password, $email, $code);
		header("Location: https://".$_SERVER['HTTP_HOST']."/signedup");

		#Forms the activation link
		$activate_code = 'https://'.$_SERVER['HTTP_HOST']."/users/activate/".urlencode($email)."/".$code;

		#Sets the remaining parameters for sending email
		$params['to'] = $email;
		$params['subject'] = 'Account Activation';
		$params['html'] = '<p>Thank you for signing up wth Relive That Moment. Click <a href="'.$activate_code.'">here</a> to activate your account.</p>';

		#Forms the mail request URL
		$request = $req_url."api/mail.send.json";

		#Initialise the request
		$session = curl_init($request);

		#Use POST method to API
		curl_setopt($session, CURLOPT_POST, true);

		#Supplies the POST parameters
		curl_setopt($session, CURLOPT_POSTFIELDS, $params);

		#Do not return header but return response
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		#Sends the email
		curl_exec($session);
	}

	#Activates a user account
	function activate(){
		#Handles activation via URL
		$url_elements = explode('/', $_SERVER['REQUEST_URI']);
		$model = new Model();
		if(count($url_elements)==5){
			$email = strip_tags(urldecode($url_elements[3]));
			$code = strip_tags($url_elements[4]);
			if($model->activate($email, $code))
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/login');
			else
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
		#Handles activation by admin
		elseif($_SERVER['REQUEST_METHOD']=='POST' &&
		$_SESSION['user_id']==0 && count($url_elements)==4){
			$id = strip_tags($url_elements[3]);
			if(!$model->admin_activate($id))
				http_response_code(400);
			
		}
		else
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
	}

	#Resets the user's password.
	#Should only be accessed via POST request in JSON format with keys name
	#and email specified.
	function reset_password(){
		$req_headers = getallheaders();
		if($_SERVER['REQUEST_METHOD']=='POST' &&
		$req_headers['Content-Type']=='application/json; charset=UTF-8'){
			$req_params = json_decode(file_get_contents('php://input'), true);
			try{
				$name = strip_tags($req_params['name']);
				$email = strip_tags($req_params['email']);
				$model = new Model();
				$passwd = $model->reset_password($name, $email);

				#Checks if password reset succeeded
				if(!$passwd){
					echo 'Your request could not be processed';
					exit();
				}

			#Prepares URL and parameters for SendGrid API call
				$req_url = 'https://api.sendgrid.com/';
				$params = array(
					'api_user' => USER,
					'api_key' => PASS,
					'from' => 'donotreply@localhost.com'
				);

				#Sets the POSTFIELDS for sending email
				$params['to'] = $email;
				$params['subject'] = 'Password Reset';
				$params['text'] = 'Your password has been reset. Your new password is '.$passwd.'. Please change your password upon logging in.';
				# Forms the mail request URL
				$request = $req_url."api/mail.send.json";

				# Initialise the request
				$session = curl_init($request);

				# Use POST method to API
				curl_setopt($session, CURLOPT_POST, true);

				# Supplies the POST parameters
				curl_setopt($session, CURLOPT_POSTFIELDS, $params);

				# Do not return header but return response
				curl_setopt($session, CURLOPT_HEADER, false);
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

				#Sends the email
				curl_exec($session);
				curl_close($session);
				echo 'Password successfully reset. Please check email for new password.';
			}
			catch(Exception $e){
				http_response_code(400);
			}
		}
		else
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
	}

	/* Ensure that the rest of the script is accessed via HTTPS */
	/*if(empty($_SERVER['HTTPS'])){
		header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		exit(0);
	}*/

	session_start();

	$arr = getallheaders();
	$url_elements = explode('/', $_SERVER['REQUEST_URI']);
	$action = $url_elements[2];

	#Apply the appropriate function based on action given un URL
	switch($action){
		case 'validate_login': validate_login();
			break;
		case 'login': login();
			break;
		case 'checkName': checkUsername();
			break;
		case 'checkEmail': checkEmail();
			break;
		case 'signup': signup();
			break;
		case 'activate': activate();
			break;
		case 'reset_password': reset_password();
			break;
		case 'changepassword':
			break;
		case 'logout': logout();
			break;
		default: http_response_code(400);
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
	}
	exit();

	$req_url = 'https://api.sendgrid.com/';
	$params = array(
		'api_user' => USER,
		'api_key' => PASS,
		'from' => 'donotreply@localhost.com'
	);

	/* Handles exception of users entering users.php into URL */
	/*if(!isset($_GET['action'])){
		if(isset($_SESSION['user_id']))
			$url = "https://";
		else
			$url = "http://";
		$url.=$_SERVER['HTTP_HOST'];
	}*/

	/* Handles logout requests
	 * Logout is done by erasing all the session variables and destroying
	 * the cookie witht he session ID
	 */
	/*elseif($_GET['action']=="logout"){
		$_SESSION = array();
		session_destroy();
		setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		$url = "http://".$_SERVER['HTTP_HOST']."/loggedout";
	}*/

	/* Activates the appropriate account if the email and
	 * activation code matches. Else, redirects to page not found
	 */
	#elseif($_GET['action']=="activate"){
		/* If activation script is called manually by user */
		/*if(isset($_GET['x']) && isset($_GET['y'])){
			$email = urldecode(strip_tags($_GET['x']));
			$code = strip_tags($_GET['y']);
			if($model->activate($email, $code)){
				file_put_contents("message.txt", "Account activated! Please proceed to log in.");
				$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=login";
			}
			else
				$url = "https://".$_SERVER['HTTP_HOST']."/index.php?page=404";
		}*/

		/* If activation script is called by administrator*/
		/*elseif($_SESSION['user_id']==0 && isset($_GET['id'])){
			$id = (int)strip_tags((string)$_GET['id']);
			$model->admin_activate($id);
			$url = "https://".$_SERVER['HTTP_HOST'];
		}
		else
			$url = "http://".$_SERVER['HTTP_HOST']."/index.php?page=404";
	}*/

	/* Handles password reset requests */
	#elseif($_GET['action']=="reset_passwd"){
		/* Initialise $name and $email, whether the information
		 * is submitted by user through form or directly by
		 * admin's link
		 */
		/*if($_SERVER['REQUEST_METHOD']=="POST"){
			$name = strip_tags($_POST['name']);
			$email = strip_tags($_POST['email']);
		}
		elseif($_SESSION['user_id']==0 && isset($_GET['email'])){
			$name = strip_tags($_GET['name']);
			$email = strip_tags(urldecode($_GET['email']));
		}
		# Prevent any prankster attempt
		else{
			header("Location: https://".$_SERVER['HTTP_HOST']);
			exit(0);
		}*/

		/* Attempts to reset the password given the name and email
		 * and retrieves the new password on successful attempt
		 */
		/*$passwd = $model->reset_password($name, $email);
		if($passwd){
			# For non-admin users
			if(!isset($_SESSION['user_id']))
				file_put_contents("message.txt", "Password successfully reset. Please check your email for your new password. Please change your password upon logging in. If you do not receive any email, please contact the site administrator regarding your password.");

			#Sets the POSTFIELDS for sending email
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
			curl_close($session);
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

	#Subsequent blocks should only be executed if the method is POST
	elseif($_SERVER['REQUEST_METHOD']!="POST"){
		if(isset($_SESSION['user_id']))
			$url = "https://";
		else
			$url = "http://";
		$url.=$_SERVER['HTTP_HOST'];
	}

	#Executes request to sign up as new user
	elseif($_GET['action']=="signup"){
		$name = strip_tags((string)$_POST['name']);
		$email = strip_tags((string)$_POST['email']);
		$passwd = strip_tags((string)$_POST['passwd']);
		$passwd2 = strip_tags((string)$_POST['re-passwd']);

		#Checks if $name is still valid after removing tags
		if(strlen($name)==0){
			file_put_contents("message.txt", "Invalid username.");
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}

		#Checks if the username is already taken
		elseif($model->contains_username($name)){
			file_put_contents("message.txt", "This username is already taken.");
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}*/

		/* If email is not currently used by another user,
		 * add the user to database.
		 */
		/*elseif(!is_valid_email($email) || $model->contains_email($email)){
			file_put_contents("message.txt", "Invalid email or email is used by another user.");
			$url = "https://".$_SERVER['HTTP_HOST']."/signup";
		}

		#Validates password
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

	#Handles login requests
	elseif($_GET['action']=="login"){
		$email = strip_tags((string)$_POST['email']);
		$passwd = strip_tags((string)$_POST['passwd']);*/

		/* If login credentials are correct, start a new session.
		 * Sets the user's ID and username as session variables.
		 * Else, returns the user to the login page.
		 */
		/*if($model->is_valid_user($email, $passwd)){
			$user = $model->get_user($email, $passwd);
			$_SESSION['user_id'] = $user['ID'];
			$_SESSION['username'] = $user['USERNAME'];
			$url = "https://".$_SERVER['HTTP_HOST'];
		}
		else{
			#file_put_contents("message.txt", "Incorrect email or password or account is not activated.");
			$url = "https://".$_SERVER['HTTP_HOST']."/login";
		}
	}*/

	/* Handles password change request.
	 * Code is executed only if the user is logged in.
	 */
	/*elseif($_GET['action']=="changepasswd"){
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
			$url = "http://".$_SERVER['HTTP_HOST'];
	}

	Destroys the session if user is not logged in
	if(!isset($_SESSION['user_id'])){
		$_SESSION = array();
		#session_destroy();
		#setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
	}

	unset($_GET['action']);
	header("Location: ".$url);
	exit(0);*/
?>

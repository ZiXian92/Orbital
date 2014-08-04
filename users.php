<?php
	/* Handles all user account-related requests, such as signup,
	 * login, logout, change and forgot password.
	 * Incoming URLs have request URI of the form
	 * /users/action or /users/action/params
	 * Requests of the form /users is dealt with by redirecting to index.php
	 * in .htaccess file.
	 * The case of request URI being /users/ is checked after
	 * processing the incoming request URI.
	 * getallheaders() function is currently not supported on
	 * Heroku's PHP-FPM
	 */

	require "model.php";
	DEFINE('USER', 'zixian');
	DEFINE('PASS', 'Nana7Nana');

	#Checks if user with the supplied credentials exists in database
	#Request is successful only if called by POST method and JSON string
	#containing email and password is supplied.
	function validate_login(){
		try{
			if($_SERVER['REQUEST_METHOD']=='POST' &&
			preg_match('/^application\/json/', $_SERVER['CONTENT_TYPE'])){
				$req_params = json_decode(file_get_contents('php://input'), true);
				$model = new Model();
				$email = strip_tags($req_params['email']);
				$passwd = strip_tags($req_params['password']);
				if($model->is_valid_user($email, $passwd))
					echo 'Login successful';
				else
					echo 'Incorrect email and/or password.';
			}
			elseif($_SERVER['REQUEST_METHOD']=='POST')
				http_response_code(400);
			else
				header('Location: http://'.$_SERVER['HTTP_HOST'].'/404');
		}
		catch(Exception $e){
			http_response_code(400);
		}
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
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['username'] = $user['username'];
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
		try{
			if($_SERVER['REQUEST_METHOD']=='POST' && preg_match('/^application\/json/', $_SERVER['CONTENT_TYPE'])){
				$req_params = json_decode(file_get_contents('php://input'), true);
				$name = strip_tags($req_params['name']);
				$model = new Model();
				if($model->contains_username($name))
					echo 'This username is already taken.';
				else
					echo 'Ok';
			}
			elseif($_SERVER['REQUEST_METHOD']=='POST')
				http_response_code(400);
			else
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
		catch(Exception $e){
			http_response_code(400);
		}
	}

	#Checks if the given email is taken by another user.
	#Only accessible by POST request using JSON format.
	function checkEmail(){
		try{
			if($_SERVER['REQUEST_METHOD']=='POST' && preg_match('/^application\/json/', $_SERVER['CONTENT_TYPE'])){
				$req_params = json_decode(file_get_contents('php://input'), true);
				$email = strip_tags($req_params['email']);
				$model = new Model();
				if($model->contains_email($email))
					echo 'This email address is already used by another user.';
				else
					echo 'Ok';
			}
			elseif($_SERVER['REQUEST_METHOD']=='POST')
				http_response_code(400);
			else
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
		catch(Exception $e){
			http_response_code(400);
		}
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
			return;
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
		try{
			if($_SERVER['REQUEST_METHOD']=='POST' &&
			preg_match('/^application\/json/', $_SERVER['CONTENT_TYPE'])){
				$req_params = json_decode(file_get_contents('php://input'), true);
				$name = strip_tags($req_params['name']);
				$email = strip_tags($req_params['email']);
				$model = new Model();
				$passwd = $model->reset_password($name, $email);

				#Checks if password reset succeeded
				if(!$passwd){
					echo 'Your request could not be processed';
					return;
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
			elseif($_SERVER['REQUEST_METHOD']=='POST')
				http_response_code(400);
			else
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
		catch(Exception $e){
			http_response_code(400);
		}
	}

	#Changes a user's password
	function changepassword(){
		try{
			if($_SERVER['REQUEST_METHOD']=='POST' && $_SERVER['CONTENT_TYPE']=='application/json; charset=UTF-8'){
				$req_params = json_decode(file_get_contents('php://input'), true);
				$oldpass = strip_tags($req_params['oldpass']);
				$newpass = strip_tags($req_params['newpass']);
				$model = new Model();
				if($model->get_password_by_id($_SESSION['user_id'])==SHA1($oldpass)){
					$model->set_new_password($_SESSION['user_id'], $newpass);
					echo 'Success';
				}
				else
					echo 'Current password is incorrect';
			}
			elseif($_SERVER['REQUEST_METHOD']=='POST')
				http_response_code(400);
			else
				header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
		}
		catch(Exception $e){
			http_response_code(400);
		}
	}

	/* Ensure that the rest of the script is accessed via HTTPS */
	/*if(empty($_SERVER['HTTPS'])){
		header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		exit(0);
	}*/

	session_start();

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
		case 'changepassword': changepassword();
			break;
		case 'logout': logout();
			break;
		default: http_response_code(400);
			header('Location: https://'.$_SERVER['HTTP_HOST'].'/404');
	}
?>

<?php
	/* This only handles page requests, which are checked by .htaccess.
	 * Page request URI is in the format /page, where page is the page
	 * requested.
	 */

	require 'view.php';
	require 'model.php';

	session_start();

	if($_SERVER['HTTP_X_FORWARDED_PROTO']=="https")
		$_SERVER['HTTPS'] = "on";
	else
		$_SERVER['HTTPS'] = NULL;

	# Gets the page requested
	$page = explode('/', $_SERVER['REQUEST_URI']);
	$page = $page[1];

	/* Use HTTPS if user is logged in or if signup, login, or password
	 * reset pages are requested.
	 */
	if(isset($_SESSION['user_id']) && empty($_SERVER['HTTPS'])){
		header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		exit();
	}

	if(($page=='login' || $page=='signup' || $page=='reset_password') &&
	empty($_SERVER['HTTPS'])){
		header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		exit();
	}

	$model = new Model();
	$content_array;

	/* Evaluates the page requested and retrieves
	 * required data for the page
	 */
	if(empty($page)){
		$content_array = $model->get_page_params('home');
	}	
	else{
		/* Redirects to home page if logged in user attempts to access
		 * a page not available in the navigation bar
		 */
		if(isset($_SESSION['user_id']) && $page!='about' &&
		$page!='create_entry' && $page!='change_passwd'){
			header('Location: https://'.$_SERVER['HTTP_HOST']);
			exit();
		}

		/* Prevents users who are not logged in from accessing
		 * change_passwd page.
		 */
		if(!isset($_SESSION['user_id']) && $page=='change_passwd'){
			header('Location: http://'.$_SERVER['HTTP_HOST']);
			exit();
		}

		# Prevents admins from accessing create_entry page
		if($page=='create_entry' && isset($_SESSION['user_id']) &&
		$_SESSION['user_id']==0){
			header('Location: https://'.$_SERVER['HTTP_HOST']);
			exit();
		}

		$content_array = $model->get_page_params($page);
	}

	$view = new View($content_array);
	$view->render();
?>

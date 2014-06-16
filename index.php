<?php
	/* Part of the controller of the MVC framework.
	 * Handles only page requests.
	 * Form submission requests to be handled by upload_handler.php.
	 * User login/logout requests to be handled by login.php
	 * and logout.php respectively.
	 * More edits to be made upon successful URL rewriting for friendlier
	 * URLs.
	 */

	require "view.php";
	require "model.php";

	session_start();

	header("Content-type: text/html; charset=utf-8");

	$model = new Model();

	#echo $_GET['page'];
	
	/* Handles page requests using the 'ugly' URLs */
	/* Initialise to an empty array */
	$content_array = array();

	/* When user enters URL of main page, $_GET['page']
	 * does not hold any value
	 */
	if(!isset($_GET['page'])){
		if(!isset($_SESSION['user_id']) && !empty($_SERVER['HTTPS'])){
			header("Location: http://".$_SERVER['HTTP_HOST']);
			exit(0);
		}
		$_GET['page'] = "home";
	}

	/* Assigns values to replace placeholders with into $content_array
	 * $content_array is passed by reference
	 */
	$model->set_template($content_array, $_GET['page']);

	/* Gets the content from the appropriate HTML file based
	 * on page requested.
	 */
	$content_array['content'] = $model->get_page($_GET['page']);

	/* Creates a new View object once all the required information
	 * are stored in the array.
	 */
	$view = new View($content_array);
	$view->render();

	/* Destroys the session if the user is not logged in */
	if(!isset($_SESSION['user_id'])){
		$_SESSION = array();
		session_destroy();
		setcookie("PHPSESSID", "", time()-3600, "/", "", 0, 0);
	}

	/* Clears the $_GET['page'] superglobal variable to
	 * prevent wrong execution in the future.
	 */
	unset($_GET['page']);
?>

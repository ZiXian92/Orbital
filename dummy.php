<?php
	require 'Facebook/FacebookSession.php';
	require 'Facebook/FacebookJavaScriptLoginHelper.php';
	require 'Facebook/FacebookRequest.php';
	require 'Facebook/FacebookRequestException.php';
	require 'Facebook/GraphObject.php';
	use Facebook\FacebookSession;
	use Facebook\FacebookRequest;
	use Facebook\FacebookRequestException;
	use Facebook\FacebookJavaScriptLoginHelper;
	use Facebook\GraphObject;

	#Returns a new Facebook session if a user is logged in.
	#Returns null otherwise.
	function createFBSession(){
		$helper = new FacebookJavaScriptLoginHelper();
		var_dump($helper);
		try{
			var_dump($helper->getSession());
			return $helper->getSession();
		} catch(Exception $e){
			return null;
		}
	}
	ini_set('display_errors', 'On');

	echo 'Hello';
	FacebookSession::setDefaultApplication("823148504363911", "2e6875c6ad028b5a7f1099016b4f535f");

	$fbsess = createFBSession();
	#var_dump($fbsess);
	if($fbsess){
		try{
			$user_profile = (new FacebookRequest($fbsess, 'GET', '/me'))->execute()->getGraphObject();
			$name = $user_profile->getProperty('name');
			$email = $user_profile->getProperty('email');
			echo $name."<br/>".$email;
			#$model = new Model();
			#if !user exists
				#$model->add_user($model->get_user_id, $name, null, $email, null);
			#$user = $model->get_user($email, null);
				
		} catch(Exception $e){}
	}
	else
		echo 'Not logged in to Facebook.';
?>

<?php
	require 'facebook-php-sdk-v4-4.0-dev/autoload.php';
	require 'facebook-php-sdk-v4-4.0-dev/src/Facebook/Entities/AccessToken.php';
	require 'facebook-php-sdk-v4-4.0-dev/src/Facebook/Entities/SignedRequest.php';
	use Facebook\FacebookSession;
	use Facebook\FacebookRedirectLoginHelper;
	use Facebook\FacebookJavaScriptLoginHelper;
	use Facebook\FacebookRequest;
	use Facebook\GraphUser;
	use Facebook\FacebookRequestException;

	#Returns a new Facebook session if a user is logged in.
	#Returns null otherwise.
	function createFBSession(){
		$helper = new FacebookRedirectLoginHelper('/dummy.php');
		var_dump($helper);
		try{
			var_dump($helper->getSession());
			return $helper->getSession();
		} catch(Exception $e){
			return null;
		}
	}
	ini_set('display_errors', 'On');
	$arr = json_decode(file_get_contents('fbsdk.json'), true);

	FacebookSession::setDefaultApplication($arr['app_id'], $arr['app_secret']);

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

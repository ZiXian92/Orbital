<?php
	require 'facebook-php-sdk-v4-4.0-dev/autoload.php';
	use Facebook\FacebookSession;
	use Facebook\FacebookJavaScriptLoginHelper;
	use Facebook\FacebookRequest;
	use Facebook\GraphUser;
	use Facebook\FacebookRequestException;

	#Returns a new Facebook session if a user is logged in.
	#Returns null otherwise.
	function createFBSession(){
		$helper = new FacebookJavaScriptLoginHelper();
		try{
			return $helper->getSession();
		} catch(Exception $e){
			return null;
		}
	}
	$app_info = json_decode(file_get_contents('fbsdk.json'), true);
	FacebookSession::setDefaultApplication();
	$fbsess = createFBSession();
	if($fbsess){
		try{
			$user_profile = (new FacebookRequest($fbsess, 'GET', '/me'))->execute()->getGraphObject();
			$name = $user_profile->getProperty('name');
			$email = $user_profile->getProperty('email');
			echo $name.'<br/>'.$email;
			#$model = new Model();
			#if !user exists
				#$model->add_user($model->get_user_id, $name, null, $email, null);
			#$user = $model->get_user($email, null);
				
		} catch(Exception $e){}
	}
?>

<?php
	$url = 'https://api.sendgrid.com/';
	$user = 'zixian';
	$pass = 'Nana7Nana';

	$params = array(
		'api_user' => $user,
		'api_key' => $pass,
		'to' => 'zixian1992@hotmail.com',
		'subject' => 'First SendGrid Email',
		'text' => 'This is the first email successfully sent using SendGrid!',
		'from' => 'zixian1992@hotmail.com',
	);

	$request = $url."api/mail.send.json";

	#Create new cURL request
	$session = curl_init($request);

	#Use HTTP POST
	curl_setopt($session, CURLOPT_POST, true);

	#Sets the POST parameters
	curl_setopt($session, CURLOPT_POSTFIELDS, $params);

	#Don't return headers, but returns the response
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

	#Execute the request and get response
	$response = curl_exec($session);

	#Closes the session
	curl_close($session);

	print_r($response);
?>

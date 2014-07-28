<?php
	require 'model.php';
	$model = new Model();
	if(!$model->contains_user('zx', 'zx@email.com'))
		$model->add_user(3, 'zx', null, 'zx@email.com', null);
	$model->get_user($email, null);
?>

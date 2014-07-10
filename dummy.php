<?php
	#mail("zixian1992@hotmail.com", "Hello", "Mizuki Nana desu~", "From: admin@relivethemoment.host-ed.me\r\n");
	require 'model.php';

	$model = new Model();
	var_dump($model->get_user('zixian1992@hotmail.com', 'aaaaaaaaaa'));
?>

<?php
	require 'model.php';

	$model = new Model();
	echo $model->contains_user('zixian', 'zx@email.com');
?>

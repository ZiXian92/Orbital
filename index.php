<?php
	include 'view.php';

	if(!isset($_GET['page']))
		render_page('home');
	else
		render_page($_GET['page']);
	unset($_GET['page']);
?>

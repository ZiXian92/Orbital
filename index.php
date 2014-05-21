<?php
	/* The controller component of the MVC framework.
	 * Currently handles only page requests.
	 * Form submission requests to be handled later upon figuring out the
	 * SQL portion of the model component.
	 * More edits to be made upon successful URL rewriting for friendlier
	 * URLs.
	 */

	include 'view.php';

	if(!isset($_GET['page']))
		render_page('home');
	else
		render_page($_GET['page']);
	unset($_GET['page']);
?>

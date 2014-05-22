<?php
	/* The controller component of the MVC framework.
	 * Currently handles only page requests.
	 * Form submission requests to be handled later upon figuring out the
	 * SQL portion of the model component.
	 * More edits to be made upon successful URL rewriting for friendlier
	 * URLs.
	 */

	include 'view.php';

	/* To be outsourced to model component */
	function getContent($page){
		$arr = array();
		$arr['title'] = strtoupper(substr($page, 0, 1)).substr($page, 1);
		$arr['content'] = file_get_contents($page.".html");
		if($page=="create_entry")
			$arr['javascript'] = "<script src=\"javascripts/jscript.js\"></script>";
		else
			$arr['javascript'] = "";
		return $arr;
	}
	
	$content_array = array();
	if(!isset($_GET['page']))
		$_GET['page'] = "home";
	$content_array = getContent($_GET['page']);
	$view = new View($content_array);
	$view->render();
	unset($_GET['page']);
?>

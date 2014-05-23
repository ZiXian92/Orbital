<?php
/* This file only handles form submissions.
 * To be incorporated or called by index.php
 * Temporary measure, only to test working functionality.
 * Next step is to write the image and all form details preoperly into
 * PDF file
 * Commented by: Qua Zi Xian
 */
#This file only handles form submissions.
	if($_SERVER['REQUEST_METHOD']=="POST"){
		echo "File name: ".$_FILES['img']['name']."<br/>";
		echo "Title: ".$_POST['title']."<br/>";
		echo "Story: ".$_POST['story'];

	/* Destination folder requires permission setting of 777 instead of
	 * 755 or 766. Why?
	 */
		move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");
	}
?>

<?php
/* This file only handles form submissions.
 * To-Do: Redirect browser back to the same page before prompting
 * file download.
 * Commented by: Qua Zi Xian on 26 May 2014
 */
	/* FPDF class definition is in the specified file */
	require 'PDF.php';
	require 'model.php';

	/* The only condition when this code should be executed is when
	 * a form is submitted. Entering the URL directly will not work.
	 */
	if($_SERVER['REQUEST_METHOD']=="POST"){

		/* Check for invalid file types */
		$fileinfo = finfo_open(FILEINFO_MIME_TYPE);
		$filetype = finfo_file($fileinfo, $_FILES['img']['tmp_name']);
		finfo_close($fileinfo);
		if($filetype!="image/jpg" &&
		$filetype!="image/jpeg" &&
		$filetype!="image/png"){
			file_put_contents("message.txt", "Please use only JPEG or PNG files");
			header("Location: http://".$_SERVER['HTTP_HOST']."/index.php?page=create_entry");
			exit(0);
		}
			
	
		session_start();	
		$model = new Model();

	/* Moves image to uploads folder in server for use in PDF.
	 * Please create the destination folder called uploads with the same
	 * relative paths in the 2nd parameter of move_uploaded_file.
	 * ../uploads and ../entries requires permission setting of 777
	 * instead of 755 or 766. Why?
	 */
		move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");

		/* Somehow, having the author field disabled for
		 * logged in users prevent the field value from
		 * being submitted
		 */
		if(isset($_SESSION['user_id'])){
			$_POST['author'] = $_SESSION['username'];
			$_POST['entry_id'] = $model->get_entry_id();
		}

		/*Prevent any possible XSS injection by removing tags */
		$file = "../uploads/{$_FILES['img']['name']}";
		$author = strip_tags((string)$_POST['author']);
		$title = strip_tags((string)$_POST['title']);
		$story = strip_tags((string)$_POST['story']);

		/* Creates and loads form contents into a new PDF document */
		$pdf = new PDF($file, $author, $title, $story);

		/* Destroys the PDF object, saving to ../entries folder in
	 	* the process, and then sending the file to browser
	 	* for download.
	 	*/
		unset($pdf);

		/* Enter entry information to database */
		if(isset($_SESSION['user_id'])){
			$model->add_entry($_POST['entry_id'], $title, $_SESSION['user_id'], date("Y-m-d"), "../entries/".(string)$_POST['entry_id'].".pdf");
		}
		else{
			$_SESSION = array();
			session_destroy();
			setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0);
		}

		/* Removes the image file from ..uploads folder */
		unlink("../uploads/{$_FILES['img']['name']}");
	}
	header("Location: https://".$_SERVER['HTTP_HOST']."/index.php?page=create_entry");
?>

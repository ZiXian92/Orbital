<?php
/* This file only handles form submissions.
 * To be modularised as an object to be used by index.php
 * Commented by: Qua Zi Xian on 26 May 2014
 */
	/* FPDF class definition is in the specified file */
	require 'PDF.php';

	if($_SERVER['REQUEST_METHOD']=="POST"){
	/* Moves image to uploads folder in server for use in PDF.
	 * Please create the destination folder called uploads with the same
	 * relative paths in the 2nd parameter of move_uploaded_file.
	 * ../uploads and ../entries requires permission setting of 777 instead of
	 * 755 or 766. Why?
	 */
		move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");

		/* Creates and loads form contents into a new PDF document */
		$pdf = new PDF();

		/* Destroys the PDF object, saving to ../entries folder in
		 * the process, and then sending the file to browser
		 * for download.
		 */
		unset($pdf);

		/* Removes the image file from ..uploads folder */
		unlink("../uploads/{$_FILES['img']['name']}");
	}
	else{
		//Redirect to form entry page
	}
?>

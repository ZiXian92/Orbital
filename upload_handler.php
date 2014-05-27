<?php
/* This file only handles form submissions.
 * To be modularised as an object to be used by index.php
 * Commented by: Qua Zi Xian on 26 May 2014
 */
	/* FPDF class definition is in the specified file */
	require 'PDF.php';

	/*if($_SERVER['REQUEST_METHOD']!="POST"){
		//Redirect to create_entry page
	}*/

	/* Moves image to uploads folder in server for use in PDF
	 * Image to be deleted form uploads folder after use
	 * Please create the destination folder called uploads with the same
	 * relative paths in the 2nd parameter of move_uploaded_file.
	 * Destination folder requires permission setting of 777 instead of
	 * 755 or 766. Why?
	 */
	move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");
	

	/* Sends the document to the specified destination.
	 * 1st parameter is the name to be given to document.
	 * Options: I = Show in browser, with download option
	 *	    D = Send to browser and force download
	 *	    F = Save to local file
	 *	    S = Return document as string
	 * Change the destination to D before publishing.
	 */
	$pdf = new PDF();
	unset($pdf);
?>

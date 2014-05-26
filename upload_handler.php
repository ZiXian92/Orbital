<?php
/* This file only handles form submissions.
 * To be modularised as an object to be used by index.php
 * Commented by: Qua Zi Xian on 26 May 2014
 */
	/* FPDF class definition is in the specified file */
	require 'fpdf/fpdf.php';

	/*if($_SERVER['REQUEST_METHOD']!="POST"){
		//Redirect to create_entry page
	}*/

	/*Destination folder requires permission setting of 777 instead of
	 * 755 or 766. Why?
	 */
	
	/* Moves image to uploads folder in server for use in PDF
	 * Image to be deleted form uploads folder after use
	 * Please create the destination folder called uploads with the same
	 * relative pathas in the 2nd parameter of move_uploaded_file.
	 * Destination folder requires permission setting of 777 instead of
	 * 755 or 766. Why?
	 */
	move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");
	
	/* Creates a new PDF document.
	 * Default page settings for PDF are Portrait and A4 size */
	$fpdf = new FPDF('P', 'pt', 'A4');

	/* Default values are 100% zoom and Portrait layout */
	$fpdf->SetDisplayMode('default', 'default');

	/* Adds a new page to the document */
	$fpdf->AddPage();

	$fpdf->SetFont('Arial', '', 14);
	#$fpdf->MultiCell(0, 16, $_POST['title'], 0, 'L');

	/* Gets an array of size information
	 * Elements 0 and 1 are width and height of image respectively
	 */
	move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");
	$img_size = getimagesize("../uploads/{$_FILES['img']['name']}");

	/* Each cell/multicell represents a line
	 * Cell parameters: width, height, text, border, next cursor
	 * position after call, text alignment, fill
	 * MultiCell parameters: width, height, text, border,
	 * text alignment, fill
	 */
	/* To-Do: Center ANY image in the cell.
	 * Currently not exactly centered
	 */
	$fpdf->Cell(0, 0, $fpdf->Image("../uploads/{$_FILES['img']['name']}"), 0, 1, 'C');

	/* Prints the title */
	$fpdf->SetFont('Arial', 'B', '18');
	$fpdf->Cell(0, 20, $_POST['title'], 0, 1, 'C');

	/* Prints the story */
	$fpdf->SetFont('Arial', '', 14);
	$fpdf->MultiCell(0, 16, $_POST['story'], 0, 'J');

	/* Sends the document to the specified destination.
	 * 1st parameter is the name to be given to document.
	 * Options: I = Show in browser, with download option
	 *	    D = Send to browser and force download
	 *	    F = Save to local file
	 *	    S = Return document as string
	 * Change the destination to D before publishing.
	 */
	$fpdf->Output("{$_FILES['img']['name']}.pdf", "I");
?>

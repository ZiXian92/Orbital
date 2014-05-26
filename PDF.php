<?php
	require 'fpdf/fpdf.php';

	/* Defines the PDF class using FPDF class.
	 * Fills in content according to the way that is used for this
	 * application(not meant to be portable of reusable in other contexts).
	 * Author: Qua Zi Xian
	 */
	class PDF extends FPDF{
		/* Object variables */
		const MAX_IMG_WIDTH = 800;
		const MAX_IMG_HEIGHT = 500;
		private $img_file;
		private $img_size;
		private $author;
		private $title;
		private $story;
		private $scale;

		/* Constructor */
		/* Initialises all variables and sets up the document,
		 * followed by a call to fill in the contents.
		 * Make sure the uploaded file in the temporary directory is
		 * not removed before calling the constructor.
		 * To-Do: Implement exception handling for moving uploaded file.
		 */
		public function __construct(){
			parent::__construct("P", "", "A4");
			parent::SetDisplayMode("default", "default");
			parent::SetMargins(40, 50, 40);
			move_uploaded_file($_FILES['img']['tmp_name'], "../uploads/{$_FILES['img']['name']}");
			$img_file = "../uploads/{$_FILES['img']['name']}";
			$img_size = getimagesize($img_file);
			$author = $_POST['author'];
			$title = $_POST['title'];
			$story = $_POST['story'];
			$scale = getScale();
			fill();
		}
	
		/* Destructor */
		/* On destruction, outputs the completed PDF file */
		public function __destruct(){
			$this->Output($title.".pdf", "I");
		}

		/* Methods */
		/* Returns the scale factor to resize by if image exceeds
		 * maximum image dimensions specified.
		 * Else, returns scale factor of 1
		 */
		private function getScale(){
			
		}

		/* Fills in the contents into the PDF file in the
		 * following format:
		 * Uploaded image at the top in the center.
		 * Title in the center on next line.
		 * Author in center on next line
		 * Story aligned letf on next line
		 */
		private function fill(){
			parent::AddPage();
			parent::SetFont("Arial", "", 0);
			parent::Cell(0, 0, parent::Image($img_file,
				(660-$img_size[0]*$scale)/2.0, parent::GetY(),
				$img_size[0]*$scale, $img_size[1]*$scale),
				0, 1, "C");
		}
	}
?>

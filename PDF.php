<?php
	require 'fpdf/fpdf.php';

	/* Defines the PDF class using FPDF class.
	 * Fills in content according to the way that is used for this
	 * application(not meant to be portable of reusable in other contexts).
	 * To-Do: Explore use of header and footer to set credits to this web
	 * application.
	 * Author: Qua Zi Xian
	 */
	class PDF extends FPDF{
		/* Object variables */
		const MAX_IMG_WIDTH = 300;
		const MAX_IMG_HEIGHT = 300;
		private $_img_file;
		private $_img_size;
		private $_img_width;
		private $_img_height;
		private $_author;
		private $_title;
		private $_story;
		private $_scale;

		/* Constructor */
		/* Initialises all variables and sets up the document,
		 * followed by a call to fill in the contents.
		 * To-Do: Implement exception handling for moving uploaded file.
		 */
		public function __construct(){
			parent::__construct("P", "pt", "A4");
			parent::SetDisplayMode("real", "default");
			parent::SetMargins(50, 50, 50);
			$this->_img_file = "../uploads/{$_FILES['img']['name']}";
			$this->_author = $_POST['author'];
			$this->_title = $_POST['title'];
			$this->_story = $_POST['story'];
			$this->_img_size = getimagesize($this->_img_file);
			$this->_scale = $this->get_scale();
			$this->set_size();
			$this->fill();
		}
	
		/* Destructor */
		/* On destruction, outputs the completed PDF file */
		public function __destruct(){
			parent::Output($this->_title.".pdf", "I");
		}

		/* Methods */
		/* Returns the scale factor to resize by if image exceeds
		 * maximum image dimensions specified.
		 * Else, returns scale factor of 1
		 */
		private function get_scale(){
			$width = 0.75*$this->_img_size[0];
			$height = 0.75*$this->_img_size[1];
			$width_scale = $height_scale = 1.0;
			if($width>self::MAX_IMG_WIDTH)
				$width_scale = self::MAX_IMG_WIDTH/(float)$width;
			if($height>self::MAX_IMG_HEIGHT)
				$height_scale = self::MAX_IMG_HEIGHT/(float)$height;
			if($width_scale<=$height_scale)
				return $width_scale;
			return $height_scale;
		}
		
		private function set_size(){
			$this->_img_width = 0.75*$this->_scale*$this->_img_size[0];
			$this->_img_height = 0.75*$this->_scale*$this->_img_size[1];
		}

		private function center_image(){
			parent::SetFont("Times", "", 14);
			parent::Cell(0, $this->_img_height,
				parent::Image($this->_img_file,
				(595-$this->_img_width)/2.0,
				parent::GetY(), $this->_img_width,
				$this->_img_height), 0, 1, "C");
		}

		/* Fills in the contents into the PDF file in the
		 * following format:
		 * Uploaded image at the top in the center.
		 * Title in the center on next line.
		 * Author in center on next line
		 * Story aligned left on next line
		 */
		private function fill(){
			parent::AddPage();
			$this->center_image();
			parent::SetFont("Times", "B", 18);
			parent::Cell(0, 20, $this->_title, 0, 1, "C");
			parent::SetFont("Times", "", 15);
			parent::Cell(0, 18, "By ".$this->_author, 0, 1, "C");
			parent::Cell(0, 18, "", 0, 1, "L");
			parent::SetFont("Times", "", 13);
			parent::MultiCell(0, 15, $this->_story, 0, "J");
		}
	}
?>

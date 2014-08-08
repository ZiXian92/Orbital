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
		/* Defines the maximum width and height allowed for the image.
		 * Change the maximum size accordingly using only these 2
		 * constants.
		 * Leave the function implementations untouched unless change
		 * in implementation is called for.
		 */
		const MAX_IMG_WIDTH = 300;
		const MAX_IMG_HEIGHT = 300;

		/* Object variables */
		private $_img_file;	//Name of image file
		private $_img_size;	//Array of size information of image
		private $_img_width;	//Width to be applied on image
		private $_img_height;	//Height to be applied on image
		private $_author;	//Name of author
		private $_title;	//Title of story
		private $_story;	//The story
		private $_scale;	//Scale factor used to calculate width
					//and height

		/* Constructor */
		/* Initialises all variables and sets up the document,
		 * followed by a call to fill in the contents.
		 * Takes in 4 string parameters: directory path of image file,
		 * author of the story, title of the story, and the content of
		 * the story.
		 */
		public function __construct($file, $author, $title, $story){
			/* Creates a new PDF document */
			parent::__construct("P", "pt", "A4");

			/* Sets document settings */
			parent::SetDisplayMode("real", "default");
			parent::SetMargins(50, 50, 50);
			parent::SetCreator("XXX");
			parent::SetSubject("My XXX");

			/* Initialise member attributes */
			$this->_img_file = $file;
			$this->_author = $author;
			parent::SetAuthor($this->_author);
			$this->_title = $title;
			parent::SetTitle($this->_title);
			$this->_story = $story;
		
			/* Width and height are in pixels */
			$this->_img_size = getimagesize($this->_img_file);
			$this->_scale = $this->get_scale();
			$this->set_size();

			/* Fills in the contents */
			$this->fill();
		}
	
		/* Destructor
		 * On destruction, saves a local copy of the document
		 * and also sends to the browser for download.
		 * 1st parameter is the name to be given to document.
		 * Options: I = Show in browser, with download option
		 *	    D = Send to browser and force download
		 *	    F = Save to local file
		 *	    S = Return document as string
		 * Change the destination to D before publishing.
		 */
		public function __destruct(){
			$file = '/tmp/'.(string)$this->_title.'.pdf';
			parent::Output($file, "I");
			if(isset($_SESSION['user_id']))
				parent::Output('/tmp/'.(string)$_POST['entry_id'].'.pdf', "F");
		}

		/* Methods */
		/* Returns the scale factor to resize by if image exceeds
		 * maximum image dimensions specified.
		 * Else, returns scale factor of 1
		 */
		private function get_scale(){
			/* 1 pt = 0.75 pixels */
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
		
		/* Sets the dimensions ti be applied to the image
		 * using the scale factor to scale any oversized images
		 */
		private function set_size(){
			$this->_img_width = 0.75*$this->_scale*$this->_img_size[0];
			$this->_img_height = 0.75*$this->_scale*$this->_img_size[1];
		}

		/* Horizontally aligns the image to center of page */
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
		 * Author in center on next line.
		 * Story aligned left on next line.
		 * Each cell/multicell represents a line.
		 * Cell parameters: width, height, text, border, next cursor
		 * position after call, text alignment, fill
		 * MultiCell parameters: width, height, text, border,
		 * text alignment, fill.
		 * Note: Must set the font before using Cell/MultiCell.
		 */
		private function fill(){
			/* Creates a new page */
			parent::AddPage();

			/* Horizontally aligns the image to center of page */
			$this->center_image();
	
			/* Prints the title */
			parent::SetFont("Times", "B", 18);
			parent::Cell(0, 20, $this->_title, 0, 1, "C");
		
			/* Prints the author */
			parent::SetFont("Times", "", 15);
			parent::Cell(0, 18, "By ".$this->_author, 0, 1, "C");

			/* Leaves some extra spacing */
			parent::Cell(0, 18, "", 0, 1, "L");

			/* Prints the story */
			parent::SetFont("Times", "", 13);
			parent::MultiCell(0, 15, $this->_story, 0, "J");
		}
	}
?>

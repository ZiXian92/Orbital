<?php
	/* Defines each view object.
	 * Each view is o be created after compiling all required contents
	 * into an array.
	 * $arr must be an array of key-value pairs
	 */
	class View{
		/* Variable to hold location of template file */
		private $_template = "html/template.html";
		private $_content;	#Holds contents of web page

		/* Constructor */
		public function __construct($arr){
			$this->_content = $this->get_template();
			$this->setContent($arr);
		}

		/* Loads the template */
		private function get_template(){
			return file_get_contents($this->_template);
		}

		/* Replaces all placeholders in the HTML code */
		private function setContent($arr){
			/* Main content is loaded 1st as it may contain
			 * other placeholders to be replaced.
			 */
			$this->_content = str_replace("{{content}}", $arr['content'], $this->_content);
			foreach($arr as $key=>$value)
				$this->_content = str_replace("{{".$key."}}",
					$value, $this->_content);
		}

		/* Displays the fully constructed HTML web page */
		public function render(){
			echo $this->_content;
		}
	}
?>

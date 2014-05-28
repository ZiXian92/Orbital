<?php
	/* Defines each view object.
	 * Each view is o be created after compiling all required contents
	 * into an array.
	 * $arr must be an array of key-value pairs
	 */
	class View{
		private $_template = "html/template.html";
		private $_content;
		public function __construct($arr){
			$this->_content = $this->get_template();
			$this->setContent($arr);
		}
		private function get_template(){
			return file_get_contents($this->_template);
		}
		private function setContent($arr){
			foreach($arr as $key=>$value)
				$this->_content = str_replace("{{".$key."}}",
					$value, $this->_content);
		}
		public function render(){
			echo $this->_content;
		}
	}
	
	/* Code to test that the class is working */
	/*$dict = array();
	$dict['title'] = 'Home';
	$dict['javascript'] = '';
	$dict['content'] = file_get_contents('home.html');
	$view = new View($dict);
	$view->render();*/
?>

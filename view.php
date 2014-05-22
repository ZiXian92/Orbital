<?php
	class View{
		private $_template = "template.html";
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
	
	/*$dict = array();
	$dict['title'] = 'Home';
	$dict['javascript'] = '';
	$dict['content'] = file_get_contents('home.html');
	$view = new View($dict);
	$view->render();*/
	#print_r($dict);
?>

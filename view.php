<?php
class View{
	private $_template = "template.html";
	private $_content = file_get_contents($_template);
	public function render() { echo $_content; }
}

$home = new View();
$home->render();
?>

<?php
	function render_page($page){
		$content = file_get_contents("template.html");
		$content = str_replace("{{title}}", $page, $content);
		$content = str_replace("{{content}}", file_get_contents($page.".html"), $content);
		echo $content;
	}
?>

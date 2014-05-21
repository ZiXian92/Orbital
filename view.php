<?php
	function render_page($page){
		$content = file_get_contents("template.html");
		$content = str_replace("{{title}}", $page, $content);
		$content = str_replace("{{content}}", file_get_contents($page.".html"), $content);
		if(page==='create_entry')
			$content = str_replace("{{javascript}}", "<script src=\"javascripts/jscript.js\"></script>", $content);
		else
			$content = str_replace("{{javascript}}", "", $content);
		echo $content;
	}
?>

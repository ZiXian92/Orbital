<?php
	/* Defines the view component of the MVC framework.
	 * Defining view as a class object causes many statements to be
	 * not executed so this function is the next better alternative.
	 * Currently handles only page requests.
	 * To be modified when more placeholders in the template are needed.
	 * Note: As this function reads in the entire template as a big chunk
	 * of text, network bandwidth may be insufficient if template.html
	 * gets too big.
	 * Comments by Zi Xian
	 */
	function render_page($page){
		$content = file_get_contents("template.html");
		$content = str_replace("{{title}}", $page, $content);
		$content = str_replace("{{content}}", file_get_contents($page.".html"), $content);
		if($page=='create_entry')
			$content = str_replace("{{javascript}}", "<script src=\"javascripts/jscript.js\"></script>", $content);
		else
			$content = str_replace("{{javascript}}", "", $content);
		echo $content;
	}
?>

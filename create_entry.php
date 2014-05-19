<!DOCTYPE html>
<html>
<head>
	<title>Home-XXX</title>
	<link rel="stylesheet" type="text/css" href="stylesheets/style.css">
	<script src="javascripts/jscript.js"></script>
</head>

<body>
	<!--Imports the header and navigation links-->
	<?php
	include('includes/header.php');
	include('includes/nav.php');
	?>

	<!--Main content of the page-->
	<div id="content">
		<!--Form to gather required information for an entry-->
		<form name="new_entry" action="upload_handler.php" method="post" id="entry" onsubmit="return validate();">

		<!--Image file upload field-->
			<label for="file">Image File:</label>
		<!--readURL() function to be implemented. Complicated.-->
			<input type="file" name="img_file" id="file"
			accept="image/gif, image/jpeg, image/png"
			onchange="readURL(this)">*required<br/>

		<!--Handle the preview issue at a later stage-->
			<!--Image preview-->
			<img src="#" alt="No preview available"><br/>

			<!--Title field-->
			<label>Title:
			<input type="text" name="title" size="20">*required
			</label><br/>

			<!--Story field-->
			<label>Story:</label>
			<textarea name="story" rows="20" cols="80" form="entry" placeholder="Write your story here..."></textarea>*required<br/>

			<!--Submit button-->
			<input type="submit" name="submit" value="submit">
			</label>
		</form>
	</div>

	<!--Imports the footer-->
	<?php include('includes/footer.php'); ?>
</body>
</html>

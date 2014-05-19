<!DOCTYPE html>
<html>
<head>
	<title>Home-XXX</title>
	<link rel="stylesheet" type="text/css" href="stylesheets/style.css">
</head>

<body>
	<?php include('includes/header.php'); ?>

	<?php include('includes/nav.php'); ?>

	<div id="content">
		<!--Use Javascript to make sure no field is blank.-->
		<form action="upload_handler.php" method="post" id="entry">

		<!--Image file upload field-->
			<label for="file">Image File*:</label>
		<!--readURL() function to be implemented. Complicated.-->
			<input type="file" name="img_file" id="file"
			accept="image/gif, image/jpeg, image/png"
			onchange="readURL(this)"><br/>

			<!--Image preview-->
			<img src="#" alt="No preview available"><br/>

			<!--Title field-->
			<label>Title*:
			<input type="text" name="title" size="20">
			</label><br/>

			<!--Story field-->
			<label>Story*:</label>
			<textarea rows="20" cols="98" form="entry" placeholder="Write your story here..."></textarea><br/>

			<!--Submit button-->
			<input type="submit" name="submit" value="submit">
			</label>
		</form>
	</div>

	<?php include('includes/footer.php'); ?>
</body>
</html>

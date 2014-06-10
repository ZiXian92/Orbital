//Returns true if ALL fields are not empty and false otherwise
function validate(){
	var filename = document.forms["new_entry"]["img"].value;
	var title = document.forms["new_entry"]["title"].value;
	var text = document.forms["new_entry"]["story"].value;
	if(filename=="" || title=="" || text==""){
		alert("All fields are required.");
		return false;
	}
	return true;
}

function validate_file(){
	var filename = document.forms["new_entry"]["img"].value;
	var arr = str.split(filename, ".");
	var ext = arr[arr.length-1];
	alert(ext);
	if(ext!="jpg" && ext!="jpeg" && ext!="png"){
		document.forms["new_entry"]["img_file"]="";
		alert("Please upload an image file(extensions: jpg, jpeg, png");
	}
}

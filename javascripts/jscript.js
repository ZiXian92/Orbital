//Returns true if ALL fields are not empty and false otherwise
function validate(){
	var a = document.forms["new_entry"]["img_file"].value;
	var b = document.forms["new_entry"]["title"].value;
	var c = document.forms["new_entry"]["story"].value;
	if(a=="" || b=="" || c==""){
		alert("All fields are required.");
		return false;
	}
	return true;
}

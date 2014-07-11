/* jscript.js
 * Defines the functions to be used for form validation or any other
 * event-handling in the web page.
 * To be updated by Yulong
 */

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
	if(ext!="jpg" && ext!="jpeg" && ext!="png"){
		document.forms["new_entry"]["img"]="";
		alert("Please upload an image file(extensions: jpg, jpeg, png)");
	}
}

/* Validates signup form submission */
function validate_signup(){
	var name = document.forms["signup"]["name"].value;
	var email = document.forms["signup"]['email'].value;
	var pass1 = document.forms["signup"]["passwd"].value;
	var pass2 = document.forms["signup"]["re-passwd"].value;
	var email_format = /^[a-zA-Z0-9]+[a-zA-Z0-9_]*@[a-z\.]+\.com$/;

	if(name.length==0 || email.length==0 ||
		pass1.length==0 || pass2.length==0){
		alert("Please fill out all required fields.");
		return false;
	}

	if(!email.match(email_format)){
		alert("Invalid email address.");
		return false;
	}

	if(pass1!==pass2){
		alert("Please make sure the 2 password fields are the same.");
		return false;
	}

	return true;
}

/* Validates the login form */
function validate_login(){
	var email = document.forms["login_form"]["email"].value;
	var pass = document.forms["login_form"]["passwd"].value;
	var email_format = /^[a-zA-Z0-9]+[a-zA-Z0-9_]*@.+\..+$/;

	if(email.length==0 || pass.length==0){
		alert("Please fill out all required fields.");
		return false;
	}

	if(!email.match(email_format)){
		alert("Invalid email address.");
		return false;
	}

	return true;
}

/* Prompts user for confirmation of delete action */
function confirm_delete(){
	return confirm("Proceed with delete?");
}

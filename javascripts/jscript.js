/* jscript.js
 * Defines the functions to be used for form validation or any other
 * event-handling in the web page.
 * To be updated by Yulong
 */

var ajaxRequest;

//Checks the database to see if the username is available for use
function checkName(name){
	if(name.length==0){
		document.getElementById('checkName').innerHTML = 'Please enter a username';
		return;
	}
	ajaxRequest = new XMLHttpRequest();
	var data = {};
	data.name = name;
	ajaxRequest.open("POST", "users/checkName", true);
	ajaxRequest.setRequestHeader("Content-type", "application/json");
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200){
			document.getElementById("checkName").innerHTML = ajaxRequest.responseText;
		}
	}
	ajaxRequest.send(JSON.stringify(data));
}

//Checks if the given email addressis valid
function is_valid_email(email){
	var email_format = /^[a-zA-Z0-9]+[a-zA-Z0-9_]*@[a-z\.]+\.[a-z]+$/;
	return email.match(email_format);
}

//Checks the database to see if the email address is used by another user.
//Valid email address is sent to the server for checking against database.
function checkEmail(email){
	if(!is_valid_email(email)){
		document.getElementById('checkEmail').innerHTML = 'Invalid email address';
		return;
	}

	var data = {};
	data.email = email;
	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
			document.getElementById('checkEmail').innerHTML = ajaxRequest.responseText;
	};
	ajaxRequest.open('POST', 'users/checkEmail', true);
	ajaxRequest.setRequestHeader('Content-Type', 'application/json');
	ajaxRequest.send(JSON.stringify(data));
}

//Checks if the given password is valid
function checkPassword(passwd){
	var pass_format = /^[a-zA-Z0-9]+$/;
	if(passwd.match(pass_format))
		document.getElementById('checkPassword').innerHTML = 'Ok';
	else
		document.getElementById('checkPassword').innerHTML = 'Invalid password';
}

//Checks if the 2nd password matches the first
function confirmPassword(passwd2){
	var pass = document.forms['signup']['passwd'].value;
	if(pass==passwd2)
		document.getElementById('confirmPassword').innerHTML = 'Ok';
	else
		document.getElementById('confirmPassword').innerHTML = 'The passwords do not match';
}

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
	var nameCheck = document.getElementById('checkName').value;
	var emailCheck = document.getElementById('checkEmail').value;
	var passCheck = document.getElementById('checkPassword').value;
	var pass2Check = document.getElementById('confirmPassword').value;

	if(nameCheck!='Ok' || emailCheck!='Ok' || passCheck!='Ok' ||
	pass2Check!='Ok'){
		document.getElementById('error').innerHTML = 'Please make sure all fields are valid';
		return false;
	}
	return true;
}

//Validates the login form and logs the user in if login is successful
function validate_login(ev){
	var email = document.forms["login_form"]["email"].value;
	var pass = document.forms["login_form"]["passwd"].value;
	var email_format = /^[a-zA-Z0-9]+[a-zA-Z0-9_]*@.+\..+$/;

	//Performs input validation
	if(email.length==0 || pass.length==0){
		document.getElementById('error').innerHTML='Empty email and/or password field(s).';
		return false;
	}

	if(!email.match(email_format)){
		document.getElementById('error').innerHTML="Invalid email address.";
		return false;
	}

	//Prevent form from submitting by default
	ev.preventDefault();

	//Sending request to server to check login credentials
	var data = {};
	data.email = email;
	data.password = pass;

	ajaxRequest = new XMLHttpRequest();

	//Defines function to execute when request is completely processed,
	//as seen in conditional check for readyState to be 4.
	ajaxRequest.onreadystatechange=function(){
		if(ajaxRequest.readyState==4){
			if(ajaxRequest.status==200){
				document.getElementById('error').innerHTML=ajaxRequest.responseText;
				if(ajaxRequest.responseText=='Login successful')
					document.getElementById('login_form').submit();
			}
			else if(ajaxRequest.status==400)
				document.getElementById('error').innerHTML='Bad request';
		}
	};
	ajaxRequest.open("POST", "users/validate_login", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/json");
	ajaxRequest.send(JSON.stringify(data));
}

/* Prompts user for confirmation of delete action */
function confirm_delete(){
	return confirm("Proceed with delete?");
}

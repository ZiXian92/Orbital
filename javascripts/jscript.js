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

//Displays the message indicating validity of email in password reset page
function validate_email(email){
	if(is_valid_email(email))
		document.getElementById('checkEmail').innerHTML = 'Ok';
	else
		document.getElementById('checkEmail').innerHTML = 'Invalid email address';
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
	var pass = document.forms[0].passwd.value;
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
	var nameCheck = document.getElementById('checkName').innerHTML;
	var emailCheck = document.getElementById('checkEmail').innerHTML;
	var passCheck = document.getElementById('checkPassword').innerHTML;
	var pass2Check = document.getElementById('confirmPassword').innerHTML;

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

	//Performs input validation
	if(email.length==0 || pass.length==0){
		document.getElementById('error').innerHTML='Empty email and/or password field(s).';
		return false;
	}

	if(!is_valid_email(email)){
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

//Activates the user account identified by id.
//Only available to admin user.
function activate(ev, id){
	ev.preventDefault();
	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange=function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
			document.getElementById('active_'+id).innerHTML = 'Activated';
	};
	ajaxRequest.open("POST", "users/activate/"+id, true);
	ajaxRequest.send();
}

//Sends a request to reset password
function reset_passwd(ev){
	var name = document.forms['reset_password']['name'].value;
	var email = document.forms['reset_password']['email'].value;
	var checkEmail = document.getElementById('checkEmail').innerHTML;

	if(name.length==0){
		document.getElementById('error').innerHTML = 'Please enter a username';
		return false;
	}

	if(checkEmail!='Ok'){
		document.getElementById('error').innerHTML = 'Please use a valid email address';
		return false;
	}

	ev.preventDefault();

	//Prepares form data to be sent in JSON format
	var data = {};
	data.name = name;
	data.email = email;

	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
			document.getElementById('error').innerHTML = ajaxRequest.responseText;
	};
	ajaxRequest.open("POST", "users/reset_password", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/json");
	ajaxRequest.send(JSON.stringify(data));
}

//Sends a request to server to reset password for the selected user
//Can only be executed by admin user.
function admin_reset_password(ev, name, email){
	ev.preventDefault();
	var data = {};
	data.name = name;
	data.email = email;
	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange=function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
			document.getElementById('message').innerHTML = ajaxRequest.responseText;
	};
	ajaxRequest.open("POST", "users/reset_password", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/json");
	ajaxRequest.send(JSON.stringify(data));
}

//Sends password change request to server
function change_password(ev){
	ev.preventDefault();
	var oldpass = document.forms[0].oldpasswd.value;
	var newpass = document.forms[0].passwd.value;
	var checkPass = document.getElementById('checkPassword').innerHTML;
	var checkPass2 = document.getElementById('confirmPassword').innerHTML;

	if(oldpass.length==0){
		document.getElementById('error').innerHTML = 'Please enter your current password';
		return false;
	}

	if(checkPass!='Ok' || checkPass2!='Ok'){
		document.getElementById('error').innerHTML = 'Please make sure the 2 new passwords match';
		return false;
	}

	var data = {};
	data.oldpass = oldpass;
	data.newpass = newpass;

	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange=function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
			document.getElementById('error').innerHTML = ajaxRequest.responseText;
			if(ajaxRequest.responseText=='Success')
				document.forms[0].reset();
	};
	ajaxRequest.open('POST', 'users/changepassword', true);
	ajaxRequest.setRequestHeader('Content-Type', 'application/json');
	ajaxRequest.send(JSON.stringify(data));
}

/* Prompts user for confirmation of delete action */
function confirm_delete(){
	return confirm("Proceed with delete?");
}

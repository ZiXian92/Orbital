/* jscript.js
 * Defines the functions to be used for form validation or any other
 * event-handling in the web page.
 */

var ajaxRequest;

//Checks the database to see if the username is available for use
function checkName(name){
	//Checks if name is empty
	if(name.length==0){
		document.getElementById('checkName').innerHTML = 'Please enter a username';
		return;
	}

	//Sends POST request with parameters in JSON format
	ajaxRequest = new XMLHttpRequest();
	var data = {};
	data.name = name;
	ajaxRequest.open("POST", "/users/checkName", true);
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
	var email_format = /^[a-zA-Z0-9]+[a-zA-Z0-9_\.]*@[a-z\.]+\.[a-z]+$/;
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
	ajaxRequest.open('POST', '/users/checkEmail', true);
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

//Returns true if ALL fields are not empty and false otherwise
function validate_entry(ev){
	ev.preventDefault();
	var filename = document.forms[0].img.value;
	var title = document.forms[0].title.value;
	var text = document.forms[0].story.value;
	var checkFile = document.getElementById('checkFile').innerHTML;
	if(filename=="" || title=="" || text=="" || checkFile!='Ok')
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">Please make sure all fields are properly filled</div>';
	else{
		FB.getLoginStatus(function(response){
			if(response.status==='connected'){
				FB.login(function(){
					FB.api('/me/feed', 'post', {message: title+'\n'+text});
				}, {scope: 'publish_actions', auth_type: 'rerequest'});
			}
			document.forms[0].submit();
		});
	}
}

//Checks if the given image is of the supported type.
function is_valid_image(img){
	var imgType = img.type;
	if(imgType!="image/jpeg" && imgType!="image/png" && imgType!="image/bmp")
		return false;
	return true;
}

//Displays the preview of the selected image
function previewImage(){
	if(is_valid_image(document.forms[0].img.files[0]))
		document.getElementById('checkFile').innerHTML = 'Ok';
	else
		document.getElementById('checkFile').innerHTML = 'Only JPEG, PNG, and BMP files allowed';
	var ifReader = new FileReader();
	ifReader.readAsDataURL(document.forms[0].img.files[0]);
	ifReader.onload = function(evt){
		document.getElementById('preview').src = evt.target.result;
	};
}

/* Validates signup form submission */
function validate_signup(){
	var nameCheck = document.getElementById('checkName').innerHTML;
	var emailCheck = document.getElementById('checkEmail').innerHTML;
	var pass = document.forms[0].passwd.value;
	var pass2 = document.forms[0].passwd2.value;
	var terms = document.forms[0].terms.checked;

	if(nameCheck!='Ok'){
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">Invalid/Unavailable username.</div>';
		return false;
	}
	else if(emailCheck!='Ok'){
		document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">Invalid/Unavailable email address.</div>';
		return false;
	}
	else if(pass!=pass2){
		document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">The 2 passwords do not match.</div>';
		return false;
	}
	else if(!terms){
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">You must agree to our Terms of Use to sign up.</div>';
		return false;
	}
	
	return true;
}

//Validates the login form and logs the user in if login is successful
function validate_login(ev){
	//Prevent form from submitting by default
	ev.preventDefault();

	var email = document.forms[0].email.value;
	var pass = document.forms[0].passwd.value;

	//Performs input validation
	if(email.length==0 || pass.length==0){
		document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">Empty email and/or password field(s).</div>';
		return false;
	}

	if(!is_valid_email(email)){
		document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">Invalid email address.</div>';
		return false;
	}

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
				if(ajaxRequest.responseText=='Login successful'){
					document.getElementById('error').innerHTML='<div class="alert alert-success" role="alert">Login successful</div>';
					document.getElementById('login_form').submit();
				}
				else
					document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">'+ajaxRequest.responseText+'</div>';
			}
			else if(ajaxRequest.status==400)
				document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">Bad request</div>';
		}
	};
	ajaxRequest.open("POST", "/users/validate_login", true);
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
	ajaxRequest.open("POST", "/users/activate/"+id, true);
	ajaxRequest.send();
}

//Sends a request to reset password
function reset_passwd(ev){
	ev.preventDefault();

	var name = document.forms[0].name.value;
	var email = document.forms[0].email.value;
	var checkEmail = document.getElementById('checkEmail').innerHTML;

	if(name.length==0){
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">Please enter a username</div>';
		return false;
	}

	if(checkEmail!='Ok'){
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">Please use a valid email address</div>';
		return false;
	}

	//Prepares form data to be sent in JSON format
	var data = {};
	data.name = name;
	data.email = email;

	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200){
			document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">'+ajaxRequest.responseText+'</div>';
			document.forms[0].reset();
		}
	};
	ajaxRequest.open("POST", "/users/reset_password", true);
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
			document.getElementById('message').innerHTML = '<div class="alert alert-success" role="alert">'+ajaxRequest.responseText+'</div>';
	};
	ajaxRequest.open("POST", "/users/reset_password", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/json");
	ajaxRequest.send(JSON.stringify(data));
}

//Sends password change request to server
function change_password(ev){
	ev.preventDefault();
	var oldpass = document.forms[0].oldpasswd.value;
	var newpass = document.forms[0].passwd.value;
	var pass2 = document.forms[0].passwd2.value;
	var passCheck = document.getElementById('checkPassword').innerHTML;

	if(oldpass.length==0){
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">Please enter your current password</div>';
		return false;
	}
	else if(passCheck!='Ok'){
		document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">Invalid password.</div>';
		return false;
	}
	else if(newpass!=pass2){
		document.getElementById('error').innerHTML = '<div class="alert alert-danger" role="alert">The 2 new passwords do not match.</div>';
		return false;
	}

	var data = {};
	data.oldpass = oldpass;
	data.newpass = newpass;

	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange=function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200){
			if(ajaxRequest.responseText=='Success'){
				document.getElementById('error').innerHTML='<div class="alert alert-success" role="alert">Success</div>';
				document.forms[0].reset();
			}
		else
			document.getElementById('error').innerHTML='<div class="alert alert-danger" role="alert">'+ajaxRequest.responseText+'</div>';
		}
	};
	ajaxRequest.open('POST', '/users/changepassword', true);
	ajaxRequest.setRequestHeader('Content-Type', 'application/json');
	ajaxRequest.send(JSON.stringify(data));
}

/* Prompts user for confirmation of delete action */
function confirm_delete(){
	return confirm("Proceed with delete?");
}

//Confirms and deletes the selected user if admin chooses Ok
function delete_user(ev, id){
	ev.preventDefault();
	if(confirm_delete()){
		ajaxRequest = new XMLHttpRequest();
		ajaxRequest.onreadystatechange=function(){
			if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
				window.location.reload(true);
		};
		ajaxRequest.open('POST', '/admin/delete/'+id, true);
		ajaxRequest.send();
	}
}

//Sends request to server to delete entry identified by id
function delete_entry(ev, id){
	ev.preventDefault();
	if(confirm_delete()){
		ajaxRequest = new XMLHttpRequest();
		ajaxRequest.onreadystatechange=function(){
			if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
				window.location.reload(true);
		};
		ajaxRequest.open('POST', '/entries_handler/delete/'+id, true);
		ajaxRequest.send();
	}
}

//Sends request to server, telling it that it is log in via Facebook
function fb_login(){
	FB.getLoginStatus(function(response){
		if(response.status==='connected')
			document.getElementById('error').innerHTML = 'Logged in to Facebook';
		else{
			FB.login(function(response){
				if(response.authResponse)
					fb_login();
			}, {scope: 'public_profile, email'});
		}
	});
}

/* jscript.js
 * Defines the functions to be used for form validation or any other
 * event-handling in the web page.
 * To be updated by Yulong
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
	ajaxRequest.setRequestHeader("Content-Type", "application/json");
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

//Checks if the 2nd password matches the first
function confirmPassword(passwd2){
	var pass = document.forms[0].passwd.value;
	if(pass==passwd2)
		document.getElementById('confirmPassword').innerHTML = 'Ok';
	else
		document.getElementById('confirmPassword').innerHTML = 'The passwords do not match';
}

//Returns true if ALL fields are not empty and false otherwise
function validate_entry(ev){
	ev.preventDefault();
	var filename = document.forms[0].img.value;
	var title = document.forms[0].title.value;
	var text = document.forms[0].story.value;
	var checkFile = document.getElementById('checkFile').innerHTML;
	if(filename=="" || title=="" || text=="" || checkFile!='Ok')
		document.getElementById('error').innerHTML = 'Please make sure all fields are properly filled';
	else{
		document.forms[0].submit();
		//window.location.reload(false);
		document.forms[0].reset();
		document.getElementById('error').innerHTML = '';
		document.getElementById('preview').src = '#';
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
	//Prevent form from submitting by default
	ev.preventDefault();

	var email = document.forms[0].email.value;
	var pass = document.forms[0].passwd.value;

	//Performs input validation
	if(email.length==0 || pass.length==0){
		document.getElementById('error').innerHTML='Empty email and/or password field(s).';
		return false;
	}

	if(!is_valid_email(email)){
		document.getElementById('error').innerHTML="Invalid email address.";
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
				document.getElementById('error').innerHTML=ajaxRequest.responseText;
				if(ajaxRequest.responseText=='Login successful')
					document.getElementById('login_form').submit();
			}
			else if(ajaxRequest.status==400)
				document.getElementById('error').innerHTML='Bad request';
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
		document.getElementById('error').innerHTML = 'Please enter a username';
		return false;
	}

	if(checkEmail!='Ok'){
		document.getElementById('error').innerHTML = 'Please use a valid email address';
		return false;
	}

	//Prepares form data to be sent in JSON format
	var data = {};
	data.name = name;
	data.email = email;

	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200){
			document.getElementById('error').innerHTML = ajaxRequest.responseText;
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
			document.getElementById('message').innerHTML = ajaxRequest.responseText;
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
		if(response.status=='connected'){
			/*FB.api('/me', function(response) {
				data = {};
				data.name = response.name;
				data.email = response.email;
				ajaxRequest = new XMLHttpRequest();
				ajaxRequest.onreadystatechange = function(){
					if(ajaxRequest.readyState==4 && ajaxRequest.status==200)
						//document.getElement*/
			document.getElementById('error').innerHTML = 'Connected to Facebook';
				/*};
				ajaxRequest.open('POST', '/users/fb_login', true);
				ajaxRequest.send(JSON.stringify(data));
			}*/
		}
		else
			FB.login(function(response){
			//If Facebook login is complete
				if(response.authResponse)
					fb_login();
			});
	});
}

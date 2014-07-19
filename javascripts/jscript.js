/* jscript.js
 * Defines the functions to be used for form validation or any other
 * event-handling in the web page.
 * To be updated by Yulong
 */

var ajaxRequest;

function setCheckName(str){
	document.getElementById("checkName").innerHTML = str;
}

function checkName(name){
	ajaxRequest = new XMLHttpRequest();
	var data = {};
	data.name = name;
	ajaxRequest.open("GET", "/dummy/checkname", true);
	ajaxRequest.setRequestHeader("Content-type", "application/json");
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200){
			data = ajaxRequest.responseText;
	ajaxRequest.setRequestHeader("Accept", "application/json");
	ajaxRequest.setRequestHeader("Accept", "application/json");
			document.getElementById("checkName").innerHTML = data;
		}
	}
	ajaxRequest.send(JSON.stringify(data));
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
function validate_login(ev){
	var email = document.forms["login_form"]["email"].value;
	var pass = document.forms["login_form"]["passwd"].value;
	var email_format = /^[a-zA-Z0-9]+[a-zA-Z0-9_]*@.+\..+$/;

	//Performs input validation
	if(email.length==0 || pass.length==0){
		document.getElementById('error').innerHTML='Please fill out all required fields';
		return false;
	}

	if(!email.match(email_format)){
		document.getElementById('error').innerHTML="Invalid email address.";
		return false;
	}

	//Prevent form from submitting by default
	ev.preventDefault();
	//alert('Hello');
	//return false;

	//Sending request to server to check login credentials
	var data = {};
	var form = document.forms[0];
	data.email = form[0].value;
	data.password = form[1].value;

	ajaxRequest = new XMLHttpRequest();
	ajaxRequest.onreadystatechange=function(){
		if(ajaxRequest.readyState==4 && ajaxRequest.status==200){
			document.getElementById('error').innerHTML=ajaxRequest.responseText;
			if(ajaxRequest.responseText=='Login successful')
				login(JSON.stringify(data));
		}
	};
	ajaxRequest.open("POST", "users/validate_login", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/json");
	ajaxRequest.send(JSON.stringify(data));
	return false;
}

/* Prompts user for confirmation of delete action */
function confirm_delete(){
	return confirm("Proceed with delete?");
}

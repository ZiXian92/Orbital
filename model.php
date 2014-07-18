<?php
	require_once 'dropbox-sdk/Dropbox/autoload.php';
	use \Dropbox as dbx;

	/* Defines the Model class, which is responsible for retrieving
	 * web pages or data from database.
	 */

	#DEFINE('DB_HOST', 'ec2-23-23-177-33.compute-1.amazonaws.com');
	DEFINE('DB_HOST', 'localhost');

	/* For use on local server only */
	
	DEFINE('DB_USER', 'zixian');
	DEFINE('PASSWD', 'NanaMizuki');
	DEFINE('DB_NAME', 'ORBITAL');
	

	/* For use on published host */
	#DEFINE('DB_USER', 'bpcukcvcxyvolg');
	#DEFINE('PASSWD', 'fshWTu-St1ZFONmZIiIdI8HC9G');
	#DEFINE('DB_NAME', 'dcnc1cpi3klhlg');

	class Model{
		/* Variable holding connection with database */
		private $sql_con;

		/* Constructor. Establishes database connection. */
		public function __construct(){
			$this->sql_con = mysqli_connect(DB_HOST, DB_USER, PASSWD, DB_NAME);
			#$this->sql_con = pg_connect("host=".DB_HOST." user=".DB_USER." password=".PASSWD." port=5432 dbname=".DB_NAME);
			if(mysqli_connect_errno()){
				echo "Unable to connect to database. Error: ".mysqli_connect_error()."<br/>";
				$this->__destruct();
			}
		}

		/* Destructor. Closes database connection. */
		public function __destruct(){
			mysqli_close($this->sql_con);
		}

		/* Page Content-related Functions */

		/* Sets all placeholder values in the template,
		 * except for contents
		 */
		public function set_template(&$arr, $page){
			$arr['title'] = strtoupper(substr($page, 0, 1)).substr($page, 1);
			if(isset($_SESSION['username'])){
				if($_SESSION['user_id']==0)
					$arr['usrmenu'] = file_get_contents("html/admin_menu.html");
				else
					$arr['usrmenu'] = file_get_contents("html/loggedinmenu.html");
				$arr['author'] = "<input type=\"text\" 
					name=\"author\" size=\"20\" 
					value=\"".$_SESSION['username'].
					"\"maxlength=\"20\" disabled>";
			}
			else{
				$arr['usrmenu'] = file_get_contents("html/loggedoutmenu.html");
				$arr['author'] = "<input type=\"text\" 
					name=\"author\" size=\"20\" 
					placeholder=\"Your name here\"
					maxlength=\"20\">*required";
			}

			$arr['message'] = file_get_contents("message.txt");
			file_put_contents("message.txt", "");
		}

		/* Returns the contents of the HTML file referred
		 * to by $page.
		 * Returns a 404 error page if the page requested
		 * does not exist.
		 */
		public function get_page($page){
			if(isset($_SESSION['user_id']) && $page=="home"){
				if($_SESSION['user_id']!=0)
					return "{{message}}<p>Welcome, ".$_SESSION['username']."</p>".$this->list_entries_by_id($_SESSION['user_id']);
				return "{{message}}<p>Welcome, ".$_SESSION['username']."</p>".$this->list_users();
			}

			if(file_exists("html/".$page.".html"))
				return file_get_contents("html/".$page.".html");
			return file_get_contents("html/404.html");
		}

		/* User-related Administrative Functions */

		/* Adds a new user to the database.
		 * Refer to database design on restrictions on parameters.
		 * Restrictions to be listed here once finalised.
		 * $id must be an integer from 0 to 99999.
		 * $name is a string of maximum length 20
		 * $passwd is a string of 10 alphanumeric characters.
		 * $email must be a valid email address of length 50
		 */
		public function add_user($id, $name, $passwd, $email, $code){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$name = mysqli_real_escape_string($this->sql_con, $name);
			$passwd = mysqli_real_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$email = mysqli_real_escape_string($this->sql_con, $email);

			/* Using prepared statement for security purpose */
			$q = "INSERT INTO USERS VALUES(?, ?, ?, ?, ?)";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "issss", $id, $name, $passwd, $email, $code);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		/* Removes a user identified by $id from database.
		 * $id must be an integer between 1 and 99999.
		 * ID 0 is reserved for site admin and should only be
		 * deleted manually through an admin script or through
		 * the database client.
		 */
		#public function remove_user($id){
			#$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			/* Gets the file paths of all entries authored by
			 * the user with the given user ID and removes
			 * these files
			 */
			/*$q = "SELECT FILE FROM ENTRIES WHERE AUTHOR=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $file);
			while(mysqli_stmt_fetch($stmt))
				unlink($file);
			mysqli_stmt_close($stmt);

			/* Remove all records of entries related to the user
			 * identified by the given user ID
			 */
			/*$q = "DELETE FROM ENTRIES WHERE AUTHOR=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);

			/* Delete information about the user identified
			 * by the given user ID
			 */
			/*$q = "DELETE FROM USERS WHERE ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			if($id!=0)
				mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}*/
		public function remove_user($id, $dbxClient){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			/* Gets the file paths of all entries authored by
			 * the user with the given user ID and removes
			 * these files
			 */
			$q = "SELECT FILE FROM ENTRIES WHERE AUTHOR=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $file);
			while(mysqli_stmt_fetch($stmt))
				$dbxClient->delete("/".$file);
			mysqli_stmt_close($stmt);

			/* Remove all records of entries related to the user
			 * identified by the given user ID
			 */
			$q = "DELETE FROM ENTRIES WHERE AUTHOR=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);

			/* Delete information about the user identified
			 * by the given user ID
			 */
			$q = "DELETE FROM USERS WHERE ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			if($id!=0)
				mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		/* Checks if the given id exists in the database.
		 * $id must be an integer between 0 and 99999
		 */
		public function contains_id($id){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$q = "SELECT ID FROM USERS WHERE ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $res);
			return mysqli_stmt_fetch($stmt);
		}

		/* Returns the encrypted password of the user
		 * identified by $id
		 */
		public function get_password_by_id($id){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$q = "SELECT PASSWD FROM USERS WHERE ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $passwd);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
			return $passwd;
		}

		/* Resets the password of the user identified by the given
		 * email address to a random password.
		 * Returns the new random password if such a user can be
		 * found and false otherwise.
		 */
		public function reset_password($name, $email){
			$name = mysqli_real_escape_string($this->sql_con, $name);
			$email = mysqli_real_escape_string($this->sql_con, $email);
			$passwd = substr(md5(uniqid(rand(), true)), 0, 10);
			$enc_passwd = SHA1($passwd);
			$q = "UPDATE USERS SET PASSWD=? WHERE USERNAME=? AND EMAIL=? AND ACTIVE IS NULL";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "sss", $enc_passwd, $name, $email);
			mysqli_stmt_execute($stmt);
			if(mysqli_stmt_affected_rows($stmt)==1){
				mysqli_stmt_close($stmt);
				return $passwd;
			}
			mysqli_stmt_close($stmt);
			return false;
		}

		/* Changes the password of a user identified by $id */
		public function set_new_password($id, $passwd){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$passwd = mysqli_real_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$q = "UPDATE USERS SET PASSWD=? WHERE ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "si", $passwd, $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		/* Returns a table list of registered users */
		public function list_users(){
			$table = file_get_contents("html/users_table.html");
			$q = "SELECT ID, USERNAME, EMAIL, ACTIVE IS NULL FROM USERS WHERE ID!=0";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $id, $name, $email, $activated);
			$list = "";
			for($counter=1;;$counter++){
				if(!mysqli_stmt_fetch($stmt)){
					if(preg_match("/<\/tr>$/", $list))
						$list.="</span>";
					break;
				}

		/* Every 1st user should be the start of a new group of 10 */
				if($counter%10==1)
					$list.="<span class=\"section\" id=\"".(string)(floor($counter/10)+1)."\">";

		/* Gets all the elements of each row(user) */
				$list.="<tr><td>".$id."</td>
					<td>".$name."</td>
					<td>".$email."</td>
					<td><a href=\"admin.php?action=view&id=".(string)$id."\">View</a></td>";
					if($activated)
						$list.="<td>Activated</td>";
					else
						$list.="<td><a href=\"users.php?action=activate&id=".$id."\">Activate</a></td>";
					$list.="<td><a href=\"users.php?action=reset_passwd&name=".$name."&email=".urlencode($email)."\">Reset Password</a></td>
					<td><a href=\"admin.php?action=delete&id=".(string)$id."\" onclick=\"return confirm_delete();\">Delete</a></td></tr>";

		/* Every 10th user is the last of the group of 10 */
				if($counter%10==0)
					$list.="</span>";
			}
			$table = str_replace("{{list}}", $list, $table);
			mysqli_stmt_close($stmt);
			return $table;
		}

		/* User signup-related functions */

		/* Returns the next user_id to assign to the new user */
		public function get_user_id(){
			$q = "SELECT MAX(ID) MAX FROM USERS";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $next_id);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
			return (int)$next_id+1;
		}

		/* Checks if the database contains a user with the
		 * given email address
		 */
		public function contains_email($email){
			$email = mysqli_real_escape_string($this->sql_con, $email);
			$q = "SELECT ID FROM USERS WHERE EMAIL=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "s", $email);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $id);
			return mysqli_stmt_fetch($stmt)!=NULL;
		}

		/* Checks if the given username is already taken */
		public function contains_username($name){
			$name = mysqli_real_escape_string($this->sql_con, $name);
			$q = "SELECT ID FROM USERS WHERE USERNAME=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "s", $name);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $id);
			return mysqli_stmt_fetch($stmt)!=NULL;
		}

		/* Activates a new user account. Returns true on successful
		 * activation and false otherwise
		 */
		public function activate($email, $code){
			$email = mysqli_real_escape_string($this->sql_con, $email);
			$code = mysqli_real_escape_string($this->sql_con, $code);
			$q = "UPDATE USERS SET ACTIVE=NULL WHERE EMAIL=? AND ACTIVE=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "ss", $email, $code);
			mysqli_stmt_execute($stmt);
			if(mysqli_stmt_affected_rows($stmt)==1){
				mysqli_stmt_close($stmt);
				return true;
			}
			mysqli_stmt_close($stmt);
			return false;
		}

		/* Activates any account with administrative privileges */
		public function admin_activate($id){
			$id =  (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$q = "UPDATE USERS SET ACTIVE=NULL WHERE ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		/* User Login Functions */

		/* Checks if the user credentials are valid */
		public function is_valid_user($email, $passwd){
			$email = mysqli_real_escape_string($this->sql_con, $email);
			$passwd = mysqli_real_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$q = "SELECT ID FROM USERS WHERE EMAIL=? AND PASSWD=? AND ACTIVE IS NULL";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "ss", $email, $passwd);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $id);
			return mysqli_stmt_fetch($stmt)!=NULL;
		}

		/* Returns the user id and username based on the given
		 * email and password.
		 * Query Result will never be NULL as email and passwrod
		 * should be verified before calling this function.
		 */
		public function get_user($email, $passwd){
			$email = mysqli_real_escape_string($this->sql_con, $email);
			$passwd = mysqli_real_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$q = "SELECT ID, USERNAME FROM USERS WHERE EMAIL=? AND PASSWD=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "ss", $email, $passwd);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $id, $name);
			$arr = array();
			if(mysqli_stmt_fetch($stmt)==NULL)
				$arr['ID'] = $arr['USERNAME'] = NULL;
			else{
				$arr['ID'] = $id;
				$arr['USERNAME'] = $name;
			}
			mysqli_stmt_close($stmt);
			return $arr;
		}

		/* Entries-related Administrative Functions */

		/* Authenticates if the given entry request is valid.
		 * Admin ID will have overriding authority to deal with any
		 * valid entry
		 */
		public function authenticate_entry_request($user_id, $entry_id){
			$user_id = (int)mysqli_real_escape_string($this->sql_con, (string)$user_id);
			$entry_id = (int)mysqli_real_escape_string($this->sql_con, (string)$entry_id);
			if($user_id==0){
				$q = "SELECT ENTRY_ID FROM ENTRIES WHERE ENTRY_ID=?";
				$stmt = mysqli_prepare($this->sql_con, $q);
				mysqli_stmt_bind_param($stmt, "i", $entry_id);
			}
			else{
				$q = "SELECT ENTRY_ID FROM ENTRIES WHERE AUTHOR=? AND ENTRY_ID=?";
				$stmt = mysqli_prepare($this->sql_con, $q);
				mysqli_stmt_bind_param($stmt, "ii", $user_id, $entry_id);
			}
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $e_id);
			return mysqli_stmt_fetch($stmt);
		}

		/* Adds a new entry to the database.
		 * $entry_id must be an integer between 0 and 99999.
		 * $title must be a string of length
		 * $user_id must be an integer of between 0 and 99999.
		 * $date must be
		 * $file must be a string of length
		 */
		public function add_entry($entry_id, $title, $user_id, $date, $file){
			$entry_id = (int)mysqli_real_escape_string($this->sql_con, (string)$entry_id);
			$title = mysqli_real_escape_string($this->sql_con, $title);
			$user_id = (int)mysqli_real_escape_string($this->sql_con, (string)$user_id);
			$q = "INSERT INTO ENTRIES VALUES(?, ?, ?, ?, ?)";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "isiss", $entry_id, $title, $user_id, $date, $file);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}
		
		/* Removes an entry from the database.
		 * $id must be an integer between 0 to 99999.
		 */
		public function remove_entry($id){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$q = "DELETE FROM ENTRIES WHERE ENTRY_ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		/* Returns a table of entries by the user of the given $id */
		public function list_entries_by_id($id){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$q = "SELECT ENTRY_ID, DATE, TITLE FROM ENTRIES WHERE AUTHOR=? ORDER BY ENTRY_ID DESC";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $e_id, $date, $title);
			$table = file_get_contents("html/entries_table.html");
			$list = "";
			for($counter=1;;$counter++){
				if(!mysqli_stmt_fetch($stmt)){
					if(preg_match("/<\/tr>$/", $list))
						$list.="</span>";
					break;
				}
				if($counter%10==1)
					$list.="<span class=\"section\" id=\"".(string)(floor($counter/10)+1)."\">";
				$list.="<tr><td>".$date."</td>
					<td>".$title."</td>
					<td><a href=\"entries_handler.php?action=view&id=".$e_id."\">View</a>
					<a href=\"entries_handler.php?action=delete&id=".$e_id."\" onclick=\"return confirm_delete();\">Delete</a></td></tr>";
				if($counter%10==0)
					$list.="</span>";
			}
			$table = str_replace("{{list}}", $list, $table);
			mysqli_stmt_close($stmt);
			return $table;
		}

		/* Returns the next entry_id to assign to the new entry */
		public function get_entry_id(){
			$q = "SELECT MAX(ENTRY_ID) MAX FROM ENTRIES";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $e_id);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
			return (int)$e_id+1;
		}

		/* Returns the path to the entry file
		 * specified by the entry id.
		 * Returns NULL if the query returns nothing.
		 */
		public function get_entry_file($id){
			$id = (int)mysqli_real_escape_string($this->sql_con, (string)$id);
			$q = "SELECT FILE FROM ENTRIES WHERE ENTRY_ID=?";
			$stmt = mysqli_prepare($this->sql_con, $q);
			mysqli_stmt_bind_param($stmt, "i", $id);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $file);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
			return $file;
		}
	}
?>

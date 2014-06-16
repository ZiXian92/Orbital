<?php
	/* Defines the Model class, which is responsible for retrieving
	 * web pages or data from database.
	 */

	class Model{
		/* Variable holding connection with database */
		private $sql_con;

		/* Constructor. Establishes database connection. */
		public function __construct(){
			$this->sql_con = mysqli_connect("localhost", "zixian", "NanaMizuki", "ORBITAL");
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
			if($page=="create_entry" || $page=="signup" || $page=="login" || $page=="change_passwd")
				$arr['javascript'] = "<script src=\"javascripts/jscript.js\"></script>";
			else
				$arr['javascript'] = "";
			if(isset($_SESSION['username'])){
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

			if(file_exists("message.txt")){
				$arr['message'] = file_get_contents("message.txt");
				unlink("message.txt");
			}
			else
				$arr['message'] = "";
		}

		/* Returns the contents of the HTML file referred
		 * to by $page.
		 * Returns a 404 error page if the page requested
		 * does not exist.
		 */
		public function get_page($page){
			if(isset($_SESSION['user_id']) && $page=="home"){
				return "<p>Welcome, ".$_SESSION['username']."</p>".$this->list_entries_by_id($_SESSION['user_id']);
				
			}

			/* For logged in users, requests to any other pages
			 * are prohibited.
			 * If the page requested is to change password, only
			 * logged is users are allowed.
			 * All prohibited access are redirected to
			 * the home page.
			 */
			if((isset($_SESSION['user_id']) && ($page!="about" &&
					$page!="create_entry" &&
					$page!="change_passwd")) ||
				(!isset($_SESSION['user_id']) &&
					$page=="change_passwd")){
				header("Location: http://".$_SERVER['HTTP_HOST']."/index.php?page=home");
				exit(0);
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
		 * $name must be of length 20
		 * $passwd must be a non-empty string of length 10
		 * $email must be a valid email address of length 50
		 */
		public function add_user($id, $name, $passwd, $email){
			mysqli_query($this->sql_con, "INSERT INTO USERS VALUES(".(string)$id.", \"".$name."\", \"".SHA1($passwd)."\", \"".$email."\");");
		}

		/* Removes a user identified by $id from database.
		 * $id must be an integer between 1 and 99999.
		 * ID 0 is reserved for site admin and should only be
		 * deleted manually through an admin script or through
		 * the database client.
		 */
		public function remove_user($id){
			if($id!=0)
				mysqli_query($this->sql_con, "DELETE FROM USERS WHERE ID=".(string)$id.";");
		}

		/* Returns the encrypted password of the user
		 * identified by $id
		 */
		public function get_password_by_id($id){
			$result = mysqli_query($this->sql_con, "SELECT PASSWD FROM USERS WHERE ID=".(string)$id.";");
			$result = mysqli_fetch_assoc($result);
			return $result['PASSWD'];
		}

		/* Changes the password of a user identified by $id */
		public function set_new_password($id, $passwd){
			mysqli_query($this->sql_con, "UPDATE USERS SET PASSWD=\"".SHA1($passwd)."\" WHERE ID=".(string)$id.";");
		}

		/* User signup-related functions */

		/* Returns the next user_id to assign to the new user */
		public function get_user_id(){
			$result = mysqli_query($this->sql_con, "SELECT MAX(ID) MAX FROM USERS;");
			$row = mysqli_fetch_assoc($result);
			return ((int)$row['MAX'])+1;
		}

		/* Checks if the database contains a user with the
		 * given email address
		 */
		public function contains_email($email){
			$result = mysqli_query($this->sql_con, "SELECT * FROM USERS WHERE EMAIL=\"".$email."\";");
			$row = mysqli_fetch_assoc($result);
			if($row==NULL)
				return false;
			return true;
		}

		/* User Login Functions */

		/* Checks if the user credentials are valid */
		public function is_valid_user($email, $passwd){
			$result = mysqli_query($this->sql_con, "SELECT * FROM USERS WHERE EMAIL=\"".$email."\" AND PASSWD=\"".SHA1($passwd)."\";");
			$row = mysqli_fetch_assoc($result);
			if($row==NULL)
				return false;
			return true;
		}

		/* Returns the user id and username based on the given
		 * email and password
		 */
		public function get_user($email, $passwd){
			$result = mysqli_query($this->sql_con, "SELECT ID, USERNAME from USERS WHERE EMAIL=\"".$email."\" AND PASSWD=\"".SHA1($passwd)."\";");
			return mysqli_fetch_assoc($result);
		}

		/* Entries-related Administrative Functions */

		/* AUthenticates if the given entry request is valid */
		public function authenticate_entry_request($user_id, $entry_id){
			$result = mysqli_query($this->sql_con, "SELECT ENTRY_ID FROM ENTRIES WHERE AUTHOR=".(string)$user_id." AND ENTRY_ID=".(string)$entry_id.";");
			return ($result!=null);
		}

		/* Adds a new entry to the database.
		 * $entry_id must be an integer between 0 and 99999.
		 * $title must be a string of length
		 * $user_id must be an integer of between 0 and 99999.
		 * $date must be
		 * $file must be a string of length
		 */
		public function add_entry($entry_id, $title, $user_id, $date, $file){
			mysqli_query($this->sql_con, "INSERT INTO ENTRIES VALUES(".(string)$entry_id.", \"".$title."\", ".(string)$user_id.", \"".$date."\", \"".$file."\");");
		}
		
		/* Removes an entry from the database.
		 * $id must be an integer between 0 to 99999.
		 */
		public function remove_entry($id){
			mysqli_query($this->sql_con, "DELETE FROM ENTRIES WHERE ENTRY_ID=".(string)$id.";");
		}

		/* Returns a table of entries by the user of the given $id */
		private function list_entries_by_id($id){
			$result = mysqli_query($this->sql_con, "SELECT ENTRY_ID, DATE, TITLE FROM ENTRIES WHERE AUTHOR=".(string)$id." ORDER BY ENTRY_ID DESC;");
			$table = "<table border=\"1\">
				<tr><th>Date</th><th>Title</th>
				<th>Action</th><tr>";
			$row = mysqli_fetch_assoc($result);
			while($row!=NULL){
				$table.="<tr><td>".$row['DATE']."</td>
					<td>".$row['TITLE']."</td>
					<td><a href=\"entries_handler.php?action=view&id=".$row['ENTRY_ID']."\">View</a>
					<a href=\"entries_handler.php?action=delete&id=".$row['ENTRY_ID']."\">Delete</a></td></tr>";
				$row = mysqli_fetch_assoc($result);
			}
			$table.="</table>";
			return $table;
		}

		/* Returns the next entry_id to assign to the new entry */
		public function get_entry_id(){
			$result = mysqli_query($this->sql_con, "SELECT MAX(ENTRY_ID) MAX FROM ENTRIES;");
			$row = mysqli_fetch_assoc($result);
			return ((int)$row['MAX'])+1;
		}

		/* Returns the path to the entry file
		 * specified by the entry id.
		 * Returns NULL if the query returns nothing.
		 */
		public function get_entry_file($id){
			$result = mysqli_query($this->sql_con, "SELECT FILE FROM ENTRIES WHERE ENTRY_ID=".(string)$id.";");
			if($result==null)
				return null;
			$result = mysqli_fetch_assoc($result);
			return $result['FILE'];
		}
	}
?>

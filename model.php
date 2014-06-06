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

		/* Sets all placeholder values in the template,
		 * except for contents
		 */
		public function set_template(&$arr, $page){
			$arr['title'] = strtoupper(substr($page, 0, 1)).substr($page, 1);
			if($page=="create_entry" || $page=="signup" || $page=="login")
				$arr['javascript'] = "<script src=\"javascripts/jscript.js\"></script>";
			else
				$arr['javascript'] = "";
			if(isset($_COOKIE['username']))
				$arr['usrmenu'] = "<li>
					<a href=\"index.php?page=create_entry\">
					Create Entry</a>
					</li>
					<li>
					<a href=\"logout.php\">Logout</a>
					</li>";
			else
				$arr['usrmenu'] = "<li>
					<a href=\"index.php?page=signup\">
					Sign Up</a>
					</li>
					<li>
					<a href=\"index.php?page=login\">
					Login</a>
					</li>";

			if(isset($_GET['error']))
				$arr['error'] = $_GET['error'];
			else
				$arr['error'] = "";
		}

		/* Returns the contents of the HTML file referred
		 * to by $page.
		 * Returns a 404 error page if the page requested
		 * does not exist.
		 */
		public function get_page($page){
			if(isset($_COOKIE['user_id']) && $page=="home"){
				//list table of entries
				#return;
			}
			if(file_exists("html/".$page.".html"))
				return file_get_contents("html/".$page.".html");
			return file_get_contents("html/404.html");
		}

		/* Adds a new user to the database.
		 * Refer to database design on restrictions on parameters.
		 * Restrictions to be listed here once finalised.
		 * $id must be an integer from 0 to 99999.
		 * $name must be of length 20
		 * $passwd must be a non-empty string of length
		 * $email must be a valid email address of length 50
		 */
		public function add_user($id, $name, $passwd, $email){
			mysqli_query($this->sql_con, "INSERT INTO USERS VALUES(".(string)$id.", \"".(string)$name."\", \"".$passwd."\", \"".$email."\");");
		}

		/* Removes a user identified bt $id from database.
		 * $id must be an integer between 0 and 99999
		 */
		public function remove_user($id){
			mysqli_query($this->sql_con, "DELETE FROM USERS WHERE ID=".(string)$id.";");
		}

		/* Returns the next user_id to assign to the new user */
		public function get_user_id(){
			$result = mysqli_query($this->con, "SELECT MAX(ID) MAX FROM USERS;");
			$row = mysqli_fetch_assoc($result);
			return ((int)$row['MAX'])+1;
		}

		/* Returns the next entry_id to assign to the new entry */
		public function get_entry_id(){
			$result = mysqli_query($this->con, "SELECT MAX(ENTRY_ID) MAX FROM ENTRIES;");
			$row = mysqli_fetch_assoc($result);
			return ((int)$row['MAX'])+1;
		}

		/* Adds a new entry to the database.
		 * $entry_id must be an integer between 0 and 99999.
		 * $title must be a string of length
		 * $user_id must be an integer of between 0 and 99999.
		 * $date must be
		 * $file must be a string of length
		 */
		public function add_entry($entry_id, $title, $user_id, $date, $file){
			mysqli_query($this->sql_con, "INSERT INTO ENTRIES VALUES(".$entry_id.", \"".$title."\", ".$user_id.", ".$date.", \"".$file."\");");
		}
		
		/* Removes an entry from the database.
		 * $id must be an integer between 0 to 99999.
		 */
		public function remove_entry($id){
			mysqli_query($this->sql_con, "DELETE FROM ENTRIES WHERE ID=".(string)$id.";");
		}

		/* Returns a table of entries by the user of the given $id */
		private function list_entries_by_id($id){
			$result = mysqli_query($this->sql_con, "SELECT ENTRY_ID, DATE, TITLE FROM ENTRIES WHERE AUTHOR=".(string)$id." ORDER BY ENTRY_ID DESC;");
			$table = "<table>
				<tr><th>Date</th><th>Title</th>
				<th>Action</th><tr>";
			$row = mysqli_fetch_assoc($result);
			while($row!=NULL){
				$table.="<tr><td>".$row['DATE']."</td>
					<td>".$row['TITLE']."</td>
					<td><a href=\"view_entry.php?id=".$row['ENTRY_ID']."\">View</a>
					<a href=\"delete_entry.php?id=".$row['ENTRY_ID']."\">Delete</a></td></tr>";
				$row = mysqli_fetch_assoc($result);
			}
			$table.="</table>";
		}

		/* Checks if the database contains a user with the
		 * given email address
		 */
		public function contains_email($email){
			$result = mysqli_query($this->sql_con, "SELECT * FROM USERS WHERE EMAIL=".$email.";");
			$row = mysqli_fetch_assoc($result);
			if($row==NULL)
				return false;
			return true;
		}

		/* Checks if the user credentials are valid */
		public function is_valid_user($email, $passwd){
			$result = mysqli_query($this->sql_con, "SELECT * FROM USERS WHERE EMAIL=".$email."AND PASSWD=".$passwd.";");
			$row = mysqli_fetch_assoc($result);
			if($row==NULL)
				return false;
			return true;
		}
	}
	
	/*
	$model = new Model();
	$model->remove_user(1);
	#$model->add_user(1, "xxx", NULL, "xxx@gmail.com");
	unset($model);
	*/
?>

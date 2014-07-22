<?php
	require_once 'dropbox-sdk/Dropbox/autoload.php';
	use \Dropbox as dbx;

	/* Defines the Model class, which is responsible for retrieving
	 * web pages or data from database.
	 */

	DEFINE('DB_HOST', 'ec2-23-23-177-33.compute-1.amazonaws.com');

	/* For use on local server only */
	/*
	DEFINE('DB_USER', 'zixian');
	DEFINE('PASSWD', 'NanaMizuki');
	DEFINE('DB_NAME', "ORBITAL");
	*/

	/* For use on published host */
	DEFINE('DB_USER', 'bpcukcvcxyvolg');
	DEFINE('PASSWD', 'fshWTu-St1ZFONmZIiIdI8HC9G');
	DEFINE('DB_NAME', 'dcnc1cpi3klhlg');

	class Model{
		/* Variable holding connection with database */
		private $sql_con;

		/* Constructor. Establishes database connection. */
		public function __construct(){
			#$this->sql_con = mysqli_connect(DB_HOST, DB_USER, PASSWD, DB_NAME);
			$this->sql_con = pg_connect("host=".DB_HOST." user=".DB_USER." password=".PASSWD." port=5432 dbname=".DB_NAME);
			if(!$this->sql_con){
				echo "Unable to connect to database.";
				exit(0);
			}
		}

		/* Destructor. Closes database connection. */
		public function __destruct(){
			pg_close($this->sql_con);
		}

		/* Page Content-related Functions */

		#Returns an array of all the page parameters
		public function get_page_params($page){
			$arr = array();

			//Sets the menu bar and the author field of entry form
			if(isset($_SESSION['username'])){
				if($_SESSION['user_id']==0)
					$arr['usrmenu'] = file_get_contents("/html/admin_menu.html");
				else
					$arr['usrmenu'] = file_get_contents("/html/loggedinmenu.html");
				$arr['author'] = "<input type=\"text\" 
					name=\"author\" size=\"20\" 
					value=\"".$_SESSION['username'].
					"\"maxlength=\"20\" disabled>";
			}
			else{
				$arr['usrmenu'] = file_get_contents("/html/loggedoutmenu.html");
				$arr['author'] = "<input type=\"text\" 
					name=\"author\" size=\"20\" 
					placeholder=\"Your name here\"
					maxlength=\"20\">*required";
			}

			if(isset($_SESSION['user_id']) && $page=='home'){
				$arr['content'] = '<div class="message" id="message"></div><p>Welcome, '.$_SESSION['username'].'</p>';
				if($_SESSION['user_id']==0)
					$arr['content'].=$this->list_users();
				else
					$arr['content'].=$this->list_entries_by_id($_SESSION['user_id']);
				$arr['title'] = 'Home';
			}

			//Sets the title and the main content
			elseif(file_exists("/html/".$page.".html")){
				$arr['content'] = file_get_contents("html/".$page.".html");
				$arr['title'] = strtoupper(substr($page, 0, 1)).substr($page, 1);
			}
			else{
				$arr['content'] = file_get_contents("html/404.html");
				$arr['title'] = '404';
			}
			return $arr;
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
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$name = pg_escape_string($this->sql_con, $name);
			$passwd = pg_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$email = pg_escape_string($this->sql_con, $email);

			/* Using prepared statement for security purpose */
			$q = "INSERT INTO USERS VALUES($1, $2, $3, $4, $5)";
			pg_prepare($this->sql_con, "", $q);
			pg_execute($this->sql_con, "", array($id, $name, $passwd, $email, $code));
		}

		/* Removes a user identified by $id from database.
		 * $id must be an integer between 1 and 99999.
		 * ID 0 is reserved for site admin and should only be
		 * deleted manually through an admin script or through
		 * the database client.
		 */
		public function remove_user($id, $dbxClient){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			/* Gets the file paths of all entries authored by
			 * the user with the given user ID and removes
			 * these files
			 */
			$q = "SELECT FILE FROM ENTRIES WHERE AUTHOR=$1";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($id));
			while($row = pg_fetch_assoc($result))
				$dbxClient->delete("/".$row['file']);
			pg_free_result($result);

			/* Remove all records of entries related to the user
			 * identified by the given user ID
			 */
			$q = "DELETE FROM ENTRIES WHERE AUTHOR=$1";
			pg_prepare($this->sql_con, "", $q);
			pg_execute($this->sql_con, "", array($id));

			/* Delete information about the user identified
			 * by the given user ID
			 */
			$q = "DELETE FROM USERS WHERE ID=$1";
			pg_prepare($this->sql_con, "", $q);
			if($id!=0)
				pg_execute($this->sql_con, "", array($id));
		}

		/* Checks if the given id exists in the database.
		 * $id must be an integer between 0 and 99999
		 */
		public function contains_id($id){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$q = "SELECT ID FROM USERS WHERE ID=$1";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($id));
			return pg_fetch_row($result);
		}

		/* Returns the encrypted password of the user
		 * identified by $id
		 * For checking that the original password is correct and
		 * hence authorises the change of password.
		 */
		public function get_password_by_id($id){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$q = "SELECT PASSWD FROM USERS WHERE ID=$1";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($id));
			$row = pg_fetch_assoc($result);
			return $row['passwd'];
		}

		/* Resets the password of the user identified by the given
		 * email address to a random password.
		 * Returns the new random password if such a user can be
		 * found and false otherwise.
		 */
		public function reset_password($name, $email){
			$name = pg_escape_string($this->sql_con, $name);
			$email = pg_escape_string($this->sql_con, $email);
			$passwd = substr(md5(uniqid(rand(), true)), 0, 10);
			$enc_passwd = SHA1($passwd);
			$q = "UPDATE USERS SET PASSWD=$1 WHERE USERNAME=$2 AND EMAIL=$3 AND ACTIVE IS NULL";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($enc_passwd, $name, $email));
			if(pg_affected_rows($result)==1)
				return $passwd;
			return false;
		}

		/* Changes the password of a user identified by $id */
		public function set_new_password($id, $passwd){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$passwd = pg_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$q = "UPDATE USERS SET PASSWD=$1 WHERE ID=$2";
			pg_prepare($this->sql_con, "", $q);
			pg_execute($this->sql_con, "", array($passwd, $id));
		}

		/* Returns a table list of registered users */
		public function list_users(){
			$table = file_get_contents("/html/users_table.html");
			$q = "SELECT ID, USERNAME, EMAIL, ACTIVE IS NULL ACTIVATED FROM USERS WHERE ID!=0";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array());
			$list = "";
			for($counter=1;;$counter++){
				if(!$row = pg_fetch_assoc($result)){
					if(preg_match("/<\/tr>$/", $list))
						$list.="</span>";
					break;
				}

		/* Every 1st user should be the start of a new group of 10 */
				if($counter%10==1)
					$list.="<span class=\"section\" id=\"".(string)(floor($counter/10)+1)."\">";

		/* Gets all the elements of each row(user) */
				$list.="<tr><td>".$row['id']."</td>
					<td>".$row['username']."</td>
					<td>".$row['email']."</td>
					<td><a href=\"/admin/view/".(string)$row['id']."\">View</a></td>";
					if($row['activated'])
						$list.="<td>Activated</td>";
					else
						$list.="<td id=\"active_".$id."\"><a href=\"/users/activate/".$row['id']."\" onclick=\"activate(event, ".$row['id'].");\">Activate</a></td>";
					$list.="<td><a href=\"/users/reset_password/".$row['username']."/".urlencode($row['email'])."\" onclick=\"admin_reset_password(event, '".$row['username']."', '".$row['email']."');\">Reset Password</a></td>
					<td><a href=\"admin/delete/".(string)$row['id']."\" onclick=\"delete_user(event, ".(string)$row['id'].");\">Delete</a></td></tr>";

		/* Every 10th user is the last of the group of 10 */
				if($counter%10==0)
					$list.="</span>";
			}
			$table = str_replace("{{list}}", $list, $table);
			pg_free_result($result);
			return $table;
		}

		/* User signup-related functions */

		/* Returns the next user_id to assign to the new user */
		public function get_user_id(){
			$q = "SELECT MAX(ID) MAX FROM USERS";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array());
			$row = pg_fetch_assoc($result);
			return (int)$row['max']+1;
		}

		/* Checks if the database contains a user with the
		 * given email address
		 */
		public function contains_email($email){
			$email = pg_escape_string($this->sql_con, $email);
			$q = "SELECT ID FROM USERS WHERE EMAIL=$1";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($email));
			return pg_fetch_row($result);
		}

		/* Checks if the given username is already taken */
		public function contains_username($name){
			$name = pg_escape_string($this->sql_con, $name);
			$q = "SELECT ID FROM USERS WHERE USERNAME=$1";
			pg_prepare($this->sql_con, "", $q);
			pg_execute($this->sql_con, "", array($name));
			return pg_fetch_row($stmt);
		}

		/* Activates a new user account. Returns true on successful
		 * activation and false otherwise
		 */
		public function activate($email, $code){
			$email = pg_escape_string($this->sql_con, $email);
			$code = pg_escape_string($this->sql_con, $code);
			$q = "UPDATE USERS SET ACTIVE=NULL WHERE EMAIL=$1 AND ACTIVE=$2";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($email, $code));
			if(pg_affected_rows($result)==1)
				return true;
			return false;
		}

		/* Activates any account with administrative privileges */
		public function admin_activate($id){
			$id =  (int)pg_escape_string($this->sql_con, (string)$id);
			$q = "UPDATE USERS SET ACTIVE=NULL WHERE ID=$1";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($id));
			if(pg_affected_rows($result)==1)
				return true;
			return false;
		}

		/* User Login Functions */

		/* Checks if the user credentials are valid */
		public function is_valid_user($email, $passwd){
			$email = pg_escape_string($this->sql_con, $email);
			$passwd = pg_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$q = "SELECT ID FROM USERS WHERE EMAIL=$1 AND PASSWD=$2 AND ACTIVE IS NULL";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($email, $passwd));
			return pg_fetch_row($result);
		}

		/* Returns the user id and username based on the given
		 * email and password.
		 * Query result will never be NULL as email and password
		 * should be verified before calling this function.
		 * PostgreSQL converts all field names to lowercase,
		 * so associative contains indices 'id' and 'username'.
		 */
		public function get_user($email, $passwd){
			$email = pg_escape_string($this->sql_con, $email);
			$passwd = pg_escape_string($this->sql_con, $passwd);
			$passwd = SHA1($passwd);
			$q = "SELECT ID, USERNAME FROM USERS WHERE EMAIL=$1 AND PASSWD=$2";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($email, $passwd));
			if($row = pg_fetch_assoc($result))
				return $row;
			pg_free_result($result);
			return false;
		}

		/* Entries-related Administrative Functions */

		/* Authenticates if the given entry request is valid.
		 * Admin ID will have overriding authority to deal with any
		 * valid entry
		 */
		public function authenticate_entry_request($user_id, $entry_id){
			$user_id = (int)pg_escape_string($this->sql_con, (string)$user_id);
			$entry_id = (int)pg_escape_string($this->sql_con, (string)$entry_id);
			if($user_id==0){
				$q = "SELECT ENTRY_ID FROM ENTRIES WHERE ENTRY_ID=$1";
				$params = array($entry_id);
			}
			else{
				$q = "SELECT ENTRY_ID FROM ENTRIES WHERE AUTHOR=$1 AND ENTRY_ID=$2";
				$params = array($user_id, $entry_id);
			}
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", $params);
			return pg_fetch_row($result);
		}

		/* Adds a new entry to the database.
		 * $entry_id must be an integer between 0 and 99999.
		 * $title must be a string of length
		 * $user_id must be an integer of between 0 and 99999.
		 * $date must be
		 * $file must be a string of length
		 */
		public function add_entry($entry_id, $title, $user_id, $date, $file){
			$entry_id = (int)pg_escape_string($this->sql_con, (string)$entry_id);
			$title = pg_escape_string($this->sql_con, $title);
			$user_id = (int)pg_escape_string($this->sql_con, (string)$user_id);
			$q = "INSERT INTO ENTRIES VALUES($1, $2, $3, $4, $5)";
			pg_prepare($this->sql_con, "", $q);
			pg_execute($this->sql_con, "", array($entry_id, $title, $user_id, $date, $file));
		}
		
		/* Removes an entry from the database.
		 * $id must be an integer between 0 to 99999.
		 */
		public function remove_entry($id){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$q = "DELETE FROM ENTRIES WHERE ENTRY_ID=$1";
			pg_prepare($this->sql_con, "", $q);
			pg_execute($this->sql_con, "", array($id));
		}

		/* Returns a table of entries by the user of the given $id */
		public function list_entries_by_id($id){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$q = "SELECT ENTRY_ID, DATE, TITLE FROM ENTRIES WHERE AUTHOR=$1 ORDER BY ENTRY_ID DESC";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($id));
			$table = file_get_contents("/html/entries_table.html");
			$list = "";
			for($counter=1;;$counter++){
				if(!$row = pg_fetch_assoc($result)){
					if(preg_match("/<\/tr>$/", $list))
						$list.="</span>";
					break;
				}
				if($counter%10==1)
					$list.="<span class=\"section\" id=\"".(string)(floor($counter/10)+1)."\">";
				$list.="<tr><td>".$row['date']."</td>
					<td>".$row['title']."</td>
					<td><a href=\"/entries_handler/view/".$row['entry_id']."\">View</a>
					<a href=\"/entries_handler/delete/".$row['entry_id']."\" onclick=\"delete_entry(event, ".$row['entry_id'].");\">Delete</a></td></tr>";
				if($counter%10==0)
					$list.="</span>";
			}
			$table = str_replace("{{list}}", $list, $table);
			pg_free_result($result);
			return $table;
		}

		/* Returns the next entry_id to assign to the new entry */
		public function get_entry_id(){
			$q = "SELECT MAX(ENTRY_ID) MAX FROM ENTRIES";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array());
			mysqli_stmt_bind_result($stmt, $e_id);
			$row = pg_fetch_assoc($result);
			pg_free_result($result);
			return (int)$row['max']+1;
		}

		/* Returns the path to the entry file
		 * specified by the entry id.
		 * Returns NULL if the query returns nothing.
		 */
		public function get_entry_file($id){
			$id = (int)pg_escape_string($this->sql_con, (string)$id);
			$q = "SELECT FILE FROM ENTRIES WHERE ENTRY_ID=$1";
			pg_prepare($this->sql_con, "", $q);
			$result = pg_execute($this->sql_con, "", array($id));
			$row = pg_fetch_assoc($result);
			pg_free_result($result);
			return $row['file'];
		}
	}
?>

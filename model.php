<?php
	/* Defines the Model class, which is responsible for retrieving
	 * web pages or data from database.
	 */

	class Model{
		private $sql_con;

		/* Constructor. Establishes database connection. */
		public function __construct(){
			$this->sql_con = mysqli_connect("localhost", "zixian", "NanaMizuki", "ORBITAL");
			if(mysqli_connect_errno()){
				echo "Unable to connect to database. Error: ".mysqli_connect_error()."<br/>";
				$this->__destruct();
			}
			#echo "Connection success!<br/>";
		}

		/* Destructor. Closes database connection. */
		public function __destruct(){
			mysqli_close($this->sql_con);
			#echo "Connection closed.<br/>";
		}

		/* Returns the contents of the HTML file referred
		 * to by $page
		 */
		public function get_page($page){
			if(file_exists("html/".$page.".html"))
				return file_get_contents("html/".$page.".html");
			#else throw an exception or return contents of 404.html
			return NULL;
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

		/* Adds a new entry to the database.
		 * To be completed.
		 */
		public function add_entry(){

		}
		
		/* Removes an entry from the database.
		 * $id must be an integer between 0 to 99999.
		 */
		public function remove_entry($id){
			mysqli_query($this->sql_con, "DELETE FROM ENTRIES WHERE ID=".(string)$id.";");
		}
	}
	
	/*
	$model = new Model();
	$model->remove_user(1);
	#$model->add_user(1, "xxx", NULL, "xxx@gmail.com");
	unset($model);
	*/
?>

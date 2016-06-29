<?php
function controle(array $keys){
	foreach($keys as $name){
		if(!array_key_exists($name, $_POST)){
			return false;
		}
	}
	
	return true;
}
if(controle([
		"db_host",
		"db_user",
		"db_pass",
		"db_table",
		"db_prefix",
		"username",
		"password",
		"email"
])){
	//this is a test of the mysql connection 
	$mysqli = @new mysqli($_POST["db_host"], $_POST["db_user"], $_POST["db_pass"], $_POST["db_table"]);
	if(empty($mysqli->connect_errno)){
		$_SESSION["username"] = $_POST["username"];
		$_SESSION["password"] = $_POST["password"];
		$_SESSION["email"]    = $_POST["email"];
		$fopen = fopen("../include/config.json", "w+");
		fwrite($fopen, json_encode([
				"host" => $_POST["db_host"],
				"user" => $_POST["db_user"],
				"pass" => $_POST["db_pass"],
				"table" => $_POST["db_table"],
				"prefix" => $_POST["db_prefix"]
		]));
		fclose($fopen);
		header("location:index.php");
		exit;
	}else{
		$error = "[Database error]".$mysqli->connect_error;
	}
}
?>
<!DOCTYPE html>
<html>
 <head>
   <title>Weclommen to CowChat install progroam</title>
 </head>
 <body>
   <?php if(isset($error)){?>
     <div class='error'>
       <?php echo $error;?>
     </div>
   <?php }?>
   <div>
   <form method="POST">
     <div>To install the chat wee need a admin account and database connections</div>
     <fieldset>
      <legend>Admin account</legend>
      <table>
      <tr>
       <th>Username</th>
       <td><input name="username"></td>
      </tr>
      <tr>
       <th>Password</th>
       <td><input type="password" name="password"></td>
      </tr>
      <tr>
       <th>Email</th>
       <td><input type="email" name="email"></td>
      </tr>
      </table>
     </fieldset>
     <fieldset>
       <legend>MySQL Connection data</legend>
       <table>
         <tr>
           <th>Host</th>
           <td><input name="db_host" value="localhost"></td> 
         </tr>
         <tr>
           <th>User</th>
           <td><input name="db_user" value="root"></td>
         </tr>
         <tr>
           <th>Password</th>
           <td><input name="db_pass" type="password"></td>
         </tr>
         <tr>
           <th>Database</th>
           <td><input name="db_table" value="CowChat"></td>
         </tr>
         <tr>
           <th>Prefix</th>
           <td><input name="db_prefix" value="chat"></td>
         </tr>
       </table>
     </fieldset>
     <input type="submit" value="Create config and continue to create table">
     </form>
   </div>
 </body>
</html>
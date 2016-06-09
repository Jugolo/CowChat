<?php
define("NO_CONTEXT", true);
include 'index.php';
echo "Try to controle the login page\r\n";

$buffer = [];
for($i = 0;$i < 1001;$i++){
	$cache = User::createUser($i, $i + 1001, "rix17172@gmail.com");
	$buffer[] = [
			"pass" => $i + 1001,
			"hash" => $cache["hash"],
			"active" => $cache["active"]
	];
	echo "Save info at number: " . $i . " at 1000\r\n";
}

echo "Try now to login. it stop at the first fail\r\n";
for($i = 0;$i < 1001;$i++){
	$sql = Database::query("SELECT * FROM " . table("user") . " WHERE `username`='" . $i . "'");
	$row = $sql->fetch();
	if(hash_password($i + 1001, $row["hash"], $row["active"]) != $row["password"]){
		echo $buffer[$i]["active"] . "(" . gettype($buffer[$i]["active"]) . ")\r\n";
		echo $row["active"] . "(" . gettype($row["active"]) . ")\r\n";
		exit("Fail");
	}else{
		echo "Pass for user " . $i . " is accepted\r\n";
	}
}
?>
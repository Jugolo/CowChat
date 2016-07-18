<?php
define("NO_CONTEXT", true);
include "index.php";

$json = ["need_table" => []];
function get_table_data($table, array &$data){
	$array = [];
	$array["item"] = get_columen($table, $prime);
	if($prime !== null){
		$array["primary_key"] = $prime;
	}
	
	$array["on_install"] = get_table_context($table);
	
	$data["need_table"][substr($table, strlen(Database::$prefix) + 1)] = $array;
}

function get_table_context($table){
	$buffer = [];
	$query = Database::query("SELECT * FROM `".$table."`");
	while($row = $query->fetch())
		$buffer[] = $row;
	return $buffer;
}

function get_columen($table, &$prime){
	$prime = null;
	$data = [];
	$cache = [];
	$query = Database::query("SHOW COLUMNS FROM `" . $table . "`");
	while($row = $query->fetch()){
		$cache["name"] = $row["Field"];
		if(isset($row["Type"])){
			if(($pos = strpos($row["Type"], "(")) !== false){
				$cache["length"] = intval(substr($row["Type"], $pos+1, -1));
				$cache["type"] = substr($row["Type"], 0, $pos);
			}else{
				$cache["type"] = $row["Type"];
			}
		}
		
		$cache["not_null"] = $row["Null"] == "NO";
		if(isset($row["Key"]) && $row["Key"] === "PRI"){
			$prime = $row["Field"];
		}
		if(isset($row["Extra"]) && $row["Extra"] === "auto_increment"){
			$cache["auto"] = true;
		}
		
		if(isset($row["Default"])){
			$cache["default"] = $row["Default"];
		}
		$data[] = $cache;
		$cache = [];
	}
	return $data;
}

$query = Database::query("SHOW TABLES");
while($row = $query->arrays()){
	if(strpos($row[0], Database::$prefix) === 0){
		get_table_data($row[0], $json);
	}
}
$json["settings"] = [];
$query = Database::query("SELECT `key`, `value` FROM ".table("setting"));
while($row = $query->fetch()){
	$json["settings"][$row["key"]] = $row["value"];
}

exit(json_encode($json));
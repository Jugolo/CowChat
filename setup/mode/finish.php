<?php
define("DEBUG", true);
include "../inc/autoloader.php";
AutoLoader::set_path(dirname(__FILE__, 3) . "\\");
AutoLoader::set();
AutoLoader::loadOnce("inc/func.php");
use inc\database\Database;
use inc\setting\Setting;
use inc\user\User;
use inc\debug\Debug;
use inc\logging\Logging;
use inc\error\HeigLevelError;
function controle_table(array $data){
	$current = [];
	foreach(Database::getInstance()->getTables() as $name){
		if(strpos($name, Database::$prefix) === 0){
			$current[] = substr($name, strlen(Database::$prefix) + 1);
		}
	}
	
	$log = Logging::getInstance("install");
	$log->push("Begin to find table to create");
	create_table(array_diff(array_keys($data["need_table"]), $current), $data); // create missing table
	$log->push("---------");
	$log->push("Begin to find table to delete");
	delete_table(array_diff($current, array_keys($data["need_table"]))); // delete the table. There for do not create table widt the prefix. Use plugin_[prefix]_[name]
	$log->push("---------");
	
	$log->push("Begin to find modfication to tables");
	// run through all the table the chat is using
	foreach(array_keys($data["need_table"]) as $table){
		controle_columns($table, $data);
	}
	$log->push("Finish");
	$log->push("--------");
	$log->push("Controle settings");
	controle_setting($data["settings"]);
	$log->push("Done");
}
function controle_setting(array $settings){
	Setting::init();
	// wee could use setting class but wee also need to get settings wee dont need. There for wee use it semi
	// get all setting and cache them
	$query = Database::getInstance()->query("SELECT `key`, `value` FROM " . table("setting"));
	$cache = [];
	while($row = $query->fetch()){
		$cache[$row["key"]] = $row["value"];
	}
	
	// first wee finde all data wee need to create
	foreach(array_diff(array_keys($settings), array_keys($cache)) as $key){
		Setting::push($key, $settings[$key]);
	}
	
	foreach(array_diff(array_keys($cache), array_keys($settings)) as $key){
		Setting::delete($key);
	}
}
function controle_columns($name, array $data){
	$buffer = [];
	foreach(Database::getInstance()->getColumnsData(Database::$prefix . "_" . $name) as $col){
		$buffer[$col->getName()] = $col;
	}
	
	$names = [];
	$log = Logging::getInstance("install");
	$log->push("Begin to find missing columens");
	// wee foreach the array
	for($i = 0;$i < count($data["need_table"][$name]["item"]);$i++){
		$item = $data["need_table"][$name]["item"][$i];
		if(!in_array($item["name"], array_keys($buffer))){
			create_columns($name, $item["name"], $item);
		}
		$names[] = $item["name"]; // cache this in a array to finde old columnes
	}
	$log->push("----done----");
	$log->push("----Begin to find columens there should be deleted");
	foreach(array_diff(array_keys($buffer), $names) as $col){
		drop_column($name, $col);
	}
	$log->push("----done----");
}
function drop_column($table, $column){
	Database::getInstance()->query(($line = "ALTER TABLE `" . Database::$prefix . "_" . $table . "` DROP COLUMN `" . $column . "`"));
	Logging::getInstance("install")->push($line);
}
function create_columns($table, $name, $data){
	try{
		Database::getInstance()->query(($line = "ALTER TABLE `" . Database::$prefix . "_" . $table . "` ADD " . get_tab_create_item($data, "")));
		Logging::getInstance("install")->push($line);
	}catch(\inc\error\LowLevelError $error){
		$log = Logging::getInstance("install");
		$log->push("Status : fail");
		$log->push("Name   : ".table($table));
		$log->push("Sql    : ".$line);
		$log->push("Message: ".$error->getMessage());
		$log->push("Install exit. please correct the error and try again");
		exit;
	}
}
function delete_table($name){
	if(is_array($name)){
		foreach($name as $tname){
			delete_table($tname);
		}
		return;
	}
	
	Database::query("DROP TABLE `" . Database::$prefix . "_" . $name . "`");
	Logging::getInstance("install")->push("DROP TABLE `" . Database::$prefix . "_" . $name . "`");
}
function create_table($name, array $data){
	if(is_array($name)){
		foreach($name as $tname){
			create_table($tname, $data);
		}
		return;
	}
	
	$log = Logging::getInstance("install");
	
	$sql = "CREATE TABLE `" . Database::$prefix . "_" . $name . "` (\r\n";
	$item = [];
	foreach($data["need_table"][$name]["item"] as $i){
		$item[] = get_tab_create_item($i, array_key_exists("primary_key", $data["need_table"][$name]) ? $data["need_table"][$name]["primary_key"] : null);
	}
	if(($databaase = Database::getInstance())->primary_single() && array_key_exists("primary_key", $data["need_table"][$name])){
		$item[] = "PRIMARY KEY (`" . $data["need_table"][$name]["primary_key"] . "`)";
	}
	$sql .= " " . implode(",\r\n ", $item);
	$sql .= "\r\n) " . $databaase->get_energy();
	$log->push($sql);
	try{
		$databaase->query($sql);
	}catch(\inc\error\LowLevelError $error){
		$log->push("-----status: failed------");
		$log->push("Message: " . $error->getMessage());
		$log->push("Install stopped. find the error and try again");
		exit();
	}
	$log->push("-----status: success-----");
	after_create_table($name, $data);
}
function after_create_table($name, array $data){
	foreach($data["need_table"][$name]["on_install"] as $d){
		Database::insert($name, $d);
	}
}
function get_tab_create_item(array $data, $pramary){
	$not_null = true;
	$sql = "`" . $data["name"] . "` ";
	
	if(array_key_exists("type", $data)){
		$sql .= Database::getInstance()->convert_type_name($data["type"]);
	}
	
	if(array_key_exists("length", $data)){
		if($pramary != $data["name"] && !Database::getInstance()->primary_single() || Database::getInstance()->primary_single()){
			$sql .= "(" . $data["length"] . ")";
		}
	}
	
	if(!Database::getInstance()->primary_single() && $pramary == $data["name"]){
		$sql .= " PRIMARY KEY";
		$not_null = false;
	}
	
	if($not_null && array_key_exists("not_null", $data) && $data["not_null"]){
		$sql .= " NOT NULL";
	}
	
	if(array_key_exists("default", $data)){
		$dont_quete = [
				"CURRENT_TIMESTAMP"
		];
		
		$sql .= " DEFAULT " . (in_array($data["default"], $dont_quete) ? $data["default"] : "'" . $data["default"] . "'");
	}
	
	if(array_key_exists("auto", $data) && $data["auto"]){
		$sql .= " " . Database::getInstance()->auto_increment();
		if(!$not_null && array_key_exists("not_null", $data) && $data["not_null"]){
			$sql .= " NOT NULL";
		}
	}
	return $sql;
}

try{
	Logging::getInstance("install")->push("Start create of database");
	// controle if the zip file exists
	if(!file_exists("setup.json")){
		exit("setup file missing: setup/setup.json");
	}
	controle_table(json_decode(file_get_contents("setup.json"), true));
}catch(HeigLevelError $error){
	echo "Error happens when try to install table<br>\r\n";
	echo "In line: " . $error->getLine() . "<br>\r\n";
	echo "In file: " . $error->getFile() . "<br>\r\n";
	echo "Message: " . $error->getMessage() . "<br>\r\n";
	if($error->isExtrea()){
		echo "Extra message: " . $error->getExtra();
	}
	exit();
}
// controle if wee got user, password and email.
// in update there will not be need for admin and password
if(!empty($_SESSION["username"]) && !empty($_SESSION["password"]) && !empty($_SESSION["email"])){
	// now wee need to update all files. (In this way wee knew the files structure is okay)
	$user_data = inc\user\User::createUser($_SESSION["username"], $_SESSION["password"], $_SESSION["email"]);
	User::get($user_data["nick"])->groupId(2);
	session_destroy();
}
header("location:../index.php?install=done&work=yes&error=no&time_done=" . time());
exit();

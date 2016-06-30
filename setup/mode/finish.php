<?php
include "../include/database.php";
include "../include/setting.php";

$json = json_decode(file_get_contents("../include/config.json"));
if(!Database::init($json->host, $json->user, $json->pass, $json->table, $json->prefix)){
   exit("Fail to connect to database");
}

function controle_table(array $data){
   $query = Database::query("SHOW TABLES");//get all table in the database
   $current = [];
   while($row = $query->arrays()){
      if(strpos($row[0], Database::$prefix) === 0){
         $current[] = substr($row[0], strlen(Database::$prefix)+1);
      }
   }

   create_table(array_diff(array_keys($data["need_table"]), $current), $data);//create missing table
   delete_table(array_diff($current, array_keys($data["need_table"])));//delete the table. There for do not create table widt the prefix. Use plugin_[prefix]_[name]

   //run through all the table the chat is using
   foreach(array_keys($data["need_table"]) as $table){
     controle_columns($table, $data);
   }

   controle_setting($data["settings"]);
}

function controle_setting(array $settings){
   Setting::init();
  //wee could use setting class but wee also need to get settings wee dont need. There for wee use it semi
  //get all setting and cache them
  $query = Database::query("SELECT `key`, `value` FROM ".table("setting"));
  $cache = [];
  while($row = $query->fetch()){
    $cache[$row["key"]] = $row["value"];
  }

  //first wee finde all data wee need to create
  foreach(array_diff(array_keys($settings), array_keys($cache)) as $key){
     Setting::push($key, $settings[$key]);
  }

  foreach(array_diff(array_keys($cache), array_keys($settings)) as $key){
     Setting::delete($key);
  }
}

function controle_columns($name, array $data){
   $query = Database::query("SHOW COLUMNS FROM `".Database::$prefix."_".$name."`");
   $buffer = [];
   while($row = $query->fetch()){
     $buffer[$row["Field"]] = $row;
   }

   $names = [];
   
   //wee foreach the array 
   for($i=0;$i<count($data["need_table"][$name]["item"]);$i++){
   	  $item = $data["need_table"][$name]["item"][$i];
   	  if(!in_array($item["name"], array_keys($buffer))){
   	  	  create_columns($name, $item["name"], $item);
   	  }
   	  $names[] = $item["name"];//cache this in a array to finde old columnes
   }
   
   //wee remove columnen.
   foreach(array_diff($names, array_keys($buffer)) as $col){
      drop_column($name, $col);
   }
}

function drop_column($table, $column){
   Database::query("ALTER TABLE `".Database::$prefix."_".$table."` DROP COLUMN `".$column."`");
}

function create_columns($table, $name, $data){
  Database::query("ALTER TABLE `".Database::$prefix."_".$table."` ADD ".get_tab_create_item($data));
}

function delete_table($name){
   if(is_array($name)){
      foreach($name as $tname){
         delete_table($tname);
      }
      return;
   }
  
   Database::query("DROP TABLE `".Database::$prefix."_".$name."`");
}

function create_table($name, array $data){
   if(is_array($name)){
      foreach($name as $tname){
         create_table($tname, $data);
      }
      return;
   }

   $sql = "CREATE TABLE `".Database::$prefix."_".$name."` (";
   $item = [];
   foreach($data["need_table"][$name]["item"] as $i){
      $item[] = get_tab_create_item($i);
   }
   if(array_key_exists("primary_key", $data["need_table"][$name])){
     $item[] = "PRIMARY KEY (`".$data["need_table"][$name]["primary_key"]."`)";
   }
   $sql .= " ".implode(",\r\n ", $item);
   $sql .= "\r\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
   Database::query($sql);
   after_create_table($name, $data);
}

function after_create_table($name, array $data){
	foreach($data["need_table"][$name]["on_install"] as $d){
		Database::insert($name, $d);
	}
}

function get_tab_create_item(array $data){
  $sql = "`".$data["name"]."` ";
  
  if(array_key_exists("type", $data)){
    $sql .= $data["type"];
  }

  if(array_key_exists("length", $data)){
    $sql .= "(".$data["length"].")";
  }

  if(array_key_exists("not_null", $data) && $data["not_null"]){
    $sql .= " NOT NULL";
  }

  if(array_key_exists("default", $data)){
    $dont_quete = [
       "CURRENT_TIMESTAMP",
    ];

    $sql .= " DEFAULT ".(in_array($data["default"], $dont_quete) ? $data["default"] : "'".$data["default"]."'");
  }

  if(array_key_exists("auto", $data) && $data["auto"]){
    $sql .= " AUTO_INCREMENT";
  }
  return $sql;
}

//controle if the zip file exists
if(!file_exists("setup.json")){
  exit("setup file missing: setup/setup.json");
}

controle_table(json_decode(file_get_contents("setup.json"), true));
//controle if wee got user, password and email.
//in update there will not be need for admin and password
if(!empty($_SESSION["username"]) && !empty($_SESSION["password"]) && !empty($_SESSION["email"])){
  //now wee need to update all files. (In this way wee knew the files structure is okay)
  include "../include/user.php";
  User::createUser($_SESSION["username"], $_SESSION["password"], $_SESSION["email"]);
  session_destroy();
}
header("location:../index.php?install=done&work=yes&error=no&time_done=".time());
exit;

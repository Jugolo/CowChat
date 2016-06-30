<?php
include "../index.php";

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
   foreach($data["need_table"] as $table){
     controle_columns($table, $data);
   }

   controle_setting($data["settings"]);
}

function controle_setting(array $settings){
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

   //wee add missing columns
   foreach(array_diff(array_keys($data["table"][$name]["item"]), array_keys($buffer)) as $col){
      create_columns($name, $col, $buffer[$col]);
   }

   //wee remove columnen.
   foreach(array_diff(array_keys($buffer), array_keys($data["table"][$name]["item"])) as $col){
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
   foreach($data["table"][$name]["item"] as $i){
      $item[] = get_tab_create_item($i);
   }
   if(array_key_exists("primary_key", $data["table"][$name])){
     $item[] = "PRIMARY KEY (`".$data["table"][$name]["primary_key"]."`)";
   }
   $sql .= " ".implode(",\r\n ", $item);
   $sql .= "\r\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
   Database::query($sql);
}

function get_tab_create_item(array $data){
  $sql .= "`".$data["name"]."` ";
  
  if(array_key_exists("type", $data)){
    $sql .= $data["type"];
  }

  if(array_key_exists("length", $data)){
    $sql .= "(".$data["length"].")";
  }

  if(array_key_exists("not_null", $data) && $data["not_null"]){
    $sql .= " NOT NULL";
  }

  if(array_key_exists("deafult", $data)){
    $sql .= "DEFAULT '".$data["deafult"]."'";
  }

  if(array_key_exists("auto", $data) && $data["auto"]){
    $sql .= " AUTO_INCREMENT";
  }
  return $sql;
}

//controle if the zip file exists
if(!file_exists("setup/setup.json")){
  exit("setup file missing: setup/setup.json");
}

controle_table(json_decode(file_get_contents("setup/setup.json"), true));
//now wee need to update all files. (In this way wee knew the files structure is okay)

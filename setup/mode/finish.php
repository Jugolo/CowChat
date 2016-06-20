<?php
include "../index.php";

function zip_dir(){
  return "zip/temp.zip";
}

function zip_get_context(ZipArchive $zip, $name){
   $stream = $zip->getStream($name);
   if(!$stream){
     return null;
   }
   $str = "";
   while(!feof($stream)){
     $str .= fread($stream, 2);
   }
   fclose($stream);
   return $str;
}

function controle_table(array $data){
   $query = Database::query("SHOW TABLES");//get all table in the database
   $current = [];
   while($row = $query->fetch()){
      if(strpos($row[0], Database::$prefix) === 0){
         $current[] = substr($row[0], strlen(Database::$prefix)+1);
      }
   }

   create_table(array_diff($data["need_table"], $current), $data);//create missing table
   delete_table(array_diff($current, $data["need_table"]));//delete the table. There for do not create table widt the prefix. Use plugin_[prefix]_[name]

   //run through all the table the chat is using
   foreach($data["need_table"] as $table){
     controle_columns($table, $data);
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
      create_columns($name, $$col, $buffer[$col]
   }
}

function create_columns($table, $name, $data){
  $end = " ";
  
  if(array_key_exists("length", $data)){
    $end .= "(".$data["length"].")";
  }

  if(array_key_exists("not_null", $data) && $data["not_null"]){
    $end .= " NOT NULL";
  }

  Database::query("ALTER TABLE `".Database::$prefix."_".$table."` ADD `".$name."`".$end);
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
         create_table($tname);
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
  $sql .= "`".$data["name"]."`";
  if(array_key_exists("length", $data)){
    $sql .= "(".$data["length"].")";
  }

  if(array_key_exists("not_null", $data) && $data["not_null"]){
    $sql .= " NOT NULL";
  }
  return $sql;
}

//controle if the zip file exists
if(!file_exists(zip_dir()){
  exit("zip dir missing: setup/".zip_dir());
}

$zip = new ZipArchive();
if(!$zip->open(zip_dir())){
  exit("fail to open zip file: setup/".zip_dir());
}

controle_table(json_decode(zip_get_context($zip, "sql/from_".str_replace(".","_",CHAT_OLD_VERSION).".json"), true);

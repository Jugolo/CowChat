<?php
include "../index.php";

function zip_dir(){
  return "zip/".str_replace(".","_",CHAT_OLD_VERSION).".zip";
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

   create_table(array_diff($data["need_table"], $current), $data);
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
   if(array_key_exists("primary_key", $data["table"])){
     $item[] = "PRIMARY KEY (`".$data["table"]["primary_key"]."`)";
   }
   $sql .= " ".implode("\r\n ", $item);
   $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
   Database::query($sql);
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

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

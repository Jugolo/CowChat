<?php
if(!file_exists("./lib/config.php")){
  exit("Config file missing (./lib/config.php) see ./lib/config-test.txt and rename it to config.php");
}

if(!file_exists("./chat.sql")){
  exit("Missing sql file ('./chat.sql')");
}

$data = include("./lib/config.php");
$mysql = new mysqli(
   $data["db"]["host"],
   $data["db"]["user"],
   $data["db"]["pass"],
   $data["db"]["table"]
);

if($mysql->connect_error){
  exit("Failed to connect to MySQL: ".$mysql->connect_error);
}

function okay(string $msg){
  echo "<div style='color: green'>".$msg."</div>\r\n";
}

function error(string $msg){
  echo "<div style='color: red'>".$msg."</div>\r\n";
}

okay("Connected to MySQL");
$file = file_get_contents("./chat.sql");
$file = str_replace("%prefix%", $data["db"]["prefix"], $file);
$mysql->query($file);
if($mysql->multi_query($file)){
  while($mysql->more_results() && $mysql->next_result()){
      if($result = $mysql->store_result()){
         while($row = $result->fetch_row()){
           printf("%s\n", $row[0]);
         }
         $result->free();
      }
  }
}else{
 exit("MySQL error: ".$mysql->error);
}
if(!@mkdir("./lib/log/")){
  error("Failed to create dir './lib/log/'");
}else{
  okay("Created './lib/log/'");
}

$query = $mysql->query("SELECT * FROM `".$data["db"]["prefix"]."chat_updater`");
$buffer = [];
while($row=$query->fetch_assoc()){
  $buffer[] = $row;
}
define("UPDATER_BUFFER", $buffer);

function isUpdateInsralled(array $data){
  foreach(UPDATE_BUFFER as $buffer){
    if($data["dir"] == $buffer["dir"] && $data["owner"] == $buffer["owner"] && $data["repo"] == $buffer["repo"]){
      return true;
    }
  }
  return false;
}

function handleUpdateFile(string $dir){
  global $mysql, $data;
  foreach(json_decode(file_get_contents($dir."update.json"), true) as $d){
    if(!isUpdateInstalled($d)){
      $result = @$mysql->query("INSERT INTO `".$data["db"]["prefix"]."chat_updater` INSERT (
          `dir`,
          `version`,
          `last_check`,
          `owner`,
          `repo`
        ) VALUES (
          '".$mysql->escape_string($dir)."',
          'V0.0',
          '0',
          '".$mysql->escape_string($d["owner"])."',
          '".$mysql->escape_string($d["repo"])."'
        )");
      if(!$result){
        error("Failed to install update item in ".$dir);
      }
    }
  }
}

function controleUpdater(string $dir){
  $ress = opendir($dir);
  while($name = readdir($ress)){
    if($name == "update.json"){
      handleUpdateFile($dir);
    }elseif($name != "." && $name != ".." && is_dir($dir.$name)){
      controleUpdater($dir.$name."/");
    }
  }
}

controleUpdater("./");

okay("Chat installed. Please remove ./setup.php and ./lib/config-test.txt");

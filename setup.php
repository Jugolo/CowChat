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

if(!@mkdir("./lib/plugin/")){
  error("Failed to create './lib/plugin'");
}else{
  okay("Created './lib/plugin'");
}

okay("Chat installed. Please remove ./setup.php and ./lib/config-test.txt");

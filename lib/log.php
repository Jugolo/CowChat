<?php
//in version 1.1 the log system is creted. 
//it append a system log and a channel log.

function system_log($message){
  if(!Config::get("enable_systemlog")){
    return;
  }
  $fopen = fopen("./lib/log/system.log", "a+");
  $size = fstat($fopen)["size"];
  $dateString = "-----[".date("d/m-Y")."]-----\r\n";
  if($size != 0){
    if(strpos(fread($fopen, $size), $dateString) === false){
      fwrite($fopen, "\r\n".$dateString);
    }else{
      fwrite($fopen, "\r\n");
    }
  }else{
    fwrite($fopen, $dateString);
  }
  fwrite($fopen, "[".date("s:i:H")."]".$message);
  fclose($fopen);
}

<?php
//in version 1.1 the log system is creted. 
//it append a system log and a channel log.

function system_log($message){
  $fopen = fopen("./lib/log/system.log", "a+");
  $size = fstat($fopen)["size"];
  $dateString = "-----[".date("d/m-Y")."]-----";
  if($size != 0){
    if(strpos(fread($fopen, $size), $dateString) === false){
      fwrite($fopen, "\r\n".$dateString);
    }
  }
  fwrite($fopen, ($size == 0 ? "" : "\r\n")."[".date("s:i:H")."]".$message);
  fclose($fopen);
}

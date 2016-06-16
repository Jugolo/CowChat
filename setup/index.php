<?php
define("NO_CONTEXT", true);//wee do not wich to show context (will probably show login page)
define("IN_SETUP", true);//tell the chat wee are in setup mode
define("CHAT_PATH", "../".dirname(__FILE__) . '\\');//set the chat root dir so the system dont crach
//controle if the config.json exists
if(file_exists("../include/config.json")){
   include "mode/finish.php";
}else{
   include "mode/begin.php";
}

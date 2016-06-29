<?php
define("NO_CONTEXT", true);//wee do not wich to show context (will probably show login page)
define("IN_SETUP", true);//tell the chat wee are in setup mode
//controle if the config.json exists
if(file_exists("../include/config.json")){
   include "mode/finish.php";
}else{
   include "mode/begin.php";
}

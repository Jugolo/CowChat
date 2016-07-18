<?php
define("DEBUG", true);
use inc\system\System;

define("CHAT_VERSION", "V0.2B1");
error_reporting(E_ALL);
ini_set('display_errors', '1');

include "inc/autoloader.php";
AutoLoader::set_path(dirname(__FILE__));
AutoLoader::set();
AutoLoader::loadOnce("inc/func.php");

$system = new System();
$system->inilize();

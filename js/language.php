<?php
header('Content-Type: application/javascript');
header("Expires: Mon, 26 Jul 12012 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "../inc/autoloader.php";
AutoLoader::set_path(dirname(__FILE__, 2));
AutoLoader::set();

use inc\user\User;
use inc\language\Language;

if(User::current() == null){
	echo "alert('" . Language::get("You need to be login to get context for this file") . "');";
	exit();
}

echo "var languageArray = {";
if(Language::getLanguageName() != null){
	if(Dirs::isDir("include/temp/js/language/" . Language::getLanguageName() . ".tmp")){
		echo Files::context("include/temp/js/language/" . Language::getLanguageName() . ".temp");
	}
}
echo "};";
?>

function language(){
  var lang_arg = [];
  
  if(typeof languageArray[arguments[0]] !== "undefined"){
    lang_arg.push(languageArray[arguments[0]]);
  }else{
    lang_arg.push(arguments[0]);
  }
  
  if(arguments.length > 1){
     for(var i=1;i < arguments.length;i++){
       lang_arg.push(arguments[i]);
     }
  }
  
  return sprintf.apply(this,lang_arg);
}
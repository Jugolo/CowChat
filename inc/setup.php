<?php
namespace inc\setup;

use inc\file\Files;
use inc\system\System;

class Setup{
  private $data;

  public function __construct(){
    if(System::is_cli()){
      Console::writeLine("A setup dir is exists. Please delete it so other not can use it!");
    }

    $this->data = json_decode(Files::context("setup/info.json"), true);

    if(Files::exists("inc/config.json")){
      return;
    }
    header("location:setup/index.php");
    exit;
  }
  
  private function version(){
  	return $this->data["version"];
  }
}

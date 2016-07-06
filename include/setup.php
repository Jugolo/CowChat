<?php

class Setup{
  private $data;

  public function __construct(){
    if(Server::is_cli()){
      Console::writeLine("A setup dir is exists. Please delete it so other not can use it!");
    }

    $this->data = json_decode(Files::context("setup/info.json"), true);

    if(Files::exists("include/config.json")){
      return;
    }
    header("location:setup/index.php");
    exit;
  }
  
  private function version(){
  	return $this->data["version"];
  }
}

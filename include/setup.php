<?php

class Setup{
  private $data;

  public function __construct(){
    if(Server::is_cli()){
      exit("You can not start the chat server when the setup dir is online.\r\nPlease delte it or rename it (wee not recommend to rename)");
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

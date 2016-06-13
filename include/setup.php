<?php

class Setup{
  private $data;

  public function __construct(){
    if(Server::is_cli()){
      exit("You can not start the chat server when the setup dir is online.\r\nPlease delte it or rename it (wee not recommend to rename)");
    }

    $this->data = json_decode(Files::context("setup/info.json"), true);

    if(!$this->is_new()){
       if(Files::exists("include/config.json")){
         exit("You need to delete the setup dir to let the chat work");
       }
    }
    header("location:setup/index.php");
    exit;
  }

  private function is_new(){
    return version_compare(CHAT_VERSION, $this->version(), ">");
  }
  
  private function version(){
  	return $this->data["version"];
  }
}

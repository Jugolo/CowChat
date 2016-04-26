<?php
class MessageParser{
  private $data = [];

  function __construct($message){
     $first = explode(" ", substr($message, 0, strpos($message, ": ")));
     if(strpos($first[0], "!")){
        list($prefix, $command) = explode("!", $first[0]);
        $this->data["prefix"] = $prefix;
        $first[0] = $command;
     }
     $this->data["command"] = $first[0];
     $this->data["isCommand"] = count($first) == 1;
     $this->data["channel"] = !$this->isCommand() ? Channel::get($first[1]) : null;
     $this->data["message"] = substr($message, strpos($message, ": ")+2);
  }

  function isCommand(){
     return $this->data["isCommand"];
  }

  function command(){
      return $this->data["command"];
  }

  function channel(){
     return $this->data["channel"];
  }

  function message(){
     return $this->data["message"];
  }

  function hasPrefix(){
     return !empty($this->data["prefix"]);
  }

  function prefix(){
     return $this->data["prefix"];
  }
}

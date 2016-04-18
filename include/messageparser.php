<?php
class MessageParser{
  private $data = [];

  function __construct($message){
     $first = explode(" ", substr($message, 0, strpos($message, ": ")));
     $this->data["command"] = $first[0];
     $this->data["isCommand"] = count($first) == 0;
     $this->data["channel"] = $this->isCommand() ? get_channel($first[1]) : null;
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
}

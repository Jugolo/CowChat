<?php
class MessageParser{
  private $data = [];

  function __construct($message){
     $first = explode(" ", substr($message, 0, strpos($message, ": ")));
     $this->data["command"] = $first[0];
     $this->data["isCommand"] = count($first) == 0;
  }
}

<?php
namespace Lib;

class Mailer{
  private $args = [];
  private $tempelate;
  
  public function selectTempelate(string $tempelate) : bool{
    if(!file_exists($tempelate)){
      return false;
    }
    $this->tempelate = $tempelate;
    return true;
  }
  
  public function setArg(string $key, string $value){
    $this->args[$key] = $value;
  }
}

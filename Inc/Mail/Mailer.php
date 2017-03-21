<?php
namespace Lib\Mail;

use Language;

class Mailer{
  private $args = [];
  private $tempelate;
  
  public function selectTempelate(string $tempelate) : bool{
    $tempelate = $this->getTempelateDir($tempelate);
    if(!file_exists($tempelate)){
      return false;
    }
    $this->tempelate = $tempelate;
    return true;
  }
  
  public function setArg(string $key, string $value){
    $this->args[$key] = $value;
  }
  
  public function send(string $email){
    $parser = new MailTempelate($this->tempelate ? : "");
    mail($email, $this->convertString($parser->subject()), $this->convertString($parser->message()), $parser->headers());
  }
  
  private function getTempelateDir(string $file) : string{
    return "locale/".Language::getCode()."/mail/".$file.".mail";
  }
  
  private function convertString(string $str) : string{
    $args = $this->args;
    $line = preg_replace_callback("/\[\[([a-zA-Z]\]\]/", function($all, $arg) use($args){
      if(!empty($args[$arg])){
        return $args[$arg];
      }
      return $all;
    }, $line);
  }
}

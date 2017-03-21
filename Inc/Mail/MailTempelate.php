<?php
namespace Inc\Mail;

class MailTempelate{
  private $header = [];
  private $subject = "Please do not replay to this mail";
  private $message = "";
  
  public function __construct(string $file){
     $fopen = fopen($file, "r");
    while($line = fgets($fopen)){
      if(trim($line) == ""){
        break;
      }
      
      $pos = strpos($line, ": ");
      if($pos == -1){
        continue;
      }
      $key = substr($line, 0, $pos);
      if($key == "Subject"){
        $this->subject = substr($line, $pos+2);
      }else{
        $this->header[$key] = substr($line, $pos+2);
      }
    }
    while($char = fread($fopen, 1))
      $this->message .= $char;
  }
}

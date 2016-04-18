<?php
function send(MessageParser $parser, $message){
   if($parser->hasPrefix()){
     $message = $parser->prefix()."!".$message;
   }

   echo $message."\r\n";
}

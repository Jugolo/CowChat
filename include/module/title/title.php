<?php
function title_command(MessageParser $msg){
   if(User::current()->isMember($msg->channel())){
      if($msg->message() == ""){
         title($msg, $msg->channel()->title(), false);
      }else{
         $msg->channel()->title($msg->message());
         title($msg, $msg->channel()->title(), true);
      }
   }else{
      error($msg, "You are not member of the channel");
   }
}

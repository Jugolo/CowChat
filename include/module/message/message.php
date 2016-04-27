<?php
function message_command(MessageParser $message){
   if($message->channel() != null){

   }else{
     if(substr($channel->channelName(), 0, 1) == "#"){
       //controle if wee got a user width that name
       if(($user = User::get($channel->channelName())) != null){
         //insert into pm table so the user can se it 
         Database::insert("pm", [
           "from" => User::current()->id(),
           "to"   => $user->id(),
           "msg"  => $message->message()
         ]);
       }else{
         error($message, "Unknown user");
       }
     }else{
       error($message, "Unknown channel");
     }
   }
}

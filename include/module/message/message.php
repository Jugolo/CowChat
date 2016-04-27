<?php
function message_command(MessageParser $message){
   if(User::current()->isMember($message->channel())){

   }else{
     //the user is not member of this channel so wee look after if this is a channel or not
     if(substr($message->channelName(), 0, 1) == "#"){
       error($message, "You are not member of the channel");
     }else{
       //private channel do not be createt width join command but will be createt here :)
       if(($user = User::get($message->channelName())) != null){
          //okay wee has a private message to anthor user. If this is a websocket chat only it is easy. But this should also work
          //width ajax so wee need to crate a name there is easy for the system to finde out how to work. 
       }else{
          error($message, "Unknown user");
       }
     }
   }
}

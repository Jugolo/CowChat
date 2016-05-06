<?php
function info_command(MessageParser $parser){
    if(User::current()->isMember($parser->channel())){
       //wee controle if the user is member of the channel
       if(($user = User::get($parser->message())) != null){

       }else{
          send($parser, "Unknown user");
       }
    }else{
       error($parser, "You are not member of the channel);
    }
}

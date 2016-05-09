<?php
function info_command(MessageParser $parser){
    if($parser->channel()->isMember(User::current())){
       //wee controle if the user is member of the channel
       if(($user = User::get($parser->message())) != null){
           //wee controle the user is member of the channel
           if($parser->channel()->isMember($user)){
              //okay wee send the user data to the user
              send($parser, "INFO ".$parser->channel()->name().": ".$parser->channel()->getMember($user)->group());
           }else{
              error($parser, "The user is not member of the channel");
           }
       }else{
          error($parser, "Unknown user");
       }
    }else{
       error($parser, "You are not member of the channel");
    }
}

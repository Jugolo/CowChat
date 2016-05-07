<?php
function info_command(MessageParser $parser){
    if(User::current()->isMember($parser->channel())){
       //wee controle if the user is member of the channel
       if(($user = User::get($parser->message())) != null){
           //wee controle the user is member of the channel
           if($user->isMember($parser->channel())){
              //okay wee send the user data to the user
              send($parser, "INFO ".$parser->channel()->name().": ".$parser->channel()->getMember($user)->group());
           }else{
              error($parser, "The user is not member of the channel");
           }
       }else{
          error($parser, "Unknown user");
       }
    }else{
       error($parser, "You are not member of the channel);
    }
}

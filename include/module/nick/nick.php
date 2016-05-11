<?php

function nick_command(MessageParser $msg){
    if(controleNick($msg->message(), User::current()){
       //okay the nick is free 
       User::current()->renderChannels(function(ChannelData $data) use($msg){
           $data->send("NICK ".$data->name().": ".$msg->message());
       });

       User::current()->nick($msg->message());
    }else{
       error($msg, "Nick is taken");
    }
}

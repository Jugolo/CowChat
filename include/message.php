<?php
function send(MessageParser $parser, $message){
   if($parser->hasPrefix()){
     $message = $parser->prefix()."!".$message;
   }

   echo $message."\r\n";
}

function send_channel(ChannelData $channel, UserData $user, $message){
    if(!$user->isMember($channel)){
       if($user == User::current()){
         send(new MessageParser(), "ERROR: notMember");
       }
       return false;
    }
    Database::insert("message",[
        'uid'  => $user->id(),
        'cid'  => $channel->id(),
        'msg'  => $message,
        'nick' => $user->nick()//if this is a geaust the user will be deletede so there for wee use this as a backup
    ]);

    if(Server::is_cli()){
      WebSocket::send($channel, $message);
    }
    return true;
}

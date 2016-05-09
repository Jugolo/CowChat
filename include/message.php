<?php
function send(MessageParser $parser, $message){
   if($parser->hasPrefix()){
     $message = $parser->prefix()."!".$message;
   }

   echo $message."\r\n";
}

function send_channel(ChannelData $channel, UserData $user, $message){
    if(!$channel->isMember($user)){
       if($user == User::current()){
         send(new MessageParser(), "ERROR: notMember");
       }
       return false;
    }
    Database::insert("message",[
        'uid'            => $user->id(),
        'cid'            => $channel->id(),
        'message_qlean'  => $message,
		'message'        => $message,
    ]);

    if(Server::is_cli()){
      WebSocket::send($channel, $message);
    }
    return true;
}

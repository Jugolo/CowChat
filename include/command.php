<?php
function error(MessageParser $parser, $message){
	send($parser, "ERROR: ".$message);
}

function inaktiv(MessageParser $parser, $channel, UserData $user){
	if(!is_array($inaktiv)){
		$inaktv = [$inaktv];
	}
	
	foreach($inaktv as $in){
	  if($globel){
		 Channel::get($channel)->send("INAKTIV ".$channel.": YES", User::current());
	  }else{
	  	send($parser, $message))
	  }
	}
}

function title(MessageParser $parser, $channel, $title, $globel){
        if($globel)
           Channel::get($channel)->send("TITLE ".$channel.": ".$title);
        else
	   send($parser, "TITLE ".$channel.": ".$title);
}

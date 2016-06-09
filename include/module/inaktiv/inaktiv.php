<?php
function inaktiv_command(MessageParser $parser){
	if($parser->channel()->isMember(User::current())){
		send($parser, "INAKTIV " . $parser->channel()->name() . ": " . ($parser->channel()->getMember(User::current())->isInaktiv() ? "YES" : "NO"));
	}else{
		error($parser, "You are not member of the channel");
	}
}
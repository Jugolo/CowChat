<?php
function error(MessageParser $parser, $message){
	send($parser, "ERROR: " . $message);
}
function title(MessageParser $parser, $channel, $title, $globel){
	if($globel)
		Channel::get($channel)->send("TITLE " . $channel . ": " . $title);
	else
		send($parser, "TITLE " . $channel . ": " . $title);
}

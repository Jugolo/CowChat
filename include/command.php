<?php
function error(MessageParser $parser, $message){
	send($parser, "ERROR: ".$message);
}

function title(MessageParser $parser, $channel, $title){
	send($parser, "TITLE ".$channel.": ".$title);
}
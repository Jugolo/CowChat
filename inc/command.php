<?php
namespace inc\command;

use inc\messageparser\MessageParser;
use inc\message\Message;
use inc\channel\channel\Channel;
use inc\user\data\UserData;

class Command{
	static function error($parser, $message){
		Message::send($parser, "ERROR: " . $message);
	}
	/**
	 * Send title to channel or user. 
	 * @param MessageParser $parser the message parser
	 * @param string $channelName the name of the channel
	 * @param string $title the title
	 * @param bool $globel true on all in the channel or false if it only to the user
	 */
	static function title(MessageParser $parser, string $channelName, string $title, bool $globel){
		if($globel)
			Channel::get($channel)->send("TITLE " . $channelName . ": " . $title);
		else
			Message::send($parser, "TITLE " . $channelName . ": " . $title);
	}
	
	static function join(Channel $channel, UserData $data){
		$channel->send($data, "JOIN ".$channel->getName().": ".$data->getUsername());
	}
}

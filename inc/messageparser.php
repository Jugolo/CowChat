<?php
namespace inc\messageparser;

use inc\channel\Channel;

class MessageParser{
	private $data = [];
	function __construct($message){
		$first = explode(" ", substr($message, 0, strpos($message, ": ")));
		if(strpos($first[0], "!")){
			list($prefix, $command) = explode("!", $first[0]);
			$this->data["prefix"] = $prefix;
			$first[0] = $command;
		}
		$this->data["command"] = $first[0];
		$this->data["isCommand"] = count($first) == 1;
		$this->data["channel"] = !$this->isCommand() ? Channel::get(($this->data["channelName"] = $first[1])) : null;
		$this->data["message"] = substr($message, strpos($message, ": ") + 2);
	}
	function isCommand(){
		return $this->data["isCommand"];
	}
	function command(){
		return $this->data["command"];
	}
	
	/**
	 * Get the channel data object
	 * 
	 * @return ChannelData channel data object
	 */
	function channel(){
		return $this->data["channel"];
	}
	function channelName(){
		return $this->data["channelName"];
	}
	function message(){
		return $this->data["message"];
	}
	function encode(){
		// this method encode the message so it can be sendt secure to the user (it not standart becuse it can be error in some module)
		$this->data["message"] = htmlentities($this->data["message"]);
	}
	function hasPrefix(){
		return !empty($this->data["prefix"]);
	}
	function prefix(){
		return $this->data["prefix"];
	}
}

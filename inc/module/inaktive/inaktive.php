<?php
use inc\messageparser\MessageParser;
function inaktive_command(MessageParser $parser){
	if($parser->channel()->isMember(User::current())){
		// get all member
		$array = [];
		foreach($parser->channel()->getMembers() as $member){
			if($member->isInaktiv()){
				$array[] = $member->getUser()->nick();
			}
		}
	}else{
		error($parser, "You are not member of the channel");
	}
}
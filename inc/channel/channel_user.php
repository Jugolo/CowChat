<?php

namespace inc\channel\channel_user;

use inc\channel\channel\Channel;
use inc\channel\helper\ChannelHelper;
use inc\command\Command;
use inc\database\Database;
use inc\user\data\UserData;
use inc\head\Head;

class ChannelUser{
	public static function join(UserData $user, Channel $channel, $parser): bool{
		// controle if the user is member of the channel
		if(ChannelHelper::isMember($channel, $user)){
			if(Head::get("ajax")){
				Command::error($parser, "You can`t join a channel you are member of");
			}
			return false;
		}
		
		Database::insert("channel_member", [
				"cid" => $channel->getId(),
				"uid" => $user->getId(),
				"gid" => $channel->getStandartGroupId(),
				"active" => time()
		]);
		
		// send command to all members in the channel and tell there is a new member of the channel
		Command::join($channel, $user);
		return true;
	}
}
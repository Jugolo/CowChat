<?php

namespace inc\channel\helper;

use inc\database\Database;
use inc\user\data\UserData;
use inc\channel\channel\Channel;
use inc\user\User;

class ChannelHelper{
	public static function getUsersChannel(UserData $user): array{
		$query = Database::getInstance()->query("SELECT `cid` FROM " . table("channel_member") . " WHERE `uid`='" . $user->getUserId() . "'");
		$buffer = [];
		while($row = $query->fetch()){
			$buffer[] = new Channel($row["cid"]);
		}
		$query->free();
		return $buffer;
	}
	public static function getChannel(string $name, UserData $user): Channel{
		$query = ($database = Database::getInstance())->query("SELECT `id` FROM " . table("channel") . " WHERE `name`=" . $database->clean($name));
		$row = $query->fetch();
		$query->free();
		if(!$row){
			$channel = new Channel(Database::insert("channel", [
					"name" => $name,
					"title" => $name,
					"creater" => $user->getUserId(),
					"start_group" => 0
			]));
			
			self::createUserGroups($channel);
			return $channel;
		}else{
			return new Channel($row["id"]);
		}
	}
	public static function isMember(Channel $channel, UserData $user): bool{
		$query = Database::getInstance()->query("SELECT `cid` FROM " . table("channel_member") . " WHERE `cid`='" . $channel->getId() . "' AND `uid`='" . $user->getUserId() . "'");
		$row = $query->fetch();
		$query->free();
		return is_array($row);
	}
	private static function createUserGroups(Channel $channel){
		$access = [
				[
						"name" => "User",
						"cid" => $channel->getId(),
						"changeTitle" => "N",
						"ignoreFlood" => "N",
						"kick" => "N"
				],
				[
						"name" => "Moderater",
						"cid" => $channel->getId(),
						"changeTitle" => "Y",
						"ignoreFlood" => "N",
						"kick" => "Y"
				],
				[
						"name" => "Admin",
						"cid" => $channel->getId(),
						"changeTitle" => "Y",
						"ignoreFlood" => "Y",
						"kick" => "Y"
				]
		];
		
		for($i=0;$i<count($access);$i++){
			$buffer = Database::insert("channel_group", $access[$i]);
			if($i==0){
				$channel->setStandartGroupId($buffer);
			}
		}
	}
}
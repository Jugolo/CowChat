<?php
function controleChannelName($name){
	// at start of channel name there must be a #
	if(strpos($name, "#") !== 0){
		return false;
	}
	
	// controle length
	if(strlen($name) < 3 || strlen($name) > 10){
		return false;
	}
	
	// wee could use regex to find out wich char there is in but no.
	for($i = 1;$i < strlen($name);$i++){
		if(($char = ord($name[$i])) < 65 || $char > 90 && $char < 97 || $char > 122){
			return false;
		}
	}
	
	return true;
}
class Channel{
	private static $channels = [];
	public static function init(){
		// get all channels from the database :)
		$query = Database::query("SELECT * FROM " . table("channel"));
		while($row = $query->fetch()){
			self::$channels[$row['id']] = new ChannelData($row);
		}
	}
	public static function remove(ChannelData $channel){
		$channel->remove();
		unset(self::$channels[$channel->id()]);
		Database::query("DELETE FROM " . table("channel") . " WHERE `id`='" . $channel->id() . "'");
		Database::query("DELETE FROM " . table("channel_group") . " WHERE `cid`='" . $channel->id() . "'");
		Database::query("DELETE FROM " . table("flood") . " WHERE `cid`='".$channel->id()."'");//clean up in flood table.
	}
	public static function getUserChannel(UserData $user){
		$return = [];
		foreach(self::$channels as $channel){
			if($channel->isMember($user)){
				$return[$channel->id()] = $channel;
			}
		}
		return $return;
	}
	public static function get($name){
		foreach(self::$channels as $channel){
			if($channel->name() == $name){
				return $channel;
			}
		}
		
		return null;
	}
	public static function join($name, UserData $user, MessageParser $message = null){
		$channel = null;
		if(($channel = self::get($name)) == null){
			list($group, $channel) = self::create($name, $user);
		}else{
			$query = Database::query("SELECT `id` FROM " . table("channel_group") . " WHERE `standart`='Y' AND `cid`='" . $channel->id() . "'");
			if($query->rows() != 1){
				error($message == null ? new MessageParser("JOIN: unknown") : $message, "Could not finde a group for you");
				return false;
			}
			$row = $query->fetch();
			$group = $row["id"];
		}
		
		if($channel->isMember($user)){
			if($message){
				error($message, "You are allready member of the channel");
			}
			return false;
		}
		
		$data["id"] = Database::insert("channel_member", [
				'cid' => $channel->id(),
				'uid' => $user->id(),
				'gid' => $group,
				'active' => time()
		]);
		
		$user->pushChannel($channel);
		$channel->join(User::current());
		if($message != null){
			send($message, "JOIN: " . $channel->name());
		}
		
		$channel->send("JOIN: " . $channel->name(), User::current());
		
		return true;
	}
	public static function garbage_collect(){
		foreach(self::$channels as $channel){
			$channel->garbage_collect();
		}
	}
	private static function create($name, UserData $user){
		$data = [
				"name" => $name,
				"title" => $name,
				"creater" => $user->id()
		];
		
		$data["id"] = Database::insert("channel", $data);
		
		$admin = Database::insert("channel_group", [
				"name" => "Admin",
				"cid" => $data["id"],
				"standart" => "N",
				"changeTitle" => "Y",
				"ignoreFlood" => "Y",
				"kick" => "Y"
		]);
		Database::insert("channel_group", [
				"name" => "Moderater",
				"cid" => $data["id"],
				"standart" => "N",
				"changeTitle" => "N",
				"ignoreFlood" => "N",
				"kick" => "Y"
		]);
		$users = Database::insert("channel_group", [
				"name" => "User",
				"cid" => $data["id"],
				"standart" => "Y",
				"changeTitle" => "N",
				"ignoreFlood" => "N",
				"kick" => "N"
		]);
		
		return [
				($user->isGeaust() ? $users : $admin),
				(self::$channels[$data["id"]] = new ChannelData($data))
		];
	}
}
class ChannelData{
	private $data;
	private $members = [];
	function __construct($data){
		$this->data = $data;
		$sql = Database::query("SELECT `uid` FROM " . table("channel_member") . " WHERE `cid`='" . $this->id() . "'");
		while($row = $sql->fetch()){
			if(($user = User::get($row["uid"])) != null){ // geaust is delted efter no member og channels so control it befor wee add it in cache
				$this->members[$user->id()] = new ChannelMember($user, $this);
			}
		}
	}
	function id(){
		return $this->data["id"];
	}
	function name(){
		return $this->data["name"];
	}
	function title($new = null){
		if($new != null){
			// wee append a title here
			Database::query("UPDATE " . table("channel") . " SET `title`=" . Database::qlean($new) . " WHERE `id`='" . $this->id() . "'");
			$this->data["title"] = $new;
		}
		return $this->data["title"];
	}
	function creater(){
		return $this->data['creater'];
	}
	
	/**
	 * Get ChannelMember array
	 * 
	 * @return array ChannelMember itam
	 */
	function getMembers(){
		return $this->members;
	}
	
	/**
	 * Get the member data for the user in this channel
	 * 
	 * @param UserData $user
	 *        	user you want to find
	 * @return ChannelMember or null on fail
	 */
	function getMember(UserData $user){
		if(empty($this->members[$user->id()])){
			return null;
		}
		
		return $this->members[$user->id()];
	}
	function leave(UserData $user, $sendMessage = "Leave the channel"){
		if($this->isMember($user)){
			// exit($sendMessage);
			// wee delete the user in the channel
			Database::query("DELETE FROM " . table("channel_member") . " WHERE `cid`='" . $this->id() . "' AND `uid`='" . $user->id() . "'");
			unset($this->members[$user->id()]);
			if(count($this->members) == 0){
				Channel::remove($this);
			}else{
				if($sendMessage){
					$this->send("LEAVE " . $this->name() . ": " . $sendMessage, $user);
				}
			}
			
			if(Server::is_cli()){
				$isOnline = $user->websocket() != null;
			}else{
				$isOnline = $user->countUpdatet() > time()-300;
			}
				
			if($isOnline){
				send_user($user, $user->nick()."@LEAVE " . $this->name() . ": " . ($sendMessage ? $sendMessage : "Unknown whey"));
			}
		}
	}
	function isMember(UserData $user){
		return !empty($this->members[$user->id()]);
	}
	function join(UserData $user){
		$this->members[$user->id()] = new ChannelMember($user, $this);
		return true;
	}
	function send($message, UserData $user = null){
		if($user == null){
			$user = User::current();
		}
		return send_channel($this, $user, $user->nick() . "@" . $message);
	}
	function updateActive(UserData $user){
		if(!empty($member = $this->members[$user->id()])){
			$member->updateActive();
		}
	}
	function remove(){
		if(count($this->members) != 0){
			foreach($this->members as $member){
				$member->getUser()->leave($this);
			}
		}
		
		// wee also clean up in message so no message is saving from this channel :)
		Database::query("DELETE FROM " . table("message") . " WHERE `cid`='" . $this->id() . "'");
	}
	function garbage_collect(){
		// wee look after how soon is the user typed a message if more end 5 min and not more end 10 min the user is marked inaktiv. if the user has not writet more end 10 min the user is kicked out of the channel and the user need to join again :)
		foreach($this->members as $member){
			if($member->writeTime() <= time() - (60 * 5) && $member->writeTime() >= time() - (60 * 15) && !$member->isInaktiv()){
				$member->markInaktiv();
			}elseif($member->writeTime() <= time() - (60 * 15)){
				// the user need to be delteded form the channel :)
				$this->leave($member->getUser(), "Inaktiv to long time now");
			}
		}
	}
}
class ChannelMember{
	private $user;
	private $channel;
	private $data;
	private $groups;
	public function __construct(UserData $user, ChannelData $data){
		$this->user = $user;
		$this->user->pushChannel($data);
		$this->data = Database::query("SELECT * FROM " . table("channel_member") . " WHERE `cid`='" . $data->id() . "' AND `uid`='" . $user->id() . "'")->fetch();
		$this->channel = $data;
		$this->groups = new ChannelGroup($this);
	}
	public function group(){
		return $this->groups;
	}
	public function groupId(){
		return $this->data["gid"];
	}
	public function getUser(){
		return $this->user;
	}
	public function writeTime(){
		return $this->data['active'];
	}
	public function isInaktiv(){
		return $this->data["isInaktiv"] == "Y";
	}
	public function updateActive(){
		$append = "";
		$this->data["active"] = time();
		if($this->isInaktiv()){
			$append = ", `isInaktiv`='N'";
			$this->data["isInaktiv"] = "N";
			$this->channel->send("INAKTIV " . $this->channel->name() . ": NO", $this->user);
		}
		
		Database::query("UPDATE " . table("channel_member") . " SET `active`='" . time() . "'" . $append . " WHERE `id`='" . $this->data["id"] . "'");
	}
	public function markInaktiv(){
		$this->channel->send("INAKTIV " . $this->channel->name() . ": YES", $this->user);
		$this->data["isInaktiv"] = "Y";
		Database::query("UPDATE " . table("channel_member") . " SET `isInaktiv`='Y' WHERE `cid`='" . $this->channel->id() . "' AND `uid`='" . $this->user->id() . "'");
	}
}
class ChannelGroup{
	private $data;
	function __construct(ChannelMember $member){
		// wee get the information for the group :)
		$this->data = Database::query("SELECT * FROM " . table("channel_group") . " WHERE `id`='" . $member->groupId() . "'")->fetch();
	}
	function allowChangeTitle(){
		return $this->data["changeTitle"] == "Y";
	}
	function allowIgnoreFlood(){
		return $this->data["ignoreFlood"] == "Y";
	}
	function allowKick(){
		return $this->data["kick"] == "Y";
	}
	
	// get all data in a single string
	public function __toString(){
		$data = $this->data;
		unset($data["cid"]);
		$return = [];
		foreach($data as $key => $value){
			$return[] = $key . "=" . $value;
		}
		
		return implode(";", $return);
	}
}
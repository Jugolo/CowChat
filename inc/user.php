<?php

namespace inc\user;

use inc\user\helper\UserHelper;
use inc\error\HeigLevelError;
use inc\user\stack\UserStack;

class User{
	private static $stack;
	private static $has_init = false;
	/**
	 * Get the helper to user handling
	 * @return UserHelper
	 */
	public static function helpers() : UserHelper{
		return new UserHelper();
	}
	
	public static function getStack() : UserStack{
		return self::$stack;
	}
	
	public static function init(){
		if(self::$has_init){
			throw new HeigLevelError("Class User is init allready");
		}
		
		self::$has_init = true;
		self::$stack = new UserStack();
	}
}

User::init();

/*
function generate_hash(){
	$chars = "qwertyuioplkjhgfdsazxcvbnmøåQWERTYUIOPASDFGHJKLZXCVBNMÆØÅ,.-_:;'¨^*<>½§1234567890+´!\"#¤%&/()=?`";
	$return = "";
	for($i = 0;$i < 99999;$i++){
		$return .= $chars[mt_rand(0, mt_rand(0, strlen($chars) - 1))];
	}
	
	return sha1($return);
}
function get_user_group($name){
	$query = ($database = Database::getInstance())->query("SELECT `id` FROM " . table("user_group") . " WHERE `name`=" . $database->clean($name));
	if($query->rows() == 0){
		return null;
	}
	$row = $query->fetch();
	return new UserGroup($row["id"]);
}
class User{
	private static $users = [];
	private static $current = null;
	public static function createGaust($nick){
		$data = [
				"nick" => $nick,
				"hash" => generate_hash(),
				"groupId" => Setting::get("startGroup"),
				"type" => "g",
				"ip" => \ip(),
				"message_id" => System::getLastId(),
				"active" => time(),
				"defenderCount" => 0.5,
				"countUpdatet" => time(),
				"lastMessage" => time()
		];
		
		$data["id"] = Database::insert("user", $data);
		return $data;
	}
	public static function createUser($nick, $password, $email){
		$hash = generate_hash();
		$time = time();
		$data = [
				"username" => $nick,
				"nick" => $nick,
				"password" => hash_password($password, $hash, $time),
				"hash" => $hash,
				"groupId" => Setting::get("startGroup"),
				"message_id" => defined("IN_SETUP") ? 0 : System::getLastId(),
				"type" => "u",
				"ip" => ip(),
				"active" => $time,
				"defenderCount" => 0.5,
				"countUpdatet" => $time,
				"lastMessage" => $time
		];
		
		$data["id"] = Database::insert("user", $data);
		return $data;
	}
	/**
	 * Get UserData object for the current user
	 * 
	 * @param UserData $current
	 *        	push current user to cache
	 * @return UserData current user data object
	 
	public static function current(UserData $current = null){
		if($current != null){
			$current->updateCount();
			self::$current = $current;
		}
		
		return self::$current;
	}
	public static function remove(UserData $user){
		if(!empty(self::$users[$user->id()])){
			unset(self::$users[$user->id()]);
		}
	}
	public static function get($uid) : UserData{
		if(is_numeric($uid)){
			$field = "id";
		}else{
			$field = "nick";
		}
		
		$query = ($database = Database::getInstance())->query("SELECT * FROM " . table("user") . " WHERE `" . $field . "`=" . $database->clean($uid));
		$r = $query->fetch();
		if($r == null){
			throw new LowLevelError("Could find user", $uid);
		}
		if(!empty(self::$users[$r["id"]])){
			return self::$users[$r["id"]];
		}
		return self::$users[$r["id"]] = new UserData($r);
	}
	public static function garbage_collector(){
		// run all user thrue and finde the geaust
		$query = Database::getInstance()->query("SELECT `id` FROM " . table("user") . " WHERE `type`='g' AND `countUpdatet`<'" . (time() - (60 * 30)) . "'");
		while($row = $query->fetch()){
			if($user = User::get($row['id'])){
				$user->delete();
				unset(self::$users[$user->id()]);
			}
		}
	}
}
class UserData{
	private $channels = [];
	private $data = [];
	private $ignore = [];
	private $group = null;
	private $_websocket = null;
	function __construct(array $data){
		$this->data = $data;
		$query = Database::getInstance()->query("SELECT `iid` FROM " . table("ignore") . " WHERE `uid`='" . $this->id() . "'");
		while($row = $query->fetch()){
			$this->ignore[] = $row["iid"];
		}
		$this->group = new UserGroup($this->data["groupId"]);
	}
	function updateLastMessage(){
		$this->data["lastMessage"] = time();
		Database::getInstance()->query("UPDATE " . table("user") . " SET `lastMessage`='" . time() . "' WHERE `id`='" . $this->data["id"] . "'");
	}
	function defenderCount($new = null){
		if($new != null){
			Database::getInstance()->query("UPDATE " . table("user") . " SET `defenderCount`='" . (string)$new . "' WHERE `id`='" . $this->id() . "'");
			$this->data["defenderCount"] = $new;
		}
		
		return $this->data["defenderCount"];
	}
	function updateCount(){
		if($this->defenderCount() < 1){
			$count = $this->defenderCount() + (((($time = time()) - $this->data["countUpdatet"]) / 86400) * 0.00625);
			if($count > 1.0){
				$count = 1.0;
			}
			Database::getInstance()->query("UPDATE " . table("user") . " SET `countUpdatet`='" . $time . "', `defenderCount`='" . (string)$count . "' WHERE `id`='" . $this->id() . "'");
			$this->data["defenderCount"] = $count;
		}
	}
	
	/**
	 * ) Push a channel the user is allerady in
	 
	function pushChannel(ChannelData $data){
		$this->channels[$data->id()] = $data;
	}
	function renderChannels($callback){
		foreach($this->channels as $chan){
			if($callback($chan)){
				return;
			}
		}
	}
	function websocket($sock = null){
		if($sock != null){
			$this->_websocket = $sock;
		}
		
		return $this->_websocket;
	}
	function id(){
		return $this->data["id"];
	}
	function groupId($new = null){
		if($new !== null){
			$query = ($database = Database::getInstance())->query("SELECT `id` FROM " . table("user_group") . " WHERE `id`=" . $query->qlean($new));
			if($query->rows() != 1){
				return;
			}
			$database->query("UPDATE " . table("user") . " SET `groupId`=" . $database->qlean($new) . " WHERE `id`='" . $this->id() . "'");
			$this->data["groupId"] = $new;
			$this->group = new UserGroup($new);
		}
		return $this->data["groupId"];
	}
	function group(){
		return $this->group;
	}
	function isGeaust(){
		return $this->type() == "g";
	}
	function type(){
		return $this->data["type"];
	}
	function active(){
		return $this->data["active"];
	}
	function countUpdatet(){
		return $this->data["countUpdatet"];
	}
	function send($msg){
		// this method will send message to all channels the users is in
		foreach($this->channels as $channel){
			$channel->send(sprintf($msg, $channel->name()), $this);
		}
	}
	function nick($nick = null){
		if($nick != null){
			if(!nick_taken($nick, $this)){
				$this->send("NICK %s: " . $nick);
				$query = ($database = Database::getInstance())->query("UPDATE " . table("user") . " SET `nick`=" . $database->clean($nick) . " WHERE `id`='" . $this->id() . "'");
				if($query->rows() != 1){
					return false;
				}
				$this->data["nick"] = $nick;
			}else{
				return false;
			}
		}
		return $this->data["nick"];
	}
	function isMember($name){
		if(($channel = Channel::get($name)) != null)
			return $channel->isMember($this);
		return false;
	}
	function leave(ChannelData $channel, $message = false){
		if($channel->isMember($this)){
			unset($this->channels[$channel->id()]);
			$channel->leave($this, $message);
		}
	}
	function remove($message = "leave the chat"){
		foreach($this->channels as $channel){
			$channel->send("QUIT: " . $message);
			$channel->leave($this, false);
		}
		
		// to be sure
		$this->channels = [];
	}
	function isIgnore($uid){
		return in_array($uid, $this->ignore);
	}
	function addIgnore(UserData $user){
		if($this->isIgnore($user->id())){
			return false; // no reason to continue becuse the user is allerady on the list
		}
		
		Database::insert("ignore", [
				'uid' => $this->id(),
				'iid' => $user->id()
		]);
		$this->ignore[] = $user->id();
	}
	function unIgnore($id){
		if(!$this->isIgnore($id)){
			return false;
		}
		
		unset($this->ignore[array_search($id, $this->ignore)]);
		Database::getInstance()->query("DELETE FROM " . table("ignore") . " WHERE `uid`='" . $this->id() . "' AND `iid`='" . (int)$id . "'");
		return true;
	}
	function message_id($id = null){
		if($id != null){
			($database = Database::getInstance())->query("UPDATE " . table("user") . " SET `message_id`=" . $database->clean($id) . " WHERE `id`='" . $this->id() . "'");
			$this->data["message_id"] = $id;
		}
		return $this->data["message_id"];
	}
	function delete(){
		foreach($this->channels as $channel){
			$channel->leave($this, "Good by all");
		}
		Database::getInstance()->query("DELETE FROM " . table("user") . " WHERE `id`='" . $this->id() . "'");
	}
}
class UserGroup{
	private $data;
	public function __construct($id){
		$this->data = Database::getInstance()->query("SELECT * FROM " . table("user_group") . " WHERE `id`='" . $id . "'")->fetch();
		if($this->data === null){
			throw new LowLevelError("Could not find the usergroup", $id);
		}
	}
	public function id(){
		return $this->data["id"];
	}
	public function name(){
		return $this->data["name"];
	}
	public function show_user(){
		return $this->data["showUser"] == "Y";
	}
	public function show_ip(){
		return $this->data["showIP"] == "Y";
	}
	public function show_defender(){
		return $this->data["showDefender"] == "Y";
	}
	public function show_user_group(){
		return $this->data["showUserGroup"] == "Y";
	}
	public function unset_defender(){
		return $this->data["unsetDefender"] == "Y";
	}
	public function delete_ip(){
		return $this->data["deleteIp"] == "Y";
	}
	public function delete_user(){
		return $this->data["deleteUser"] == "Y";
	}
	public function delete_user_group(){
		return $this->data["deleteUserGroup"] == "Y";
	}
	public function delete_user_group_access(){
		return $this->data["deleteUserGroupAccess"] == "Y";
	}
	public function cretaeUserGroup(){
		return $this->data["createUserGroup"] == "Y";
	}
	public function appendUserGroupAccess(){
		return $this->data["appendUserGroupAccess"] == "Y";
	}
	public function appendUserGroupUser(){
		return $this->data["appendUserGroupUser"] == "Y";
	}
	public function getUserGroupId(){
		return $this->data["getUserGroupId"] == "Y";
	}
	public function changeConfig(){
		return $this->data["changeConfig"] == "Y";
	}
	public function getAccessList(){
		$data = $this->data;
		unset($data["id"]);
		unset($data["name"]);
		return $data;
	}
	public function getMembersRow(){
		$query = Database::query("SELECT COUNT(`id`) as id FROM " . table("user") . " WHERE `groupId`='" . $this->id() . "'");
		$row = $query->fetch();
		return intval($row["id"]);
	}
	public function delete(){
		return Database::query("DELETE FROM " . table("user_group") . " WHERE `id`='" . $this->id() . "'")->rows() != 0;
	}
	public function hasAccess($name){
		return !empty($this->data[$name]) && $this->data[$name] == "Y";
	}
	public function removeAccess($name){
		if(!$this->hasAccess($name))
			return false;
		return Database::query("UPDATE " . table("user_group") . " SET `" . $name . "`='N' WHERE `id`='" . $this->id() . "'")->rows() == 1;
	}
	public function appendAccess($name){
		if($this->hasAccess($name))
			return false;
		return Database::query("UPDATE " . table("user_group") . " SET `" . $name . "`='Y' WHERE `id`='" . $this->id() . "'")->rows() == 1;
	}
}
*/

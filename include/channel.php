<?php

function controleChannelName($name){
  //at start of channel name there must be a #
  if(strpos($name, "#") !== 0){
    return false;
  }

  $name = substr($name, 1);

  //controle length
  if(strlen($name) < 3 || strlen($name) > 10){
    return false;
  }

  //wee could use regex to find out wich char there is in but no. 
  for($i=0;$i<strlen($name);$i++){
     if(($char = ord($name[$i])) < 65 || $char > 90 && $char < 97 || $char > 122){
        return false;
     }
  }

  return true;
}

class Channel{
   private static $channels = [];

   public static function init(){
	  //get all channels from the database :)
	  $query = Database::query("SELECT * FROM ".table("channel"));
	  while($row = $query->fetch()){
		  self::$channels[$row['id']] = new ChannelData($row);
	  }
   }
   
   public static function remove(ChannelData $channel){
      $channel->remove();
      unset(self::$channels[$channel->id()]);
      Database::query("DELETE FROM ".table("channel")." WHERE `id`='".$channel->id()."'");
      Database::query("DELETE FROM ".table("channel_group")." WHERE `cid`='".$channel->id()."`");
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
                    $query = Database::query("SELECT `id` FROM ".table("channel_group")." WHERE `standart`='Y'");
                    if($query->rows() != 1){
                       error($message, "Could not finde a group for you");
                       return false;
                    }
                    $row = $query->fetch();
                    $group = $row["id"];
           }
	   
	   if($user->isMember($channel)){
		   if($message){
			   error($message, "You are allready member of the channel");
		   }
		   return false;
	   }
	   
	   $data["id"] = Database::insert("channel_member",[
		   'cid'    => $channel->id(),
		   'uid'    => $user->id(),
		   'gid'    => $group,
		   'active' => time(),
	   ]);
	   
	   
	   $user->pushChannel($channel);
	   if($message != null){
	   	 send($message, "JOIN: ".$channel->name());
	   }
	   
	   return true;
   }
   
   public static function garbage_collect(){
	   foreach(self::$channels as $channel){
		   $channel->garbage_collect();
	   }
   }
   
   private static function create($name, UserData $data){
      $data = [
        "name"    => $name,
        "title"   => $name,
		"creater" => $data->id(), 
      ];

      $data["id"] = Database::insert("channel", $data);

      $group = Database::insert("channel_group", [
         "name"        => "Admin",
         "cid"         => $data["id"],
         "standart"    => "N",
         "changeTitle" => "Y"
      ]);
      Database::insert("channel_group", [
         "name"        => "Moderater",
         "cid"         => $data["id"],
         "standart"    => "N",
         "changeTitle" => "N",
      ]);
      Database::insert("channel_group", [
         "name"        => "User",
         "cid"         => $data["id"],
         "standart"    => "Y",
         "changeTitle" => "N",
      ]);
        
	  return [$group, (self::$channels[$data["id"]] = new ChannelData($data))];
   }
}

class ChannelData{
   private $data;
   private $members = [];

   function __construct($data){
     $this->data = $data;
    $sql = Database::query("SELECT `uid` FROM ".table("channel_member")." WHERE `cid`='".$this->id()."'");
    while($row = $sql->fetch()){
       if(($user = User::get($row["uid"])) != null){//geaust is delted efter no member og channels so control it befor wee add it in cache
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

   function title(){
     return $this->data["title"];
   }
   
   function creater(){
	   return $this->data['creater'];
   }

   function getMembers(){
      return $this->members;
   }

   function getMember(UserData $user){
      if(empty($this->members[$user->id()])){
         return null;
      }

      return $this->members[$user->id()];
   }

   function leave(UserData $user, $sendMessage = "Leave the channel"){
      if($this->isMember($user)){
      //exit($sendMessage);
		//wee delete the user in the channel
		Database::query("DELETE FROM ".table("channel_member")." WHERE `cid`='".$this->id()."' AND `uid`='".$user->id()."'");
        unset($this->members[$user->id()]);
        if(count($this->members) == 0){
           Channel::remove($this);
        }else{
           if($sendMessage){
              $this->send("LEAVE ".$this->name().": ".$sendMessage, $user);
           }
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
      return send_channel($this, $user, $user->nick()."@".$message);
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
	  
	  //wee also clean up in message so no message is saving from this channel :)
	  Database::query("DELETE FROM ".table("message")." WHERE `cid`='".$this->id()."'");
   }
   
   function garbage_collect(){
	   //wee look after how soon is the user typed a message if more end 5 min and not more end 10 min the user is marked inaktiv. if the user has not writet more end 10 min the user is kicked out of the channel and the user need to join again :)
	   foreach($this->members as $member){
		   if($member->writeTime() <= time()-(60*5) && $member->writeTime() >= time()-(60*15) && !$member->isInaktiv()){
			   $member->markInaktiv();
		   }elseif($member->writeTime() <= time()-(60*15)){
			   //the user need to be delteded form the channel :)
			   $this->leave($member->getUser(), "(".$member->writeTime()."|".(time()-(60*15)).")Inaktiv to long time now");
		   }
	   }
   }
}


class ChannelMember{
	private $user;
	private $channel;
	private $data;
	
	public function __construct(UserData $user, ChannelData $data){
		$this->user = $user;
		$this->user->pushChannel($data);
		$this->data = Database::query("SELECT * FROM ".table("channel_member")." WHERE `cid`='".$data->id()."' AND `uid`='".$user->id()."'")->fetch();
		$this->channel = $data;
	}
	
	public function getUser(){
		return $this->user;
	}
	
	public function writeTime(){
		if(empty($this->data["active"])){
			print_r($this->data);
		}
		return $this->data['active'];
	}
	
	public function isInaktiv(){
		return $this->data["isInaktiv"] == "Y";
	}
	
	public function updateActive(){
		$append = "";
		if($this->isInaktiv()){
			$append = ", `isInaktiv`='N'";
			$this->data["isInaktiv"] = "N";
			$this->channel->send("INAKTIV ".$this->channel->name().": NO", $this->user);
		}
		
		Database::query("UPDATE ".table("channel_member")." SET `active`='".time()."'".$append." WHERE `id`='".$this->data["id"]."'");
	}
	
	public function markInaktiv(){
		$this->channel->send("INAKTIV ".$this->channel->name().": YES", $this->user);
		$this->data["isInaktiv"] = "Y";
		Database::query("UPDATE ".table("channel_member")." SET `isInaktiv`='Y' WHERE `cid`='".$this->channel->id()."' AND `uid`='".$this->user->id()."'");
	}
}

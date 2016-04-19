<?php

class Channel{
   private static $channels = [];

   public static function create($name){
      //controle the channels is exits
      foreach(self::$channels as $channel){
         if($channel->name() == $name)
           return false;
      }

      $channel = new ChannelData($name);//if the channel dont exists it will be created here.
      self::$channels[$channel->id()] = $channel;
   }

   public static function remove(ChannelData $channel){
      $channel->remove();
      unset(self::$channels[$channel->id()]);
      Database::query("DELETE FROM ".table("channel")." WHERE `id`='".$channel->id()."'");
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
}

class ChannelData{
   private $data;
   private $members;

   function __construct($name){
     $sql = Database::query("SELECT * FROM ".table("channel")." WHERE `name`=".qlean($name));
     $this->data = $sql->rows() == 0 ? $this->create($name) : $sql->fetch();

    $sql = Database::query("SELECT `uid` FROM ".table("channel_member")." WHERE `cid`='".$this->id()."'");
    while($row = $sql->fetch()){
       if(($user = User::get($row["uid"])) != null){//geaust is delted efter no member og channels so control it befor wee add it in cache
          $this->members[$user->id()] = $user;
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

   function leave(UserData $user, $sendMessage = "Leave the channel"){
      if($this->isMember($user)){
        unset($this->members[$user->id()]);
        if(count($this->members) == 0){
           Channel::remove($this);
        }else{
           if($sendMessage){
              send_channel($this, "LEAVE ".$this->name().": ".$sendMessage);
           }
        }
      }
   }
    
   function isMember(UserData $user){
      return !empty($this->members[$user->id()]);
   }

   function send($message, UserData $user = null){
      if($user == null){
         $user = User::current();
      }
      return send_channel($this, $user->nick()."@".$message);
   }

   function remove(){
      if(count($this->members) != 0){
         foreach($this->members as $member){
            $member->leave($this);
         }
      }
   }

   private function create($name){
      $data = [
        "name"  => $name,
        "title" => $name,
      ];

      $data["id"] = Database::insert("channel", $data);
      return $data;
   }
}

<?php
class User{
  private static $users = [];
  private static $current = null;

  public static function push($data, $setCurrent = false){
     //wee control that the user is not set in our cache system
     if(empty(self::$users[$data["id"]])){
       //push the user to the cahce system 
       self::$users[$data["id"]] = new UserData($data);
     }

     if($setCurrent)
       self::$current = self::$users[$data["id"]];
  }

  public static function current(){
    return self::$current;
  }

  public static function remove(UserData $user){
    if(!empty(self::$users[$user->id()])){
       unset(self::$users[$user->id()]);
    }
  }

}

class UserData{
   private $channels = [];
   private $data = [];

   function __construct(array $data){
      $this->data     = $data;
      $this->channels = Channel::getUserChannel($this);
   }

   function id(){
      return $this->data["id"];
   }

   function nick(){
      return $this->data["nick"];
   }

   function isMember(ChannelData $data){
     return !empty($this->channels[$data->cid()]);
   }

   function leave(ChannelData $channel,$message = false){
      if($this->isMember($channel)){
         unset($this->channels[$channel->id()]);
         $channel->leave($this, $message);
      }
   }

   function remove($message = "leave the chat"){
      foreach($this->channels as $channel){
         $channel->send("QUIT: ".$message);
         $channel->leave($this, false);
      }

      //to be sure
      $this->channels = [];
   }
}

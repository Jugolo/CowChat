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

  public static function controleNick($nick, UserData $user = null){
     return Database::query("SELECT `id` FROM ".table("user")." WHERE `nick`=".Database::qlean($nick).($user != null ? " AND `id`<>'".$user->id()."'"))->rows() != 0;
  }

  public static function get($uid){
     if(is_numeric($uid)){
       if(!empty(self::$users[$uid])){
          return self::$user[$uid];
       }
       $field = "uid";
     }else{
       $field = "nick";
     }

     $query = Database::query("SELECT * FROM ".table("user")." WHERE `".$field."`=".Database::qlean($uid));
     if($query->rows() != 0){
        if(is_numeric($uid)){
           return new UserData($query->fetch());
        }else{
           $r = $fetch();
           return self::get($r["id"]);
        }
     }

     return null;
  }
}

class UserData{
   private $channels = [];
   private $data     = [];
   private $ignore   = [];
   private $group    = null;

   function __construct(array $data){
      $this->data     = $data;
      $this->channels = Channel::getUserChannel($this);
      $query = Database::query("SELECT `iid` FROM ".table("ignore")." WHERE `uid`='".$this->id()."'");
      while($row = $query->fetch()){
        $this->ignore[] = $row["iid"];
      }
      $this->group = SystemGroup::get($this);
   }

   function id(){
      return $this->data["id"];
   }

   function send($msg){
      //this method will send message to all channels the users is in
      foreach($this->channels as $channel){
         $channel->send($msg, $this);
      }
   }

   function nick($new = null){
      if($new == null){
         if(User::controleNick($nick, $this)){
            $query = Database::query("UPDATE ".table("user")." SET `nick`=".Database::qlean($nick)." WHERE `id`='".$this->id()."'");
            if($query->rows() != 1){
               return false;
            }
            $this->send("NICK: ".$nick);
            $this->data["nick"] = $nick;
         }else{
           return false;
         }
      }
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

   function isIgnore($uid){
     return in_array($uid, $this->ignore);
   }

   function addIgnore(UserData $user){
      if($this->isIgnore($user->id())){
         return false;//no reason to continue becuse the user is allerady on the list
      }

      Database::insert("ignore",[
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
     Database::query("DELETE FROM ".table("ignore")." WHERE `uid`='".$this->id()."' AND `iid`='".(int)$id."'");
     return true;
   }

   function message_id(){
      return $this->data["message_id"];
   }
}

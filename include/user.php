<?php
class User{
  private static $users = [];
  private static $current = null;

  public static function createGaust($nick){
     $data = [
        "nick"       => $nick,
        "hash"       => generate_hash(),
        "groupId"    => Setting::get("startGroup"),
        "type"       => "g",
		"ip"         => ip(),
		"message_id" => Server::getLastId(),
		"active"     => time(),
        "defenderCount" => 0.5,
     ];

     $data["id"] = Database::insert("user", $data);
     return $data;
  }

  public static function current($current = null){
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

  public static function controleNick($nick, UserData $user = null){
     return Database::query("SELECT `id` FROM ".table("user")." WHERE `nick`=".Database::qlean($nick).($user != null ? " AND `id`<>'".$user->id()."'" : ""))->rows() != 0;
  }

  public static function get($uid){
     if(is_numeric($uid)){
       if(!empty(self::$users[$uid])){
          return self::$users[$uid];
       }
       $field = "id";
     }else{
       $field = "nick";
     }

     $query = Database::query("SELECT * FROM ".table("user")." WHERE `".$field."`=".Database::qlean($uid));
     if($query->rows() != 0){
        $r = $query->fetch();
        return self::$users[$r["id"]] = new UserData($r);
     }

     return null;
  }
  
  public static function garbage_collector(){
	  //run all user thrue and finde the geaust
	  $query = Database::query("SELECT `id`, `active` FROM ".table("user")." WHERE `type`='g' AND `active`<'".(time()-(60*30))."'");
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
   private $data     = [];
   private $ignore   = [];
   private $group    = null;
   private $_websocket = null;

   function __construct(array $data){
      $this->data     = $data;
      $query = Database::query("SELECT `iid` FROM ".table("ignore")." WHERE `uid`='".$this->id()."'");
      while($row = $query->fetch()){
        $this->ignore[] = $row["iid"];
      }
      $this->group = SystemGroup::get($this);
   }
   
   function defenderCount($new=null){
      if($new != null){
         Database::query("UPDATE ".table("user")." SET `defenderCount`='".(string)$new."' WHERE `id`='".$this->id()."'");
         $this->data["defenderCount"] = $new;
      }

      return $this->data["defenderCount"];
   }

   function updateCount(){
      if($this->defenderCount() < 1){
         $count = ((($time = time()) - $this->data["countUpdatet"])*3600)*0.000625;
         if($count > 1){
           $count = 1;
         }
         Database::query("UPDATE ".table("user")." SET `countUpdatet`='".$time."', `defenderCount`='".$count."' WHERE `id`='".$this->id()."'");
         $this->data["defenderCount"] = $count;
      }
   }

   /**
   ) Push a channel the user is allerady in
   */
   function pushChannel(ChannelData $data){
	   $this->channels[$data->id()] = $data;
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

   function groupId(){
      return $this->data["groupId"];
   }

   function group(){
      return $this->group;
   }
   
   function isGeaust(){
	   return $this->data["type"] == "g";
   }
   
   function active(){
	   return $this->data["active"];
   }

   function send($msg){
      //this method will send message to all channels the users is in
      foreach($this->channels as $channel){
         $channel->send($msg, $this);
      }
   }

   function nick($nick = null){
      if($nick != null){
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
     return !empty($this->channels[$data->id()]);
   }
   
   function join(ChannelData $channel, MessageParser $message = null){
	   if($this->isMember($channel)){
		   if($message){
			   error($message, "You are allready member of the channel");
		   }
		   return false;
	   }
	   
	   if($channel->join($this)){
	       $this->channels[$channel->id()] = $channel;
	       if($message != null){
		      send($message, "JOIN: ".$channel->name());
	       }
	       return true;
	   }else{
		   return false;
	   }
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

   function message_id($id = null){
	   if($id != null){
		   Database::query("UPDATE ".table("user")." SET `message_id`=".Database::qlean($id)." WHERE `id`='".$this->id()."'");
		   $this->data["message_id"] = $id;
	   }
      return $this->data["message_id"];
   }
   
   function delete(){
	   foreach($this->channels as $channel){
		   $channel->leave($this, "Good by all");
	   }
	   Database::query("DELETE FROM ".table("user")." WHERE `id`='".$this->id()."'");
   }
}

function generate_hash(){
	$chars = "qwertyuioplkjhgfdsazxcvbnmøåQWERTYUIOPASDFGHJKLZXCVBNMÆØÅ,.-_:;'¨^*<>½§1234567890+´!\"#¤%&/()=?`";
	$return = "";
	for($i=0;$i<99999;$i++){
		$return .= $chars[mt_rand(0, mt_rand(0, strlen($chars)-1))];
	}
	
	return sha1($return);
}

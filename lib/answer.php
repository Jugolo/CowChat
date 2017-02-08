<?php
class Answer{
  /**
  *Save socket here so it can write to the diffrence websocket clients
  */
  private static $sockets = [];
  /**
  *Save the difference ajax answer to later output
  */
  private static $ajax = [
    "message" => []
  ];
  /**
  *Current user
  */
  private static $user;
  /**
  *database cache
  */
  private static $database;

  public static function setDatabase(DatabaseHandler $database){
    self::$database = $database;
  }

  public static function setUser(User $data){
    self::$user = $data;
  }

  public static function currentUser() : User{
    return self::$user;
  }

  public static function parse(AnswerRequest $answer){
    self::handle(
      $answer->getTo(),
      $answer->hasFrom() ? $answer->getFrom() : (self::$user == null ? 0 : self::$user->id()),
      $answer->hasAvatar() ? $answer->getAvatar() : (self::$user == null ?  "" : self::$user->avatar()),
      $answer->hasNick() ? $answer->getNick() : "Bot",
      $answer->hasChannel() ? $answer->getChannel() : "Bot",
      $answer->getMessage()
    );
  }

  public static function outputToUser(AnswerRequest $data){
    self::output(
      $data->getChannel(),
      $data->getTime(),
      $data->getAvatar(),
      $data->getNick(),
      $data->getMessage()
    );
  }

  public static function outputAjax(){
     if(headers_sent()){
       return;//if header is sent stop here
     }
     header('Content-Type: application/json');
     echo json_encode(self::$ajax);
  }

  private static function handle($private, int $from, string $avatar, string $nick, $channel, string $message){
    if($private !== null && $private == $from && $from == self::$user->id()){
      //no need to save message there is only to this user
      self::output(
        $channel,
        time(),
        $avatar,
        $nick,
        $message
      );
      return;
    }
    self::save(
       $from,
       $private,
       $channel,
       $nick,
       $message
    );
  }

  private static function save(int $from, $to, string $channel, string $nick, string $message){
     if(!is_numeric($channel)){
       $channel = self::convertNameToCid($channel);
     }

     self::$database->query("INSERT INTO `".DB_PREFIX."chat_message`(
       `uid`,
       `cid`,
       `isBot`,
       `time`,
       `message`,
       `isMsg`,
       `msgTo`
     ) VALUES (
       '".$from."',
       '".(int)$channel."',
       '".($nick == "Bot" ? Yes : No)."',
       NOW(),
       '".self::$database->clean($message)."',
       '".($to == null ? No : Yes)."',
       '".($to == null ? 0 : (int)$to)."'
     );");
  }

  private static function output($channel, int $time, string $avatar, string $nick, string $message){
    if(is_numeric($channel)){
      $channel = self::convertCidToName($channel);
    }

    self::$ajax["message"][] = [
      "channel" => htmlentities($channel),
      "time"    => date("H:i", $time),
      "avatar"  => htmlentities($avatar),
      "nick"    => htmlentities($nick),
      "message" => htmlentities($message),
    ];
  }

  private static function convertNameToCid(string $name) : int{
    if($name == "Bot"){
      return 1;
    }else{
      $query = self::$database->query("SELECT `id` FROM `".DB_PREFIX."chat_name` WHERE `name`='".self::$database->clean($name)."'");
      if(self::$database->isError){
        exit(self::$database->getError());
      }
      $data = $query->get();
      if(!$data)
        return 1;
      return $data["id"];
    }
  }

  private static function convertCidToName(int $cid) : string{
    if($cid == 1){
      return "Bot";
    }else{
      $query = self::$database->query("SELECT `name` FROM `".DB_PREFIX."chat_name` WHERE `id`='".$cid."'");
      if(self::$database->isError){
        exit(self::$database->getError());
      }

      $result = $query->get();
      return $result ? $result["name"] : "Bot";
    }
  }
}

class AnswerRequest{
  private $private;
  private $nick;
  private $channel;
  private $time;
  private $message;
  private $from;
  private $avatar;

  public function hasAvatar() : bool{
    return $this->avatar !== null;
  }

  public function setAvatar(string $avatar){
    $this->avatar = $avatar;
  }

  public function getAvatar(){
    return $this->avatar;
  }

  public function isPrivate() : bool{
   return $this->private !== null;
  }

  public function setPrivate(int $uid){
    $this->private = $uid;
  }

  public function setPublic(){
    $this->private = null;
  }

  public function getTo(){
    return $this->private;
  }

  public function setFrom(int $from){
    $this->from = $from;
  }

  public function getFrom() : int{
    return $this->from;
  }

  public function hasFrom() : bool{
    return $this->from !== null;
  }

  public function hasNick() : bool{
    return $this->nick !== null;
  }

  public function setNick(string $nick){
    $this->nick = $nick;
  }

  public function getNick() : string{
    return $this->nick;
  }

  public function hasChannel() : bool{
    return $this->channel !== null;
  }

  public function getChannel(){
    return $this->channel;
  }

  public function setChannel($channel){
    $this->channel = $channel;
  }

  public function setTime(int $time){
    $this->time = $time;
  }

  public function hasTime() : bool{
    return $this->time !== null;
  }

  public function getTime() : int{
    return $this->time;
  }

  public function setMessage(string $msg){
    $this->message = $msg;
  }

  public function getMessage() : string{
    return $this->message;
  }

  public function hasMessage() : bool{
    return $this->message !== null;
  }
}

function bot_other(int $uid, $channel, string $message){
  $data = new AnswerRequest();
  $data->setMessage($message);
  $data->setChannel($channel);
  $data->setNick("Bot");
  $data->setFrom($uid);
  Answer::parse($data);
}

function bot_self_other(int $uid, $channel, string $message){
  $data = new AnswerRequest();
  $data->setPrivate($uid);
  $data->setFrom($uid);
  $data->setMessage($message);
  $data->setChannel($channel);
  $data->setNick("Bot");
  Answer::parse($data);
}

function bot($channel, string $message){
  $data = new AnswerRequest();
  $data->setMessage($message);
  $data->setChannel($channel);
  $data->setNick("Bot");
  Answer::parse($data);
}

function bot_self($channel, string $message){
  $data = new AnswerRequest();
  $data->setPrivate(Answer::currentUser()->id());
  $data->setMessage($message);
  $data->setChannel($channel);
  $data->setNick("Bot");
  Answer::parse($data);
}

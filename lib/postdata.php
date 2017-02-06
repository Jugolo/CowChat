<?php
class PostData{
  private $channel;
  private $message;
  private $cid;

  public function __construct(string $message, string $channel, int $cid){
    $this->message = $message;
    $this->channel = $channel;
    $this->cid = $cid;
  }

  public function id() : int{
    return $this->cid;
  }

  public function isCommand() : bool{
    return $this->message[0] == "/";
  }

  public function getCommand() : string{
    if(($pos = strpos($this->message, " ")) !== false)
      return substr($this->message, 1, $pos-1);
    return substr($this->message, 1);
  }

  public function getMessage() : string{
    return $this->message;
  }

  public function getChannel() : string{
    return $this->channel;
  }
}

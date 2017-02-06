<?php
class User{
  private $db;
  private $data;
  private $cache = [];

  public function __construct(DatabaseHandler $db, array $data){
    $this->db = $db;
    $this->data = $data;
  }

  public function id() : int{
    return $this->data["id"];
  }

  public function nick(string $nick = "") : string{
    if($nick !== ""){
      $this->db->query("UPDATE `".DB_PREFIX."chat_user` SET `nick`='".$this->db->clean($nick)."' WHERE `id`='".$this->id()."'");
      $this->data["nick"] = $nick;
    }
    return $this->data["nick"];
  }

  public function avatar(string $avatar = "") : string{
    if($avatar !== ""){
      $this->db->query("UPDATE `".DB_PREFIX."chat_user` SET `avatar`='".$this->db->clean($avatar)."' WHERE `id`='".$this->id()."'");
      $this->data["avatar"] = $avatar;
    }
    return $this->data["avatar"];
  }

  public function username() : string{
    return $this->data["username"];
  }

  public function appendIgnore(int $id){
     $this->db->query("INSERT INTO `".DB_PREFIX."chat_ignore` (
        `uid`,
        `ignore`
     ) VALUES (
        '".$this->id()."',
        '".$id."'
     );");
     if(array_key_exists("ignore", $this->cache)){
       $this->cache["ignore"][] = $id;
     }
  }

  public function unIgnore(int $id){
     $this->db->query("DELETE FROM `".DB_PREFIX."chat_ignore`
                             WHERE `uid`='".$this->id()."'
                             AND `ignore`='".$id."'");
     if(array_key_exists("ignore", $this->cache)){
       unset($this->cache["ignore"][array_search($id, $this->cache["ignore"])]);
     }
  }

  public function ignoreList() : array{
    if(array_key_exists("ignore", $this->cache))
      return $this->cache["ignore"];

    $cache = [];
  
    $query = $this->db->query("SELECT `ignore` FROM `".DB_PREFIX."chat_ignore` WHERE `uid`='".$this->id()."'");
    if($this->db->isError){
      exit($this->db->getError());
    }
    while($row = $query->get())
      $cache[] = $row["ignore"];

    $this->cache["ignore"] = $cache;
    return $cache;
  }
}

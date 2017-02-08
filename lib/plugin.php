<?php
//append in version 1.1
class Plugin{
  private $db;
  private $plugin = ["trigger" => [], "event" => [], "command" => []];
  private $obj = [];
  
  public function __construct(DatabaseHandler $db){
    $this->db = $db;
    $query = $this->db->query("SELECT * FROM `".DB_PREFIX."chat_plugin`");
    while($row = $query->get()){
      $this->cachePlugin(
      $row["type"],//type of plugin
      $row["name"],//the event/trigger/command name
      $row["dir"],//the dir of the plugin
      $row["class"],//the class name
      $row["method"]//the method name
      );
    }
  }
  
  public function command(string $name, User $user, PostData $post){
     if(empty($this->plugin["command"][$name]))
       return false;
     $this->evulate($this->plugin["trigger"][$name][0], [$user, $post]);
     return true;
  }
  
  public function trigger(string $name, array $arg = []){
    //this trigger all trigger.
    if(empty($this->plugin["trigger"][$name]))
      return;
    foreach($this->plugin["trigger"][$name] as $data){
      $this->evulate($data, $arg);
    }
  }
  
  private function evulate(array $data, array $arg){
     if(empty($this->obj[$data["dir"]])){
       include "./lib/plugin/".$data["dir"];
       $class = $data["dir"]."_Plugin";
       $this->obj[$data["dir"]] = new $class($this->db);
     }
     
     call_user_func_array([$this->obj[$data["dir"]], $data["method"]], $arg);
  }
  
  private function cachePlugin(string $type, string $name, string $dir, string $method){
     if(empty($this->plugin[$type][$name])){
       $this->plugin[$type][$name] = [];
     }
     $this->plugin[$type][$name][] = [
       "dir"    => $dir,
       "method" => $method
     ];
  }
}

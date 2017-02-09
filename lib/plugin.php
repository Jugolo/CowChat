<?php
//append in version 1.1
class Plugin{
  private $db;
  private $plugin = ["trigger" => [], "command" => []];
  private $obj = [];
  
  public function __construct(DatabaseHandler $db){
    $this->db = $db;
    $query = $this->db->query("SELECT * FROM `".DB_PREFIX."chat_plugin`");
    while($row = $query->get()){
      $this->cachePlugin(
      $row["type"],//type of plugin
      $row["name"],//the event/trigger/command name
      $row["dir"],//the dir of the plugin
      $row["method"]//the method name
      );
    }
  }
  
  public function command(string $name, User $user, PostData $post){
    //this plugin listen after /plugin command :)
    if($name == "plugin"){
      $this->handleCommand(trim($post->getMessage()) == "/plugin" ? [] : explode(" ", substr($post->getMessage(), 8)), $user, $post);
      return true;
    }
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
       include "./lib/plugin/".$data["dir"]."/plugin.php";
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
  
  private function handleCommand(array $arg, User $user, PostData $data){
    if(!is_admin($user->id())){
      error("accessDeniad");
      return;
    }
    
    if(count($arg) == 0){
      $this->sendList($data->id());
      return;
    }
    
    for($i=0;$i<count($arg);$i++){
      switch($arg[$i]){
        case "-i":
          $this->installPlugin($arg, $i, $data, $user);
        break;
        case "-u":
          $this->uninstallPlugin($arg, $i, $data, $user);
        break;
        default:
          error($data, "unkownCommand");
          //stop here if the user missing - in -i it will send this twise :)
          return;
        break;
      }
    }
  }
  
  private function uninstallPlugin(array $arg, int &$i, PostData $post, User $user){
    if($i+1 >= count($arg)){
      error($post, "unknownCommand");
      return;
    }
    
    $i++;
    $name = $arg[$i];
    if(!in_array($name, $this->getInstalledPlugin())){
      error($post, "pluginNotInstalled");
      return;
    }
    
    $this->db->query("DELETE FROM `".DB_PREFIX."chat_plugin` WHERE `dir`='".$this->db->clean($name)."'");
    
    if(!class_exists($name."_Plugin")){
      include "./lib/plugin/".$name."/plugin.php";
    }
    $cName = $name."_Plugin";
    $obj = !empty($this->obj[$name]) ? $this->obj[$name] : new $cName($this->db);
    if(method_exists($obj, "uninstall")){
      $obj->uninstall();
    }
    bot_self($post->id(), "/pluginRemoved");
  }
  
  private function installPlugin(array $arg, int &$i, PostData $post, User $user){
    if($i+1 >= count($arg)){
      error($post, "unkownCommand");
      return;
    }
    $i++;
    $name = $arg[$i];
    
    if(in_array($name, $this->getInstalledPlugin())){
      error($post, "pluginInstalled");
      return;
    }
    
    if(!file_exists("./lib/plugin/".$name."/plugin.php")){
      error($post, "unknownPlugin");
      return;
    }
    
    include "./lib/plugin/".$name."/plugin.php";
    if(!class_exists($name."_Plugin")){
      error($post, "unknownPlugin");
      return;
    }
    $cName = $name."_Plugin";
    $obj = new $cName($this->db);
    foreach($obj->events() as $event){
      $this->db->query("INSERT INTO `".DB_PREFIX."chat_plugin` (
        `type`,
        `name`,
        `dir`,
        `method`
      ) VALUES (
        '".$this->db->clean($event->getType())."',
        '".$this->db->clean($event->getName())."',
        '".$this->db->clean($name)."',
        '".$this->db->clean($event->getMethod())."'
      );");
      $this->cachePlugin(
        $event->getType(),
        $event->getName(),
        $name,
        $event->getMethod()
        );
    }
    $this->obj[$name] = $obj;
    if(method_exists($obj, "doInstall")){
      $obj->doInstall();
    }
    bot_self($post->id(), "/plugininstalled");
  }
  
  private function sendList(int $cid){
    $insttaled = $this->getInstalledPlugin();
    $all = $this->getAllPlugin();
    $buffer = [];//all plugin will be cached in this buffer
    foreach($all as $name){
      $buffer[] = (in_array($name, $insttaled) ? "+" : "-").$name;
    }
    bot_self($cid, "/pluginlist ".implode(",", $buffer));
  }
       
  private function getAllPlugin() : array{
    $dir = opendir("./lib/plugin/");
    $buffer = [];
    while($i = readdir($dir)){
      if($i != "." && $i != ".." && is_dir("./lib/plugin/".$i)){
        $buffer[] = $i;
      }
    }
    return $buffer;
  }
  
  private function getInstalledPlugin() : array{
    $query = $this->db->query("SELECT `dir` FROM `".DB_PREFIX."chat_plugin` GROUP BY dir");
    $buffer = [];
    while($row = $query->get())
      $buffer[] = $row["dir"];
    return $buffer;
  }
}

class PluginEventList{
  private $type, $name, $method;
  
  public function __construct(string $type, string $name, string $method){
    $this->type = $type;
    $this->name = $name;
    $this->method = $method;
  }
  
  public function getType() : string{
    return $this->type;
  }
  
  public function getName() : string{
    return $this->name;
  }
  
  public function getMethod() : string{
    return $this->method;
  }
}

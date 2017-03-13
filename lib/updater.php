<?php
class Updater{
  public static function controle(DatabaseHandler $db){
    $query = $database->query("SELECT * FROM `".DB_PREFIX."chat_updater` WHERE `last_check`<'".time()."'");
    while($row = $query->get()){
      if(self::needUpdate($row)){
        
      }
    }
  }
  
  private static function needUpdate(array $data) : bool{
    $current = self::getCurrentVersion($data);
    if(!$current){
      return false;
    }
    return version_compare($data["version"], $current, '<');
  }
  
  private static function getCurrentVersion($data){
    $data = self::request("https://api.github.com/repos/".$data["owner"]."/".$data["repo"]."/tags");
    if(!$data){
      return $data["version"];
    }
    $n = "V0.0";
    foreach($data as $item){
      if(version_compare($n, $item["name"], '<')){
        $n = $item["name"];
      }
    }
    
    return $n;
  }
  
  private static request($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $source = curl_exec($curl);
    if(!$source){
      return null;
    }
    return json_decode($source, true);
  }
}

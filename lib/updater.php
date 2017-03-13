<?php
class Updater{
  public static function controle(DatabaseHandler $db){
    $query = $database->query("SELECT * FROM `".DB_PREFIX."chat_updater` WHERE `last_check`<'".time()."'");
    while($row = $query->get()){
      if(($data = self::needUpdate($row))[0]){
        self::doUpdate($db, $data[1], $row);
      }
    }
  }
  
  private static function doUpdate(DatabaseHandler $db, string $zip, array $data){
    $zipContext = self::request($zip);
    if(!$zipContext){
      return;
    }
  }
  
  private static function needUpdate(array $data) : bool{
    $current = self::getCurrentVersion($data);
    if(!$current){
      return [false];
    }
    return [version_compare($data["version"], $current[0], '<'), $current[1]];
  }
  
  private static function getCurrentVersion($data){
    $data = self::request("https://api.github.com/repos/".$data["owner"]."/".$data["repo"]."/tags");
    if(!$data){
      return null;
    }
    $n = "V0.0";
    foreach(json_decode($data, true) as $item){
      if(version_compare($n[0], $item["name"], '<')){
        $n = [$item["name"], $item["zipball_url"]];
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
    return $source;
  }
}

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
    $data = self::request("https://api.github.com/repos/Jugolo/CowChat/tags");
  }
  
  private static request($url){
    $curl = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/re);
  }
}

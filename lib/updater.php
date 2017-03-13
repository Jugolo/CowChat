<?php
class Updater{
  public static function controle(DatabaseHandler $db){
    $query = $db->query("SELECT * FROM `".DB_PREFIX."chat_updater` WHERE `last_check`<'".time()."'");
    while($row = $query->get()){
      if(($data = self::needUpdate($row))[0]){
        self::doUpdate($data[1], $row);
      }
    }
    $db->query("UPDATE `".DB_PREFIX."chat_updater` SET `last_check`='".strtotime("+1 day")."' WHERE `last_check`<'".time()."'");
  }
  
  private static function doUpdate(string $zip, array $data){
    $zipContext = self::request($zip);
    if(!$zipContext){
      return;
    }
    
    $name = tempnam(sys_get_temp_dir(), "cowchat_updater");
    $file = fopen($name, "w+");
    fwrite($file, $zipContext);
    fclose($file);
    
    $z = new ZipArchive();
    if(!$z->open($name) || $z->numFiles <= 1){
      return;
    }
    
    $mdir = strlen($z->getNameIndex(0))+1;
    for($i=0;$i<$z->numFiles;$i++){
      if(preg_match("/\/$/", $z->getNameIndex($i))){
        @mkdir($data["dir"].substr($z->getNameIndex($i), $mdir));
      }else{
        $fopen = fopen($data["dir"].substr($z->getNameIndex($i), $mdir), "w+");
        fwrite($fopen, $z->getFromIndex($i));
        fclose($fopen);
      }
    }
    $z->close();
  }
  
  private static function needUpdate(array $data) : array{
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
  
  private static function request($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    $source = curl_exec($curl);
    if(!$source){
      return null;
    }
    return $source;
  }
}

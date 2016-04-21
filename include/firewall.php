<?php 
//the chat is written as data container. But not the firewall
class FireWall{
   private static $ip = [];//contains all ip there is temporary banned.

   public static function init(){
     self::garbage();
     self::load();
   }

   public static function getBans(){
      if(self::garbage() != 0){
        self::load();
      }

      return self::$ip;
   }

   public static function getInfoBan($ip){
      $query = Database::query("SELECT * FROM ".table("ip_ban")." WHERE `ip`='".Database::qlean($ip));
      if($query->rows() != 1)
        return null;

      $return = [];
      foreach($this->fetch() as $key = $value)
         $return[$key] = $value;

      return $return;
          
   }

   public static function isBan(){
      if(self::garbage() != 0){
         self::load();
      }

      return in_array(ip(), self::$ip);
   }

   public static function getBlacklist(){
      if(file_exists("include/firewall/blacklist.txt")){
         return explode("\r\n", file_get_contents("include/firewall/blacklist.txt"));
      }
      return [];
   }

   public static function getWhiteList(){
       if(file_exists("include/firewall/whitelist.txt")){
          return explode("\r\n", file_get_contents("include/firewall/whitelist.txt"));
       }
       return [];
   }

   public static function isBlacklist($ip){
       if(in_array($ip, self::getWhiteList())){
          return false;//this ip can never be baned from here or in channels
       }

       return in_array($ip, self::getBlacklist());
   }

   private static function garbage(){
     return Database::query("DELETE FROM ".table("ip_ban")." WHERE `timeout`>'".time()."'")->rows();
   }

   private static function load(){
     self::$ip = [];
     $query = Database::query("SELECT `id`, `ip` FROM ".table("ip_ban"));
     while($row = $query->fetch()){
        self::$ip[$row["id"]] = $row["ip"];
     }
   }
}

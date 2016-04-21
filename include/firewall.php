<?php 
//the chat is written as data container. But not the firewall
class FireWall{
   private static $ip = [];//contains all ip there is temporary banned.

   public static function init(){
     self::garbage();
     $query = Database::query("SELECT `id`, `ip` FROM ".table("ip_ban"));
     while($row = $query->fetch()){
        self::$ip[$row["id"]] = $row["ip"];
     }
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
}

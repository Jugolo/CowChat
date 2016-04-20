<?php 
//the chat is written as data container. But not the firewall
class FireWall{
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
}

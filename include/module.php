<?php
class Module{
   private static $loaded = [];

   public static function load($load){
      if!empty(self::$loaded[$load])){
         return false;
      }
      self::$loaded[$load] = true;
      include self::url($load);
   }

   private static function url($name){
      return "include/module/".$name."/".$name.".php";
   }
}

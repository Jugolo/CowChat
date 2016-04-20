<?php

class SystemGroup{
   private static $groups = [];

   public static function get(UserData $user){
       if(!empty(self::$groups[$user->groupId()])){
          return self::$groups[$user->groupId()];
       }

       $return = self::$groups[$user->groupId()] = new SystemGroupData($user);
       return $return;
   }
}
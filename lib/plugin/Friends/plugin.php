<?php

class Friends_Plugin{
   private $db;
   
   public function __construct(DatabaseHandler $db){
      $this->db = $db;
   }
   
   public function events(){
     return [
       new PluginEventList("trigger", "system.user.login", "userlogin")
     ];
   }
   
   public function userlogin(User $user){
     $query = $this->db->query("SELECT * FROM `".DB_PREFIX."chat_friends` WHERE `one`='".$user->id()."' OR `two`='".$user->id()."'");
     while($row = $query->get()){
       bot_self_other($row['one'] == $user->id() ? $row['two'] : $row['one'], 1, "/friend login ".$user->nick());
     }
   }
   
   public function doInstall(){//trigged when the install is installed
     $this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."chat_friends` (
       `one` int(11) NOT NULL,
       `two` int(11) NOT NULL
     ) ENGINE=MyISAM;");
   }
   
   public function uninstall(){
     $this->db->query("DROP TABLE `".DB_PREFIX."chat_friends`;");
   }
}

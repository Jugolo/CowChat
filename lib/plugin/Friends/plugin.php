<?php

class Friends_Plugin{
   private $db;
   
   public function __construct(DatabaseHandler $db){
      $this->db = $db;
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

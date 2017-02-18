<?php

class Friends_Plugin{
   private $db;
   
   public function __construct(DatabaseHandler $db){
      $this->db = $db;
   }
   
   public function events(){
     return [
       new PluginEventList("trigger", "system.user.login",     "userlogin"),
       new PluginEventList("trigger", "client.javascript.end", "javascriptend"),
       new PluginEventList("command", "addfriend",             "addfriend")
     ];
   }
   
   public function addfriend(User $user, PostData $post){
      $command = $post->getMessage();
      if(trim($command) == "/addfriend"){
         bot_self($post->id(), "[color=red]Missing username to add to frind list[/color]");
         return;
      }
      
      $command = substr($command, 11);
      $id = $this->db->query("SELECT `id` FROM `".DB_PREFIX."chat_user` WHERE `nick`='".$this->db->clean($command)."'")->get();
      if(!$id){
         bot_self($post->id(), "[color=red]Unknown user[/color]");
         return;
      }
      $this->db->query("INSERT INTO `".DB_PREFIX."chat_friends` (
        `one`,
        `two`
      ) VALUES (
        '".$user->id()."',
        '".$id."'
      );");
      bot_self($post->id(), "[color=green]The user is now you friend[/color]");
   }
   
   public function javascriptend(){
      ?>
   if(typeof commands !== "undefined"){
      commands["friend"] = function(data){
         var arg = data.message.substr(8).split(" ");
         for(var i=0;i<arg.length;i++){
            switch(arg[i]){
               case "login":
                  i++;
                  sys.currentPage().line(
                     data.time,
                     "",
                     "Bot",
                     "[color=green]Your friend "+arg[i]+" has just login[/color]"
                  );
                  break;
              }
         }
      };
   }
      <?php
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
     bot_self(1, "Thanks to install friends plugin. This plugin is not done yet but please go to github to help to get it done");
   }
   
   public function uninstall(){
     $this->db->query("DROP TABLE `".DB_PREFIX."chat_friends`;");
   }
}

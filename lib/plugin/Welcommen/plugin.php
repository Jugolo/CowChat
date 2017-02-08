<?php
class Welcommen_Plugin{
  public function events(){
    return [
      new PluginEventList("trigger", "system.user.create", "usercreate")
    ];
  }
  
  public function usercreate($username){
    bot(Config::get("startChannel"), "Wellcommen to ".$username." there just has created a new account");
  }
}

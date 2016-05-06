<?php
function online_command(MessageParser $msg){
    if(User::current()->isMember(Channel::get($msg->message()))){
      $member = [];
      foreach(Channel::get($msg->message())->getMember() as $member){
         $member[] = $member->getUser()->nick();
      }
      send($msg, implode(",", $member));
    }else{
       error($msg, "You are not member of the channel");
    }
}

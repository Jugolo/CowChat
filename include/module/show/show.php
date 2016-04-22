<?php

function show(MessageParser $message){
  $command = explode(" ", $message->message());
  if(count($command) == 0){
    error($message, "/show command has not enaoug data");
    return;
  }
  

  if($command[0] == "ip")
    if(!User::current()->showIp()){
      error($message, "Access denaid");
      return;
    }

    if(count($command) <= 1){
      error($message, "Missing agument for /show ip");
      return;
    }

    switch($command[1]){
       case "blacklisted":
         send($message, "SHOW: blacklist ".implode(",", FireWall::getBlacklist()));
       break;
       case "whitelisted":
         send($message, "SHOW: whitlist ".implode(",", FireWall::getWhiteList()));
       break;
       case "ban":
          send($message, "SHOW: ban ".implode(",", FireWall::getBans()));
       break;
       case "info":
          if(count($command) <= 2){
            error($message, "Missing info for /show info [ip]");
            return;
          }
          send($message, "SHOW: info ".showInfoBan(FireWall::getInfoBan($command[2])));
       break;
       deadult:
         error($message, "Unknown show aguments");
         return;
       break;
    }
  }
}

function showIpInfo(array $info)
  $return = [];

  foreach($info as $key => $value)
     $return[] = $key."=".$value;

  return implode(";", $return);
}

<?php

function nick_command(MessageParser $msg){
    if(!User::current()->nick($msg->message())){
       error($msg, "Nick is taken");
    }
}

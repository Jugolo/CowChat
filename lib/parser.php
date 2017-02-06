<?php
function is_number(int $ord) : bool{
  return $ord >= 48 && $ord <= 57;
}

function is_word(int $ord) : bool{
  return $ord >= 65 && $ord <= 90 || $ord >= 97 && $ord <= 122;
}

function parseChannelName(string $name) : bool{
  if($name[0] != "#"){
    return false;//channel name must contain # at the start
  }
  
  for($i=1;$i<strlen($name);$i++){
    if(!is_number(($ord = ord($name[$i]))) && !is_word($ord)){
      return false;
    }
  }
  return true;
}

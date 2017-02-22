<?php

function is_admin(int $id) : bool{
  return in_array($id, Config::get("serverAdmin"));
}

function disabledNick(string $nick) : bool{
  return in_array($nick, Config::get("disableNick"));
}

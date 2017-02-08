<?php

function is_admin(int $id) : bool{
  return in_array($id, Config::get("serverAdmin"));
}

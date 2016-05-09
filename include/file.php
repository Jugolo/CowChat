<?php

class Files{
  public static function exists($name){
     return @fopen($name, "r", true) == true;
  }

  public static function context($name){
    return file_get_contents($name, true);
  }
}

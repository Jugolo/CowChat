<?php

class Files{
  public static function exists($name){
     $fopen = @fopen($name, "r", true);
     $exists = $fopen == true;
     fclose($fopen);
     return $fopen;
  }

  public static function context($name){
    return file_get_contents($name, true);
  }
}

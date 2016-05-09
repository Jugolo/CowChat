<?php

class Files{
  public static function exists($name){
     return @fopen($name, "r", true) == true;
  }

  public static function context($name){
    return file_get_contents($name, true);
  }

  public static function isFile($name){
     return is_file(get_include_path().$name);
  }
}

class Dirs{
   public static function isDir($name){
      return is_dir(get_include_path().$name);
   }
}

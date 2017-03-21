<?php
namespace Lib;

class Random{
  public static function string(int $length) : string{
    $buffer = "";
    for($i=0;$i<$length;$i++){
      $buffer .= chr(mt_rand(33, 127));
    }
    return $buffer;
  }
}

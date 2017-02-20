<?php
class Tempelate{
  public function parse($file) : bool{
    if(!file_exists($file)){
      return true;
    }
    $source = file_get_contents($file);
    $preg = preg_match_all("/@-([a-z]*) (.*?[^-@])-@/", $source, $reg);
    for($i=0;$i<$preg;$i++){
      switch($reg[1][$i]){
          
      }
    }
    echo $source;
  }
}

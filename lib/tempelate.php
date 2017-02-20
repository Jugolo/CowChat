<?php
class Tempelate{
  private $path;
  
  public function path(string $dir){
    $this->path = $dir;
  }
  
  public function parse($file) : bool{
    if(!file_exists($file)){
      return false;
    }
    $source = file_get_contents($file);
    $preg = preg_match_all("/@-([a-z]*) (.*?[^-@])-@/", $source, $reg);
    for($i=0;$i<$preg;$i++){
      switch($reg[1][$i]){
          
      }
    }
    echo $source;
    return true;
  }
}

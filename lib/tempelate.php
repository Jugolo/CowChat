<?php
class Tempelate{
  private $path;
  
  public function path(string $dir){
    if(!preg_match("/\/$/", $dir)){
      $dir .= "/";
    }
    $this->path = $dir;
  }
  
  public function parse($file) : bool{
    if(!file_exists($this->dir.$file)){
      return false;
    }
    $source = file_get_contents($this->dir.$file);
    $preg = preg_match_all("/@-([a-z]*) (.*?[^-@])-@/", $source, $reg);
    for($i=0;$i<$preg;$i++){
      switch($reg[1][$i]){
          
      }
    }
    echo $source;
    return true;
  }
}

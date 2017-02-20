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
    if(!file_exists($this->path.$file)){
      return false;
    }
    $arg = $this->render(file_get_contents($this->path.$file));
    if(!$arg){
      return false;
    }
    echo $arg;
    return false;
  }
    
  private function render(string $source){
    $preg = preg_match_all("/@-([a-z]*) (.*?[^-@])-@/", $source, $reg);
    for($i=0;$i<$preg;$i++){
      switch($reg[1][$i]){
        case "include":
          $item = explode(".", $reg[2][$i]);
          $f = array_pop($item);
          $dir = $this->path;
          for($d=0;$d<count($item);$d++){
            if(trim($item[$d]) == "" || !file_exists($dir.$item[$d])){
              return false;
            }
            $dir .= $item[$d]."/";
          }
          
          if(!file_exists($dir.$f.".style")){
            return false;
          }
          $s = $this->render(file_get_contents($dir.$f.".style"));
          if(!$s){
            return false;
          }
          $source = str_replace($reg[0][$i], $s, $source);
          break;
      }
    }
    return $source;
  }
}

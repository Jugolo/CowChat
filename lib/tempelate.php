<?php
class Tempelate{
  private $path;
  private $lang = [];
  
  public function setLang(array $lang){
    $this->lang = $lang;
  }
  
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
    return true;
  }
    
  private function render(string $source){
    $preg = preg_match_all("/@-(@?[a-z]*) (.*?[^-@])-@/", $source, $reg);
    for($i=0;$i<$preg;$i++){
      switch($reg[1][$i]){
        case "echo":
        case "@echo":
          if($this->getExpresion($reg[2][$i], $expresion)){
             $source = str_replace($reg[0][$i], $reg[1][$i] == "echo" ? $expresion : htmlentities($expresion), $source);
          }else{
            return false;
          }
        case "lang":
          $lang = "";
          if(!empty($this->lang[$reg[2][$i]])){
            $lang = $this->lang[$reg[2][$i]];
          }
          $source = str_replace($reg[0][$i], $lang, $source);
        break;
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
  
  private function getExpresion($str, &$expresion){
  }
}

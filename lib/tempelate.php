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
    $i=0;
    $source = file_get_contents($this->path.$file);
    $arg = $this->render($source, $i);
    //controle if got to the end and the render not return true. true is return when @-end-@
    if(strlen($source)-1 > $i || $arg["type"] === "block"){
      return false;
    }
    echo $arg["source"];
    return true;
  }
    
  private function render(string $source, &$i){
    $buffer = "";
    for(;$i<strlen($source);$i++){
      if($source[$i] == "@" && $source[$i+1] == "-"){
        //wee has a block start here! 
        $i+=2;
        $block = $this->renderBlock($source, $i);
        if(!$block){
          return false;
        }
        if($block == "end"){
          return ["type" => "block", "source" => $buffer];
        }
        $pos = strpos($block, " ");
        switch($pos !== false ? substr($block, 0, $pos) : ""){
          case "lang":
            $code = trim(substr($block, $pos+1));
            $lang = "";
            if(!empty($this->lang[$code])){
              $lang = $this->lang[$code];
            }
            $buffer .= $this->lang[$code];
          break;
          case "include":
          $item = explode(".", substr($block, $pos+1));
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
          $b = 0;
          $s = $this->render(file_get_contents($dir.$f.".style"), $b);
          if(!$s || $s["type"] != "code"){
            return false;
          }
          $buffer .= $s["source"];
          break;
          case "foreach":
            $scope = substr($block, $pos+1);
            $b=0;
            $identify = $this->getIdentify(ltrim($scope), $b);
            if(!$identify){
              return false;
            }
            $this->removeJunk($scope, $b);
            if($scope[$b] != ":"){
              return false;
            }
            
            $arg = $this->expresion();
            if(!$arg || !is_array($arg)){
              return false;
            }
            
            $bl = $this->render($source, $i);
            if(!$bl || $bl["type"] != "block"){
              return false;
            }
          break;
        }
      }else{
        $buffer .= $source[$i];
      }
    }
    return ["type" => "code", "source" => $buffer];
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
  
  private function renderBlock($str, &$i){
    $buffer = "";
    for(;$i<strlen($str);$i++){
      if($str[$i] == "-" && $str[$i+1] == "@"){
        $i++;
        return $buffer;
      }
      $buffer .= $str[$i];
    }
    return false;
  }
}

<?php
class Tempelate{
  private $path;
  private $variabel = [];
  private $lang = [];
  
  public function putVariabel(string $name, $value){
    $this->variabel[$name] = $value;
  }
  
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
    if(strlen($source)-1 > $i || !$arg){
      return false;
    }
    eval("?> {$arg} <?php ");
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
          $buffer .= "<?php } ?>";
          continue;
        }elseif($block == "else"){
          $buffer .= "<?php }else{ ?>";
          continue;
        }
        $pos = strpos($block, " ");
        switch($pos !== false ? substr($block, 0, $pos) : ""){
          case "lang":
            $buffer .= "<?php echo \$this->getLang('".trim(substr($block, $pos+1))."'); ?>";
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
          if(!$s){
            return false;
          }
          $buffer .= $s;
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
            
            $b++;
            
            $arg = $this->getExpresion($scope, $b);
            if(!$arg){
              return false;
            }
            
            $buffer .= "<?php foreach(".$arg." as \$value){ \$this->variabel['".$identify."']=\$value; ?>";
          break;
          case "if":
            $b = 0;
            $buffer .= "<?php if(".$this->getExpresion(substr($block, $pos+1), $b)."){ ?>";
          break;
          case "elseif":
            $b = 0;
            $buffer .= "<?php }elseif(".$this->getExpresion(substr($block, $pos+1), $b)."){ ?>";
          break;
          case "echo":
            $b = 0;
            $buffer .= "<?php echo htmlentities(".$this->getExpresion(substr($block, $pos+1), $b)."); ?>";
          break;
          case "@echo":
            $b=0;
            $buffer .= "<?php echo ".$this->getExpresion(substr($block, $pos), $b)."; ?>";
          break;
        }
      }else{
        $buffer .= $source[$i];
      }
    }
    return $buffer;
  }
  
  public function getLang(string $name){
    return empty($this->lang[$name]) ? "" : $this->lang[$name];
  }
  
  private function isIdentify($str){
    return ($o=ord($str)) >= 97 && $o <= 122 || $o >= 65 && $o <= 90;
  }
 
  private function getIdentify($str, &$i){
    $buffer = "";
    for(;$i<strlen($str);$i++){
      if($this->isIdentify($str[$i])){
        $buffer .= $str[$i];
      }else{
        break;
      }
    }
    return $buffer;
  }
  
  private function getExpresion($str, &$i){
    $this->removeJunk($str, $i);
    return $this->expresion($str, $i);
  }
  
  private function expresion($str, &$i){
    $e = $this->primary($str, $i);
    if(!$e){
      return false;
    }
    switch(substr($str, $i, 2)){
      case "&&":
        $i += 2;
        $this->removeJunk($str, $i);
        $b = $this->primary($str, $i);
        if(!$b){
          return false;
        }
        return $e." && ".$b;
      case "||":
        $i += 2;
        $this->removeJunk($str, $i);
        $b = $this->primary($str, $i);
        if(!$b){
          return $e." || ".$b;
        }
    }
    return $e;
  }
  
  private function primary($str, &$i){
    if($this->isIdentify($str[$i])){
      $e = $this->getIdentify($str, $i);
      $this->removeJunk($str, $i);
      switch($e){
        case "true":
        case "false":
        case "null":
          return $e;
        case "not":
          return "!".$this->primary($str, $i);
        case "exist":
          return "(!empty(".$this->primary($str, $i)."))";
      }
      return "\$this->variabel['".$e."']";
    }elseif($str[$i] == "_" && $str[$i+1] == "_"){
      //wee got the name
      $i+=2;
      $identify = $this->getIdentify($str, $i);
      if(!$identify || $str[$i] != "_" || $str[$i+1] != "_"){
        return false;
      }
      $i += 2;
      $this->removeJunk($str, $i);
      switch($identify){
        case "DIR":
          return "'".$this->path."'";
        default:
          return false;
      }
    }
    
    return false;
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
  
  private function removeJunk($str, &$i){
    while($i<strlen($str) && ($str[$i] == " " || $str[$i] == "\r" || $str[$i] == "\n")){
      $i++;
    }
  }
}

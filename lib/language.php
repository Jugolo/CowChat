<?php
class Language{
  private static $langCode;
  private static $lang = [];
  
  public static function getCode(){
    return self::$langCode;
  }
  
  public static function init(){
    if(($cookie = Request::cookie("language")) && self::exists($cookie)){
      self::$langCode = $cookie;
    }elseif($name = self::detectLanguage()){
      self::$langCode = $name;
    }elseif(self::exists(Config::get("locale"))){
      self::$langCode = Config::get("locale");
    }else{
      exit("Could not find a language too you");
    }
  }
  
  public static function get(string $key) : string{
    if(empty(self::$lang[$key])){
      return "";
    }
    
    return self::$lang[$key];
  }
  
  public static function load(string $file) : bool{
    $path = "./locale/".self::$langCode."/".$file.".php";
    if(file_exists($path)){
      self::$lang = array_merge(self::$lang, $locale);
      return true;
    }
    return false;
  }
  
  private static function exists(string $name){
    return is_dir("./locale/".$name);
  }
  
  private static function detectLanguage(){
    $available_languages = array_flip(self::getLanguageList());

    $langs = [];
    preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]), $matches, PREG_SET_ORDER);
    foreach($matches as $match) {

        list($a, $b) = explode('-', $match[1]) + array('', '');
        $value = isset($match[2]) ? (float) $match[2] : 1.0;

        if(isset($available_languages[$match[1]])) {
            $langs[$match[1]] = $value;
            continue;
        }

        if(isset($available_languages[$a])) {
            $langs[$a] = $value - 0.1;
        }

    }
    arsort($langs);

    $langs = array_keys($langs);
    if(count($langs) == 0){
      return null;
    }
    
    return $langs[0];
  }
  
  private static function getLanguageList() : array{
    $ress = opendir("./locale");
    $buffer = [];
    while($name = readdir($ress)){
      if($name == "." && $name == ".." && is_dir("./locale/".$name)){
        $buffer[] = $name;
      }
    }
    
    return $buffer;
  }
}

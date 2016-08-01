<?php
namespace inc\tempelate\render;

use inc\error\HeigLevelError;
use inc\tempelate\parser\StringParser;
use inc\tempelate\tempelate\Tempelate;
use inc\file\Files;
use inc\tempelate\driver\TempelateDriver;
use inc\file\Dirs;

class Render{
	private static $context = "";
	private static $files = [];
	
	public static function reseat(){
		self::$context = "";
		self::$files = [];
	}
	
	public static function getFilesList() : array{
		return self::$files;
	}
	
	public static function push(string $str){
		self::$context .= $str;
	}
	
	public static function render(string $url, array $option) : string{
		self::$files[] = $url;
		$file = Files::context($url);
		$lines = 1;
		while(strlen($file) != 0){
			//wee split the file up in lines :)
			$line = substr($file, 0, ($pos = strpos($file, "\r\n")) !== false ? $pos : strlen($file));
			if(($pos = strpos($line, "<@--")) !== false){
				//wee get to the <@-- and put in context
				self::$context .= substr($line, 0, $pos);
				$line = substr($line, $pos+4);
				//wee know there is a cap betwen the tag and context
				if(strpos($line, "end--@>") === 0){
					$file = substr($file, $pos+11);
					self::$context .= '<?php } ?>';
					continue;
				}else{
					$tag = substr($line, 0, strpos($line, " "));
					if($tag === ""){
						throw new HeigLevelError("Missing tag in line ".$lines);
					}
				}
				//wee remove this from the line
				$file = substr($file, $pos+5+strlen($tag));
				//wee get to --@>
				if(($pos = strpos($file, "--@>")) === false){
					throw new HeigLevelError("Missing ---@> as line ".$lines);
				}
				$agument = substr($file, 0, $pos);
				if($tag === "INCLUDE"){
					self::includes($agument, $option);
				}else{
					self::render_block($tag, $agument, $option);
				}
				$file = substr($file, $pos+4);
			}else{
				self::$context .= $line;
				$file = substr($file, strlen($line)+1);
				$lines++;
			}
		}
		
		return self::$context;
	}
	
	public static function parseString(string $string) : string{
		$s = new StringParser($string);
		$context = "";
		while(($data = $s->context())[0] != "EOF"){
			$context .= self::next($data, $s);
		}
		
		return $context;
	}
	
	public static function getUrl(string $dir, array $option){
		$end = $option["in_js"] ? ".js" : ($option["in_css"] ? ".css" : ".inc");
		$clean = explode(".", $dir);
		if(array_key_exists("dir", $option)){
			$dir = $option["dir"];
			for($i=0;$i<count($clean);$i++){
				if(count($clean)-1 == $i){
					if(!file_exists($dir.$clean[$i].$end)){
						throw new HeigLevelError("Missing the include tempelate file", $dir.$clean[$i].$end);
					}
					$dir .= $clean[$i].$end;
				}else{
					if(!is_dir($dir.$clean[$i])){
						throw new HeigLevelError("Missing tempelate dir", $dir.$clean[$i]);
					}
					$dir .= $clean[$i]."/";
				}
			}
				
			return Dirs::getDir().$dir;
		}else{
			return Dirs::getDir().explode("/", $clean).".inc";
		}
	}
	
	private static function includes(string $dir, array $option){
		self::render(self::getUrl($dir, $option), $option);
	}
	
	private static function next(array $data, StringParser $s){
		switch($data[0]){
			case "variabel":
				if($s->getReader()->current() == 40){//function
					$s->context();
					return "call_user_func_array(\$this->database->controled_callable('".$data[1]."'), [".self::get_aguments($s)."])";
				}elseif($s->getReader()->current() == 46){//object
					$s->context();
					return  self::handle_object($s, $data[1]);
				}elseif($s->getReader()->current() == 91){//array
					$s->context();
					$use = "\$this->database->controled_array_get('".$data[1]."', ".self::next($s->context(), $s).")";
					if($s->getReader()->current() !== 93){
						throw new HeigLevelError("Missing ] in end of array get");
					}
					$s->context();
					return $use;
				}else{
					return '$this->database->controled_get("'.$data[1].'")';
				}
			case "string":
				return "'".htmlentities($data[1])."'";
			case "int":
				return $data[1];
			default:
				throw new HeigLevelError("Unknown token: ".$data[0]);
		}
	}
	
	private static function handle_object(StringParser $parser, string $obj_name){
		$first = $parser->context();
		if($first[0] != "variabel"){
			throw new HeigLevelError("After . there must be a variabel!", $first[1]);
		}
		
		//okay let try to detext if next is (
		if($parser->getReader()->current() == 40){
			$parser->context();
			return "call_user_func_array([\$this->database->controled_object('".$obj_name."'), '".$first[1]."'], ".self::get_aguments($parser).")";
		}else{
			exit($parser->getReader()->current()."<-");
		}
	}
	
	private static function get_aguments(StringParser $parser){
		$key = $parser->getReader()->getKey();
		if($parser->context()[0] !== "right_bue"){
			$buffer = [];
			$parser->getReader()->toKey($key);
			$buffer[] = self::next($parser->context(), $parser);
			while($parser->context()[0] === "comma"){
				$buffer[] = self::next($parser->context(), $parser);
			}
			return implode(", ", $buffer);
		}else{
			return "[]";
		}
	}
	
	private static function render_block(string $key, string $value, array $options){
		$drivers = TempelateDriver::getInstance();
		$key = strtolower($key);
		if(!$drivers->exists($key)){
			throw new HeigLevelError("Unknown prefix ".$key."[".(ord($key))."]", $key);
		}
		$driver = $drivers->getDriver($key);
		if($driver->allow_php_tag()){
			self::$context .= "<?php ".$driver->render($value, $options)." ?>";
		}else{
			self::$context .= $driver->render($value, $options);
		}
	}
}
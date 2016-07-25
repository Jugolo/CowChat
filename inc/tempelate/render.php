<?php
namespace inc\tempelate\render;

use inc\tempelate\driver\TempelateDriver;
use inc\error\HeigLevelError;
use inc\tempelate\parser\StringParser;
use inc\tempelate\tempelate\Tempelate;

class Render{
	private $matchs;
	private $counts;
	private $context;
	
	private $driver;
	private $tempelate;
	
	public function __construct(string $context, Tempelate $tempelate){
		$this->context = $context;
		$this->counts = preg_match_all("/<\@--([A-Z]*) (.*?)--\@>/", $context, $this->matchs);
		$this->driver = TempelateDriver::getInstance();
		$this->tempelate = $tempelate;
	}
	
	public function getTempeleate() : Tempelate{
		return $this->tempelate;
	}
	
	public function render(){
		for($i=0;$i<$this->counts;$i++){
			$this->context = str_replace($this->matchs[0][$i], $this->render_block($this->matchs[1][$i], $this->matchs[2][$i]), $this->context);
		}
		$this->context = str_replace("<@--end--@>", "<?php } ?>", $this->context);
	}
	
	public function getContext() : string{
		return $this->context;
	}
	
	public function parseString(string $string) : string{
		$s = new StringParser($string);
		$context = "";
		while(($data = $s->context())[0] != "EOF"){
			$context .= $this->next($data, $s);
		}
		
		return $context;
	}
	
	private function next(array $data, StringParser $s){
		switch($data[0]){
			case "variabel":
				if($s->getReader()->current() == 40){
					$s->context();
					return "call_user_func_array(\$this->database->controled_callable('".$data[1]."'), [".$this->get_aguments($s)."])";
				}elseif($s->getReader()->current() == 46){
					$s->context();
					return  $this->handle_object($s, $data[1]);
				}else{
					return '$this->database->controled_get("'.$data[1].'")';
				}
				break;
			case "string":
				return "'".htmlentities($data[1])."'";
				break;
		}
	}
	
	private function handle_object(StringParser $parser, string $obj_name){
		$first = $parser->context();
		if($first[0] != "variabel"){
			throw new HeigLevelError("After . there must be a variabel!", $first[1]);
		}
		
		//okay let try to detext if next is (
		if($parser->getReader()->current() == 40){
			$parser->context();
			return "call_user_func_array([\$this->database->controled_object('".$obj_name."'), '".$first[1]."'], ".$this->get_aguments($parser).")";
		}else{
			exit($parser->getReader()->current()."<-");
		}
	}
	
	private function get_aguments(StringParser $parser){
		$key = $parser->getReader()->getKey();
		if($parser->context()[0] !== "right_bue"){
			$buffer = [];
			$parser->getReader()->toKey($key);
			$buffer[] = $this->next($parser->context(), $parser);
			while($parser->context()[0] === "comma"){
				$buffer[] = $this->next($parser->context(), $parser);
			}
			return implode(", ", $buffer);
		}else{
			return "[]";
		}
	}
	
	private function render_block(string $key, string $value) : string{
		$key = strtolower($key);
		if(!$this->driver->exists($key)){
			throw new HeigLevelError("Unknown prefix", $key);
		}
		$driver = $this->driver->getDriver($key);
		if($driver->allow_php_tag()){
			return "<?php ".$driver->render($value, $this)." ?>";
		}else{
			return $driver->render($value, $this);
		}
	}
}
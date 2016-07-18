<?php
namespace inc\tempelate\render;

use inc\tempelate\driver\TempelateDriver;
use inc\error\HeigLevelError;
use inc\tempelate\parser\StringParser;

class Render{
	private $matchs;
	private $counts;
	private $context;
	
	private $driver;
	
	public function __construct(string $context){
		$this->context = $context;
		$this->counts = preg_match_all("/<\@--([A-Z]*) (.*?)--\@>/", $context, $this->matchs);
		$this->driver = TempelateDriver::getInstance();
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
			switch($data[0]){
				case "variabel":
					$context .= '$this->database->controled_get("'.$data[1].'")';
			}
		}
		
		return $context;
	}
	
	private function render_block(string $key, string $value) : string{
		$key = strtolower($key);
		if(!$this->driver->exists($key)){
			throw new HeigLevelError("Unknown prefix", $key);
		}
		
		return "<?php ".$this->driver->getDriver($key)->render($value, $this)." ?>";
	}
}
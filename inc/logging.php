<?php
namespace inc\logging;

use inc\error\HeigLevelError;
use inc\file\Dirs;

class Logging{
	
	/**
	 * a buffer for the diffrence type of logging
	 * @var array Logging
	 */
	private static $object = [];
	
	/**
	 * Get a object for log evetent.
	 * @param string $type the type of log it is (exempel "debug")
	 * @return Logging
	 */
	public static function getInstance(string $type) : Logging{
		if(!array_key_exists($type, self::$object)){
			self::$object[$type] = new Logging($type);
		}
		
		return self::$object[$type];
	}
	
	/**
	 * cache the type
	 * @var string
	 */
	private $type;
	
	/**
	 * A cache for stream to write and read the file
	 * @var stream 
	 */
	private $stream;
	
	public function __construct(string $type){
		$this->type = $type;
		if(($this->stream = @fopen(($filename = Dirs::getDir()."inc/logging/".$this->type.".log"), "c+")) === false){
			throw new HeigLevelError("Could not open log file", $filename);
		}
		//wee look after date and month and year
		$date = "---------------".date("d:m:Y")."---------------";
		//wee a now looking after the string
		if(($filesize = filesize($filename)) <= 0 || strpos(fread($this->stream, $filesize), $date) === false){
			//okay no date is addedd let us try it now
			fwrite($this->stream, $date."\r\n");
		}
	}
	
	/**
	 * Push a line to logging
	 * @param string $line
	 * @return bool
	 */
	public function push(string $line) : bool{
		return fwrite($this->stream, "[".$this->getTime()."]".$line."\r\n") !== false;
	}
	
	/**
	 * Get the time to append in the log
	 * @return string
	 */
	private function getTime() : string{
		return date("H:i:s");
	}
}
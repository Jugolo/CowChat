<?php
namespace inc\debug;

use inc\logging\Logging;
use inc\error\LowLevelError;

class Debug{
	public static function debug(string $line) : bool{
		if(defined("DEBUG")){
			if(!Logging::getInstance("debug")->push($line)){
				throw new LowLevelError("Failed to debug line", $line);
			}
			return true;
		}
		
		return false;
	}
}
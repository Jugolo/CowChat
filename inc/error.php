<?php
namespace inc\error;

use inc\logging\Logging;

/**
 * Class to tell the system at there happens a error but it not a heigh or medium error
 * @author CowScript
 *
 */
class LowLevelError extends \Exception{
	/**
	 * To offer a option to tell there is extra information
	 * @var string
	 */
	private $extra = null;
	
	public function __construct(string $message, $extra = null){
		parent::__construct($message);
		$this->extra = $extra;
	}
	
	/**
	 * Tell if there is extra information
	 * @return bool
	 */
	public function isExtrea() : bool{
		return $this->extra != null;
	}
	
	/**
	 * Get extra information about the error
	 * @return string
	 */
	public function getExtra() : string{
		return $this->extra ? : "";
	}
}

class MidleLevelError extends \Exception{
	
}

/**
 * Class to tell the system to shout down and show a error message
 * @author CowScript
 */
class HeigLevelError extends \Exception{
	/**
	 * To offer a option to tell there is extra information
	 * @var string
	 */
	private $extra = null;
	
	public function __construct(string $message, $extra = null){
		parent::__construct($message);
		$this->extra = $extra;
	}
	
	/**
	 * Tell if there is extra information
	 * @return bool
	 */
	public function isExtrea() : bool{
		return $this->extra != null;
	}
	
	/**
	 * Get extra information about the error
	 * @return string
	 */
	public function getExtra() : string{
		return $this->extra ? : "";
	}
}

class ErrorHandler{
	public static function set(){
		//try catch exception
		set_exception_handler(array(__CLASS__, "exception_catch"));
	}
	
	public static function exception_catch($exception){
		if($exception instanceof LowLevelError){
			$cache = "----new low level exception----\r\nStatus : not catch\r\nIn file: ".$exception->getFile()."\r\nIn line: ".$exception->getLine()."\r\nMessage: ".$exception->getMessage()."\r\nExtra  : ".($exception->isExtrea() ? $exception->getExtra() : "No extra data is given")."\r\n--------------------------------";
			Logging::getInstance("low_level_error")->push($cache);
			exit("Low level error is not catch. please look in log file to see details");
		}else if($exception instanceof  HeigLevelError){
			$cache = "----new heigh level exception----\r\nIt is importent you find this error and get it fix!!!!\r\nStatus : not catch\r\nIn file: ".$exception->getFile()."\r\nIn line: ".$exception->getLine()."\r\nMessage: ".$exception->getMessage()."\r\nExtra  : ".($exception->isExtrea() ? $exception->getExtra() : "No extra data is given")."\r\n--------------------------------";
			Logging::getInstance("heigh_level_error")->push($cache);
			exit("Height level error is not catch. please look in log file to see details");
		}else if($exception instanceof \Error){
			$cache = "----new Error----\r\nIt is importent you find this error and get it fix!!!!\r\nStatus : not catch\r\nIn file: ". $exception->getFile()."\r\nIn line: ".$exception->getLine()."\r\nMessage: ".$exception->getMessage()."\r\n--------------------------------";
			Logging::getInstance("error")->push($cache);
			exit("Error happens. Please look in log file to see details");
		}else if($exception instanceof \Twig_Error_Loader || $exception instanceof \Twig_Error_Syntax){
			Logging::getInstance("twig_error")->push("It is importent you find this error and get it fix!!!!\r\nStatus : not catch\r\nIn file: ".$exception->getFile()."\r\nIn line: ".$exception->getLine()."\r\nMessage: ".$exception->getMessage()."\r\n---------------------------------");
			exit("Twig error happens. Please look in log file to see details");
		}else{
			exit(get_class($exception));
		}
	}
}
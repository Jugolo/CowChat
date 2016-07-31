<?php
namespace inc\driver\authentication\auth_drivers;

use inc\file\Dirs;
use inc\file\Files;
use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\error\HeigLevelError;
use inc\exception\AuthDriverNotFound\AuthDriverNotFound;

class AuthDrivers{
	private $list = [];
	public function __construct(){
		$dir = Dirs::openDir("inc/driver/authentication/");
		while($name = readdir($dir)){
			if($name == "." || $name == ".."){
				continue;
			}
			$item = Dirs::getDir()."inc/driver/authentication/".$name."/";
			if(is_dir($item)){
				$this->controle($item, $name);
			}
		}
	}
	
	public function getDriver(string $name) : AuthenticationDriverInterface{
		foreach($this->list as $item){
			if($item[0] == $name)
				return $item[1];
		}
		
		throw new AuthDriverNotFound();
	}
	
	public function count() : int{
		return count($this->list);
	}
	
	public function get(int $i) : array{
		return $this->list[$i];
	}
	
	public function toArray() : array{
		return $this->list;
	}
	
	private function controle(string $dir, string $name) : bool{
		if(!Files::exists($dir.$name.".php")){
			return false;
		}
		
		$className = str_replace("/", "\\", $dir.$name)."\\AuthenticationDriver";
		
		
		$obj = new $className();
		if($obj instanceof AuthenticationDriverInterface){
			if($obj->enabled()){
			$this->list[] = [$name, $obj];
			return true;
			}
		}else{
			throw new HeigLevelError("A auth driver do not implements AuthenticationDriverInterface");
		}
		return false;
	}
}
<?php
namespace inc\driver\dir;
use inc\file\Dirs;
use inc\error\LowLevelError;
use inc\driver\file\FileDriver;
use inc\interfaces\driver_file\DriverFileItem;

class DriverDir implements \Iterator{
	private $dir = [];
	private $index;
	
	public function __construct(string $dir){
		$dir = "inc/driver/".$dir;
		if(!Dirs::isDir($dir)){
			throw new LowLevelError("Could not find the dir", $dir);
		}
		
		$open = opendir($dir);
		while($item = readdir($open)){
			if($item == "." || $item == ".."){
				continue;
			}
			
			$path = $dir."/".$item;
			$this->dir[] = is_file($path) ? new FileDriver($path) : new DirDriver($path);
		}
		closedir($open);
	}
	
	/**
	 * Return a instanceof DriverFileItem
	 * @return DriverFileItem
	 */
	public function current(){
		return $this->dir[$this->index];
	}
	
	public function key(){
		exit(__METHOD__);
	}
	
	public function next(){
		$this->index++;
	}
	
	public function rewind(){
		$this->index = 0;
	}
	
	public function valid() : bool{
		return count($this->dir) > $this->index;
	}
}

class DirDriver implements DriverFileItem{
	private $path;
	public function __construct(string $path){
		$this->path = $path;
	}
	
	public function isFile() : bool{
		return false;
	}
	
	public function getItemName() : string{
		return basename($this->path);
	}
}
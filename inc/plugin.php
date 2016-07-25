<?php
namespace inc\plugin;

use inc\database\Database;
use inc\temp\Temp;
use inc\interfaces\plugin\PluginInterface;
use inc\error\HeigLevelError;

class Plugin{
	public static function getInstance(){
		static $obj = null;
		
		if($obj === null){
			$obj = new Plugin();
		}
		
		return $obj;
	}
	
	private $buffer = [
			"event"   => [],
			"trigger" => [],
	];
	
	private $cache = [];
	
	public function __construct(){
		$this->buffer = $this->getList();
	}
	
	public function event(string $name, array $aguement){
		if(array_key_exists($name, $this->buffer["event"])){
			foreach($this->buffer["event"][$name] as $namespace){
				$this->callEvent($name, $namespace, $aguement);
			}
		}
	}
	
	private function callEvent(string $name, string $namespace, array $agument){
		$obj = $this->getObject($namespace);
		$list = $obj->load();
		if(!array_key_exists($name, $list)){
			$this->removePlugin($namespace);
			throw new HeigLevelError("A plugin has not a event", $name);
		}
		
		call_user_func_array($list[$name], $agument);
	}
	
	private function getObject(string $namespace) : PluginInterface{
		if(array_key_exists($namespace, $this->cache)){
			return $this->cache[$namespace];
		}
		
		$obj = new $namespace();
		if(!($obj instanceof PluginInterface)){
			$this->removePlugin($namespace);
			throw new HeigLevelError("Unknown plugin namespace", $namespace);
		}
		return $this->cache[$namespace] = $obj;
	}
	
	private function removePlugin(string $namespace){
		if(!Temp::remove("plugin")){
			throw new HeigLevelError("Could not remove temp file for plugin");
		}
		
		$database = Database::getInstance();
		$database->query("DELETE FROM ".table("events")." WHERE `type`='plugin' AND `namespace`=".$database->clean($namespace));
	}
	
	private function getList() : array{
		if(Temp::exists("plugin")){
			return json_decode(Temp::get("plugin"), true);
		}else{
			$query = Database::getInstance()->query("SELECT `namespace`, `name`, `caller` FROM ".table("events")." WHERE `type`='plugin' AND (`caller`='event' OR `caller`='trigger')");
			$buffer = [
					"event"   => [],
					"trigger" => [],
			];
			while($row = $query->fetch()){
				if(!array_key_exists($row["name"], $buffer[$row["caller"]])){
					$buffer[$row["caller"]][$row["name"]] = [];
				}
				
				$buffer[$row["caller"]][$row["name"]][] = $row["namespace"];
			}
			
			Temp::create("plugin", json_encode($buffer));
			return $buffer;
		}
	}
}
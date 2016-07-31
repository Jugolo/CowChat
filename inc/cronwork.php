<?php
namespace inc\cronwork;


use inc\database\Database;

class CronWork{
	public static function render(){
		$sql = "SELECT `id`, `namespace`, `next_updatet` FROM ".table("cronwork");
		if(!defined("CRONWORK")){
			$sql .= " WHERE `next_updatet`<'".time()."'";
		}
		$query = Database::getInstance()->query($sql);
		while($row = $query->fetch()){
			self::handle_cronwork($row);
		}
	}
	
	private static function handle_cronwork(array $data){
		$class = $data["namespace"]."CronWorker";
		$obj = new $class();
		$obj->render();
		Database::getInstance()->query("UPDATE ".table("cronwork")." SET `last_updatet`='".time()."', `next_updatet`='".(time() + ($obj->updateInterval()*60))."' WHERE `id`='".$data["id"]."'");
	}
}
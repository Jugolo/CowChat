<?php
class Flood{
	private static $data;
	
	public static function controle(ChannelData $data){
		//remove old flood item
		Database::query("DELETE FROM ".table("flood")." WHERE `time`<'".(time()-300)."'");//5 min
		//insert a new item in the database
		Database::insert("flood", [
				'uid'  => User::current()->id(),
				'cid'  => $data->id(),
				'time' => time(),
		]);
		//get all nodes from the flood
		$query = Database::query("SELECT `id` FROM ".table("flood")." WHERE `uid`='".User::current()->id()."' AND `cid`='".$data->id()."'");
		$count = $query->rows()-1;//dont count this
		if($count < 5){
			return true;
		}
		
		if($count > 10){
			Defender::updateCount(-0.15);
		}
		
		return false;
	}
}

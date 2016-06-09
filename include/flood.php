<?php
class Flood{
	private static $data;
	public static function controle(ChannelData $data){
		// remove old flood item
		Database::query("DELETE FROM " . table("flood") . " WHERE `time`<'" . (time() - (60 * 5)) . "'"); // 5 min
		                                                                                      // insert a new item in the database
		Database::insert("flood", [
				'uid' => User::current()->id(),
				'cid' => $data->id(),
				'time' => time()
		]);
		// get all nodes from the flood
		$query = Database::query("SELECT `id` FROM " . table("flood") . " WHERE `uid`='" . User::current()->id() . "' AND `cid`='" . $data->id() . "'");
		$count = $query->rows() - 1; // dont count this
		if($count < 20){
			return true;
		}
		
		if($count > 25){
			Defender::updateCount(-0.15);
		}
		
		return false;
	}
}

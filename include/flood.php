<?php
class Flood{
	private static $data;
	
	public static function controle(ChannelData $data){
		$query = Database::query("SELECT `count`, `id` FROM ".table("flood")." WHERE `uid`='".User::current()->id()."' AND `cid`='".$data->id()."'");
		if($query->rows() == 0){
			Database::insert("flood", [
					'uid' => User::current()->id(),
					'cid' => $data->id(),
			]);
			return 1;
		}
		$row = $query->fetch();
		Database::query("UPDATE ".table("flood")." SET `count`=count+1 WHERE `id`=".Database::qlean($row["id"]));
		return $row["count"]+1;
	}
}

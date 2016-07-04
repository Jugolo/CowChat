<?php
function config_command(MessageParser $msg){
	if(!allowChangeConfig()){
		Defender::updateCount(-0.15);
		error($msg, "Access denaid");
		return;
	}
	
	$command = explode(" ", $msg->message());
	if(count($command) != 2){
		Defender::updateCount(-0.001);
		error($msg, "Missing agument");
		return;
	}
	
	if(!Setting::exists($command[0])){
		Defender::updateCount(-0.001);
		error($msg, "Unknown config key");
		return;
	}
	
	Setting::push($command[0], $command[1]);
	send($msg, "CONFIG: success");
}
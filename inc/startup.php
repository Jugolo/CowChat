<?php
use inc\error\ErrorHandler;
use inc\system\System;
use inc\head\Head;
use inc\firewall\FireWall;
use inc\plugin\Plugin;
use inc\cronwork\CronWork;
use inc\shoutdown\ShoutDown;

/*
 * This file set up the system so it ready to be use. 
 * Please be notify it change the include path to the chat root!
 */
define("CHAT_VERSION", "V0.2B1");
define("CHAT_PATH", dirname(__FILE__, 2));
if(set_include_path(CHAT_PATH) === false){
	exit("Failed to set the include path.");
}

/**
 * Set up the auto loader
 */
include 'inc/autoloader.php';
AutoLoader::set();
AutoLoader::loadOnce("inc/func.php");
ShoutDown::init();

/**
 * Setup the error handler
 */
ErrorHandler::set();

//let see if the user is banned
if(!System::is_cli()){
	if(FireWall::isBlacklist(ip())){
		exit("You ip is denid access to this website. Contact our admin for explaining of whay you not has access to this site");
	}
	
	Head::sendNoCache();
}else{
	exit("WebSocket is not supportet yet");
}

//at last we call event
Plugin::getInstance()->event("system.start", []);

//wee render cron to see if this is needed
CronWork::render();
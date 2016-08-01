<?php
use inc\system\System;
use inc\head\Head;
use inc\language\Language;
use inc\driver\authentication\auth_drivers\AuthDrivers;
use inc\tempelate\tempelate\Tempelate;
use inc\html\Html;
use inc\user\User;
use inc\user\update\UserUpdate;
use inc\authentication\driver\AuthenticationDriver;
use inc\plugin\Plugin;
use inc\module\Module;
use inc\database\Database;
use inc\channel\helper\ChannelHelper;
use inc\channel\channel_user\ChannelUser;
use inc\setting\Setting;
use inc\smylie\Smylie;
include 'inc/startup.php';

$data = login();

if(Head::get("language")){
	Language::show_page();
}
if(Head::get("logout")){
	AuthenticationDriver::getCurrentDriver()->logout();
	Head::cookieDestroy("login_driver");
	header("location:index.php");
	exit();
}
if(Head::get("ajax")){
	Plugin::getInstance()->event("ajax.start", []);
	
	if(Head::post("request")){
		$line = explode("\r\n", Head::post("request"));
		foreach($line as $request){
			Module::handleRequest($request, $data);
		}
	}
	
	// wee show the diffrence message here.
	$database = Database::getInstance();
	
	$query = $database->query("SELECT m.message AS msg, m.id
				                   FROM " . table("message") . " AS m
				                   LEFT JOIN " . table("channel_member") . " AS cm ON m.cid=cm.cid
				                   WHERE m.id>'" . $data->getLastId() . "'
				                   AND m.uid='" . $data->getUserId() . "'");
	while($row = $query->fetch()){
		echo $row["msg"] . "\r\n";
	}
	
	Plugin::getInstance()->event("ajax.end", []);
}else{
	$tempelate = new Tempelate([
			"dir" => "inc/style",
			"cache" => true
	]);
	
	$buffer = [];
	foreach(ChannelHelper::getUsersChannel($data) as $chan){
		$buffer[] = $chan->getName();
	}
	
	// controle if the channel buffer is empty
	if(count($buffer) === 0){
		// join the start channel
		ChannelUser::join($data, ChannelHelper::getChannel(($buffer[] = Setting::get("startChannel")), $data), null);
	}
	
	$tempelate->add_var_array([
			"username" => $data->getUsername(),
			"nick" => $data->getNick(),
			"email" => $data->getEmail(),
			"channels" => $buffer,
			"smylie" => Smylie::getList()
	]);
	$tempelate->exec("chat");
}
exit();

if(auto_login()){
	if(Head::get("logout")){
		AuthenticationDriver::getCurrentDriver()->logout();
		Head::cookieDestroy("login_driver");
		header("location:index.php");
		exit();
	}
	if(System::is_ajax()){
	}elseif(Head::get("profile")){
		UserUpdate::update();
	}elseif(Head::get("language")){
		Language::show_page();
	}else{
		// this is a chat page.
		$tempelate = new Tempelate([
				"dir" => "inc/style",
				"cache" => true
		]);
		$tempelate->add_var_array([
				"username" => User::getStack()->current()->getUsername(),
				"nick" => User::getStack()->current()->getNick(),
				"email" => User::getStack()->current()->getEmail(),
				"smylie" => Smylie::getList()
		]);
		$tempelate->exec("chat");
	}
}else{
	if(!Head::cookie("login_driver")){
		// get the list of the drivers so wee can show the driver for the user
		$auth = new AuthDrivers();
		if($auth->count() == 0){
			exit("The system missing auth driver. Please install some");
		}else if($auth->count() == 1){
			Head::make_cookie("login_driver", $auth->get(0)[0]);
		}else{
			if(Head::get("auth_method")){
				try{
					if($auth->getDriver(Head::get("auth_method"))->enabled()){
						Head::make_cookie("login_driver", Head::get("auth_method"));
						login();
						exit();
					}else{
						Html::error(Language::get_sprintf("Unknown auth driver %s", Head::get("auth_method")));
					}
				}catch(\inc\exception\AuthDriverNotFound\AuthDriverNotFound $e){
					Html::error(Language::get_sprintf("Unknown auth driver %s", Head::get("auth_method")));
				}
			}
			$tempelate = new Tempelate([
					"dir" => "inc/style/",
					"cache" => true
			]);
			$tempelate->add_var("drivers", $auth->toArray());
			$tempelate->exec("auth_chose");
			exit();
		}
	}
	
	if(login()){
		header("location:index.php");
		exit();
	}
}
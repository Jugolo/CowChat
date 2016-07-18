<?php

namespace inc\system;

use inc\firewall\FireWall;
use inc\file\Files;
use inc\database\Database;
use inc\setting\Setting;
use inc\channel\Channel;
use inc\head\Head;
use inc\user\User;
use inc\language\Language;
use inc\html\Html;
use inc\messageparser\MessageParser;
use inc\access\Access;
use inc\module\Module;
use inc\command\Command;
use inc\debug\Debug;
use inc\flood\Flood;
use inc\error\ErrorHandler;
use inc\authentication\Authentication;
use inc\error\LowLevelError;
use inc\driver\dir\DriverDir;
use inc\authentication\driver\AuthenticationDriver;
use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\tempelate\tempelate\Tempelate;

class System{
	private $clients = [];
	public static function is_cli(){
		return php_sapi_name() == "cli";
	}
	public static function getLastId(){
		return Database::getInstance()->query("SELECT `id` FROM " . table("message") . " ORDER BY `id` DESC")->fetch()["id"] ?: 0;
	}
	function inilize(){
		Debug::debug("Start system");
		ErrorHandler::set();
		if(!System::is_cli() && FireWall::isBlacklist(ip())){
			exit("You ip is denid access to this website. Contact our admin for explaining of whay you not has access to this site");
		}
		
		// send header if this is a ajax server
		if(!System::is_cli()){
			header("Expires: Mon, 26 Jul 12012 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}else{
			exit("WebSocket should work in V0.1 but fails do it not will work in V0.1!\r\nPlease go to our github and make a pull request to get it work");
			$this->updateTitle();
		}
		
		if(!defined("IN_SETUP") && Files::exists("setup/info.json")){
			new \inc\setup\Setup();
		}
		
		Setting::init();
		FireWall::init();
		Channel::init();
		if(!System::is_cli()){
			// wee has controled that the user is not in black list. Now wee see if the user has a temporary ban
			if(FireWall::isBan()){
				if(get("ajax")){
					exit("LOGIN: REQUID");
				}
				$data = FireWall::getInfoBan(ip());
				exit("You are banet to: " . date("H:i:s d-m-Y", $data["expired"]));
			}
			
			// if this is not a ajax wee do a user and channel clean now
			if(!Head::get("ajax")){
				Channel::garbage_collect();
			}
			
			if($this->userInit()){
				if(Head::get("ajax")){
					// if there is post available handle the post
					if(Head::post("message")){
						$this->handlePost(explode("\r\n", Head::post("message")));
					}
					
					$this->showMessage();
				}else{
					$this->showChat();
				}
			}
		}else{
			Channel::init();
			$this->init_websocket();
		}
		Debug::debug("System closed");
	}
	private function init_websocket(){
		include "include/websocket.php";
		Console::writeLine("Welcommen to CowChat version: " . CHAT_VERSION);
		Console::writeLine("Wee need to set up host and port");
		Console::write("Host: ");
		$host = Console::readLine();
		Console::write("Port: ");
		$port = Console::readLine();
		Console::writeLine("Starting the server");
		if(!function_exists("socket_create")){
			exit("Missing socket create!");
		}
		
		Console::write("Create socket: ");
		if(($master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false){
			Console::writeLine("failed");
			exit();
		}
		Console::writeLine("success");
		Console::write("Configuere the socket: ");
		if(socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1) === false){
			Console::writeLine("failed");
			exit();
		}
		Console::writeLine("success");
		
		if(!filter_var($host, FILTER_VALIDATE_IP)){
			Console::write("Convert '" . $host . "' to '");
			$host = gethostbyname($host);
			Console::writeLine($host . "'");
		}
		
		Console::write("Bind host and port to socket: ");
		if(@socket_bind($master, $host, $port) === false){
			Console::writeLine("failed");
			exit();
		}
		Console::writeLine("success");
		Console::write("Begin to listen after connections: ");
		if(socket_listen($master, 20) === false){
			Console::writeLine("failed");
			exit("Fail to listen socket");
		}
		Console::writeLine("success");
		
		$this->clients[] = $master;
		Console::writeLine("Server is startet.");
		Console::writeLine("Create a file to tell the client it is a webscoket connection");
		Console::writeLine("When server close please delete '[root]include/websocket.json' to allow Ajax call");
		Files::create("include/websocket.json", json_encode([
				'host' => $host,
				'port' => $port
		]));
		Console::writeLine("Wee listen now after active connections");
		while(true){
			$write = $ex = null;
			$connections = $this->clients;
			@socket_select($connections, $write, $ex, null);
			
			foreach($connections as $socket){
				if($socket == $master){
					$client = socket_accept($socket);
					if($client < 0){
						Console::writeLine("failed to accept a connection");
					}else{
						if($this->handle_new_connect($client)){
							$this->clients[] = $client;
						}
					}
					continue;
				}
				
				$recv = @socket_recv($socket, $buf, 2048, 0);
				if($recv === false || $recv == 0){
					$this->remove_client($socket);
					continue;
				}
				
				$message = unmask($buf);
				if(!$message){
					continue;
				}
				
				$this->handlePost($message);
			}
		}
	}
	private function remove_client($socket){
		Console::writeLine("A client disconnected");
		array_splice($this->clients, array_search($socket, $this->clients), 1);
		$this->updateTitle();
	}
	private function handle_new_connect($new){
		$head = array();
		// handshake :)
		$lines = explode("\r\n", socket_read($new, 1024));
		for($i = 0;$i < count($lines);$i++){
			$line = trim($lines[$i]);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
				$head[$matches[1]] = $matches[2];
			}else if(strpos($line, "GET") === 0){
				$head["Cookie"] = $this->getTokenWS($line);
			}
		}
		
		if(empty($head['Sec-WebSocket-Key'])){
			$this->remove_client($new);
			echo "Missing Sec-WebSocket-Key\r\n";
			return false;
		}
		
		setSocketCookie($head["Cookie"]);
		
		// to accsess websocket connection wee need to be login,
		if(!$this->login($new)){
			// the user has not login yet close the connection now
			socket_close($new);
			Console::writeLine("User open a connection without has login yet.");
			return false;
		}
		Console::writeLine("Client login");
		User::current()->websocket($new);
		
		if(Firewall::isBlacklist(ip()) || Firewall::isBan()){
			User::current()->websocket(null);
			Console::writeLine("Client is blacklisted");
			// this socket connection is bannet. Wee close it and return false
			socket_close($new);
			return false;
		}
		Console::writeLine("Client is not blacklisted");
		$key = $head['Sec-WebSocket-Key'];
		$hkey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		
		$uhead = array();
		
		$uhead[] = "HTTP/1.1 101 Web Socket Protocol Handshake";
		$uhead[] = "Upgrade: websocket";
		$uhead[] = "Connection: Upgrade";
		$uhead[] = "Sec-WebSocket-Accept: " . $hkey;
		$uhead[] = "Server-dec: WevSocket for CowChat. Search goolt";
		
		$handshake = implode("\r\n", $uhead) . "\r\n\r\n";
		
		if(socket_write($new, $handshake) === false){
			User::current()->websocket(null);
			Console::writeLine("Send handshake to client failed");
			return false;
		}
		echo "New client connected to server\r\n";
		$this->updateTitle();
		return true;
	}
	private function getTokenWS($str){
		return urldecode(substr($str, 6, strpos(" ", $str) - 4));
	}
	private function add_socket_client($client){
		$this->clients[] = $client;
	}
	private function updateTitle(){
		Console::title("CowScript " . CHAT_VERSION . " Online user: " . count($this->clients));
	}
	
	// message sektion
	private function showMessage(){
		$mid = null;
		$query = ($database = Database::getInstance())->query("SELECT m.message, m.uid, m.id FROM " . table("message") . " AS m
                                  LEFT JOIN " . table("channel_member") . " AS c ON m.cid=c.cid
                                  WHERE c.uid='" . User::current()->id() . "'
                                  AND m.id>'" . User::current()->message_id() . "'
				                  AND (`isPriv`='N' OR `isPriv`='Y' AND `privTo`='" . User::current()->id() . "')");
		$my = User::current();
		while($row = $query->fetch()){
			if(!$my->isIgnore($row["uid"])){
				echo $row["message"] . "\r\n";
			}
			$mid = $row["id"];
		}
		
		if($database->query("SELECT COUNT(`message`) AS id FROM " . table("user_msg") . " WHERE `uid`='" . User::current()->id() . "'")->fetch()["id"] != 0){
			$query = $database->query("SELECT `message` FROM " . table("user_msg") . " WHERE `uid`='" . User::current()->id() . "'");
			while($row = $query->fetch()){
				echo $row["message"] . "\r\n";
			}
			// delete all rows
			$database->query("DELETE FROM " . table("user_msg") . " WHERE `uid`='" . User::current()->id() . "'");
		}
		
		User::current()->message_id($mid == null ? System::getLastId() : $mid);
		Channel::garbage_collect();
		User::garbage_collector();
	}
	private function handlePost($message){
		// is message a array
		if(is_array($message)){
			foreach($message as $msg)
				$this->handlePost($msg);
			return;
		}elseif(is_string($message)){
			if(System::is_cli()){
				Console::writeLine($message);
			}
			$message = new MessageParser($message);
		}elseif(!($message instanceof MessageParser)){
			trigger_error("\$message is not a instanceof MessageParser");
		}
		
		if($message->isCommand()){
			$this->handleCommand($message);
		}else{
			$handle = true;
			if($message->command() == "MESSAGE"){
				if($message->channel() != null){
					if(!Access::allowIgnoreFlood($message->channel()->name()))
						$handle = Flood::controle($message->channel());
				}else{
					return;
				}
				
				User::current()->updateLastMessage();
			}
			if($handle){
				$this->handleCommand($message);
			}else{
				send($message, "FLOOD " . $message->channel()->name() . ": Reach");
			}
		}
	}
	private function handleCommand(MessageParser $message){
		if(Module::exists($name = strtolower($message->command()))){
			// load the module
			Module::load($name);
			// create the name to use the module
			$module_name = $name . "_command";
			$module_name($message);
		}else{
			Command::error($message, "unknown command");
		}
	}
	private function userInit(){
		if(!Head::cookie("login_driver") && Head::get("auth_method")){
			if(!AuthenticationDriver::exists(Head::get("auth_method"))){
				Html::error(sprintf(Language::get("Could not finde the %s driver"), Head::get("auth_method")));
			}else{
				Head::make_cookie("login_driver", Head::get("auth_method"));
			}
		}
		
		if(AuthenticationDriver::login()){
			return true;
		}else{
			if(!System::is_cli() && Head::get("ajax")){
				exit("LOGIN: REQUID");
			}
			
			if(Head::cookie("login_driver")){
				$auth = AuthenticationDriver::getDriver(Head::cookie("login_driver"));
				if($auth->enabled()){
					$this->loginpage($auth);
					return false;
				}
			}
				
			$this->auth_chose();
			return false;
		}
	}
	private function auth_chose(){
		$data = [];
		
		try{
			$data["drivers"] = [];
			foreach(new DriverDir("authentication") as $driver){
				if(!$driver->isFile()){
					$auth = AuthenticationDriver::getDriver($driver->getItemName());
					if($auth->enabled()){
						$data["drivers"][] = $auth;
					}
				}
			}
		}catch(LowLevelError $error){
			$data["error"] = Language::get("Could not find auth driver");
		}
		
		$this->page("auth_chose", $data);
	}
	private function loginpage(AuthenticationDriverInterface $auth){
		$this->page("login", []);
	}
	private function showChat(){
		$data = [
				'sendType' => (Files::exists("include/websocket.json") ? "WebSocket" : "AJAX"),
				'nick' => User::current()->nick()
		];
		
		if($data["sendType"] == "WebSocket"){
			$webs = json_decode(Files::context("include/websocket.json"), true);
			$data["wHost"] = $webs["host"];
			$data["wPort"] = $webs["port"];
			unseT($webs);
		}
		
		$data["channel"] = [];
		foreach(Channel::getUserChannel(User::current()) as $channel){
			$data["channel"][] = $channel->name();
		}
		
		if(Head::get("channels")){
			foreach(explode(",", Head::get("channels")) as $channel){
				if(!in_array($channel, $data["channel"]) && controleChannelName($channel) && Channel::join($channel, User::current())){
					$data["channel"][] = $channel;
				}
			}
		}
		
		$data["smylie"] = [];
		$query = Database::getInstance()->query("SELECT * FROM " . table("smylie"));
		while($row = $query->fetch()){
			$data["smylie"][] = $row;
		}
		
		if(count($data["channel"]) == 0){
			// ohhh no this user missing channel to join so wee use start channel to join the user in.
			if(Channel::join(Setting::get("startChannel"), User::current())){
				$data["channel"][] = Setting::get("startChannel");
			}else{
				throw new Exception("Failed to join the chat start group");
			}
		}
		
		$this->page("chat", $data);
	}
	private function page($name, $data){
		Language::load($name);
		
		if(defined("NO_CONTEXT")){
			return;
		}
		
		$tempelate = new Tempelate(["dir" => "inc/style/"]);
		exit($tempelate->exec($name));
		
		$loader = new \Twig_Loader_Filesystem("inc/style");
		$twig = new \Twig_Environment($loader);
		$twig->addFunction(new \Twig_SimpleFunction('language', function (){
			$arg = func_get_args();
			if(func_num_args() == 0){
				throw new Exception("Language function take a lest 1 agument");
			}
			
			$arg[0] = Language::get($arg[0]);
			
			return call_user_func_array("sprintf", $arg);
		}));
		
		$twig->addFunction(new \Twig_SimpleFunction("setting", function ($name){
			if(!Setting::exists($name)){
				throw new Exception("Unknown setting name '" . $name . "'");
			}
			
			return Setting::get($name);
		}));
		
		echo $twig->render($name . ".html", array_merge($data, Html::getAguments()));
	}
}
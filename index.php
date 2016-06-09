<?php
define("CHAT_VERSION", "V0.0.3");
error_reporting(E_ALL);
ini_set('display_errors', '1');
function ip(){
	if(Server::is_cli()){
		if(socket_getpeername(User::current()->socket(), $ip)){
			if($ip == "::1")
				$ip = "127.0.0.1";
			return $ip;
		}
		return null;
	}
	
	return $_SERVER['REMOTE_ADDR'] == "::1" ? "127.0.0.1" : $_SERVER["REMOTE_ADDR"];
}
class Server{
	public static function is_cli(){
		return php_sapi_name() == "cli";
	}
	public static function getLastId(){
		$row = Database::query("SELECT `id` FROM " . table("message") . " ORDER BY `id` DESC")->fetch();
		return $row["id"];
	}
	function inilize(){
		// set CHAT_PATH
		if(!defined("CHAT_PATH")){
			define("CHAT_PATH", dirname(__FILE__) . '\\');
			set_include_path(CHAT_PATH);
		}
		
		include "include/file.php";
		include 'include/autoloader.php';
		
		AutoLoader::set();
		include "include/firewall.php";
		
		if(!Server::is_cli() && FireWall::isBlacklist(ip())){
			exit("You ip is denid access to this website. Contact our admin for explaining of whay you not has access to this site");
		}
		
		// send header if this is a ajax server
		if(!Server::is_cli()){
			header("Expires: Mon, 26 Jul 12012 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}
		
		include "include/messageparser.php";
		include "include/message.php";
		include "include/user.php";
		include "include/channel.php";
		include "include/systemgroup.php";
		include "include/head.php";
		include "include/module.php";
		include "include/database.php";
		include "include/setting.php";
		include "include/command.php";
		include "include/defender.php";
		include "include/flood.php";
		include "include/access.php";
		
		if(!Files::exists("include" . DIR_SEP() . "config.json")){
			if(!Server::is_cli()){
				header("location:install.php?step=1");
				exit();
			}else{
				exit("Missing config file in [" . getDir() . "include" . DIR_SEP() . "config.json]. install it first");
			}
		}
		
		$json = json_decode(Files::context("include" . DIR_SEP() . "config.json"));
		
		Database::init($json->host, $json->user, $json->pass, $json->table, $json->prefix);
		
		Setting::init();
		FireWall::init();
		Channel::init();
		if(!Server::is_cli()){
			// wee has controled that the user is not in black list. Now wee see if the user has a temporary ban
			if(FireWall::isBan()){
				if(get("ajax")){
					exit("LOGIN: REQUID");
					exit();
				}
				$data = FireWall::getInfoBan(ip());
				exit("You are banet to: " . date("H:i:s d-m-Y", $data["expired"]));
			}
			
			// if this is not a ajax wee do a user and channel clean now
			if(!get("ajax")){
				Channel::garbage_collect();
				User::garbage_collector();
			}
			
			include 'include/html.php';
			if($this->userInit()){
				if(get("ajax")){
					// if there is post available handle the post
					if(post("message")){
						$this->handlePost(explode("\r\n", post("message")));
					}
					
					$this->showMessage();
				}else{
					$this->showChat();
				}
			}
		}else{
			include "include/console.php";
			Channel::init();
			$this->init_websocket();
		}
	}
	private function init_websocket(){
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
		Console::writeLine("Begin to listen after connections: ");
		if(socket_listen($master, 20) === false){
			Console::writeLine("failed");
			exit("Fail to listen socket");
		}
		Console::writeLine("success");
		
		$this->add_socket_client($master);
		Console::writeLine("Server is startet. Wee listen now after active connections");
		$connections = [];
		while(true){
			$write = $ex = null;
			
			@socket_select($connections, $write, $ex, null);
			
			foreach($read as $socket){
				if($socket == $master){
					$client = socket_accept($socket);
					if($client < 0){
						Console::writeLine("failed to accept a connection");
					}else{
						if($this->handle_new_connect($client)){
							$connections[] = $socket;
						}
					}
					continue;
				}
				
				$recv = @socket_recv($socket, $buf, 1024, 0);
				if($recv === false || $recv == 0){
					$this->remove_client($socket);
					continue;
				}
				
				$message = $konto->unmask($buf);
				if(!$message){
					continue;
				}
				
				$this->handlePost($message);
			}
		}
	}
	private function handle_new_connect($new){
		$user = $this->add_socket_client($new);
		
		$head = array();
		// handshake :)
		$lines = explode("\r\n", $user->read());
		for($i = 0;$i < count($lines);$i++){
			$line = trim($lines[$i]);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
				$head[$matches[1]] = $matches[2];
			}
		}
		
		if(empty($head['Sec-WebSocket-Key'])){
			$this->remove_client($new);
			echo "Missing Sec-WebSocket-Key\r\n";
			return false;
		}
		
		setSocketCookie($head["Cookie"]);
		
		// to accsess websocket connection wee need to be login,
		if(!$this->login()){
			// the user has not login yet close the connection now
			socket_close($new);
			Console::writeLine("User open a connection without has login yet.");
			return false;
		}
		
		if(Firewall::isBlacklist(ip()) || Firewall::isBan()){
			// this socket connection is bannet. Wee close it and return false
			socket_close($new);
			return false;
		}
		
		User::current()->websocket($new); // save the websocket so wee can use it in this program
		
		$key = $head['Sec-WebSocket-Key'];
		$hkey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		
		$uhead = array();
		
		$uhead[] = "HTTP/1.1 101 Web Socket Protocol Handshake";
		$uhead[] = "Upgrade: websocket";
		$uhead[] = "Connection: Upgrade";
		$uhead[] = "Sec-WebSocket-Accept: " . $hkey;
		
		$handshake = implode("\r\n", $uhead) . "\r\n\r\n";
		// exit($handshake);
		
		if(socket_write($new, $handshake, strlen($handshake)) === false){
			exit("Handshake fail");
		}
		echo "New client connected to server\r\n";
		return true;
	}
	private function remove_client($socket){
		$i = array_search($socket, $this->client);
		if(empty($i)){
			return false;
		}
		$this->clientObj[$i]->disconnect();
		$this->clientObj = $this->reset_array_sort($this->clientObj, $i);
		$this->client = $this->reset_array_sort($this->client, $i);
		echo "Client disconetet\r\n";
		
		return true;
	}
	private function reset_array_sort($array, $removeId = null){
		$cache = $array;
		$array = array();
		for($i = 0;$i < count($cache);$i++){
			if($removeId !== null && $i == $removeId){
				continue;
			}
			$array[] = $cache[$i];
		}
		
		return $array;
	}
	private function add_socket_client($client){
		$this->client[] = $client;
		$this->clientObj[] = $obj = new socket_user_client($client, $this->database);
		return $obj;
	}
	private function get_client($socket){
		foreach($this->clientObj as $c){
			if($c->socket == $socket){
				return $c;
			}
		}
		
		return false;
	}
	
	// message sektion
	private function showMessage(){
		$mid = null;
		$query = Database::query("SELECT m.message, m.uid, m.id FROM " . table("message") . " AS m
                                  LEFT JOIN " . table("channel_member") . " AS c ON m.cid=c.cid
                                  WHERE c.uid='" . User::current()->id() . "'
                                  AND m.id>'" . User::current()->message_id() . "'");
		$my = User::current();
		while($row = $query->fetch()){
			if(!$my->isIgnore($row["uid"])){
				echo $row["message"] . "\r\n";
			}
			$mid = $row["id"];
		}
		
		// wee got pm to this user here it are more simple end the channel system
		$query = Database::query("SELECT `from`, `msg` FROM " . table("pm") . " WHERE `to`='" . User::current()->id() . "'");
		if($query->rows() != 0){
			while($row = $query->fetch()){
				// wee controle if the user exists so wee dont show message from geaust there is delete.
				if(($from = User::get($row["from"])) != null){
					echo "MESSAGE " . $from->nick() . ": " . $row["msg"] . "\r\n";
				}
			}
			// Wee rome the message here.
			Database::query("DELETE FROM " . table("pm") . " WHERE `to`='" . User::current()->id() . "'");
		}
		
		User::current()->message_id($mid == null ? Server::getLastId() : $mid);
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
			$message = new MessageParser($message);
		}elseif(!($message instanceof MessageParser)){
			trigger_error("$message is not a instanceof MessageParser");
		}
		
		if($message->isCommand()){
			$this->handleCommand($message);
		}else{
			$handle = true;
			if($message->command() == "MESSAGE"){
				if($message->channel() != null){
					if(!allowIgnoreFlood($message->channel()->name()))
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
	private function handleCommand($message){
		switch($message->command()){
			case "SHOW":
				Module::load("show");
				show_command($message);
			break;
			case "JOIN":
				Module::load("join");
				join_command($message);
			break;
			case "LEAVE":
				Module::load("leave");
				leave_command($message);
			break;
			case "MESSAGE":
				Module::load("message");
				message_command($message);
			break;
			case "TITLE":
				Module::load("title");
				title_command($message);
			break;
			case "ONLINE":
				Module::load("online");
				online_command($message);
			break;
			case "INFO":
				Module::load("info");
				info_command($message);
			break;
			case "INAKTIV":
				Module::load("inaktiv");
				inaktiv_command($message);
			break;
			case "NICK":
				Module::load("nick");
				nick_command($message);
			break;
			case "KICK":
				Module::load("kick");
				kick_command($message);
			break;
			default:
				error($message, "Unknown command");
		}
	}
	private function userInit(){
		if($this->login()){
			return true;
		}else{
			if(!Server::is_cli() && get("ajax")){
				exit("LOGIN: REQUID");
			}
			
			$this->loginpage();
			return false;
		}
	}
	private function loginpage(){
		$this->page("login", []);
	}
	private function showChat(){
		$data = [
				'sendType' => 'AJAX',
				'nick' => User::current()->nick()
		];
		
		$data["channel"] = [];
		foreach(Channel::getUserChannel(User::current()) as $channel){
			$data["channel"][] = $channel->name();
		}
		
		if(get("channels")){
			foreach(explode(",", get("channels")) as $channel){
				if(!in_array($channel, $data["channel"]) && controleChannelName($channel) && Channel::join($channel, User::current())){
					$data["channel"][] = $channel;
				}
			}
		}
		
		$data["smylie"] = [];
		$query = Database::query("SELECT * FROM " . table("smylie"));
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
		include 'include/language.php';
		try{
			Language::load($name);
		}catch(Exception $e){
			$this->error($e);
			return;
		}
		if(defined("NO_CONTEXT")){
			return;
		}
		try{
			$loader = new Twig_Loader_Filesystem("include/style");
			$twig = new Twig_Environment($loader);
			$twig->addFunction(new Twig_SimpleFunction('language', function (){
				$arg = func_get_args();
				if(func_num_args() == 0){
					throw new Exception("Language function take a lest 1 agument");
				}
				
				$arg[0] = Language::get($arg[0]);
				
				return call_user_func_array("sprintf", $arg);
			}));
			
			$twig->addFunction(new Twig_SimpleFunction("setting", function ($name){
				if(!Setting::exists($name)){
					throw new Exception("Unknown setting name '" . $name . "'");
				}
				
				return Setting::get($name);
			}));
			
			echo $twig->render($name . ".html", array_merge($data, Html::getAguments()));
		}catch(Twig_Error_Loader $e){
			$this->error($e);
		}catch(Twig_Error_Syntax $e){
			$this->error($e);
		}catch(Exception $e){
			$this->error($e);
		}
	}
	private function error($e){
		$loader = new Twig_Loader_Filesystem("include/style/system/");
		$twig = new Twig_Environment($loader);
		exit($twig->render("error.html", array(
				"error" => $e
		)));
	}
	private function login(){
		if(cookie("token_chat")){
			$part = explode(",", cookie("token_chat"));
			if(count($part) != 2){
				cookieDestroy("token_chat");
				return false;
			}
			
			// get the id from the chat token
			$id = $part[0] - 123456789;
			// look after the user in the database
			$query = Database::query("SELECT * FROM " . table("user") . " WHERE `id`='" . $id . "'");
			if($query->rows() != 1){
				cookieDestroy("token_chat");
				return false;
			}
			
			// control if the hash value is the same and the ip is the same
			$data = $query->fetch();
			if($data["hash"] != $part[1] || ip() != $data["ip"]){
				cookieDestroy("token_chat");
				return false;
			}
			
			// okay now wee know this is the correct user!!
		}elseif(!Server::is_cli() && post("username") && post("password")){
			if(post("email")){ // create a new account
				if(nick_taken(post("nick"))){
					Html::error("Nick is taken");
					return false;
				}
				
				$data = User::createUser(trim(post("username")), trim(post("password")), trim(post("email")));
				
				make_cookie("token_chat", ($data["id"] + 123456789) . "," . $data["hash"]);
			}else{
				$query = Database::query("SELECT * FROM " . table("user") . " WHERE `username`=" . Database::qlean(post("username")));
				if($query->rows() == 1){
					$data = $query->fetch();
					if(hash_password(post("password"), $data["hash"], $data["active"]) != $data["password"]){
						Html::error("Username or/and password is wrong");
						return false;
					}
					make_cookie("token_chat", ($data["id"] + 123456789) . "," . $data["hash"]);
				}else{
					Html::error("Username or/and password is wrong");
					return false;
				}
			}
		}elseif(!Server::is_cli() && post("username")){ // geaust login
			if(nick_taken(post("username"))){
				Html::error("Nick is taken. Please pick a anthoter one and try again");
				return false;
			}
			
			// create a account for this geaust. The system will take care of put it in the database
			$data = User::createGaust(post("username"));
			make_cookie("token_chat", ($data["id"] + 123456789) . "," . $data["hash"]);
		}else{
			return false; // failed to login
		}
		if(($user = User::get($data['id'])) != null){
			User::current($user); // push to current
			return true;
		}
		return false;
	}
}

$server = new Server();
try{
	$server->inilize();
}catch(Exception $e){
	$end = "\r\n";
	if(!Server::is_cli())
		$end = "<br>";
	echo "Fail to show chat becuse:" . $end;
	echo "File: " . $e->getFile() . $end;
	echo "Line: " . $e->getLine() . $end;
	exit($e->getMessage());
}
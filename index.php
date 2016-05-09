<?php
define("CHAT_VERSION", "V0.0.3");
error_reporting(E_ALL);
ini_set('display_errors', '1');

function ip(){
  if(Server::is_cli()){
     if(socket_getpeername(User::current()->socket(), $ip)){
	   if($ip == "::1")$ip = "127.0.0.1";
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
       $row = Database::query("SELECT `id` FROM ".table("message")." ORDER BY `id` DESC")->fetch();
       return $row["id"];
     }
     
    function inilize(){
    	//set CHAT_PATH
    	if(!defined("CHAT_PATH")){
    		define("CHAT_PATH", dirname(__FILE__)."\\");
                set_include_path(CHAT_PATH);
    	}
     
        include "include/file.php";
    	
		include 'include/autoloader.php';
		AutoLoader::set();
        include "include/firewall.php";

        if(!Server::is_cli() && FireWall::isBlacklist(ip())){
           exit("You ip is denid access to this website. Contact our admin for explaining of whay you not has access to this site");
        }
          

    	//send header if this is a ajax server
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
	    if(!Files::exists("include/config.json")){
			if(!Server::is_cli()){
			    header("location:install.php?step=1");
			    exit;
			}else{
				exit("Missing config file. install it first");
			}
		}
        
		$json = json_decode(Files::context("include/config.json"));
		
        Database::init($json->host, $json->user, $json->pass, $json->table, $json->prefix);
		Setting::init();
        FireWall::init();
        Channel::init();
        if(!Server::is_cli()){
            //wee has controled that the user is not in black list. Now wee see if the user has a temporary ban
            if(FireWall::isBan()){
            	if(get("ajax")){
            		error(new MessageParser("JOIN: #null"), "You are banned. Please contact our admin to get information about the ban");
            		exit;
            	}
               exit("You are banned. Please contact our admin to get information about the ban");
            }
            
            //if this is not a ajax wee do a user and channel clean now
            if(!get("ajax")){
            	Channel::garbage_collect();
            	User::garbage_collector();
            }
            
			include 'include/html.php';
               if($this->userInit()){
                  if(get("ajax")){
                     //if there is post available handle the post
                     if(post("message")){
                        $this->handlePost(explode("\r\n", post("message")));//the new style is not json but plain text
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
         Console::writeLine("Welcommen to CowChat version: ".CHAT_VERSION);
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
         if(($master = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)) === false){
             Console::writeLine("failed");
             exit();
         }
         Console::writeLine("success");
         Console::write("Configuere the socket: ");
         if(socket_set_option($master,SOL_SOCKET,SO_REUSEADDR,1) === false){
             Console::writeLine("failed");
             exit();
         }
         Console::writeLine("success");
         
         if (!filter_var($host, FILTER_VALIDATE_IP)) {
             Console::write("Convert '".$host."' to '");
             $host = gethostbyname($host);
             Console::writeLine($host."'");
         }

         Console::write("Bind host and port to socket: ");
         if(@socket_bind(
             $master,
             $this->getConfig("socketServer"),
             $this->getConfig("socketPort")
         ) === false){
             Console::writeLine("failed");
             exit();
         }
         Console::writeLine("success");
         Console::writeLine("Begin to listen after connections: ");
	 if(socket_listen($master,20) === false){
             Console::writeLine("failed");
             exit("Fail to listen socket");
         }
         Console::writeLine("success");

         $this->add_socket_client($master);
         Console::writeLine("Server is startet. Wee listen now after active connections");
         $connections = [];
         while(true){
             $write = $ex = null;

             @socket_select($connections,$write,$ex,null);

             foreach($read AS $socket){
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

                 $recv = @socket_recv($socket,$buf,1024,0);
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
		 $user =  $this->add_socket_client($new);
		 
		 $head = array();
		 //handshake :)
		 $lines = explode("\r\n",$user->read());
		 for($i=0;$i<count($lines);$i++){
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

         //to accsess websocket connection wee need to be login, 
         if(!$this->login()){
           //the user has not login yet close the connection now 
           socket_close($new);
           Console::writeLine("User open a connection without has login yet.");
           return false;
         }

         if(Firewall::isBlacklist(ip()) || Firewall::isBan()){
           //this socket connection is bannet. Wee close it and return false
           socket_close($new);
           return false;
         }

         User::current()->websocket($new);//save the websocket so wee can use it in this program

		 $key  = $head['Sec-WebSocket-Key'];
		 $hkey = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		 
		 $uhead = array();
		 
		 $uhead[] = "HTTP/1.1 101 Web Socket Protocol Handshake";
		 $uhead[] = "Upgrade: websocket";
		 $uhead[] = "Connection: Upgrade";
		 $uhead[] = "Sec-WebSocket-Accept: ".$hkey;

         $handshake = implode("\r\n",$uhead)."\r\n\r\n";
         //exit($handshake);

         if(socket_write($new,$handshake,strlen($handshake))===false){
             exit("Handshake fail");
         }
         echo "New client connected to server\r\n";
         return true;
	 }
	 
	 private function remove_client($socket){
		 $i = array_search($socket,$this->client);
		 if(empty($i)){
			 return false;
		 }
		 $this->clientObj[$i]->disconnect();
         $this->clientObj = $this->reset_array_sort($this->clientObj,$i);
         $this->client    = $this->reset_array_sort($this->client,$i);
         echo "Client disconetet\r\n";
		 
		 return true;
	 }

     private function reset_array_sort($array,$removeId = null){
         $cache = $array;
         $array = array();
         for($i=0;$i<count($cache);$i++){
             if($removeId !== null && $i == $removeId){
                 continue;
             }
             $array[] = $cache[$i];
         }

         return $array;
     }
	 
	 private function add_socket_client($client){
		 $this->client[]    = $client;
		 $this->clientObj[] = $obj = new socket_user_client($client,$this->database);
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
    
    //message sektion
    private function showMessage(){
        $mid = null;
        $query = Database::query("SELECT m.message, m.uid, m.id FROM ".table("message")." AS m
                                  LEFT JOIN ".table("channel_member")." AS c ON m.cid=c.cid
                                  WHERE c.uid='".User::current()->id()."'
                                  AND m.id>'".User::current()->message_id()."'");
        $my = User::current();
        while($row = $query->fetch()){
           if(!$my->isIgnore($row["uid"])){
              echo $row["message"]."\r\n";
           }
           $mid = $row["id"];
        }

        //wee got pm to this user here it are more simple end the channel system
        $query = Database::query("SELECT `from`, `msg` FROM ".table("pm")." WHERE `to`='".User::current()->id()."'");
        if($query->rows() != 0){
          while($row = $query->fetch()){
             //wee controle if the user exists so wee dont show message from geaust there is delete. 
             if(($from = User::get($row["from"])) != null){
                echo "MESSAGE ".$from->nick().": ".$row["msg"]."\r\n";
             }
          }
          //Wee rome the message here. 
          Database::query("DELETE FROM ".table("pm")." WHERE `to`='".User::current()->id()."'");
        }
		
        User::current()->message_id($mid == null ? Server::getLastId() : $mid);
        Channel::garbage_collect();
	User::garbage_collector();
        return;
        $data = $this->database->query("SELECT cm.isInAktiv, cm.id, cm.uid , us.nick, cm.cid, cn.name
        FROM `".DB_PREFIX."chat_member` AS cm
        LEFT JOIN `".DB_PREFIX."users` AS us ON us.user_id = cm.uid
        LEFT JOIN `".DB_PREFIX."chat_name` AS cn ON cn.id=cm.cid
        WHERE cm.cid != '1'
         AND cm.ban = '".No."'
          AND cm.lastActiv < DATE_SUB( now( ) , INTERVAL ".(int)$this->config['inaktiv']." MINUTE )
           AND cm.isInAktiv = '".No."'
        OR cm.cid != '1'
         AND cm.ban = '".No."'
          AND cm.lastActiv < DATE_SUB( now( ) , INTERVAL ".(int)$this->config['leave']." MINUTE )
           AND cm.isInAktiv = '".Yes."'"
        );

        while($row = $data->get())
            $this->handle_inaktiv($row);
    }
	 
    private function handlePost($message){
	//is message a array
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
            if(Flood::controle($message->channel())){
                $this->handleCommand($message);
                User::current()->updateActive();
            }else{
                send($message, "FLOOD ". $message->channel()->name().": Reach");
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
        }
        return;
    	switch($message->command()){
    	    case "GETSTATUS":
    	        $this->answer_getStatus();
    	    break;
            case "JOIN":
                $this->answer_join();
    	    break;
            case "NICK":
            	if(User::current()->nick($message->message())){
                   send($message, "NICK: ".$message->message());
                }else{
                   send($message, "ERROR: nickTaken");
                }
            break;
            case 'MSG':
            	$this->answer_msg();
            break;
            case "CONFIG":
            	$this->answer_config();
            break;
            case 'GETONLINE':
            	$this->do_getOnline();
            break;
            case 'TITLE':
               if(($channel = channel($message->message())) != null){
                 if(User::current()->isMember($channel)){
                   send($message, "TITLE: ".$channel->title());
                 }else{
                   send($message, "ERROR: notMember");
                 }
               }else{
                 send($message, "ERROR: unknownChannel");
               }
            break;
             case 'EXIT':
            	User::current()->remove();
             break;
	     case 'LEAVE':
	        $this->answer_leave();
	     break;
	     case 'KICK':
                if(($user = User::get($message->message())) != null){
                   if(!$message->channel()->group()->canBan()){
                      send($message, "ERROR: cantBan");
                   }elseif(!$message->channel()->group($user)->canBaned()){//this user has from the admin set to not can be banned
                      send($mesaage, "ERROR: cantBanned");
                   }else{
                      if(User::current()->group()->level() > $user->group()->level()){
                         $user->kick($message->channel(), User::current());
                         send($message, "KICK: Okay");
                      }elseif(User::current()->group()->level() == $user->group()->level()){
                         send($message, "ERROR: accessEquels");
                      }else{
                         send($message, "ERROR: notAccess");
                      }
                   }
                }else{
                   send($message, "ERROR ".$message->channel().": unknownUser");
                }
	    break;
	    case 'BOT':
	        //this will send message to the channel like a bot. Bot has uid 0 and the object is not createt from User class but wee get the data from function bot
               
	    break;
	    case 'BAN':
	        $this->answer_ban();
	    break;
	    case 'UNBAN':
		 $this->answer_unban();
	    break;
            case 'IGNORE':
                if(($user = User::get($message->message())) != null){
                  //control if the user a
                  if(User::addIgnore($user)){
                    send($message, "IGNORE: ".$message->message());
                  }else{
                    send($messge, "ERROR: failIgnore");
                  }
                }else{
                 send($message, "ERROR: unknownUser");
                 }
            break;
            case 'UNIGNORE':
                if(($user = User::get($message->message())) != null){
                   if(User::current()->isIgnore($user->id())){
                      if(User::current()->unIgnore($user->id())){
                        send($message, "UNIGNORE: ".$message->message());
                      }else{
                        send($message, "ERROR: failUnIgnore");
                      }
                   }else{
                     send($message, "ERROR: notIgnore");
                   }
                }else{
                  send($message, "ERROR: unknownUser");
                }
            break;
            case 'PING':
                send($message, "PONG: respons");
            break;
            case 'GETCONFIG':
                send($message, "CONFIG: ".implode (",", User::current()->getData()));
            break;
            default:
            	send($message, "ERROR: UnknownCommand");
            break;
    	}
    }

     private function answer_file(){
         //findes filen?
         $input = $this->init_get_data();
         if(preg_match("/^\/file\s([0-9]*?)$/",$input['message'],$reg)){
             $sql = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_file` WHERE `id`='".(int)$reg[1]."'");
             $row = $sql->get();
             if(empty($row)){
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/noFile");
             }else{
                 $this->sendBotMessage(
                     $this->getVariabel("cid"),
                     '/file '.$row['url']
                 );
             }
         }else{
             $this->sendBotPrivMessage($this->getVariabel("cid"), "/commandDenaid");
         }
     }

     private function answer_clear(){
         if(!$this->iADMIN() && !$this->iSUPERADMIN()){
             $this->sendBotPrivMessage(
                 $this->getVariabel("cid"),
                 "/error ".sprintf($this->lang['accessDenaidKommando'],"/clear")
             );
             return;
         }

         $this->database->query("DELETE FROM ".DB_PREFIX."chat_message");//on this method wee dont reseat id to 0 but only delete all items
         $this->sendBotPrivMessage(
             $this->getVariabel("cid"),
             $this->lang['clearDone'],
             'green'
         );
     }

     private function answer_update(){
         if(!$this->iADMIN() && !$this->iSUPERADMIN()){
             //has nothing to do here idiot :()
             $this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/update"));
             return;
         }

         //system config :)
         $this->sConfig = array();
         $this->init_system_setting();

         //database config :)
         $this->config = array();
         $this->loadDatabaseConfig();

         $this->protokol->update();

         if($this->getConfig("protokol") != "socket" && $this->websocket){
             exit("Server gooing down!");
         }

         //okay now wee can tell user this system is now updatet :)
         $this->sendBotPrivMessage(
             $this->getVariabel("cid"),
             $this->lang['systemUpdatet'],
             "green"
         );
     }

     private function answer_uningore(){
         $input = $this->init_get_data();
         if(preg_match("/^\/unIgnore\s([a-zA-Z]*?)$/",$input['message'],$reg)){
             if(($uid = $this->get_user_id_from_nick($reg[1])) !== false){
                 if(!in_array($uid,$this->protokol->get_ignore())){
                     $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['isNotIgnore']);
                     return;
                 }
                 $this->protokol->remove_ignore($uid);
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/unIgnore ".$reg[1],"green");
             }else{
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['userNotFound']);
             }
         }else{
             $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".sprintf($this->lang['invalidCommand'],"unIgnore"));
         }
     }

     private function answer_ignore(){
         $input = $this->init_get_data();
         if(preg_match("/^\/ignore\s([a-zA-Z]*?)$/",$input['message'],$reg)){
             if(($uid = $this->get_user_id_from_nick($reg[1])) !== false){
                 if(in_array($uid,$this->protokol->get_ignore())){
                     $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['isIgnore'],"red");
                     return;
                 }
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/ignore ".$reg[1]);
                 $this->protokol->add_ignore($uid);
             }else{
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['userNotFound']);
             }
         }else{
             $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".sprintf($this->lang['invalidCommand'],"ignore"));
         }
     }
	 
	 private function answer_unban(){
		 
		 $input = $this->init_get_data();
		 
		if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getCidFromChannel($input['channel']), "/error ".sprintf($this->lang['accessDenaidKommando'],"/unban"));
			return;
		} 
		 
		 if(preg_match("/^\/unban\s([a-zA-Z]*?)$/",$input['message'],$reg)){
             $userData = $this->protokol->getUserInChannel($this->getVariabel("cid"),$reg[1]);
             if($userData === false){
                 $this->sendBotPrivMessage(
                     $this->getVariabel("cid"),
                     "/error ".$this->lang['userNotFound']
                 );
                 return;
             }

             if(in_array($userData['user_id'],$this->protokol->getBannetInChannel($this->getVariabel("cid")))){
                 $this->protokol->remove_ban(
                     $userData['user_id'],
                     $this->getVariabel("cid"),
                     $this->protokol->getBanId($userData['user_id'],$this->getVariabel("cid"))
                 );
                 $this->sendBotMessage(
                     $this->getVariabel("cid"),
                     $input['message'],
                     "green"
                 );
             }else{
                 $this->sendBotPrivMessage(
                     $this->getVariabel("cid"),
                     "/error ".sprintf($this->lang['notBan'],$reg[1])
                 );
             }
		 }else{
			 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['unbanBroken']);
		 }
	 }
	 
	 private function answer_ban(){
    	if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/ban"));
			return;
		}		 
		 
		 $input = $this->init_get_data();
		 
		 if(preg_match("/^\/ban\s([a-zA-Z]*?)\s([0-9]*?)$/",$input['message'],$reg)){
			 //vi sætter nu tiden frem til den tidspunkt brugeren ikke længere er bannet ;)
			 $to = strtotime("+".(int)$reg[2]." minutes",time());

             $userData = $this->protokol->getUserInChannel(
                 $this->getVariabel("cid"),
                 trim($reg[1])
             );

             if($userData !== false && is_array($userData)){
                 //you can not ban you self :)
                 if($userData['user_id'] === $this->protokol->user['user_id']){
                     $this->sendBotPrivMessage(
                         $this->getVariabel("cid"),
                         '/error '.$this->lang['banSelf']
                     );
                     return;
                 }

                 $this->ban(
                     $input['channel'],
                     $userData['user_id'],
                     $to,
                     trim($reg[1])
                 );
             }else{
                 $this->sendBotPrivMessage(
                     $this->getVariabel("cid"),
                     "/error ".sprintf($this->lang['nickNotFound'],$reg[1])
                 );
             }
		 }else{
			 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['banBroken']);
		 }
	 }
	 
	 private function answer_bot(){
		 
		 $input = $this->init_get_data();
		 
    	if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getCidFromChannel($input['channel']), "/error ".sprintf($this->lang['accessDenaidKommando'],"/bot"));
			return;
		}

		 if(preg_match("/^\/bot\s(.*?)$/",$input["message"],$reg)){
			 $this->sendBotMessage($this->getVariabel("cid"),$reg[1],($this->websocket ? $this->getVariabel("client")->user_config['textColor'] : $this->userConfig['textColor']));
		 }else{
			 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['botBroken']);
		 }		 
	 }
	 
	 private function answer_leave(){
		 if($this->getVariabel("cid") == 1){
			 return;
		 }
		 
		 $this->database->query("DELETE FROM `".DB_PREFIX."chat_member` WHERE `cid`='".(int)$this->getVariabel("cid")."' AND `uid`='".($this->websocket ? $this->getVariabel("client")->user['user_id'] : $this->user['user_id'])."'");
         if($this->database->isError){
             exit($this->database->getError());
         }
		 //vi skriver til channel at brugeren har forladt channel ;)
         $input = $this->init_get_data();

         if($this->websocket){
             unset($this->getVariabel("client")->channel[$this->getVariabel("cid")]);
             unset($this->getVariabel("client")->aktiv[$this->getVariabel("cid")]);
         }

         $this->sendBotMessage(
             $this->getVariabel("cid"),
             "/leave ".$input['channel'],
             "red",
             $this->protokol->user['user_id']
         );
	 }
	 
    private function answer_kick(){
    	if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/kick"));
			return;
		}

        $input = $this->init_get_data();
		
		if(preg_match("/^\/kick\s([a-zA-Z]*?)\s(.*?)$/",trim($input['message']),$reg)){
			//vi har nu en kick med message
			if(($uid = $this->getUserIdFromNick($reg[1])) != 0){
			   $this->kick($input['channel'],$reg[2],$uid);
			}else{
			   $this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['kickBroken']);	
			}
		}elseif(preg_match("/^\/kick\s([a-zA-Z\s]*?)$/", trim($input['message']),$reg)){
			if(($uid = $this->getUserIdFromNick($reg[1])) != 0){
				$this->kick($input['channel'],null,$uid);
			}else{
				$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['nickNotFound'],$reg[1]));
			}
		}else{
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['kickBroken']);
		}
    }
    
    private function doExit(){

        foreach($this->protokol->get_my_channel_list() AS $cid => $data){
            $this->sendBotMessage(
                 $cid,
                "/exit"
            );
        }

        if($this->websocket){
            $this->remove_client($this->getVariabel("client")->socket);
        }else{
            $this->database->query("DELETE FROM `".DB_PREFIX."chat_member`
            WHERE `uid`='".$this->protokol->user['user_id']."' AND `cid`!='1'");
        }
    }
    
    //msg
    private function answer_msg(){
    	$cid = $this->getVariabel("cid");
		$input = $this->init_get_data();
    	//vi deler stringen op ;)
    	if(preg_match("/^\/msg\s(.*?)\s(.*?)$/", $input['message'],$reg)){
    		//vi ser om vi kan finde en bruger med det nick ;)
			$data = $this->database->prepare("SELECT user_id AS id,`nick` FROM `".DB_PREFIX."users` WHERE `nick`={nick}");
            $data->add("nick",$reg[1]);
    		$row = $data->done()->get();
    		if(!empty($row['id'])){
				
				if($this->websocket){
					for($i=0;$i<count($this->clientObj);$i++){
						if($this->clientObj[$i]->isLogin && $this->clientObj[$i]->user['user_id'] == $row['id']){
							$this->clientObj[$i]->msg(array(
								'cid'     => $cid,
								'message' => "/msg ".$this->getVariabel("client")->user['nick']." -> ".$this->clientObj[$i]->user['nick'].": ".$reg[2],
								'nick'    => $this->getVariabel("client")->user['nick'],
								'channel' => $input['channel'],
								'uid'     => $this->getVariabel("client")->user['user_id'],
								'img'     => $this->getVariabel("client")->user['user_avatar']
							));
							//vi skal også sende det til afsenderen :)
							$this->getVariabel("client")->msg(array(
								'cid'     => $cid,
								'message' => "/msg ".$this->getVariabel("client")->user['nick']." -> ".$this->clientObj[$i]->user['nick'].": ".$reg[2],
								'nick'    => $this->getVariabel("client")->user['nick'],
								'channel' => $input['channel'],
								'uid'     => $this->getVariabel("client")->user['user_id'],
								'img'     => $this->getVariabel("client")->user['user_avatar']
							));
							return;
						}
					}
					
					$this->sendBotPrivMessage($cid, "/error ".sprintf($this->lang['noNick'], $reg[1]),"red");
					return;
				}
				
				$data = $this->database->prepare("INSERT INTO `".DB_PREFIX."chat_message`
            (
            `uid`,
            `cid`,
            `isBot`,
            `time`,
            `message`,
            `messageColor`,
            `isMsg`,
            `msgTo`
            ) VALUE (
                '".(int)$this->user['user_id']."',
                '".(int)$cid."',
                '".No."',
                NOW(),
                {message},
                'yellow',
                '".Yes."',
                '".(int)$row['id']."'
                )");

                $data->add("message","/msg ".$this->protokol->user['nick']." -> ".$row['nick'].": ".$reg[2]);
                $data->done();
    		}else{
    			$this->sendBotPrivMessage($cid, "/error ".sprintf($this->lang['noNick'], $reg[1]),"red");
    		}
    	}else{
    		$this->sendBotPrivMessage($cid, "/error broken /msg","red");
    	}
    }
    
    private function getOnline($id,$isCommand = false){
		$name = array();
		if($this->websocket){
			for($i=0;$i<count($this->clientObj);$i++){
				$client = $this->clientObj[$i];
                if($client->isLogin && !empty($client->channel[$id])){
					if($isCommand){
                        $name[] = $client->user['user_id']."|".$client->user['nick']."|".$client->user['user_avatar']."|".No;
					}else{
						$name[] = array(
							$client->user['user_id'],
							$client->user['nick'],
							$this->convert_image($client->user['user_avatar']),
							No
						);
					}
				}
			}
		}else{
		$data = $this->database->query("SELECT user.nick AS nick, user.user_id AS id, user.user_avatar AS img, cm.isInAktiv AS isInAktiv
		FROM ".DB_PREFIX."users AS user
		LEFT JOIN ".DB_PREFIX."chat_member AS cm ON user.user_id = cm.uid
		WHERE cm.cid='".(int)$id."' AND cm.ban <> '".Yes."'");
    	
    	while($row = $data->get()){
    		if($isCommand){
    			$name[] = $row['id']."|".$row['nick']."|".$this->convert_image($row['img'])."|".$row['isInAktiv'];
    		}else{
    			$name[] = array($row['id'],$row['nick'],$this->convert_image($row['img']),$row['isInAktiv']);
    		}
    	}
		}
    	return $name;
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
                  'nick'     => User::current()->nick(),
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
		$query = Database::query("SELECT * FROM ".table("smylie"));
		while($row = $query->fetch()){
			$data["smylie"][] = $row;
		}
		
                if(count($data["channel"]) == 0){
                   //ohhh no this user missing channel to join so wee use start channel to join the user in.
                   if(Channel::join(Setting::get("startGroup"), User::current())){
                      $data["channel"][] = Setting::get("startGroup");
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
		   $loader = new Twig_Loader_Filesystem(CHAT_PATH."include/style");
		   $twig   = new Twig_Environment($loader);
		   $twig->addFunction(new Twig_SimpleFunction('language', function () {
			   $arg = func_get_args();
			   if(func_num_args() == 0){
				   throw new Exception("Language function take a lest 1 agument");
			   }
			   
			   $arg[0] = Language::get($arg[0]);
			   
			   return call_user_func_array("sprintf", $arg);
           }));
		   
		   $twig->addFunction(new Twig_SimpleFunction("setting", function($name){
			   if(!Setting::exists($name)){
				   throw new Exception("Unknown setting name '".$name."'");
			   }
			   
			   return Setting::get($name);
		   }));
		   
		   echo $twig->render($name.".html", array_merge($data,Html::getAguments()));
		}catch(Twig_Error_Loader $e){
			$this->error($e);
		}catch(Twig_Error_Syntax $e){
			$this->error($e);
		}catch(Exception $e){
			$this->error($e);
		}
	}
	
	private function error($e){
		$loader = new Twig_Loader_Filesystem(CHAT_PATH."include/style/system/");
		$twig = new Twig_Environment($loader);
		exit($twig->render("error.html", array("error" => $e)));
	}

    private function login(){
       if (cookie("token_chat")){
          $part = explode(",", cookie("token_chat"));
          if(count($part) != 2){
             cookieDestroy("token_chat");
             return false;
          }

          //get the id from the chat token
          $id = $part[0] - 123456789; 
          //look after the user in the database
          $query = Database::query("SELECT * FROM ".table("user")." WHERE `id`='".$id."'");
          if($query->rows() != 1){
            cookieDestroy("token_chat");
            return false;
          }

          //control if the hash value is the same and the ip is the same
          $data = $query->fetch();
          if($data["hash"] != $part[1] || ip() != $data["ip"]){
             cookieDestroy("token_chat");
             return false;
          }

          //okay now wee know this is the correct user!!
          }elseif(!Server::is_cli() && post("username") && post("password")){
             if(post("email")){//create a new account

             }else{//login
                //wee look after username first
                $query = Database::query("SELECT * FROM ".table("user")." WHERE `username`=".Database::qlean(post("username")));
                if($query->rows() != 1){
                   Html::error("Wrong username or/and password");
                   return false;
                }

                //now wee control the password to see if the passwords is equally.
                $data = $query->fetch();
                if(!password_equels(post("password"), $data["password"], $data["hash"])){
                   Html::error("Wrong username or/and password");
                   return false;
                }

                //update the user ip in database...
                Database::query("UPDATE ".table("user")." SET `ip`=".Database::qlean(ip())." WHERE `id`='".$data["id"]."'");
                $data["ip"] = ip();
                //create a cookie so the user can use webseocket or ajax chat
                make_cookie("token_chat", ($data["id"]+123456789).$data["hash"]);
             }
       }elseif(!Server::is_cli() && post("username")){//geaust login
          if(User::controleNick(post("username"))){
             Html::error("Nick is taken. Please pick a anthoter one and try again");
             return false;
          }

          //create a account for this geaust. The system will take care of put it in the database 
          $data = User::createGaust(post("username"));
          make_cookie("token_chat", ($data["id"]+123456789).",".$data["hash"]);
          
       }else{
         return false;//failed to login 
       }
	   if(($user = User::get($data['id'])) != null){
		   User::current($user);//push to current
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
	if(!Server::is_cli())$end = "<br>";
	echo "Fail to show chat becuse:".$end;
	echo "File: ".$e->getFile().$end;
	echo "Line: ".$e->getLine().$end;
	exit($e->getMessage());
}

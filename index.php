<?php
define("CHAT_VERSION", "V0.0.3");
error_reporting(E_ALL);
ini_set('display_errors', '1');
 class Server{
     
     public static function is_cli(){
        return php_sapi_name() == "cli";
     } 

     private $variabel         = array();
     private $channel          = array();
     
    function inilize(){
    	//send header if this is a ajax server
        if(!Server::is_cli()){
    	  header("Expires: Mon, 26 Jul 12012 05:00:00 GMT");
          header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
          header("Cache-Control: no-store, no-cache, must-revalidate");
          header("Cache-Control: post-check=0, pre-check=0", false);
          header("Pragma: no-cache");
        }

        $this->loadPages();
        $this->init_db();
        $this->init_system_setting();
	$this->loadVariabel();
        $this->loadDatabaseConfig();

        if(!Server::is_cli()){
            $this->userInit();
            if(get("ajax")){
               //if there is post available handle the post
               if(post("message")){
                  $this->handlePost(explode("\r\n", post("message")));//the new style is not json but plain text
               }

               $this->showMessage();
            }else{
               $this->showHTML();
            }
        }else{
            $this->init_websocket();
        }

        //@todo send a error message if this is in a globel group admin
        if($this->database->isError){
            exit($this->database->getError());
        }
    }

	 private function init_websocket(){

         if(!function_exists("socket_create")){
             exit("Missing socket create!");
         }

         if(($master = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)) === false){
             exit("Denaid to create socket!");
         }

         if(socket_set_option($master,SOL_SOCKET,SO_REUSEADDR,1) === false){
             exit("Deinad to create socket");
         }

         if (!filter_var($this->getConfig("socketServer"), FILTER_VALIDATE_IP)) {
             $this->config['socketServer'] = gethostbyname($this->getConfig("socketServer"));
         }

         if(@socket_bind(
             $master,
             $this->getConfig("socketServer"),
             $this->getConfig("socketPort")
         ) === false){
             exit("Failt to bind socket");
         }

		 if(socket_listen($master,20) === false){
             exit("Fail to listen socket");
         }

         $this->add_socket_client($master);

         while($this->websocket){
             $read = $this->client;
             $write = $ex = null;

             @socket_select($read,$write,$ex,null);

             foreach($read AS $socket){
                 if($socket == $master){
                     $client = socket_accept($socket);
                     if($client < 0){
                         echo "Error to accept socket!";
                         continue;
                     }else{
                         $this->handle_new_connect($client);
                     }
                     continue;
                 }

                 $konto = $this->get_client($socket);

                 if(!$konto->my_turn()){
                     $this->remove_client($socket);
                     continue;
                 }

                 $recv = @socket_recv($socket,$buf,1024,0);
                 if($recv === false || $recv == 0){
                     $this->remove_client($socket);
                     continue;
                 }

                 $message = $konto->unmask($buf);
                 if(!$message || $message == "undefined"){
                     continue;
                 }

                 $this->postData = @json_decode($message,true);
                 if(@json_last_error() != JSON_ERROR_NONE){
                     $this->remove_client($socket);
                     continue;
                 }


                 $this->variabel['client'] = $konto;
                 if($konto->isLogin){
                     $this->protokol->turn($konto->user['user_id']);
                     $this->setLang($this->protokol->getConfig("lang"));
                 }
                 $this->re_cache_channel_id($this->postData['channel']);
                 $this->handlePost();

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
             print_r($head);
             return false;
         }

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
         return null;
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
    
    //inaktiv sektion
    private function handle_inaktiv($row){
        if($row['isInAktiv'] == Yes){
            $this->do_leave($row);
        }else{
         $this->do_inaktiv($row);
        }
    }
    
    private function do_leave($row){
        $this->sendBotMessage(
            $row['cid'],
            '/leave',
            false,
            $row['uid']
        );
        $this->database->query("DELETE FROM `".DB_PREFIX."chat_member` WHERE `id`='".(int)$row['id']."'");
        if($this->database->isError){
            exit($this->database->getError());
        }
        $this->sendBotPrivMessage(
            1,
            "/leave ".$row['name'],
            "red",
            $row['uid']
        );
    }
    
    private function do_inaktiv($row){
		$this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `isInAktiv`='".Yes."' WHERE `id`='".$row['id']."'");
        if($this->database->isError){
            exit($this->database->getError());
        }

    	$this->sendBotMessage(
            $row['cid'],
            "/inaktiv ".$row['nick'],false,$row['uid']
        );
    }
    
    //message sektion
    private function showMessage(){
        //vi sletter nu pong beskeder som er mere end 1 min gammel :)
        $this->database->query("DELETE FROM `".DB_PREFIX."chat_message` WHERE `cid`='1' AND `message`='/pong' AND DATE_SUB(time, INTERVAL 1 MINUTE) > NOW()");
        if($this->database->isError){
            exit($this->database->getError());
        }

        //big sql :D
		$data = $this->database->query("SELECT tm.id AS id, tm.message AS message, tm.isMsg AS isMsg, tm.msgTo AS msgTo, tm.messageColor AS messageColor, tm.isBot AS isBot, user.nick AS nick, tm.time AS time, tn.id AS cid, tn.name AS channel, cm.id AS cmid, user.user_id AS uid, user.user_avatar AS img, tn.isPriv AS isPriv, tn.uid AS privUid
		FROM ".DB_PREFIX."chat_message AS tm
		LEFT JOIN ".DB_PREFIX."chat_member AS cm ON tm.cid = cm.cid
		LEFT JOIN ".DB_PREFIX."users AS user ON user.user_id = tm.uid
		LEFT JOIN ".DB_PREFIX."chat_name AS tn ON tn.id = cm.cid
		WHERE cm.uid = '".(int)$this->protokol->user['user_id']."'
		AND tm.id > '".(int)$this->getVariabel("last_id")."'
		AND cm.ban <> '".Yes."'
		ORDER BY tm.id ASC ");

        if($this->database->isError){
            exit($this->database->getError());
        }

    	$message = array();
    	while($row = $data->get()){
    		if($row['message'] == null){
    			continue;
    		}

            if($this->may_show_may_show_message($row)){
                $row['time'] = date("H:i",strtotime($row['time']));
                $message[] = $this->messageMakeSafe($row);
            }
    	}


        $this->json['message'] = $message;

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

    private function may_show_may_show_message($data){
        if(in_array($data['uid'],$this->protokol->get_ignore()) && $data['isBot'] == No){
            return false;
        }

        if($data['isMsg'] == Yes){
            if(
                $data['uid'] != $this->protokol->user['user_id']
                && $data['msgTo'] != $this->protokol->user['user_id']
            ){
                return false;
            }
        }

        return true;
    }
    
    private function messageMakeSafe($row){
    	$row['message'] = $this->remove_bad_words(htmlentities($row['message']));
    	$row['time'] = htmlentities($row['time']);
    	$row['nick'] = htmlentities($row['nick']);
    	if(empty($row['img'])){
    		$row['img'] = "noavatar150.png";
    	}
    	$row['img'] = "../../images/avatars/".$row['img'];
    	return $row;
    }

    private function remove_bad_words($word){
        if($this->sConfig['bad_words_enabled'] != '1'){
            return $word;
        }

        $block = explode("\r\n",$this->sConfig["bad_words"]);
        for($i=0;$i<count($block);$i++){
            if($block[$i] == ""){
                continue;
            }

            $word = preg_replace("/".$block[$i]."/si", $this->sConfig['bad_word_replace'], $word);
        }

        return $word;
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
            if($this->is_flood($message->channel()->cid())){
                $this->handleMessage($message);
                $this->updateActivInChannel($message->channel()->cid());
            }else{
                $this->sendBotPrivMessage(
                   $message->channel()->cid(),
                   "/maxFlood",
                    'red'
                );
            }
        }
    }

    private function is_flood($cid){
        $flood = $this->protokol->get_flood($cid);


        $count = 0;
        $new_flood = array();
        for($i=0;$i<count($flood);$i++){
            $time = $flood[$i];
            if($time < strtotime("-1 minutes")){

            }else{
                $count++;
                $new_flood[] = $flood[$i];
            }
        }

        //vi indsætter en for denne :)
        $count++;

        if($count <= (int)$this->sConfig["flood_interval"]){
            $new_flood[] = time();

            $this->protokol->update_flood($new_flood,$cid);
            return true;
        }else{
            return false;
        }
    }
    
    private function updateActivInChannel($cid){
        $data = Database->query("SELECT *
        FROM `".DB_PREFIX."chat_member`
        WHERE `uid`='".current_user()->id()."'
        AND `cid`='".(int)$cid."'");

    	$row = $data->fetch();
    	
    	if($row['isInAktiv'] == Yes){
			$this->sendBotMessage($row['cid'], "/notInaktiv ".current_user()->nick(), 'green');
    	}
    	
    	$this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `lastActiv`= NOW(), `isInAktiv`='".No."' WHERE `cid`='".(int)$row['cid']."' AND `uid`='".$this->protokol->user['user_id']."'");

        if($this->database->isError){
            exit($this->database->getError());
        }
    }
    
    private function handleMessage($data){
		if($this->getVariabel("cid") == 1){
            //exit("Can not send to server 1!"); Fail from my side :( user can not send join command if user is not allow to write to bot channel :)
			return;
		}

        if($this->websocket){
            $this->send_message_to_users($data['message']);
            return;//websocket sender direkte til brugerenerne så vi har ikke brug for denne del :)
        }

    	$datas = $this->database->prepare("INSERT INTO `".DB_PREFIX."chat_message`
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
                '".(int)$this->protokol->user['user_id']."',
                '".(int)$this->getVariabel("cid")."',
                '".No."',
                NOW(),
                {message},
                {tx},
                '".No."',
                '0'
                )");

        $datas->add("message",$data['message']);
        $datas->add("tx",(string)$this->protokol->userConfig("textColor"));
        $datas->done();

        if($this->database->isError){
            exit($this->database->getError());
        }
    }

    private function get_user_id_from_nick($nick){
        $data = $this->database->prepare("SELECT `user_id` FROM `".DB_PREFIX."users` WHERE `nick`={nick}");
        $data->add("nick",$nick);
        $result = $data->done();
        if($this->database->isError){
            exit("Database error");
        }

        $row = $result->get();

        if(!empty($row['user_id']))
            return $row['user_id'];
        else
            return false;
    }
    
    private function returnCommandName(){
		$data = $this->init_get_data();
        $ex = explode(" ",$data['message']);
        return preg_replace("/^\//",null,$ex[0]);
    }
    
    private function handleCommand(){
    	switch($this->returnCommandName()){
            case 'cookie':
                if(!$this->websocket){
                    exit("ERROR");
                }
                $input = $this->init_get_data();

                if(!$this->getVariabel("client")->login(str_replace("/cookie ",null,$input['message']))){
                    $this->remove_client($this->getVariabel("client")->socket);
                }else{
                    $this->getVariabel("client")->user['user_avatar'] = $this->convert_image(
                        $this->getVariabel("client")->user['user_avatar']
                    );
                    $this->sendBotPrivMessage(1,"/cookieOkay","green");
                    $this->protokol->set_user_data($this->getVariabel("client")->user,$this->getVariabel("client"));
                }
            break;
    		case "getStatus":
    			$this->answer_getStatus();
    		break;
            case "join":
                $this->answer_join();
    		break;
            case "nick":
            	$this->answer_nick();
            break;
            case 'msg':
            	$this->answer_msg();
            break;
            case "config":
            	$this->answer_config();
            break;
            case 'getOnline':
            	$this->do_getOnline();
            break;
            case 'title':
            	$this->doTitle();
            break;
            case 'exit':
            	$this->doExit();
            break;
			case 'getLang':
			    $this->answer_getLang();
			break;
			case 'leave':
			    $this->answer_leave();
			break;
			case 'kick':
				$this->answer_kick();
			break;
			case 'bot':
			    $this->answer_bot();
			break;
			case 'ban':
			    $this->answer_ban();
			break;
			case 'unban':
			    $this->answer_unban();
			break;
            case 'ignore':
                $this->answer_ignore();
            break;
            case 'unIgnore':
                $this->answer_uningore();
            break;
            case 'ping':
                $this->sendBotPrivMessage(1,"/pong","green");
            break;
            case 'update':
                $this->answer_update();
            break;
            case 'getConfig':
                $this->sendBotPrivMessage(1,"/config ".$this->getUserConfig());
            break;
            case 'clear':
                $this->answer_clear();
            break;
            case 'file':
                $this->answer_file();
            break;
            default:
            	$this->sendBotPrivMessage($this->getCidFromChannel($this->post("channel")), "/commandDenaid");
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
    
	private function answer_getLang(){
		$this->sendBotPrivMessage(1,"/langList ".implode(",",$this->getLangList()));
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
    
    private function doTitle(){
    	
		if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/title"));
			return;
		}
		
		$input = $this->init_get_data();
		
    	if(preg_match("/^\/title\s(.*?)$/",$input['message'],$reg)){
			$sql = $this->database->prepare("UPDATE `".DB_PREFIX."chat_name`
			SET `title`={title},
			`setTitle`='".($this->websocket ? (int)$this->getVariabel("client")->user['user_id'] : (int)$this->user['user_id'])."'
			WHERE `id`='".(int)$this->getVariabel("cid")."'");

            $sql->add("title",$reg[1]);
            $sql->done();

    		$this->sendBotMessage($this->getVariabel("cid"), $reg[0],"green");
    	}else{
    		$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['titleBroken']);
    	}
    }
    
    private function do_getOnline(){
		$input = $this->init_get_data();
    	if(preg_match("/^\/getOnline\s(.*?)$/", $input["message"],$reg)){
            $this->sendBotPrivMessage(
                $this->getVariabel("cid"),
                "/online ".implode(" ", $this->getOnline($this->getCidFromChannel($reg[1],true),true))
            );
    	}else{
            exit($input['message']);
        }
    }
    
    //config
    private function answer_config(){
		$input = $this->init_get_data();
    	if(preg_match("/^\/config\s\[(.*?)\]\[(.*?)\]$/", $input['message'],$reg)){
    		switch(trim($reg[1])){
                case 'time':
    			case 'sound':
                case 'textColor':
                case 'lang':

                    $this->protokol->updateConfig($reg[1],$reg[2]);
                    if(trim($reg[1]) == "lang"){
                        //this is lang and it is importen wee update it so fast :D
                        $this->setLang($reg[2]);
                    }

    				$this->sendBotPrivMessage(
                        $this->getVariabel("cid"),
                        $this->lang['configUpdatet'],
                        "green"
                    );

                    $this->sendBotPrivMessage(
                        1,
                        "/updateConfig ".$reg[1]." ".$reg[2],
                        'black'
                    );
                break;
    			default:
    				$this->sendBotPrivMessage($this->getVariabel("cid"), "/error Wrong key: ".$reg[1],"red");
    			break;
    		}
    	}else{
    		$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['brokenConfigCommand'], "red");
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
    
    //nick
    private function answer_nick(){
		$input = $this->init_get_data();
    	if(preg_match("/\/nick\s([a-zA-Z0-9]*?)$/", $input['message'],$reg)){
    		if(!$this->nickKontrol($reg[1])){
    			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['nickTaken'], "red");
    			return;
    		}

            if(($code = $this->is_length_okay($reg[1],1,$this->getConfig("maxNickLengt"))) !== true){
                if($code == self::text_min){

                }elseif($code == self::text_max){
                    $this->sendBotPrivMessage(
                        $this->getVariabel("cid"),
                        '/error '.$this->lang['maxNick'],
                        'red'
                    );
                }
            }else{
                //wee control if user try to change nick to his nick o.O
                if($this->protokol->user['nick'] == $reg[1]){
                    $this->sendBotPrivMessage(
                        $this->getVariabel("cid"),
                        '/error '.sprintf($this->lang['nickIsYour'],$this->protokol->user['nick']),
                        'red'
                    );
                    return;
                }
                $oldNick = $this->protokol->user['nick'];
                $data = $this->database->prepare("UPDATE `".DB_PREFIX."users` SET `nick`={nick} WHERE `user_id`='".$this->protokol->user['user_id']."'");
                $data->add("nick",$reg[1]);
                $data->done();
                $this->protokol->update_nick($reg[1]);
                if($this->websocket){
                    foreach($this->getVariabel("client")->channel as $id => $name){
                        $this->sendBotMessage(
                            $id,
                            '/nick '.$oldNick,
                            'green'
                        );
                    }
                }else{
                    $data = $this->database->query("SELECT `cid` FROM `".DB_PREFIX."chat_member` WHERE `uid`='".$this->protokol->user['user_id']."' AND `cid`<>'1'");
                    while($row = $data->get()){
                        $this->sendBotMessage($row['cid'], "/nick ".$oldNick,"green");
                    }
                }
            }
        }else{
            $this->json['isOkay'] = "false";
    		$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['nickBroken'], "red");
    	}
    }
    
    //join

     private function isMemberOfChannel($name){
         foreach($this->protokol->get_my_channel_list(false) as $data){
             if(!empty($data['name']) && $data['name'] == $name){
                 return true;
             }
         }

         return false;
     }

     private function isChannelExists($name){
         foreach($this->protokol->get_channel_list() as $cid => $data){
             if($data['name'] == $name){
                 return array_merge($data,array('id' => $cid));
             }
         }

         return false;
     }

     private function getCannelIDFromChannelName($name){
         foreach($this->protokol->get_channel_list() as $cid => $data){
             if($data['name'] == $name){
                 return $cid;
             }
         }

         return 1;
     }

    private function answer_join(){
		$input = $this->init_get_data();
    	if(preg_match("/^\/join #([a-zA-Z0-9]*?)$/", $input['message'], $reg)){
			if(!$this->parseJoinChannelName($reg[1])){
				$this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['invalidJoin']);
				return;
			}
    		$channelName = "#".$reg[1];

            if($this->isMemberOfChannel($channelName)){
                //vi skal nu se om vi er bannet :)
                if(!in_array($this->protokol->user['user_id'],$this->protokol->getBannetInChannel($this->getCannelIDFromChannelName($channelName)))){
                    $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".sprintf($this->lang['isMember'],$channelName));
                    return;
                }else{
                    //brugeren er bannet :)
                    if(!$this->allowJoinInBannetChannel($this->getCannelIDFromChannelName($channelName))){
                        $this->sendBotMessage(
                            1,
                            "/bannet ".$channelName,
                            "red"
                        );
                        return;
                    }
                }
            }

            if($data = $this->isChannelExists($channelName)){
                $this->join_user_in_channel($data);
            }else{
               $data =  $this->joinUserInNewChannel($channelName);
            }

    		$this->sendBotPrivMessage((int)$data['id'], "/title ".$data['title'],"green",null,0);
    		
    	}else{
			$this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['invalidJoin']);
    	}
    }

     private function allowJoinInBannetChannel($cid){
         $sql = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_member` WHERE `cid`='".(int)$cid."' AND `uid`='".$this->protokol->user['user_id']."'");

         if($this->database->isError){
             exit($this->database->getError());
         }

         $result = $sql->get();

         if(time() > $result['banTo']){
             $this->protokol->remove_ban(
                 $result['uid'],
                 $result['cid'],
                 0
             );
             return true;
         }

         return false;
     }

     private function join_user_in_channel($data){
         $this->protokol->add_to_channel($data['id']);
         $this->sendBotMessage($data['id'],"/join","green");
         $this->sendBotPrivMessage(1,"/join ".$data['name']);
         return $data;
     }
	 
	 private function parseJoinChannelName($name){

         if($this->sConfig['bad_words_enabled'] != '1'){
             return true;
         }

		 //is ther any bad word??
         $sp = explode("\r\n",$this->sConfig['bad_words']);
		 for($i=0;$i<count($sp);$i++){
			 
			 if($sp[$i] == ""){
				 continue;
			 }
			 
			 if(preg_match("/".$sp[$i]."/si",$name)){
				 return false;
			 }
		 }
		 
		 return true;
	}
	 
    private function joinUserInNewChannel($channel){
		$data = $this->protokol->new_channel($channel);
        $this->channel = $this->protokol->get_channel_list();
        $this->protokol->add_to_channel($data['id']);

    	$this->sendBotMessage($data['id'],"/join","green");
        $this->sendBotPrivMessage(1,"/join ".$channel);
    	
    	//join user ;)
    	return $data;
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
    
    //getStatus
    private function answer_getStatus(){
        $this->sendBotPrivMessage(1,"/getStatus you are user");
		$this->sendBotPrivMessage($this->getVariabel("cid"),"/profilImage ".$this->convert_image(
                $this->protokol->user['user_avatar']
            ));
    }
    
    //nick control
    
    private function nickKontrol($nick){
		$data = $this->database->prepare("SELECT `user_id` FROM `".DB_PREFIX."users` WHERE `user_name`={nick} AND `user_id`!='".$this->protokol->user['user_id']."' OR `nick`={nick} AND `user_id`!='".$this->protokol->user['user_id']."'");
        $data->add("nick",$nick);
        $row = $data->done()->get();
    	return (empty($row['user_id']) ? true : false);
    }
    
    //user config
    private function getUserConfig(){
    	$return = array();
		
		$data = $this->database->query("SELECT *
		FROM `".DB_PREFIX."chat_userConfig`
		WHERE `uid`='".$this->protokol->user['user_id']."'");

    	while($row = $data->get()){
    		$return[] = $row['key']."=".$row['value'];
    	}
    	
    	return implode(";",$return);
    }
    
    //load sektion

     private function get_base_part($numBack = 0){
         if($this->basepart !== null){
             return $this->basepart;
         }

         $file = __FILE__;
         //remove server.php ;)
         $file = preg_replace("/server\.php$/",null,$file);

         if($numBack !== 0){
            $block = explode("\\",$file);

             for($i=0;$i<$numBack;$i++){
                 unset($block[count($block)-1]);
             }

             $file = implode("\\",$block)."\\";
         }

         return $file;
     }

     private function init_protokol(){
         if($this->getConfig("protokol") == "socket"){
             include $this->get_base_part()."lib\\protokol\\websocket.php";
         }else{
             include ("lib/protokol/ajax.php");
         }
         $this->protokol = new Protokol($this->database,$this);
     }

     private function init_system_setting(){
         $data = $this->database->query("SELECT * FROM `".DB_PREFIX."settings"."`");
         while($row = $data->get()){
             $this->sConfig[$row['settings_name']] = $row['settings_value'];
         }
     }

     private function get_system_config($name){
         if(empty($this->sConfig[$name])){
             return null;
         }

         return $this->sConfig[$name];
     }

     private function init_db(){
         $this->database = new DatabaseHandler(
             $this->getVariabel("db_host"),
             $this->getVariabel("db_user"),
             $this->getVariabel("db_pass"),
             $this->getVariabel("db_data")
         );

         if($this->database->isError){
             exit($this->database->getError());
         }

         unset($this->variabel['db_host']);
         unset($this->variabel['db_user']);
         unset($this->variabel['db_pass']);
         unset($this->variabel['db_data']);
     }
    
    private function loadPages(){
        if($this->websocket){
            $load = array(
                $this->get_base_part()."lib\\define.php",
                $this->get_base_part()."lib\\db.php",
                $this->get_base_part()."lib\\protokol\\Protokol.php",
                $this->get_base_part()."lib\\db\\mysqli.php"
            );
        }else{
            $load = array(
                'lib/define.php',
                'lib/db.php',
                'lib/protokol/Protokol.php',
                'lib/db/mysqli.php'
            );
        }

        for($i=0;$i<count($load);$i++){
            include $load[$i];
        }

        //vi skal nu have fat i php-fusions config ;)
        $db_host = $db_user = $db_pass = $db_name = null;//set all to null ;)
        if($this->websocket)
            include($this->get_base_part(3)."config.php");
        else
            include("../../config.php");
        $this->variabel['db_host'] = $db_host;
        $this->variabel['db_user'] = $db_user;
        $this->variabel['db_pass'] = $db_pass;
        $this->variabel['db_data'] = $db_name;
    }
    
    //user sektion
    private function userInit(){
		$this->initUser();
		$this->loadUserConfig();
        $this->setLang($this->userConfig['lang']);
		$this->variabel["cid"] = $this->post("channel") ? $this->getCidFromChannel($this->post("channel")) : 1;
    }
    
	 private function convert_image($img){
		if(empty($img) || !trim($img)){
			$img = "../../images/avatars/noavatar100.png";
		}else{
			$img = "../../images/avatars/".$img;
		}
		 
		 return $img;
	 }
	 
    private function initUser(){
		if($this->login()){
           //do nothing :)
		}else{
            $this->json['location'] = "../../index.php?error=sessiong";
            exit(json_encode($this->json));
        }
	}

     private function get_nick_from_user_id($uid){
         if($this->websocket){
             foreach($this->clientObj AS $clientOBJ){
                 if($clientOBJ->isLogin && $clientOBJ->user['user_id'] == $uid){
                     return $clientOBJ->user['nick'];
                 }
             }

             return null;
         }
         return null;
     }
    
    private function loadUserConfig(){
        $sql = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_userConfig` WHERE `uid`='".($this->websocket ? $this->getVariabel("client")->user['id'] : $this->user['user_id'])."'");
        while($row = $sql->get()){
            $this->userConfig[$row['key']] = $row['value'];
        }
    }
    
    //variabel sektion
    
    private function getVariabel($key){
        if(empty($this->variabel[$key])){
            return null;
        }else{
            if(is_object($this->variabel[$key])){
                return $this->variabel[$key];
            }
            if(!is_array($this->variabel[$key]) && !trim($this->variabel[$key])){
                return null;
            }

        }
        
        return $this->variabel[$key];
    }
    
    private function loadVariabel(){
        $this->variabel['isPost']    = $this->get("isPost")    ? true : false;
        $this->variabel['last_id']   = $this->get('li')        ? (int)$this->get('li') : 0;
    }
    
    private function loadDatabaseConfig(){
        $data = $this->database->query("SELECT * FROM `".DB_PREFIX."settings_inf` WHERE `settings_inf`='chat'");
        while($row = $data->get()){
            $this->config[$row['settings_name']] = $row['settings_value'];
        }
    }
    
    private function getConfig($key){
        if(empty($this->config[$key])){
            return null;
        }
        
        return $this->config[$key];
    }
    
    //session sektion
    private function sessionInit(){

        //wee kontrol if header is sendt :)
        if(headers_sent()){
            exit("Header is allray sendt!");
        }

        if(!$this->isSessionStarted()){
            $this->startSession();
        }
    }

    private function ip(){
        return $_SERVER['REMOTE_ADDR'];
    }

    private function login(){
       if (cookie("chat_token")){
          $part = explode(",", cookie("chat_token"));
          if(count($part) != 2){
             cookieDestroy("chat_token");
             return false;
          }

          //get the id from the chat token
          $id = $part[0] - 123456789; 
          //look after the user in the database
          $query = Database::query("SELECT * FROM ".table("user")." WHERE `id`='".$id."'");
          if($query->rows() != 1){
            cookieDestroy("chat_token");
            return false;
          }

          //control if the hash value is the same and the ip is the same
          $data = $query->fetch();
          if($data["hash"] != $part[1] || ip() != $data["ip"]){
             cookieDestroy("chat_token");
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
       }elseif(!Server::is_cli() && post("nick")){//geaust login

       }

       //in some case (mostly websocket) the user object for this user can already be created. This is mostly when the user has refreshing the webpage.
       if(getUser($data["id"]) == null){
          User::push($data, true);//push user to our object database and make it as the current user.
       }
       return true;
    }
    
    //send message

     private function send_message_to_users($message){
         if($this->websocket){

             $input = $this->init_get_data();

             $this->display(
                 $input['channel'],
                 $this->getVariabel('client')->user['nick'],
                 $message
             );

             for($i=0;$i<count($this->clientObj);$i++){
                 $client = $this->clientObj[$i];
                 if(!$client->isLogin){
                     continue;
                 }

                 if(!empty($client->channel[$this->getVariabel("cid")]) && !in_array($this->protokol->user['user_id'],$this->protokol->get_ignore($client->user['user_id']))){
                     $client->message(array(
                         'cid'      => $this->getVariabel("cid"),
                         'message'  => $this->remove_bad_words($message),
                         'color'    => $this->getVariabel("client")->user_config['textColor'],
                         'uid'      => $this->getVariabel("client")->user['user_id'],
                         'nick'     => $this->getVariabel("client")->user['nick'],
                         'channel'  => $this->protokol->get_channel_by_id($this->getVariabel("cid")),
                         'img'      => $this->getVariabel("client")->user['user_avatar']
                     ));
                 }
             }
         }
     }

    private function sendBotMessage($cid,$message,$color = false, $uid = false){
		if($this->websocket){
			$input = $this->init_get_data();
            $this->display(
                $input['channel'],
                'Bot',
                $message
            );
			for($i=0;$i<count($this->clientObj);$i++){
				$client = $this->clientObj[$i];
                if(!$client->isLogin){
                    continue;
                }
                if(!empty($client->channel[$cid])){
					$client->bot_message(array(
						'cid'     => $cid,
						'message' => $message,
						'color'   => ($color === false ? $this->getConfig("botTextColor") : $color),
						'uid'     => $uid !== false ? $uid : $this->getVariabel("client")->user['user_id'],
						'nick'    => $uid !== false ? $this->get_nick_from_user_id($uid) : $this->getVariabel("client")->user['nick'],
						'channel' => $this->protokol->get_channel_by_id($cid),
						'img'     => $this->getVariabel("client")->user['user_avatar']
					));
				}
			}
			return;
		}
		
    	    if(!$uid){
    	    	$uid = $this->user['user_id'];
    	    }
    	
            if($color === false){
                $color = $this->getConfig("botTextColor");
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
                '".(int)$uid."',
                '".(int)$cid."',
                '".Yes."',
                NOW(),
                {message},
                {color},
                '".No."',
                '0'
                )");

        $data->add("message",$message);
        $data->add("color",$color);
        $data->done();
    }
    
    private function sendBotPrivMessage($cid,$message,$color = false,$uid=null,$my=null){
		
		if($this->websocket){
            $this->display(
                ($cid === 1 ? 'Globel' : $this->protokol->get_channel_by_id($cid)),
                'Bot',
                $message
            );
			for($i=0;$i<count($this->clientObj);$i++){
				$client = $this->clientObj[$i];
                if(!$client->isLogin){
                    continue;
                }
				$m = ($uid === null ? $this->getVariabel("client")->user['user_id'] : $uid);
                if(!empty($client->channel[$cid]) || $cid == 1){
                    if($m==$client->user['user_id']){
					$client->bot_message(array(
						'cid'     => $cid,
						'message' => $message,
						'color'   => ($color ? $color : 'yellow'),
						'uid'     => ($my === null ? $this->getVariabel("client")->user['user_id'] : $my),
						'nick'    => $this->getVariabel("client")->user['nick'],
						'channel' => $this->protokol->get_channel_by_id($cid),
						'img'     => $this->getVariabel("client")->user['user_avatar'],
					));
                    }
				}
			}
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
                '".(int)($my === null ? $this->user['user_id'] : $my)."',
                '".(int)$cid."',
                '".Yes."',
                NOW(),
                {message},
                {color},
                '".Yes."',
                '".(int)($uid === null ? $this->user['user_id'] : $uid)."'
                )");

        $data->add("message",$message);
        $data->add("color",($color ? $color : 'yellow'));
        $data->done();

        if($this->database->isError)
            exit($this->database->getError());
    }
	 
	 private function ban($channel,$uid,$to,$nick){
		 $cid = $this->getCidFromChannel($channel,false);
		 $this->sendBotPrivMessage(1,"/ban ".$channel,"red",$uid,0);

         //Both Ajax and WebSocket need this (Websocket when admin write /update)
         $this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `ban`='".Yes."', `banTo`='".(int)$to."' WHERE `uid`='".(int)$uid."' AND `cid`='".(int)$cid."'");


         $this->protokol->banUser($cid,$uid,$to);
		 $this->sendBotMessage($cid,"/ban ".$nick,"red");
	 }
    
	 //Yes my dear :D 
	 private function kick($channel,$message = null,$uid = 0, $sendMessage = true){
		 if($uid === 0){
			 $uid = $this->protokol->user['user_id'];
		 }

         if(!is_numeric($channel)){
             $cid = $this->getCidFromChannel($channel,false);
         }else{
             $cid = (int)$channel;
         }
		 
		 if($cid == 1){
			 return;
		 }

         if($sendMessage){
             $this->sendBotPrivMessage(
                 1,
                 "/kick ".$channel.($message !== null ? ' '.$message : null),
                 "red",
                 $uid,
                 $uid//kun for denne bruger :D
             );
         }

         $this->protokol->kick(
             $cid,
             $uid
         );

         if($sendMessage){
             $this->sendBotMessage($cid, "/kick".($message !== null ? " ".$message : null),"red",$uid);
         }
	 }
 }
 
new Server()->inilize();

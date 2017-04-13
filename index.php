<?php

use Inc\User\PasswordRecovery;

//this will debug sql. Please only use it when it not life
//define("SQL_DEBUG", true);
define("CHAT_VERSION", "V1.5");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define("Yes",1);
define("No",2);
class Server{
     private $variabel         = array();
     private $database         = null;
     private $plugin           = null;//append in version 1.1
     private $tempelate        = null;
     private $postdata         = null;
     private $user             = null;
	private $postdata;
     
    function __construct($websocket = false){
        header("Expires: Mon, 26 Jul 12012 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
   
        $this->loadPages();
        
        $this->sessionInit();
	$this->plugin->trigger("server.start", [time()]);//added in version 1.1
        $user = $this->user = $this->login();

        if($user == null){
	  //in this area wee controle if the user has request a new password
	  PasswordRecovery::controle();
          if(Request::getView() == Request::VIEW_AJAX){
            exit("login");
          }
	  if(Request::get("view")){
		  if(!$this->handleView()){
			  header("HTTP/1.0 404 Not Found");
			  exit("Page not found");
		  }
		  return;
	  }
          $this->doLogin();
          return;
        }

        Answer::setUser($user);

        if(Request::getView() == Request::VIEW_HTML){
          $this->showChat($user);
          return;
        }

        $id = $this->init_lastIndex();
		
	//ajax only ;) if it is not ajax it will not work!
	if(Request::get("isPost")){
        	$this->handlePost($user, Request::post("message"), Request::get("channel") ? Request::get("channel") : "Bot");
        }
	    
	if(!Request::get("noMessage")){//append in version 1.1
           $this->showMessage($user, $id);
	}

        if($this->database->isError){
            exit($this->database->getError());
        }

        if(defined("SQL_DEBUG")){
           $debug = $this->database->getDebug();
           $cid = $this->get("channel") ?  $this->getCidFromChannel($user, $this->get("channel")) : 1;
           bot_self($cid, "[color=green][DEBUG]Number of sql query: ".count($debug)."[/color]");
           foreach($debug as $sql){
             bot_self($cid, "[color=green][DEBUG]".$sql."[/color]");
           }
        }

        Answer::outputAjax();
    }
	
    public function getTempelate() : Tempelate{
	if(Request::getView() == Request::VIEW_HTML){
		return $this->tempelate;
	}
	throw new Exception("Tempelate object is only avariabel in html view");
    }
	
    public function postData() : PostData{
	    if($this->postdata){
		    return $this->postdata;
	    }
	    throw new Exception("PostData is not avariabel");
    }
	
    public function getDatabase() : DatabaseHandler{
	    if($this->database){
		   return $this->database; 
	    }
	    throw new Exception("Database is not set yet");
    }
	
    public function getCurrentUser() : User{
	    if($this->user){
		    return $this->user;
	    }
	    throw new Exception("No user is login");
    }
	
    public function isLogin() : bool{
	    return $this->user !== null;
    }
	
    private function handleView() : bool{
	    switch(Request::get("view")){
		    case "password-request":
			    Language::load("password_request");
			    $this->showTempelate("password_request");
		    break;
		    default:
			    return false;
	    }
	    return true;
    }

    private function rawJs(User $user){
      $js = "var myNick = '".$user->nick()."';\r\n";
      $js .= "var updateFrame = ".(int)Config::get("updateFrame").";\r\n";
      $query = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_smylie`");
      while($row = $query->get()){
        $js .= "smylie.push({
  tag : '".$row["tag"]."',
  url : '".$row["url"]."'
});";
      }
      
      $this->garbageMember();
      $query = $this->database->query("SELECT cn.name
                                       FROM `".DB_PREFIX."chat_name` AS cn
                                       LEFT JOIN `".DB_PREFIX."chat_member` AS cm ON cn.id=cm.cid
                                       WHERE cm.uid='".$user->id()."'
                                       AND cn.id<>1
                                       AND LOCATE('b', cm.mode) = 0");
      
      $buffer = [];
      while($row = $query->get()){
	$buffer[] = $row["name"];
      }
	    
      if(count($buffer) !== 0){
	   $this->tempelate->putVariabel("channels", $buffer);
      }
	    
      $this->tempelate->putVariabel("rawjs", $js);
    }

    private function showChat(User $user){
      if(is_admin($user->id())){
	      include "./lib/updater.php";
	      Updater::controle($this->database);
      }
      Language::load("main");
      Request::unsetSession("li");
      if(Request::get("logout") && Request::get("sess_id") == session_id()){
	Request::unsetSession("uid");
        header("location:#");
        exit;
      }
      $this->tempelate->putBlock([
	      "avatar"   => $user->avatar(),
	      "username" => $user->username(),
	      "isadmin"  => is_admin($user->id()),
	      "js"       => [
		      "js/main.js",
		      "js/pages.js",
		      "js/userlist.js",
		      "js/user.js",
		      "js/command.js",
		      "js/bbcode.js",
		      "js/bbcode_help.js",
		      "js/lang/".Language::getCode().".js",
		      "https://raw.githubusercontent.com/less/less.js/v2.7.2/dist/less.min.js",
		      ]
	      ]);
      
      $this->rawJs($user);
      $this->plugin->trigger("client.loaded", []);
      $this->showTempelate("main");
    }
    
    //inaktiv sektion
    private function garbageMember(){
        $data = $this->database->query("SELECT cm.isInAktiv, cm.id, cm.uid , us.nick, cm.cid, cn.name, cn.members
        FROM `".DB_PREFIX."chat_member` AS cm
        LEFT JOIN `".DB_PREFIX."chat_user` AS us ON us.id = cm.uid
        LEFT JOIN `".DB_PREFIX."chat_name` AS cn ON cn.id=cm.cid
        WHERE cm.cid != '1'
        AND LOCATE('b', cm.mode) = 0
        AND (cm.lastActiv < DATE_SUB(now(), INTERVAL ".(int)Config::get("inaktiv")." MINUTE)
            AND cm.isInaktiv='".No."'
            OR cm.lastActiv < DATE_SUB(now(), INTERVAL ".(int)Config::get("leave")." MINUTE)
        )");

        if($this->database->isError){
          exit($this->database->getError());
        }

        while($row = $data->get())
            $this->handle_inaktiv($row);
    }

    private function handle_inaktiv($row){
        if($row['isInAktiv'] == Yes){
            $this->do_leave($row);
        }else{
         $this->do_inaktiv($row);
        }
    }
    
    private function do_leave($row){
        bot_other(
          $row["uid"],
          $row["cid"],
          "/leave ".$row["nick"]
        );
        
	//In version 1.3 wee handle the data wee allredy know(How many members there are) Wee will avoid to calculate it 
        $this->removeUserMember($row["cid"], $row["uid"], $row["members"]);
        bot_self_other(
          $row["uid"],
          1,
          "/leave ".$row["name"]
        );
    }
    
    private function do_inaktiv($row){
		$this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `isInAktiv`='".Yes."' WHERE `id`='".$row['id']."'");
        if($this->database->isError){
            exit($this->database->getError());
        }
        bot_other(
          $row["uid"],
          $row["cid"],
          "/inaktiv ".$row["nick"]
        );
    }

    private function init_lastIndex() : int{
      if(!Request::session("li")){
        $query = $this->database->query("SELECT `id` FROM `".DB_PREFIX."chat_message` ORDER BY id DESC LIMIT 1");
        if($this->database->isError){
          exit($this->database->getError());
        }
        $data = $query->get();
        if(!$data){
          return 0;
        }else{
          Request::setSession("li", $data["id"]);
          return $data["id"];
        }
      }else{
        return Request::session("li");
      }
    }
    
    //message sektion
    private function showMessage(User $user, int $id){
	$data = $this->database->query("SELECT tm.id AS id, tm.message AS message, tm.isMsg AS isMsg, tm.msgTo AS msgTo, tm.isBot AS isBot, user.nick AS nick, tm.time AS time, tn.id AS cid, tn.name AS channel, cm.id AS cmid, user.id AS uid, user.avatar, tn.isPriv AS isPriv
		FROM ".DB_PREFIX."chat_message AS tm
		LEFT JOIN ".DB_PREFIX."chat_member AS cm ON tm.cid = cm.cid
		LEFT JOIN ".DB_PREFIX."chat_user AS user ON user.id = tm.uid
		LEFT JOIN ".DB_PREFIX."chat_name AS tn ON tn.id = cm.cid
		WHERE cm.uid = '".(int)$user->id()."'
		AND tm.id > '".$id."'
                AND LOCATE('b', cm.mode) = 0
                GROUP BY tm.id
		ORDER BY tm.id ASC ");

        if($this->database->isError){
            exit($this->database->getError());
        }

        $cache_id = -1;
    	while($row = $data->get()){
            if($cache_id < $row["id"])
              $cache_id = $row["id"];

            if($this->shouldShow($row, $user)){
                Answer::outputToUser($this->convertMessage($row));
            }
    	}

        if($cache_id != -1){
	  Request::setSession("li", $cache_id);
        }
        $this->garbageMember();
    }

    private function shouldShow(array $data, User $user){
        if($data["isBot"] == No && in_array($data['uid'],$user->ignoreList())){
            return false;
        }

        if($data['isMsg'] == Yes){
            return $data["uid"] == $user->id() || $data["msgTo"] == $user->id();
        }

        return true;
    }

    private function convertMessage(array $row) : AnswerRequest{
       $data = new AnswerRequest();
       $data->setNick($row["isBot"] == Yes ? "Bot" : $row["nick"]);
       $data->setTime(strtotime($row["time"]));
       $data->setMessage($this->remove_bad_words($row["message"]));
       $data->setChannel($row["channel"]);
       $data->setAvatar($row["avatar"]);
       return $data;
    }

    private function remove_bad_words($word){
        if(Config::get('bad_words_enabled') != '1'){
            return $word;
        }

        $block = Config::get("bad_words");
        for($i=0;$i<count($block);$i++){
            if($block[$i] == ""){
                continue;
            }

            $word = preg_replace("/".$block[$i]."/si", str_repeat(Config::get('bad_word_replace'), strlen($block[$i])), $word);
        }

        return $word;
    }
    
    private function getUserIdFromNick(string $nick, int $id = -1){
        $sql = "";
        $end = "";
        if($id != -1){
          $sql = " LEFT JOIN `".DB_PREFIX."chat_member` as cm ON cm.uid=user.id";
          $end = " AND cm.cid='".$id."'";
        }
    	$data = $this->database->query("SELECT user.id FROM `".DB_PREFIX."chat_user` AS user".$sql." WHERE BINARY user.nick='".$this->database->clean($nick)."'".$end);
        if($this->database->isError)
            exit($this->database->getError());
    	$row = $data->get();
    	return (empty($row['id']) ? 0 : $row['id']);
    }

    protected function getCidFromChannel(User $user, string $name){
        if($name == "Bot"){
          return 1;
        }
        $query = $this->database->query("SELECT name.id 
                    FROM `".DB_PREFIX."chat_name` AS name
                    LEFT JOIN `".DB_PREFIX."chat_member` AS member ON name.id=member.cid
                    WHERE member.uid='".$user->id()."'
                    AND   name.name='".$this->database->clean($name)."'");
        if($this->database->isError){
          exit($this->database->getError());
        }

        $data = $query->get();
        return $data ? $data["id"] : 1;
    }
	 
    private function handlePost(User $user, string $message, string $channel){
        $this->postdata = $post = new PostData(
           $message,
           $channel,
           $this->getCidFromChannel($user, $channel)
        );

        if($post->id() != 1 && $this->hasUserMode($post->id(), $user->id(), "b")){
            return;
        }

    	if($post->isCommand()){
    		$this->handleCommand($user, $post);
    	}else{
            if($this->hasUserMode($post->id(), $user->id(), "o") || $this->is_flood($post->id())){
                $this->handleMessage($user, $post);

                if($post->id() !== 1){
                    $this->updateActivInChannel($user, $post->id());
                }
            }else{
                $this->error($post, "flood");
            }
        }
    }

    private function is_flood($cid){
        if(!Request::session("flood")){
	  Request::setSession("flood", [$cid=>[]]);
        }elseif(empty(Request::session("flood")[$cid])){
          $item = Request::session("flood");
          $item[$cid]=[];
          Request::setSession("flood", $item);
        }
        $flood = Request::session("flood")[$cid];

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

        if($count <= (int)Config::get("flood_count")){
            $new_flood[] = time();
	    $item = Request::get("flood");
	    $item[$cid] = $new_flood;
            Request::setSession("flood", $item);
            return true;
        }else{
            return false;
        }
    }
    
    private function updateActivInChannel(User $user, int $cid){
        $data = $this->database->query("SELECT *
        FROM `".DB_PREFIX."chat_member`
        WHERE `uid`='".(int)$user->id()."'
        AND `cid`='".(int)$cid."'");

        if($this->database->isError){
            exit($this->database->getError());
        }

    	$row = $data->get();
    	
    	if($row['isInAktiv'] == Yes){
             bot($cid, "/notInaktiv ".$user->nick());
    	}
    	
    	$this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `lastActiv`= NOW(), `isInAktiv`='".No."' WHERE `cid`='".(int)$cid."' AND `uid`='".$user->id()."'");

        if($this->database->isError){
            exit($this->database->getError());
        }
    }
    
    private function handleMessage(User $user, PostData $data){
	if($data->id() == 1){
		return;
	}

    	$datas = $this->database->query("INSERT INTO `".DB_PREFIX."chat_message`
            (
            `uid`,
            `cid`,
            `isBot`,
            `time`,
            `message`,
            `isMsg`,
            `msgTo`
            ) VALUE (
                '".$user->id()."',
                '".$data->id()."',
                '".No."',
                NOW(),
                '".$this->database->clean($data->getMessage())."',
                '".No."',
                '0'
                )");

        if($this->database->isError){
            exit($this->database->getError());
        }
    }

    private function getNickFromUid(User $user, int $uid){
      if($user->id() == $uid){
        return $user->nick();
      }
      $data = $this->database->query("SELECT `nick` FROM `".DB_PREFIX."chat_user` WHERE `id`='".$uid."'");
      if($this->database->isError){
        exit($this->database->getError());
      }
      return $data->get()["nick"];
    }
    
    private function handleCommand(User $user, PostData $data){
    	switch($data->getCommand()){
            case "avatar":
                $this->on_avatar($user, $data);
            break;
            case "join":
                $this->answer_join($user, $data);
    	    break;
            case "nick":
            	$this->answer_nick($user, $data);
            break;
            case 'msg':
            	$this->answer_msg($user, $data);
            break;
            case 'title':
            	$this->doTitle($user, $data);
            break;
            case 'exit':
            	$this->doExit($user);
            break;
	    case 'leave':
                $this->answer_leave($user, $data);
	    break;
	    case 'kick':
		$this->answer_kick($user, $data);
	    break;
	    case 'bot':
	        $this->answer_bot($user, $data);
	    break;
            case 'ignore':
                $this->answer_ignore($user, $data);
            break;
            case 'unignore':
                $this->answer_uningore($user, $data);
            break;
            case "mode":
                $this->onMode($user, $data);
            break;
	    case "userlist":
                $this->answer_userlist($user, $data);
	    break;
	    case "errorlist":
	        $this->answer_errorlist($user, $data);
	    break;
            case "online":
                if($data->id() == 1){
                  return;
                }
                bot_self($data->id(), "/online ".$this->getOnline($data->id()));
            break;
            default:
	        if(!$this->plugin->command($data->getCommand(), $user, $data)){
                   $this->error($data, "unkownCommand");
		}
            break;
    	}
    }
	
    private function answer_errorlist(User $user, PostData $post){
	    if(!is_admin($user->id())){
		    error($post, "accessDeniad");
		    return;
	    }
	    
	    $query = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_error`");
	    $buffer = [];
	    while($row = $query->get()){
		    $buffer[] = $row;
	    }
	    
	    //this is the best way i has come on.....
	    bot_self($post->getChannel(), "/errorlist ".base64_encode(json_encode($buffer)));
    }
	
    private function answer_userlist(User $user, PostData $post){
	    if(!is_admin($user->id())){
		    error($post, "accessDenaid");
		    return;
	    }
	    
	    $query = $this->database->query("SELECT `id`, `username`, `nick` FROM `".DB_PREFIX."chat_user`");
	    $buffer = [];
	    while($row = $query->get()){
		    $buffer[] = implode(",", array_values($row));
	    }
	    
	    bot_self($post->getChannel(), "/userlist ".implode(" ", $buffer));
    }

     private function onMode(User $user, PostData $post){
       $data = explode(" ", substr($post->getMessage(), 6));
       if(count($data) !== 2){
         $this->error($post, "invalidMode");
         return;
       }
       if(!$this->hasUserMode($post->id(), $user->id(), "o")){
         $this->error($post, "accessDeniad");
         return;
       }
       $u = $this->getUserObject($data[0]);
       if($this->setUserMode($post, $u, $data[1]) && $data[1] == "+b"){
         bot_self_other($u->id(), 1, "/ban ".$post->getChannel());
       }
     }

     private function on_avatar(User $user, PostData $post){
        $url = substr($post->getMessage(), 8);
        if($this->getConfig("cache_avatar")){
          $url = $this->cacheAvatar($url, $user);
        }
        
        $user->avatar($url);
        bot(1, "/avatar ".$url);
     }

     private function cacheAvatar(string $url, User $user) : string{
        $n = "./img/avatar/".$user->id().".".substr($url, strrpos($url, ".")+1);
        file_put_contents($n, file_get_contents($url));
        return $n;
     }

     private function answer_uningore(User $user, PostData $post){
         if(preg_match("/^\/unignore\s([a-zA-Z]*?)$/", $post->getMessage(), $reg)){
             if(($uid = $this->getUserIdFromNick($reg[1])) !== 0){
                 if(!in_array($uid, $user->ignoreList())){
                     $this->error($post, "notIgnore");
                     return;
                 }
                 $user->unIgnore($uid);
                 bot_self($post->getChannel(), "/unignore ".$reg[1]);
             }else{
                 $this->error($post, "unknownUser");
             }
         }else{
             $this->error($post, "invalidUnignore");
         }
     }

     private function answer_ignore(User $user, PostData $post){
         if(preg_match("/^\/ignore\s([a-zA-Z]*?)$/",$post->getMessage(), $reg)){
             if(($uid = $this->getUserIdFromNick($reg[1])) !== 0){
                 if(in_array($uid,$user->ignoreList())){
                     $this->error($post, "isIgnore");
                     return;
                 }
                 bot_self($post->id(), "/ignore ".$reg[1]);
                 $user->appendIgnore($uid);
             }else{
                 $this->error($post, "unknownUser");
             }
         }else{
             $this->error($post, "invalidIgnore");
         }
     }
	 
     private function answer_bot(User $user, PostData $post){
        if(!$this->hasUserMode($post->id(), $user->id(), "o")){
           $this->error($post, "accessDeniad");
           return;
        }
        bot($post->id(), substr($post->getMessage(), 5));
     }
	 
	 private function answer_leave(User $user, PostData $post){
		 if($post->id() == 1){
			 return;
		 }
		 
	 $query = $this->database->query("SELECT `cid`, `uid` FROM `".DB_PREFIX."chat_member` WHERE `cid`='".$post->id()."' AND `uid`='".$user->id()."'");
         if($this->database->isError){
             exit($this->database->getError());
         }

         bot(1, "/leave ".$post->getChannel());

         while($row = $query->get())
           $this->removeUserMember($row["cid"], $row["uid"]);

         bot_other($user->id(), $post->id(), "/leave ".$post->getChannel());
       }
	 
    private function answer_kick(User $user, PostData $post){
        if($post->id() == 1 || !$this->hasUserMode($post->id(), $user->id(), "o") && !$this->hasUserMode($post->id(), $user->id(), "o")){
          $this->error($post, "accessDeniad");
          return;
        }

        $msg = $post->getMessage();
        if(trim($msg) == "/kick"){
          $this->error($post, "invalidKick");
          return;
        }

        $msg = substr($msg, 6);
        $u = null;
        $message = "Goodby";
        if(($pos = strpos($msg, " ")) !== false){
          $u = substr($msg, 0, $pos);
          $message  = substr($msg, $pos+1);
        }else{
          $u = $msg;
        }
     
        $uid = $this->getUserIdFromNick($u, $post->id());
        if($uid == 0){
          $this->error($post, "unknownUser");
          return;
        }

        bot_self_other(
          $uid,
          1,
          "/kick ".$user->nick()." ".$post->getChannel()." ".$message
        );
        system_log($user->nick()."(".$user->id().") kicked ".$u."(".$uid.") out from the channel: ".$post->getChannel());
        $this->removeUserMember($post->id(), $uid);

        bot($post->id(), "/kick ".$user->nick()." ".$u." ".$message);
    }
    
    private function doExit(User $user){
        $query = $this->database->query("SELECT `cid` 
                  FROM `".DB_PREFIX."chat_member`
                  WHERE `uid`='".$user->id()."'
                  AND `id`<>'1'
                  AND LOCATE('b', `mode`) = 0;");
        if($this->database->isError){
          exit($this->database->getError());
        }
        while($row = $query->get()){
          bot(
            $row["cid"],
            "/exit"
          );
          $this->removeUserMember($row["cid"], $user->id());
        }
        session_destroy();
        bot_self("Bot", "/exit");
    }
    
    private function doTitle(User $user, PostData $post){
        if($post->getMessage() == "/title"){
           if($post->id() == 1){
             return;
           }
           $query = $this->database->query("SELECT `title` FROM `".DB_PREFIX."chat_name` WHERE `id`='".$post->id()."'");
           bot_self($post->id(), "/title ".$query->get()["title"]);
           return;
        }
    	if(!$this->hasUserMode($post->id(), $user->id(), "o")){
           $this->error($post, "accessDeniad");
           return;
        }
		
        $title = substr($post->getMessage(), 7);
        $this->database->query("UPDATE `".DB_PREFIX."chat_name` SET `title`='".$this->database->clean($title)."' WHERE `id`='".$post->id()."'");
        bot($post->id(), "/title ".$title);
    }
    
    //msg
    private function answer_msg(User $user, PostData $post){
        if(preg_match("/^\/msg\s([a-zA-Z]*)\s(.*)$/", $post->getMessage(), $reg)){
          $uid = $this->getUserIdFromNick($reg[1]);
          if($uid == 0){
            $this->error($post, "unknownUser");
            return;
          }
          $answer = new AnswerRequest();
          $answer->setPrivate($uid);
          $answer->setChannel($post->id());
          $answer->setMessage("/msg ".$reg[1]." ".$reg[2]);
          $answer->setNick($user->nick());
          Answer::parse($answer);
        }else{
          $this->error($post, "invalidMsg");
        } 
    }
    
    //nick
    private function answer_nick(User $user, PostData $post){
        $nick = substr($post->getMessage(), 6);
        $length = strlen($nick);
        if($length < Config::get("minNickLength")){
          error($post, "nickShort");
        }elseif($length > Config::get("maxNickLength")){
          error($post, "nickLong");
        }elseif($nick == $user->nick()){
          error($post, "nickEquel");
        }elseif(disabledNick($nick)){
          error($post, "nickTaken");
	}else{
          $nickClean = $this->database->clean($nick);
          $query = $this->database->query("SELECT `id` FROM `".DB_PREFIX."chat_user` WHERE (`nick`='".$nickClean."' OR `username`='".$nickClean."') AND `id`<>'".$user->id()."'");
          if($this->database->isError){
            exit($this->database->getError());
          }
          if($query->get()){
            error($post, "nickTaken");
          }else{
            $old = $user->nick();
            $user->nick($nick);
            //send the message to all the member channel
            $query = $this->database->query("SELECT `cid` FROM `".DB_PREFIX."chat_member` WHERE `uid`='".$user->id()."' AND `cid`<>'1'");
            while($row = $query->get()){
               bot($row["cid"], "/nick ".$old." ".$nick);
             }
          }
        }	
    }
    
    //join

     private function isMemberOfChannel(User $user, string $name) : bool{
        if($name == "Bot"){
          return true;
        }
        $query = $this->database->query("SELECT name.id
                 FROM `".DB_PREFIX."chat_name` AS name
                 LEFT JOIN `".DB_PREFIX."chat_member` AS member ON member.cid=name.id
                 WHERE member.uid='".$user->id()."'
                 AND name.name='".$this->database->clean($name)."'");
        if($this->database->isError){
          exit($this->database->getError());
        }
        return $query->get() ? true : false;
     }

     private function isChannelExists($name){
         $query = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_name` WHERE `name`='".$this->database->clean($name)."'");
         if($this->database->isError){
           exit($this->database->getError());
         }
         $row = $query->get();
         if(!$row)
           return false;
         
         return $row;
     }

    private function error(PostData $data, string $code){
       error($data, $code);
    }

    private function answer_join(User $user, PostData $post){
        $name = substr($post->getMessage(), 6);
        if(!parseChannelName($name)){
          $this->error($post, "invalidJoin");
          return;
        }
        
        if($data = $this->isChannelExists($name)){
          if($this->isMemberOfChannel($user, $name)){
           $this->error(
              $post,
              $this->hasUserMode($post->id(), $user->id(), "b") ? "joinBan" : "isMember"
           ); 
           return;
          }
          $this->join($user->id(), $data["id"]);
        }else{
          $data = $this->createChannel($name);
          $this->join($user->id(), $data["id"], "o");
        }

        bot_self(1, "/join ".$data["name"]);
        bot($data["id"], "/join ".$user->nick());
        if($data["members"] == 0){
          bot($data["id"], "/mode ".$user->nick()." +o");
        }else{
          bot_self($data["id"], "/online ".$this->getOnline($data["id"]), is_admin($user->id()) ? "o" : "");
	  if(is_admin($user->id())){
	     bot($data["id"], "/mode ".$user->nick()." +o");
	  }
        }
        bot_self($data["id"], "/title ".$data["title"]);
    }

    private function createChannel(string $name) : array{
      $this->database->query("INSERT INTO `".DB_PREFIX."chat_name` (
        `name`,
        `isPriv`,
        `title`,
	`members`
      ) VALUES (
        '".$this->database->clean($name)."',
        '".No."',
        '".$this->database->clean($name)."',
	'0'
      );");

      if($this->database->isError){
        exit($this->database->getError());
      }

      return [
        'id'      => $this->database->lastIndex(),
        'name'    => $name,
        'isPriv'  => No,
        'title'   => $name,
	'members' => 0,
      ];
    }

    private function hasUserMode(int $cid, int $uid, string $mode) : bool{
      $query = $this->database->query("SELECT `mode` FROM `".DB_PREFIX."chat_member` WHERE `uid`='".$uid."' AND `cid`='".$cid."'");
      if($this->database->isError){
        exit($this->database->getError());
      }
      $result = $query->get();
      return !$result ? false : strpos($result["mode"], $mode) !== false;
    }

    private function setUserMode(PostData $post, User $user, string $mode){
       if($mode[0] == "+"){
	 if($mode == '+b'){
            $this->database->query("UPDATE `".DB_PREFIX."chat_name` SET `members`=members-1 WHERE `id`='".$post->id()."'");
	 }
         $this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `mode`=CONCAT(mode, '".$mode[1]."') WHERE `uid`='".$user->id()."' AND `cid`='".$post->id()."' AND LOCATE('".$mode[1]."', `mode`) = 0");
       }elseif($mode[0] == "-"){
         if(!$this->hasUserMode($post->id(), $user->id(), $mode[1])){
           return false;
         }
         $this->database->query("UPDATE `".DB_PREFIX."chat_member` SET `mode`=REPLACE(mode, '".$mode[1]."', '') WHERE `uid`='".$user->id()."' AND `cid`='".$post->id()."'");
       }else{
          $this->error($post, "unknownModePrefix");
          return false;
       }
       if($this->database->isError){
         exit($this->database->getError());
       }
       bot($post->id(), "/mode ".$user->nick()." ".$mode);
       return true;
    }

     private function join(int $uid, int $cid, string $mode = ""){
         $this->database->query("INSERT INTO `".DB_PREFIX."chat_member`(
           `uid`,
           `cid`,
           `mode`,
           `lastActiv`,
           `isInAktiv`
         ) VALUES (
           '".$uid."',
           '".$cid."',
           '".$mode."',
           NOW(),
           '".No."'
         )");
         if($this->database->isError){
           exit($this->database->getError());
         }
	 $this->database->query("UPDATE `".DB_PREFIX."chat_name` SET `members`=members+1 WHERE `id`='".$cid."'");
     }
    
    private function getOnline($id) : string{
        
		$data = $this->database->query("SELECT user.nick, user.avatar, cm.isInAktiv, cm.mode
		FROM `".DB_PREFIX."chat_user` AS user
		LEFT JOIN `".DB_PREFIX."chat_member` AS cm ON user.id = cm.uid
		WHERE cm.cid='".(int)$id."' AND LOCATE('b', cm.mode) = 0");
        if($this->database->isError){
          exit($this->database->getError());
        }
    	
    	$buffer = [];
        while($row = $data->get()){
          $prefix = $row["isInAktiv"] == Yes ? "[i]" : "";
          if(strpos($row["mode"], "o") !== false){
             $prefix .= "@";
          }elseif(strpos($row["mode"], "v") !== false){
             $prefix .= "+";
          }
          $buffer[] = $row["avatar"]."|".$prefix.$row["nick"];
        }
    	return implode(" ", $buffer);
    }

     private function init_db(string $host, string $user, string $password, string $table){
         $this->database = new DatabaseHandler(
             $host,
             $user,
             $password,
             $table
         );

         if($this->database->isError){
             exit($this->database->getError());
         }

         Answer::setDatabase($this->database);
     }
    
    private function loadPages(){
      include 'lib/db/mysqli.php';
      include 'lib/parser.php';
      include 'lib/request.php';
      include 'lib/answer.php';
      include 'lib/user.php';
      include 'lib/postdata.php';
      include 'lib/log.php';//new in V1.1
      include 'lib/sysconfig.php';//new in V1.1
      include 'lib/plugin.php';//new in V1.1
      include 'lib/admin.php';//new in V1.1
      include 'lib/command.php';//new in V1.1
      include 'lib/language.php';
	    
	    $this->setAutoloader();
	    
      if(!file_exists("./lib/config.php")){
	      $this->missing_config();
      }
      $data = Config::init();

      $this->init_db(
         $data["host"],
         $data["user"],
         $data["pass"],
         $data["table"]
      );
	    
     $this->catchError();
      
      define("DB_PREFIX", $data["prefix"]);
      $this->plugin = new Plugin($this->database);
      
      if(Request::getView() == Request::VIEW_HTML){
	  include "lib/tempelate.php";//new in V1.3
	  $this->tempelate = new Tempelate($this->plugin);//new in V1.3
	  Language::init();
      }
    }

    private function missing_config(){
        if(Request::getView() == Request::VIEW_HTML){
          echo "<!DOCTYPE html>
 <html>
  <head>
   <title>The system is not installed</title>
  </head>
  <body>
   Sorry but the chat is not install yet.<br>
   Please first set up a database using the sql file [chat.sql]<br>
   Fill all data in the config file [lib/config-test.txt]<br>
   After that rename [lib/config-test.txt] to [lib/config.php]<br>
   You chat should now work.
  </body>
 </html>";
        }
      exit;
    }

     private function doLogin(){
	Language::load("login");
       $error = [];

       if(Request::post("login")){
         if(!Request::post("username")){
           $error[] = Language::get("missing_username");
         }
         if(!Request::post("password")){
           $error[] = Language::get("missing_password");
         }

         if(count($error) == 0){
           if($this->loginAction(Request::post("username"), Request::post("password"))){
             header("location: #");
             exit;
           }
           $error[] = Language::get("wrong_login");
         }
       }

       if(Request::post("create")){
         if(!Request::post("username")){
           $error[] = Language::get("missing_username");
         }elseif(disabledNick(Request::post("username"))){
           $error[] = Language::get("username_taken"); 
	 }else{
           $query = $this->database->query("SELECT `id` FROM `".DB_PREFIX."chat_user` WHERE `username`='".$this->database->clean(Request::post("username"))."'");
           if($this->database->isError){
            exit($this->database->getError());
           }
           if($query->get()){
             $error[] = Language::get("username_taken");
           }
         }
         if(!Request::post("password")){
           $error[] = Language::get("missing_password");
         }
         if(!Request::post("re_password")){
           $error[] = Langauge::get("missing_re_password");
         }
         if(Request::post("password") && Request::post("re_password") && Request::post("password") != Request::post("re_password")){
           $error[] = Language::get("password_mismatch");
         }
	       
	 if(!Request::post("email")){
		$error[] = Language::get("missing_email"); 
	 }
     
         if(count($error) == 0){
          $this->createAction(Request::post("username"), Request::post("password"), Request::post("email"));
          header("location: #");
          exit;
         }
       }
       
       if(count($error) > 0){
	     $this->tempelate->putVariabel("error", $error);
       }
       $this->onlineUsers();
       $this->showTempelate("login");
       return;
     }

     private function onlineUsers(){
        $query = $this->database->query("SELECT us.nick
        FROM `".DB_PREFIX."chat_member` AS cm
        LEFT JOIN `".DB_PREFIX."chat_user` AS us ON us.id = cm.uid
        WHERE cm.cid <> '1'
        AND LOCATE('b', cm.mode) = 0
        AND cm.lastActiv > DATE_SUB(now(), INTERVAL ".(int)Config::get("leave")." MINUTE)
        GROUP BY us.id");

        if($this->database->isError){
          exit($this->database->getError());
        }
        $buffer = [];
        while($row = $query->get()){
          $buffer[] = $row["nick"];
        }
	$this->tempelate->putVariabel("online", $buffer);
     }
    
    //session sektion
    private function sessionInit(){

        //wee kontrol if header is sendt :)
        if(headers_sent()){
            exit("Header is allray sendt!");
        }

        if(session_id() == ''){
            session_start();
        }
    }

    private function loginAction(string $username, string $password) : bool{
       $query = $this->database->query("SELECT `id` FROM `".DB_PREFIX."chat_user` WHERE `username`='".$this->database->clean($username)."' AND `password`='".$this->database->clean(sha1($password))."'");
       if($this->database->isError){
         exit($this->database->getError());
       }
       $data = $query->get();
       if(!$data)
         return false;

       Request::setSession("uid", $data["id"]);
       //append in version 1.1: Update the ip so the user not will be logout in the next page request
       $this->database->query("UPDATE `".DB_PREFIX."chat_user` SET
       `ip`='".$this->database->clean(Request::ip())."'
       WHERE `id`='".$data["id"]."'");
       $data["ip"] = Request::ip();
       $user = new User($this->database, $data);
       Answer::setUser($user);
       $this->plugin->trigger("system.user.login", [$user]);
       return true;
    }

    private function createAction(string $username, string $password, string $email){
      $query = $this->database->query("SELECT `username`, `id` FROM `".DB_PREFIX."chat_user` WHERE `nick`='".$this->database->clean($username)."'");
      if($this->database->isError){
        exit($this->database->getError());
      }
      
      while($user = $query->get()){
        $this->database->query("UPDATE `".DB_PREFIX."chat_user` SET `nick`='".$user["username"]."' SET `id`='".$user["id"]."'");
        $query_member = $this->database->query("SELECT `cid` 
                                                FROM `".DB_PREFIX."chat_member`
                                                WHERE `cid`<>'1'
                                                AND `uid`='".$user["id"]."'");
        while($row = $query_user->get()){
           bot_other($user["id"], $row["cid"], "/old ".$username." ".$user["username"]);
        }
      }

      $this->database->query("INSERT INTO `".DB_PREFIX."chat_user` (
        `username`,
        `nick`,
        `password`,
	`email`,
	`status`
        `avatar`,
	`ip`
      ) VALUES (
        '".$this->database->clean($username)."',
        '".$this->database->clean($username)."',
        '".$this->database->clean(sha1($password))."',
	'".$this->database->clean($email)."',
	'N',
        '".$this->database->clean(Config::get("defaultAvatar"))."',
	'".$this->database->clean(Request::ip())."'
      )");
      
      if($this->database->isError){
        exit($this->database->getError());
      }
      Request::setSession("uid", $this->database->lastIndex());

      //wee insert user in globel channel (i will devolpe so it can be deleted)
      $this->database->query("INSERT INTO `".DB_PREFIX."chat_member` (
        `uid`,
        `cid`,
        `lastActiv`,
        `isInAktiv`
      ) VALUES (
        '".$this->database->lastIndex()."',
        '1',
        NOW(),
        '".No."'
      )");
	system_log("New user is created: ".$username);//new in V1.1
	//some plugin can send some message. if wee do not call login() it will fail
	Answer::setUser($this->login());
	$this->plugin->trigger("system.user.create", [$username]);
    }

    private function getUserObject(string $nick) : User{
      $query = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_user` WHERE `nick`='".$this->database->clean($nick)."'");
      return new User($this->database, $query->get());
    }

    private function login(){

        if(!Request::session("uid")){
           return null;
        }

        $row = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_user` WHERE `id`='".(int)Request::session("uid")."'")->get();
        if($this->database->isError){
            exit("Database error");
        }

        if(empty($row) || $row["ip"] !== Request::ip()){
            return null;
        }

        $user = $this->user = new User($this->database, $row);
	$this->plugin->trigger("system.user.autologin", [$user]);//added in version 1.1
	return $user;
    }
	
	private function showTempelate(string $name){
		if(Request::session("template")){
			$this->tempelate->path("./lib/tempelate/".Request::session("tempelate"));
			if($this->tempelate->parse($name.".style")){
				return;
			}
		}
		
		//okay wee has no option wee take the default style
		$this->tempelate->path("./lib/tempelate/".Config::get("defaultStyle"));
		if(!$this->tempelate->parse($name.".style")){
			exit("Failed to show the page");
		}
	}

     private function removeUserMember(int $cid, int $uid, int $members = -1){
        $this->database->query("DELETE FROM `".DB_PREFIX."chat_member`
                                WHERE `uid`='".$uid."'
                                AND `cid`='".$cid."'");
        if($this->database->isError){
          exit($this->database->getError());
        }
	     
	if($members == -1){
		$data = $this->database->query("SELECT COUNT(`id`) AS id FROM `".DB_PREFIX."chat_member` WHERE `cid`='".$cid."' AND LOCATE('b', `mode`) = 0;");
		if($this->database->isError){
			exit($this->database->getError());
		}
		if(($row = $data->get())){
			$members = $row["id"];
		}
	}else{
		$members--;//wee has remove one user
	}
		   
        if($members == 0){
               $this->database->query("DELETE FROM `".DB_PREFIX."chat_name` WHERE `id`='".$cid."'");
                $this->database->query("DELETE FROM `".DB_PREFIX."chat_message` WHERE `cid`='".$cid."'");
                $this->database->query("DELETE FROM `".DB_PREFIX."chat_member` WHERE `cid`='".$cid."'");
        }else{
		$this->database->query("UPDATE `".DB_PREFIX."chat_name` SET `members`=members-1 WHERE `id`='".$cid."'");
	}
     }
	private function setAutoloader(){
		spl_autoload_register(function($class){
			if(!class_exists($class)){
				include str_replace("\\", "/", $class).".php";
			}
		});
	}
	
	private function catchError(){
		$self = $this;
		set_error_handler(function($errno, $errstr, $errfile, $errline) use($self){
			if($errno == E_USER_ERROR){
				//this is used for user error!
				if(Request::getView() == Request::VIEW_HTML){
					$error = [];
					if($self->getTempelate()->hasVariabel("error")){
						$error = $self->getTempelate()->getVariabel("error");
					}
					$error[] = $errstr;
					$self->getTempelate()->putVariabel("error", $error);
				}elseif(Request::getView() == Request::VIEW_AJAX){
					error($self->postData(), $errstr);
				}
			}else{
				$db = $self->getDatabase();
				$db->query("INSERT INTO `".DB_PREFIX."chat_error`(
				  `errno`,
				  `errstr`,
				  `errfile`,
				  `errline`,
				  `seen`,
				  `time`
				) VALUES (
				  '".$db->clean($errno)."',
				  '".$db->clean($errstr)."',
				  '".$db->clean($errfile)."',
				  '".$db->clean($errline)."',
				  '".No."',
				  NOW()
				);");
				if($self->isLogin() && is_admin($self->getCurrentUser()->id())){
					error($self->postData(), "systemerror");
				}
			}
		});
	}
 }
 
$server = new Server();

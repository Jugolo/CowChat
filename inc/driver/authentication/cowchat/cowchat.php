<?php

namespace inc\driver\authentication\cowchat\cowchat;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\head\Head;
use inc\tempelate\tempelate\Tempelate;
use inc\database\Database;
use inc\html\Html;
use inc\language\Language;
use inc\user\User;
use inc\setting\Setting;
use inc\mail\Mail;
use inc\logging\Logging;
use inc\user\data\UserData;
use inc\exception\LoginUserFailed\LoginUserFailed;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function login(): UserData{
		if(Head::get("method") == "create"){
			return $this->do_create();
		}elseif(Head::get("method") == "geaust"){
			
		}else{
			return $this->do_login();
		}
	}
	public function auto_login(): UserData{
		$oauth = Head::cookie("oauth");
		if(strpos($oauth, "@") === false){
			Head::cookieDestroy("oauth");
			throw new LoginUserFailed();
		}
		
		list($id, $hash) = explode("@", $oauth);
		// try to finde the user
		$database = Database::getInstance();
		$query = $database->query("SELECT * FROM " . table("user_login") . " WHERE `id`=" . $database->clean($id - 123456789) . " AND `hash`=" . $database->clean($hash));
		$row = $query->fetch();
		$query->free();
		if(!is_array($row)){
			Head::cookieDestroy("oauth");
			throw new LoginUserFailed();
		}
		
		return new UserData($row);
	}
	public function logout(){
		Head::cookieDestroy("oauth");
	}
	public function new_password(string $password): bool{
		$user = User::getStack()->current();
		// wee update hash and password
		$hash = create_hash();
		$password = hash_password($password, $hash, $user->getCreationTime());
		
		// wee update the user table
		// the user will be logout
		$database = Database::getInstance();
		$database->query("UPDATE " . table("user_login") . " SET `hash`=" . $database->clean($hash) . " AND `password`=" . $database->clean($password) . " WHERE `id`='" . $user->getId() . "'");
		return true;
	}
	private function do_create(): bool{
		if(Head::post("username") && Head::post("password") && Head::post("repeat_password") && Head::post("email")){
			if(User::helpers()->nick_taken(Head::post("username"))){
				Html::error(Language::get_sprintf("Username '%s' is taken", Head::post("username")));
			}else{
				if(Head::post("password") != Head::post("repeat_password")){
					Html::error(Language::get("The two password is not equels"));
				}else{
					if(User::helpers()->email_taken(Head::post("email"))){
						Html::error(Language::get("Email is taken"));
					}else{
						$hash = create_hash();
						$time = time();
						$password = hash_password(Head::post("email"), $hash, $time);
						$uid = Database::insert("user_login", ($data = [
								"username" => Head::post("username"),
								"password" => $password,
								"hash" => $hash,
								"email" => Head::post("email"),
								"authentication_driver" => "cowchat",
								"created" => $time,
								"activated" => "n",
								"ip" => ip(),
								"type" => 'u'
						]));
						User::helpers()->apppend_user($uid, $data["username"]);
						if(@mail(Head::post("email"), Language::get("Activate you account"), Language::get_sprintf("Hallo %s.", Head::post("username")) . "
" . Language::get_sprintf("You has just createt a account on %s", Setting::get("name")) . "
" . Language::get_sprintf("To use you new account pleace visist %s and vertify this email is valid", (Head::is_ssl() ? "https" : "http") . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . "?auth_method=cowchat&activate=" . $uid), implode("\r\n", [
								"MIME-Version: 1.0",
								"Content-type: text/html; charset=utf8",
								"from:" . Mail::format_email("support@" . $_SERVER['SERVER_NAME'])
						]))){
							Html::okay(Language::get("You has now createt a account. Please check you email and activate the account"));
						}else{
							Database::getInstance()->query("UPDATE " . table("user_login") . " SET `activated`='y' WHERE `id`='" . $uid . "'");
							Logging::getInstance("email")->push("[CowChat login driver]Fail to send email width activate information");
							$this->createLoginCookie($uid, $hash);
							return new UserData($data);
						}
					}
				}
			}
		}
		$tempelate = new Tempelate([
				"dir"   => "inc/driver/authentication/cowchat/style",
				"cache" => true,
		]);
		Html::set_agument($tempelate);
		$tempelate->exec("create");
		throw new LoginUserFailed();
	}
	private function do_login(): UserData{
		if(Head::post("username") && Head::post("password")){
			// see if the username exists
			$database = Database::getInstance();
			$query = $database->query("SELECT * FROM " . table("user_login") . " WHERE `type`='u' AND `activated`='y' AND `username`=" . $database->clean(Head::post("username")));
			$row = $query->fetch();
			$query->free();
			if(!is_array($row)){
				Html::error(Language::get("Unknown username or/and password"));
			}else{
				// first controle if hash or password is empty
				if(empty($row["password"]) || empty($row["username"])){
					Html::error(Language::get("The account is not set up to CowChat login driver. Please login widht you driver and connect the account to CowChat driver"));
				}else{
					if($row["password"] != hash_password(Head::post("password"), $row["hash"], $row["created"])){
						Html::error(Language::get("Unknown username or/and password"));
					}else{
						$this->createLoginCookie($row["id"], $row["hash"]);
						return new UserData($row);
					}
				}
			}
		}
		
		$tempelate = new Tempelate([
				"dir"   => "inc/driver/authentication/cowchat/style",
				"cache" => true
		]);
		Html::set_agument($tempelate);
		$tempelate->exec("login");
		throw new LoginUserFailed();
	}
	private function createLoginCookie(int $id, string $hash){
		Head::make_cookie("oauth", ($id + 123456789) . "@" . $hash);
	}
	public function getName(): string{
		return "cowchat";
	}
	public function title(): string{
		return "CowChat";
	}
	public function enabled(): bool{
		return true;
	}
}
function hash_password($password, $hash, $creation){
	$creation = intval($creation);
	$part = function ($item) use ($creation){
		$u = "";
		for($i = 0;$i < 1001;$i++)
			$u .= chr(ord($item) << 2);
		
		$return = "";
		for($i = 0;$i < 1001;$i++)
			$return .= sha1($u . $creation . $u . $u);
		return sha1($return);
	};
	return sha1($part($password) . $part($hash) . $part($hash) . $part($creation));
}
function create_hash(){
	$hash = "";
	for($i = 0;$i < 1001;$i++){
		$hash .= chr(mt_rand(0, 127));
	}
	return sha1($hash);
}
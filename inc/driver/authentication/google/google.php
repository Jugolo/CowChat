<?php

namespace inc\driver\authentication\google\google;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\setting\Setting;
use inc\http\connector\HttpRequest;
use inc\head\Head;
use inc\http\container\HttpContainer;
use inc\database\Database;
use inc\tempelate\tempelate\Tempelate;
use inc\user\User;
use inc\user\data\UserData;
use inc\exception\LoginUserFailed\LoginUserFailed;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function login(): UserData{
		if(!Head::get("code") && !Head::get("token")){
			header("location: https://accounts.google.com/o/oauth2/auth?" . http_build_query([
					"response_type" => "code",
					"client_id" => Setting::get("google_id"),
					"redirect_uri" => (Head::is_ssl() ? "https" : "http") . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					"scope" => "https://www.googleapis.com/auth/userinfo.email"
			]));
			exit();
		}else{
			if(!Head::get("token")){
				$respons = json_decode($this->request("https://accounts.google.com/o/oauth2/token", [
						"code" => Head::get("code"),
						"client_id" => Setting::get("google_id"),
						"client_secret" => Setting::get("google_secret"),
						"redirect_uri" => (Head::is_ssl() ? "https" : "http") . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
						"grant_type" => "authorization_code"
				])->context(), true);
				
				if(!empty($respons["error"])){
					header("location:index.php");
					exit();
				}
				$token = $respons["access_token"];
			}else{
				$token = Head::get("token");
			}
			
			$data = json_decode($this->request("https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=" . $token)->context(), true);
			
			$database = Database::getInstance();
			$query = $database->query("SELECT * FROM " . table("user_login") . " WHERE `extra`=" . $database->clean("google:" . $data["id"]));
			$row = $query->fetch();
			$query->free();
			$tempelate = new Tempelate([
					"dir" => "inc/driver/authentication/google/style/"
			]);
			$tempelate->add_var("token", $token);
			if(is_array($row)){
				// make a oauth cookie
				Head::make_cookie("oauth", substr($row["extra"], 7));
				// controle the ip
				if($row["ip"] != ip()){
					$database->query("UPDATE " . table("user_login") . " SET `ip`=" . $database->clean(ip()) . " WHERE `id`='" . $row["id"] . "`");
				}
				User::getStack()->push($row["username"]);
			}else{
				if(Head::get("create")){
					if(Head::get("nick_taken") === "yes" && Head::post("new_username") != $data["given_name"] && User::helpers()->nick_taken($data["given_name"]) && Head::post("new_username")){
						$data["given_name"] = Head::post("new_username");
					}
					
					if(User::helpers()->nick_taken($data["given_name"])){
						$tempelate->add_var("nick", $data["given_name"]);
						$tempelate->exec("nick_taken");
						exit;
					}
					if(Head::get("create") === "yes"){
						$d = [
								"username" => $data["given_name"],
								"password" => null,
								"hash" => null,
								"email" => $data["email"],
								"authentication_driver" => "google",
								"created" => time(),
								"activated" => "Y",
								"ip" => ip(),
								"extra" => "google:" . $data["id"],
								"type" => "u"
						];
						$d["id"] = Database::insert("user_login", $d);
						User::helpers()->apppend_user($uid, $d["username"]);
						Head::make_cookie("oauth", $data["id"]);
						return new UserData($d);
					}else{
						Head::cookieDestroy("login_driver");
						header("location:index.php");
						exit();
					}
				}else{
					
					$tempelate->add_var_array($data);
					$tempelate->exec("accept_create");
					exit();
				}
			}
		}
	}
	public function logout(){
		Head::cookieDestroy("oauth");
	}
	public function auto_login(): UserData{
		if(!Head::cookie("oauth")){
			throw new LoginUserFailed();
		}
		return $this->try_login();
	}
	public function new_password(string $password) : bool{
		throw new HeigLevelError(Language::get("Google dont need a password!"));
	}
	public function title(): string{
		return "Google";
	}
	public function enabled(): bool{
		return Setting::get("google_id") !== "null" && Setting::get("google_secret") !== "null";
	}
	private function try_login(): UserData{
		$database = Database::getInstance();
		$query = $database->query("SELECT * FROM " . table("user_login") . " WHERE `extra`=" . $database->clean("google:" . Head::cookie("oauth")));
		$row = $query->fetch();
		$query->free();
		if(!is_array($row)){
			throw new LoginUserFailed();
		}
		return new UserData($row);
	}
	private function request($url, array $post = []): HttpContainer{
		$http = new HttpRequest($url);
		if(count($post) !== 0){
			$http->multi_post($post);
		}
		
		return $http->exec();
	}
}
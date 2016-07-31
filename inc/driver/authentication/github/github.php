<?php

namespace inc\driver\authentication\github\github;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\user\data\UserData;
use inc\setting\Setting;
use inc\head\Head;
use inc\error\HeigLevelError;
use inc\http\connector\HttpRequest;
use inc\http\container\HttpContainer;
use inc\html\Html;
use inc\language\Language;
use inc\logging\Logging;
use inc\user\User;
use inc\database\Database;
use inc\tempelate\tempelate\Tempelate;
use inc\exception\LoginUserFailed\LoginUserFailed;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function login(): UserData{
		$log = Logging::getInstance("login_" . str_replace(".", "_", ip()), "inc/driver/authentication/github/log/");
		if(Head::get("code")){
			$log->push("Send user to github to allow us to get information");
			Head::make_cookie("state", hash('sha256', microtime(TRUE) . rand() . $_SERVER['REMOTE_ADDR']));
			header("location: https://github.com/login/oauth/authorize?" . http_build_query([
					"client_id" => Setting::get("github_id"),
					"redirect_uri" => (Head::is_ssl() ? "https" : "http") . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					"scope" => "user:email",
					"state" => Head::cookie("state")
			]));
			exit();
		}else if(Head::get("code")){
			if(!Head::get("state") || Head::cookie("state") != Head::get("state")){
				Head::cookieDestroy("state");
				throw new LoginUserFailed();
			}
			$http = $this->request("https://github.com/login/oauth/access_token", [
					'client_id' => Setting::get("github_id"),
					'client_secret' => Setting::get("github_secret"),
					'redirect_uri' => (Head::is_ssl() ? "https" : "http") . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					'state' => Head::cookie("state"),
					'code' => Head::get('code')
			]);
			if(($token = json_decode($http->context(), true)) === null){
				throw new HeigLevelError(json_last_error_msg(), $http->context());
			}
			if(!empty($token["error"])){
				// wee know this would result in login page so let us give a error message
				Html::error(Language::get("Could not get userdata from github"));
				throw new LoginUserFailed();
			}
			if($token == ""){
				throw new HeigLevelError("Could not resive user data from user");
			}
			$log->push("Get new token: " . $token["access_token"]);
			Head::make_cookie("token", $token["access_token"]);
			// send user so code and so on it not in the $_GET
			header("location: index.php");
			exit();
		}
		
		return $this->auto_login();
	}
	public function logout(){
		Head::cookieDestroy("token");
		Head::cookieDestroy("state");
	}
	public function auto_login(): UserData{
		if(Head::cookie("token") !== null){
			$user = $this->request("https://api.github.com/user");
			$json = json_decode($user->context(), true);
			if(!empty($json["message"]) && $json["message"] === "Bad credentials"){
				throw new LoginUserFailed();
			}
			$database = Database::getInstance();
			$query = $database->query("SELECT * FROM " . table("user_login") . " WHERE `extra`=" . $database->clean("github:" . $json["id"]));
			$row = $query->fetch();
			$query->free();
			if($row == null){
				$create = false;
				if(Head::get("nick_taken") && Head::get("new") && Head::post("new_username")){
					if(User::helpers()->nick_taken(Head::post("new_username"))){
						Html::error(Language::get("Nick is taken. Please pick a new nick"));
					}else{
						$json["login"] = Head::post("new_username");
						$create = true;
					}
				}
				if(!User::helpers()->nick_taken($json["login"])){
					if(!Head::get("create") && !$create){
						// no user is set
						$tempelate = new Tempelate([
								"dir" => "inc/driver/authentication/github/style/"
						]);
						$tempelate->add_var_array([
								'id' => 'id',
								'nick' => $json["login"],
								'avatar' => $json["avatar_url"]
						]);
						Html::set_agument($tempelate);
						$tempelate->exec("accept");
					}else{
						if(Head::get("create") === "yes" || $create){
							$data = [
									"username" => $json["login"],
									"password" => null,
									"hash" => null,
									"email" => $json["email"],
									"authentication_driver" => "github",
									"created" => time(),
									"activated" => "Y",
									"ip" => ip(),
									"extra" => "github:" . $json["id"],
									"type" => 'u'
							];
							$data["id"] = Database::insert("user_login", $data);
							User::helpers()->apppend_user($uid, $json["login"]);
							return new UserData($data);
						}elseif(Head::get("create") === "no"){
							Head::cookieDestroy("token");
							Head::cookieDestroy("state");
							Head::cookieDestroy("login_driver");
							header("location:index.php");
							exit();
						}
					}
				}else{
					$tempelate = new Tempelate([
							"dir" => "inc/driver/authentication/github/style/"
					]);
					Html::set_agument($tempelate);
					$tempelate->add_var("nick", $json["login"]);
					$tempelate->exec("nick_taken");
				}
			}else{
				return new UserData($row["username"]);
			}
		}
		throw new LoginUserFailed();
	}
	public function new_password(string $password): bool{
		throw new HeigLevelError(Language::get("GitHub dont need a password!"));
	}
	public function getName(): string{
		return "github";
	}
	public function title(): string{
		return "Github";
	}
	public function enabled(): bool{
		return Setting::get("github_id") !== "null" && Setting::get("github_secret") !== "null" && Setting::get("github_appname") !== "null";
	}
	private function request(string $url, array $post = []): HttpContainer{
		$http = new HttpRequest($url);
		
		if(count($post) != 0){
			$http->multi_post($post);
		}
		
		$http->set_multi_header([
				"Accept" => "application/json",
				"User-Agent" => Setting::get("github_appname")
		]);
		
		if(Head::cookie("token")){
			$http->set_header("Authorization", "token " . Head::cookie("token"));
		}
		
		return $http->exec();
	}
}
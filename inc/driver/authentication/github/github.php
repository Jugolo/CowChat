<?php
namespace inc\driver\authentication\github\github;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\user\data\UserData;
use inc\setting\Setting;
use inc\head\Head;
use inc\error\HeigLevelError;
use inc\http\connector\Connector;
use inc\http\container\HttpContainer;
use inc\html\Html;
use inc\language\Language;
use inc\logging\Logging;
use inc\user\User;
use inc\database\Database;
use inc\tempelate\tempelate\Tempelate;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function login() : bool{
		$log = Logging::getInstance("login_".str_replace(".", "_", ip()), "inc/driver/authentication/github/log/");
		if(!Head::cookie("token") && !Head::get("code")){
			$log->push("Send user to github to allow us to get information");
			Head::make_cookie("state", hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']));
			header("location: https://github.com/login/oauth/authorize?".http_build_query([
					"client_id"    => Setting::get("github_id"),
					"redirect_uri" => (Head::is_ssl() ? "https" : "http")."://".$_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					"scope"        => "user:email",
					"state"        => Head::cookie("state")
			]));
			exit;
		}else if(Head::get("code")){
			if(!Head::get("state") || Head::cookie("state") != Head::get("state")){
				Head::cookieDestroy("state");
				return false;
			}
			$http = $this->request("https://github.com/login/oauth/access_token", [
					'client_id' => Setting::get("github_id"),
					'client_secret' => Setting::get("github_secret"),
					'redirect_uri' => (Head::is_ssl() ? "https" : "http").'://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					'state' => Head::cookie("state"),
					'code' => Head::get('code')
			]);
			if(($token = json_decode($http->context(), true)) === null){
				throw new HeigLevelError(json_last_error_msg(), $http->context());
			}
			if(!empty($token["error"])){
				//wee know this would result in login page so let us give a error message
				Html::error(Language::get("Could not get userdata from github"));
				return false;
			}
			if($token == ""){
				throw new HeigLevelError("Could not resive user data from user");
			}
			$log->push("Get new token: ".$token["access_token"]);
			Head::make_cookie("token", $token["access_token"]);
		}
		
		if(Head::cookie("token") !== null){
			$user = $this->request("https://api.github.com/user");
			$json = json_decode($user->context(), true);
			$database = Database::getInstance();
			$query = $database->query("SELECT * FROM ".table("user_login")." WHERE `hash`=".$database->clean("github:".$json["id"]));
			$row = $query->fetch();
			if($row == null){
				//no user is set
				$tempelate = new Tempelate(["dir" => "inc/driver/authentication/github/style/"]);
				exit($tempelate->exec("accept", [
						'id'     => 'id',
						'nick'   => $json["login"],
						'avatar' => $json["avatar_url"]
				]));
			}else{
				
			}
			echo $user->context()."<br>";
			echo "<pre>";
			print_r(json_decode($user->context(), true));
			exit("</pre>");
			return true;
		}
		
		return false;
	}

	public function getName() : string{
		return "github";
	}
	
	public function title() : string{
		return "Github";
	}
	
	public function enabled() : bool{
		return Setting::get("github_id") !== "null" && Setting::get("github_secret") !== "null" && Setting::get("github_appname") !== "null";
	}
	
	private function request(string $url, array $post = []) : HttpContainer{
		$http = new Connector($url);
		
		if(count($post) != 0){
			$http->multi_post($post);
		}
		
		$http->set_multi_header([
				"Accept"     => "application/json",
				"User-Agent" => Setting::get("github_appname")
		]);
		
		if(Head::cookie("token")){
			$http->set_header("Authorization", "token ".Head::cookie("token"));
		}
		
		return $http->exec();
	}
}
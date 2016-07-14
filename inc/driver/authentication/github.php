<?php
namespace inc\driver\authentication\github;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\user\data\UserData;
use inc\setting\Setting;
use inc\head\Head;
use inc\error\HeigLevelError;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function autoLogin() : bool{
		if(!Head::cookie("token") && !Head::get("code")){
			Head::make_cookie("state", hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']));
			header("location: https://github.com/login/oauth/authorize?".http_build_query([
					"client_id"    => Setting::get("github_id"),
					"redirect_uri" => (Head::is_ssl() ? "https" : "http")."://".$_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					"scope"        => "user",
					"state"        => Head::cookie("state")
			]));
			exit;
		}else if(Head::get("code")){
			if(!Head::get("state") || Head::cookie("state") != Head::get("state")){
				Head::cookieDestroy("state");
				return false;
			}
			$token = $this->request("https://github.com/login/oauth/access_token", [
					'client_id' => Setting::get("github_id"),
					'client_secret' => Setting::get("github_secret"),
					'redirect_uri' => (Head::is_ssl() ? "https" : "http").'://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
					'state' => Head::cookie("state"),
					'code' => Head::get('code')
			]);
			if($token == ""){
				throw new HeigLevelError("Could not resive user data from user");
			}
			Head::make_cookie("token", $token["access_token"]);
		}
		
		if(Head::cookie("token") !== null){
			$user = $this->request("https://api.github.com/user");
			echo "<pre>";
			print_r($user);
			exit("</pre>");
			return true;
		}
		
		return false;
	}
	function login(array $data) : UserData{

	}
	function createAccount(string $username, string $password, string $email){
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
	
	private function request(string $url, array $post = []){
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		if(count($post) != 0){
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		$header = ["Accept: application/json", "User-Agent: ".Setting::get("github_appname")];
		if(Head::cookie("token")){
			$header[] = "Authorization: Bearer ".Head::cookie("token");
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		$respons = curl_exec($curl);
		return json_decode($respons, true);
	}
}
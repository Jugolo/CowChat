<?php

namespace inc\http\connector;

use inc\error\LowLevelError;
use inc\http\container\HttpContainer;
use inc\error\HeigLevelError;

class Connector{
	private $data = [
			"post" => [],
			"header" => [
					"",
					""
			]
	];
	public function __construct(string $url){
		$this->data["url"] = $url;
		$this->data["method"] = "GET";
	}
	public function post(string $name, string $value){
		$this->data["method"] = "POST";
		$this->data["post"][$name] = $value;
	}
	public function multi_post(array $post){
		foreach($post as $key => $value){
			$this->post($key, $value);
		}
	}
	public function set_header(string $key, string $value){
		$this->data["header"][] = $key . ": " . $value;
	}
	public function set_multi_header(array $header){
		foreach($header as $key => $value){
			$this->set_header($key, $value);
		}
	}
	public function exec(): HttpContainer{
		list($host, $dir) = $this->parse_url();
		$this->data["header"][0] = $this->data["method"] . " " . $dir . " HTTP/1.1";
		$this->data["header"][1] = "Host: " . $host;
		// wee try to connect to the server :)
		$socket = fsockopen((strpos($this->data["url"], "https") === 0 ? "ssl://" : "") . $host, strpos($this->data["url"], "https") === 0 ? 443 : 80);
		if($socket === false){
			throw new LowLevelError("Could not connect to the web server", $this->data["url"]);
		}
		
		$header = implode("\r\n", $this->data["header"]);
		
		if($this->data["method"] === "POST"){
			$header .= "\r\nContent-Type: application/x-www-form-urlencoded";
			$post_clean = http_build_query($this->data["post"]);
			$header .= "\r\nContent-Length: " . strlen($post_clean);
			$header .= "\r\n\r\n" . $post_clean;
		}else{
			$header .= "\r\n\r\n";
		}
		
		if(fwrite($socket, $header, strlen($header)) === false){
			throw new LowLevelError("Could not write to the web server", $header);
		}
		
		$header_done = false;
		$return = [
				"header" => [],
				"body" => []
		];
		
		$header = [];
		$body = "";
		
		while($line = fgets($socket)){
			if(rtrim($line) != ""){
				if(preg_match('/\A(\S+): (.*)\z/', rtrim($line), $matches)){
					$header[$matches[1]] = $matches[2];
				}
			}else{
				break;
			}
		}
		
		if(!empty($header["Transfer-Encoding"])){
			if(strtolower($header["Transfer-Encoding"]) == "chunked"){
				$chunk = "";
				do{
					$chunk_size = trim(fgets($socket));
					if($chunk_size == ""){
						break;
					}
					if(!ctype_xdigit($chunk_size)){
						throw new HeigLevelError("Invalid chunk size", gettype($chunk_size));
					}
					
					$chunk_size = hexdec($chunk_size);
					$next_pointer = ftell($socket) + $chunk_size;
					do{
						$current_pointer = ftell($socket);
						if($current_pointer >= $next_pointer){
							break;
						}
						
						$line = fread($socket, $next_pointer - $current_pointer);
						if(!$line){
							throw new HeigLevelError("Could not read the stream propely");
						}
						
						$chunk .= $line;
					}while(!feof($socket));
					$body .= $chunk;
				}while($chunk_size > 0);
			}
		}elseif(!empty($header["Content-Length"])){
			$length = intval($header["Content-Length"]);
			$current_pointer = ftell($socket);
			for($to = $current_pointer + $length; $to > $current_pointer; $current_pointer = ftell($socket)){
				$chunk = fread($socket, $to-$current_pointer);
				if(!$chunk){
					break;
				}
				
				$body .= $chunk;
				
				if(!feof($socket)){
					break;
				}
			}
		}
		
		return new HttpContainer($body, $header);
	}
	private function parse_url(){
		if(preg_match("|^(.*:)//([A-Za-z0-9\-\.]+)(:[0-9]+)?(.*)$|", $this->data["url"], $preg)){
			return [
					$preg[2],
					$preg[4]
			];
		}else{
			throw new LowLevelError("Unknown url: " . $this->data["url"]);
		}
	}
}
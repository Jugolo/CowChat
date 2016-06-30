<?php
class JvaScriptCompressor{
	public static function file($url, $cache = null){
		/*
		 * if(!file_exists($url)){
		 * self::error("Unknown file: ".$url);
		 * }
		 */
		if($cache != null){
			if(file_exists($cache) && filemtime($url) < filemtime($cache)){
				return file_get_contents($cache);
			}
		}
		
		$compress = self::string(file_get_contents($url));
		
		if($cache != null){
			$fopen = fopen($cache, "w+");
			fwrite($fopen, $compress);
			fclose($fopen);
		}
		
		return $compress;
	}
	public static function string($str){
		$render = new JavaScriptCompossorRender();
		return $render->render($str);
	}
	private static function error($msg){
		throw new Exception($msg);
	}
}
class JavaScriptCompossorStringBuilder{
	private $str = "";
	public function put($str){
		$this->str .= $str;
	}
	public function __toString(){
		exit("strlen: " . strlen($this->str));
		return $this->str;
	}
	public function toString(){
		exit("strlen: " . strlen($this->str));
		return $this->str;
	}
}
class JavaScriptCompossorStringReader{
	private $string;
	private $pointer = -1;
	public function __construct($str){
		$this->string = $str;
	}
	public function pop(){
		$this->pointer++;
		return $this->next($this->pointer);
	}
	public function peek(){
		return $this->next($this->pointer + 1);
	}
	private function next($index){
		if(strlen($this->string) < $index){
			return -1;
		}
		return ord($this->string[$index]);
	}
}
class JavaScriptCompossorTokenizer{
	private $reader, $buffer;
	public function __construct($str){
		$this->reader = new JavaScriptCompossorStringReader($str);
	}
	public function getReader(){
		return $this->reader;
	}
	public function next(){
		return $this->buffer = $this->getNext();
	}
	public function cache(){
		return $this->buffer;
	}
	private function getNext(){
		$char = $this->reader->pop();
		
		if($char === -1){
			return null;
		}
		
		if($char === 0 || $char === 10 || $char == 32){
			return $this->getNext();
		}
		
		switch($char){
			case 47:
				if($this->reader->peek() == 47){
					while($this->reader->pop() != 47){
					}
					return $this->getNext();
				}else if($this->reader->peek() == 42){
					$this->reader->pop();
					while(($c = $this->reader->pop()) != -1){
						if($c == 42 && $this->reader->peek() == 47){
							$this->reader->pop();
							return $this->getNext();
						}
					}
					
					$this->error("missing */");
				}
				return "/";
			default:
				return chr($char);
		}
	}
}
class JavaScriptCompossorRender{
	private $token;
	public function render($str){
		$this->token = new JavaScriptCompossorTokenizer($str);
		return $this->begin(new JavaScriptCompossorStringBuilder());
	}
	private function begin(JavaScriptCompossorStringBuilder $builder){
		while(($buffer = $this->token->next()) != null){
			switch($buffer){
				case '"':
					$this->getString($builder, 34);
				break;
				case "'":
					$this->getString($builder, 39);
				break;
				case "}":
					return;
				case "{":
					$builder->put("{");
					$this->begin($builder);
					if($this->token->cache() != "}"){
						$this->error("Missing }");
					}
					$builder->put("}");
				case "var":
					$builder->put($buffer . " ");
				break;
				default:
					$builder->put($buffer);
				break;
			}
		}
		
		return $builder->toStrig();
	}
	private function getString(JavaScriptCompossorStringBuilder $builder, $stopChar){
		while(($char = $this->token->getReader()->pop()) != -1){
			if($char == 92){
				$builder->put("\\" . chr($this->token->getReader()->pop()));
			}elseif($char == $stopChar){
				// see if the next token is +
				if($this->token->next() == "+"){
					if($this->token->next() == '"'){
						// a new string is comming.
						$this->getString($builder, 34);
						return;
					}elseif($this->token->cache() == "'"){
						$this->getString($builder, 39);
						return;
					}else{
						$builder->put(chr($char) . "+" . $this->token->cache());
						return;
					}
				}
				$builder->put(chr($char));
				return;
			}
		}
		$builder->put(chr($char));
	}
}

echo JvaScriptCompressor::file("https://code.jquery.com/jquery-1.12.3.js") . "-";
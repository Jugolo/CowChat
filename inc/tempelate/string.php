<?php
namespace inc\tempelate\string;

class TempelateString{
	private $char;
	
	public function __construct(string $string){
		$this->char = str_split($string);
	}
	
	public function current(){
		return ($char = current($this->char)) === false ? -1 : ord($char);
	}
	
	public function next(){
		return ($char = next($this->char)) === false ? -1 : ord($char);
	}
	
	public function peek(){
		if($this->current() == -1){
			return -1;
		}
		
		$next = $this->next();
		prev($this->char);
		return $next;
	}
	
	public function getKey(){
		return key($this->char);
	}
	
	public function toKey(int $key){
		if($key < $this->getKey()){
			while($this->getKey() != $key){
				prev($this->char);
			}
		}else{
			while($this->getKey() != $key){
				$this->next();
			}
		}
	}
}
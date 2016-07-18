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
}
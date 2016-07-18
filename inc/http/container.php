<?php
namespace inc\http\container;

class HttpContainer{
	private $header;
	private $context;
	
	public function __construct(string $context, array $header){
		$this->context = $context;
		$this->header  = $header;
	}
	
	public function context() : string{
		return $this->context;
	}
	
	public function header_raw() : array{
		return $this->header;
	}
}
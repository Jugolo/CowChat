<?php
namespace inc\tempelate\type;

use inc\error\HeigLevelError;

class Type{
	public function control_array($data){
		if(!is_array($data)){
			throw new HeigLevelError("Convert error. could convert '".gettype($data)."' to array");
		}
		
		return $data;
	}
	
	public function control_string($data){
		if(!is_string($data)){
			throw new HeigLevelError("Convert error. could convert '".gettype($data)."' to string");
		}
		
		return $data;
	}
}
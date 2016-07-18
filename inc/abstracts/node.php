<?php
namespace inc\abstracts\node;

use inc\tempelate\variabel_database\VariabelDatabase;
use inc\error\HeigLevelError;

abstract class Node{
	public function toString(array $options, VariabelDatabase $db) : string{
		throw new HeigLevelError(get_class($this)." can`t be convertet to string");
	}
	
	public function toBool(array $options, VariabelDatabase $db) : bool{
		throw new HeigLevelError(get_class($this)." can`t be convertet to bool");
	}
}
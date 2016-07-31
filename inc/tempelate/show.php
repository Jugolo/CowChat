<?php
namespace inc\tempelate\show;

use inc\tempelate\database\TempelateDatabase;
use inc\tempelate\type\Type;

class Show{
	private $database;
	private $type;
	
	public function __construct(string $show, TempelateDatabase $database){
		$this->database = $database;
		$this->type     = new Type();
		//exit($show);
		eval(' ?>'.$show.'<?php ');
	}
}
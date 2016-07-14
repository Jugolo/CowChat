<?php
namespace inc\interfaces\database_result;

interface DatabaseResult{
	public function fetch();
	public function rows() : int;
	public function free();
}
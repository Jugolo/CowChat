<?php
namespace inc\interfaces\database_query;

use inc\interfaces\database_result\DatabaseResult;

interface DatabaseQuery{
	public function __construct(string $host, string $user, string $password, string $database);
	public function query(string $query) : DatabaseResult;
	public function clean(string $context) : string;
	public function getTables() : array;
	public function getColumnsData(string $table) : array;
	public function convert_type_name(string $type) : string;
	public function primary_single() : bool;
	public function get_energy() : string;
	public function auto_increment() : string;
}
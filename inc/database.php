<?php

/**
 * This file handling the connection to database.
 * When call Database::getInstance() it will load the selected database driver
 * And connect to the database
 */
namespace inc\database;

use inc\interfaces\database_query\DatabaseQuery;
use inc\error\HeigLevelError;
use inc\file\Files;

/**
 * Main handler of database drivers
 * 
 * @author CowScript
 */
class Database{
	/**
	 * Driver buffer
	 * 
	 * @var DatabaseQuery
	 */
	private static $driver = null;
	
	/**
	 * Table prefix
	 * 
	 * @var string
	 */
	static $prefix;
	
	/**
	 * Get instance of database driver
	 * 
	 * @return DatabaseQuery
	 */
	public static function getInstance(): DatabaseQuery{
		if(self::$driver == null){
			self::set_driver();
		}
		
		return self::$driver;
	}
	
	/**
	 * Load database driver and set it up
	 * 
	 * @throws HeigLevelError
	 */
	private static function set_driver(){
		if(!Files::exists("inc/config.json")){
			throw new HeigLevelError("Missing config file");
		}
		
		$json = json_decode(Files::context("inc/config.json"));
		
		$driver = "inc\\driver\\database\\" . $json->driver . "\\DatabaseDriver";
		self::$driver = new $driver($json->host, $json->user, $json->pass, $json->table);
		self::$prefix = $json->prefix;
	}
	
	/**
	 * Insert data in the database
	 * 
	 * @param string $name
	 *        	the table name
	 * @param array $data        	
	 * @return int id on the inserted
	 */
	public static function insert($name, array $data): int{
		$database = Database::getInstance();
		$walk = function ($array) use ($database){
			$buffer = [];
			for($i = 0;$i < count($array);$i++){
				if(is_array($array[$i]))
					$buffer[$i] = $array[$i][0];
				else if($array[$i] === null)
					$buffer[$i] = "NULL";
				else
					$buffer[$i] = $database->clean($array[$i]);
			}
			
			return $buffer;
		};
		$query = $database->query("INSERT INTO " . table($name) . " (`" . implode("`,`", array_keys($data)) . "`) VALUES (" . implode(",", $walk(array_values($data))) . ")");
		$last = $query->last_id();
		$query->free();
		return $last;
	}
}

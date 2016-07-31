<?php

/**
 * This is a driver to connect to mysql
 */
namespace inc\driver\database\mysqli;

use inc\interfaces\database_query\DatabaseQuery;
use inc\interfaces\database_result\DatabaseResult;
use inc\error\LowLevelError;
use inc\error\HeigLevelError;
use inc\shoutdown\ShoutDown;

/**
 * Class to handle connection to mysql
 * 
 * @author CowScript
 */
class DatabaseDriver implements DatabaseQuery{
	/**
	 * Buffer for mysqli object
	 * 
	 * @var \mysqli
	 */
	private $database;
	
	/**
	 * Connect to mysql
	 * 
	 * @param string $host
	 *        	the host to connect to
	 * @param string $user
	 *        	the username to login width
	 * @param string $password
	 *        	the password to login width
	 * @param string $database
	 *        	the database to select
	 */
	public function __construct(string $host, string $user, string $password, string $database){
		$this->database = $mysqli = @new \mysqli($host, $user, $password, $database);
		if($this->database->connect_errno){
			throw new HeigLevelError("Could not connect to the database", $this->database->connect_error);
		}
		
		ShoutDown::append(function() use($mysqli){
			$mysqli->close();
		});
	}
	
	/**
	 * Make a query to the database
	 * 
	 * @param
	 *        	string @query the query to database
	 * @return DatabaseResult.
	 * @throws LowLevelError there contain the information about the error
	 */
	public function query(string $query): DatabaseResult{
		$result = $this->database->query($query);
		// if this query a fail?
		if($result === false){
			// yes it is
			throw new LowLevelError($this->database->error, $query);
		}
	}
	
	/**
	 * Get array width table names
	 * 
	 * @return array string
	 */
	public function getTables(): array{
		$query = $this->database->query("SHOW TABLES");
		$current = [];
		while($row = $query->fetch_array()){
			$current[] = $row[0];
		}
		return $current;
	}
	/**
	 * This is uses if the driver has diffrence type end mysql
	 */
	public function convert_type_name(string $type): string{
		return $type;
	}
	
	public function primary_single() : bool{
		return false;
	}
	
	public function get_energy() : string{
		return "ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	}
	
	public function auto_increment() : string{
		return "AUTO_INCREMENT";
	}
}
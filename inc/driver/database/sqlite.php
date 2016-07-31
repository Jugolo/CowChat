<?php
namespace inc\driver\database\sqlite;

use inc\interfaces\database_query\DatabaseQuery;
use inc\interfaces\database_result\DatabaseResult;
use inc\error\LowLevelError;
use inc\data\columns_data\ColumnsData;
use inc\file\Dirs;
use inc\shoutdown\ShoutDown;

class DatabaseDriver implements DatabaseQuery{
	/**
	 * a buffer to hold sqlite connection
	 * @var \SQLite3
	 */
	private $sqlite;
	
	public function __construct(string $host, string $user, string $password, string $database){
		$this->sqlite = $sqlite = new \SQLite3(Dirs::getDir().$database);
		
		$this->sqlite->createFunction("NOW", function($e){
			return date("y-M-d H:m:s");
		}, 0);
		
		ShoutDown::append(function() use($sqlite){
			$sqlite->close();
		});
	}
	
	public function query(string $query) : DatabaseResult{
		$result = $this->row_query($query);
		if(strpos($query, "INSERT") === 0){
			return new SQLiteWorkResult($result, $this->sqlite->lastInsertRowID());
		}
		return new SQLiteResult($result);
	}
	
	public function clean(string $context) : string{
		return "'".\SQLite3::escapeString($context)."'";
	}
	
	public function getTables() : array{
		$array = [];
		$result = $this->row_query("SELECT name FROM sqlite_master WHERE type='table';");
		
		while($row = $result->fetchArray()){
			$array[] = $row["name"];
		}
		return $array;
	}
	
	public function getColumnsData(string $table) : array{
		$array = [];
		
		$result = $this->row_query("PRAGMA table_info(".$table.");");
		while($row = $result->fetchArray()){
			$array[] = new ColumnsData(
					$row["name"]
			);
		}
		
		return $array;
	}
	
	private function row_query(string $query) : \SQLite3Result{
		$result = @$this->sqlite->query($query);
		if(!$result){
			throw new LowLevelError($this->sqlite->lastErrorMsg(), $query);
		}
		return $result;
	}
	
	/**
	 * Some type name is diffrence of mysql
	 * @param $type mysql type name
	 * @return string sqlite type name convertet from mysql
	 */
	public function convert_type_name(string $type) : string{
		switch($type){
			case "varchar":
				return "TEXT";
			case "int":
				return "INTEGER";
		}
		return strtoupper($type);
	}
	
	public function primary_single() : bool{
		return false;
	}
	
	public function get_energy() : string{
		return "";
	}
	
	public function auto_increment() : string{
		return "AUTOINCREMENT";//AUTOINCREMENT";
	}
}

class SQLiteResult implements DatabaseResult{
	/**
	 * Buffer for result from sqlite
	 * @var \SQLite3Result
	 */
	private $result;
	
	public function __construct(\SQLite3Result $result){
		$this->result = $result;
	}
	
	/**
	 * get the next result
	 * @return array|null null if there is no more result or array if there is
	 */
	public function fetch(){
		return $this->result->fetchArray();
	}
	
	/**
	 * Get count of rows there is in this result
	 * @return int
	 */
	public function rows() : int{
		return $this->result->numColumns();
	}
	
	/**
	 * Free the buffer
	 */
	public function free(){
		$this->result->finalize();
	}
}

class SQLiteWorkResult implements DatabaseResult{
	/**
	 * Buffer for last id from insert
	 * @var int
	 */
	private $last_id;
	private $result;
	
	public function __construct(\SQLite3Result $result, int $id){
		$this->last_id = $id;
		$this->result = $result;
	}
	
	/**
	 * this is not from select so null from standart
	 * @return null
	 */
	public function fetch(){
		return null;
	}
	
	public function rows() : int{
		return $this->last_id();
	}
	
	public function last_id(){
		return $this->last_id;
	}
	
	public function free(){
		$this->result->finalize();
	}
}
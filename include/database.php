<?php
class Database{
	public static $prefix;
	private static $connection;
	public static function init($host, $user, $pass, $data, $prefix){
		self::$connection = @new mysqli($host, $user, $pass, $data);
		if(empty(self::$connection->connect_error)){
			self::$prefix = $prefix;
			return true;
		}
		
		return false;
	}
	public static function query($q){
		$query = self::$connection->query($q);
		if(self::$connection->error){
			throw new Exception(self::$connection->error . (Server::is_cli() ? "\r\n" : "<br>") . $q);
		}
		if(strpos($q, "DELETE FROM") === 0){
			if(!$query)
				return false;
			return new DatabaseDeleteResult(self::$connection);
		}elseif(strpos($q, "UPDATE") === 0){
			if(!$query)
				return false;
			return new DatabaseUpdateResult(self::$connection);
		}
		if($query == null || is_bool($query)){
			return $query;
		}
		
		return new DatabaseResult($query);
	}
	public static function insert($name, array $data){
		$walk = function ($array){
			$buffer = [];
			for($i = 0;$i < count($array);$i++){
				if(is_array($array[$i]))
					$buffer[$i] = $array[$i][0];
				else
					$buffer[$i] = Database::qlean($array[$i]);
			}
			
			return $buffer;
		};
		$query = self::query("INSERT INTO " . table($name) . " (`" . implode("`,`", array_keys($data)) . "`) VALUES (" . implode(",", $walk(array_values($data))) . ")");
		return self::$connection->insert_id;
	}
	public static function qlean($str){
		return "'" . self::$connection->real_escape_string($str) . "'";
	}
}
function table($name){
	return "`" . Database::$prefix . "_" . $name . "`";
}
class DatabaseResult{
	private $connection;
	public function __construct($query){
		$this->connection = $query;
	}
	public function rows(){
		return $this->connection->num_rows;
	}
	public function fetch(){
		return $this->connection->fetch_assoc();
	}
}
class DatabaseDeleteResult{
	private $connection;
	public function __construct($mysql){
		$this->connection = $mysql;
	}
	public function rows(){
		return $this->connection->affected_rows;
	}
}
class DatabaseUpdateResult{
	private $connection;
	public function __construct($mysql){
		$this->connection = $mysql;
	}
	public function rows(){
		return $this->connection->affected_rows;
	}
}

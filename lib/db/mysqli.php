<?php
class DatabaseHandler{

    private $head      = null;
    public $isError   = false;
    private $errorData = array();
    private $debug = [];

    function __construct($host,$user,$pass,$data){
        $this->head = new mysqli($host,$user,$pass,$data);
        if($this->head->connect_error){
            $this->saveError(
                $this->head->connect_error,
                $this->head->connect_errno
            );
            return;
        }
    }

    function getDebug() : array{
       return $this->debug;
    }

    function query($sql){
        $result = $this->head->query($sql);
        if(defined("SQL_DEBUG")){
          $this->debug[] = $sql;
        }
        if($this->strstwidth($sql,"SELECT")){
            if(!$result){
                $this->saveError(
                    $this->head->error,
                    $this->head->errno,
                    $sql
                );
            }
        }

        return new DatabaseResult($sql,$this->head,$result);
    }

    function clean($context){
        return $this->head->escape_string($context);
    }

    function saveError($errStr,$errNo,$sql = 'null'){
        $this->isError = true;
        $this->errorData[] = array(
            'string' => $errStr,
            'number' => $errNo,
            'sql'    => $sql
        );
    }

    function getError(){
        $return =  "Error in Database:<br>";
        for($i=0;$i<count($this->errorData);$i++){
            $return .= "Error String: ".$this->errorData[$i]['string']."<br>
            Error Number: ".$this->errorData[$i]['number']."<br>
            Sql: ".$this->errorData[$i]['sql'];
        }

        return $return;
    }

    function strstwidth($tekst,$exp){
        return (strpos($tekst,$exp) === 0);
    }

    function lastIndex(){
        return $this->head->insert_id;
    }
}

class DatabaseResult{
    private $sql = null;
    private $main = null;
    private $result = null;

    function __construct($sql,$main,$result){
        $this->main = $main;
        $this->sql = $sql;
        $this->result = $result;
    }

    function get(){
        return @$this->result->fetch_assoc();
    }
}

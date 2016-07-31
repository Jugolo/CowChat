<?php
namespace inc\cron\garbage_collect;

use inc\interfaces\cron\Cron;
use inc\file\Dirs;

class CronWorker implements Cron{
	function updateInterval() : int{
		return 1140;
	}
	
	function render(){
		$this->empty_dir("inc/temp/");
	}
	
	function empty_dir($dir){
		$handler = Dirs::openDir($dir);
		while($item = readdir($handler)){
			if($item == ".." || $item == "."){
				continue;
			}
			
			if(is_file($dir.$item)){
				unlink($dir.$item);
			}else{
				$this->empty_dir($dir.$item."/");
				rmdir($dir.$item);
			}
		}
	}
}
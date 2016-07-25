<?php
namespace inc\interfaces\driver_file;

interface DriverFileItem{
	public function isFile() : bool;
	public function getItemName() : string;
}
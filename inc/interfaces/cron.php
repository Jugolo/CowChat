<?php
namespace inc\interfaces\cron;

interface Cron{
	function updateInterval() : int;
	function render();
}
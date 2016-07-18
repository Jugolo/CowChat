<?php
namespace inc\interfaces\tempelate;

use inc\tempelate\render\Render;

interface TempelateInterface{
	function render(string $context, Render $render) : string;
}
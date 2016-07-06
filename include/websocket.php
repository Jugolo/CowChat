<?php
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	echo "-----\r\n";
	echo "Mask        ".$masks."\r\n";
	echo "data        ".$data."\r\n";
	echo "Mask length ".strlen($masks)."\r\n";
	echo "Data length ".strlen($data)."\r\n";
	echo "-----\r\n";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

function mask($data){
	$frame = array();
	$encoded = "";
	$frame[0] = 0x81;
	$data_length = strlen($data);
	if($data_length <= 125){
		$frame[1] = $data_length;
	}else{
		$frame[1] = 126;
		$frame[2] = $data_length >> 8;
		$frame[3] = $data_length & 0xFF;
	}
	for($i=0;$i<sizeof($frame);$i++){
		$encoded .= chr($frame[$i]);
	}
	$encoded .= $data;
	return $encoded;
}
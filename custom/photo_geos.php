<?php
require_once "../common.php";
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");


$filename 	= isset($_GET["file"]) ? $_GET["file"] . ".json" : null;
if($filename){
$json 		= file_get_contents($filename);
	echo $json;
}
exit;

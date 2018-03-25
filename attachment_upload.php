<?php
require_once("common.php");

$response = array();
if(isset($_POST["doc"]) && isset($_POST["doc_id"])){
	$_id = $_POST["doc_id"];
	$doc = json_decode($_POST["doc"],1);
	$url = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
}elseif(isset($_POST["pdoc"]) && isset($_POST["ph_id"])){
	$_id = $_POST["ph_id"];
	$doc = json_decode($_POST["pdoc"],1);
	$url = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . $_id;
}elseif(isset($_POST["adoc"]) && isset($_POST["au_id"])){
	$_id = $_POST["au_id"];
	$doc = json_decode($_POST["adoc"],1);
	$url = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . $_id;
}else{
	$response["result"] = "error";
	$_id = "n/a";
	$doc = "n/a";
	$url = "n/a";
}

if($url != "n/a"){
	$result = doCurl($url,json_encode($doc), "PUT");
	print_r($result);
}
exit;
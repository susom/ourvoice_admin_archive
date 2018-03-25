<?php
require_once("common.php");

if(isset($_POST["doc"])){
	$doc = json_decode($_POST["doc"],1);
	print_rr($doc);

// $_id  		= $_POST["doc_id"];
// $url 		= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
// $response 	= doCurl($url);
}


echo "RECEIVE THE POST THEN RECREATE A PUT TO DATABASE CURL";
exit;
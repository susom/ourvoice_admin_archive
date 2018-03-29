<?php
require_once("common.php");



// make design doc, to get all photos attachments that were uploaded
// now create thumbnails for them

$url_path = $_SERVER['HTTP_ORIGIN'].dirname($_SERVER['PHP_SELF'])."/";
foreach($photos as $photo){
	$filename 	= $photo["name"];
	$ph_id    	= $i . "_" .$filename;
	$file_uri  	= "passthru.php?_id=".$ph_id."&_file=$filename" ;
	$thumb_uri 	= $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
	cacheThumb($ph_id,$thumb_uri);
}



exit;
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

print_r($doc);

if($url != "n/a"){
	// $result = doCurl($url,json_encode($doc), "PUT");
	// print_r($result);
}
exit;
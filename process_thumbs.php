<?php
require_once("common.php");

// make design doc, to get all photos attachments that were uploaded
function getPhotos($view, $keys_array){ //keys array is the # integer of the PrID
    $qs         = !empty($keys_array) ? "?" . http_build_query(array( 'key' => $keys_array )) : "";
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . "_design/get_photos/_view/".$view.$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

$webhook_from_app 	= false;
if(isset($_REQUEST["ph_ids"])){
	$photos 		= json_decode($_REQUEST["ph_ids"],1);
	$webhook_from_app = true;
}else{
	// GET ALL PHOTOS FOR NOW ONE TIME HIT
	$response 	= getPhotos("all", []);
	$photos 	= $response["rows"];
}

// $url_path 	= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/";
$url_path 	= "https://ourvoice-projects.med.stanford.edu/";

// now loop and create thumbnails for them
$filescreated = array();
$filescreated[] = "come the hell on";
$filescreated[] = $photos;
if(!empty($photos)){
	foreach($photos as $photo){
		$ph_id 			= $webhook_from_app ?  $photo : $photo["id"];
		$temp 			= explode("_photo_",$ph_id);
		$filename 		= "photo_" . $temp[count($temp)-1];
		$file_uri  		= "passthru.php?_id=".$ph_id."&_file=$filename" ;
		$thumb_uri 		= $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
		$filescreated[] = cacheThumb($ph_id,$thumb_uri);
	}
}
echo json_encode($filescreated);
exit;

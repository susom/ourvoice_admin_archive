<?php
require_once("common.php");


// make design doc, to get all photos attachments that were uploaded
function getPhotos($view, $keys_array){ //keys array is the # integer of the PrID
    $qs         = !empty($keys_array) ? "?" . http_build_query(array( 'key' => $keys_array )) : "";
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . "_design/get_photos/_view/".$view.$qs;
    print_rr($couch_url);
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

// GET ALL PHOTOS FOR NOW ONE TIME HIT
// $response 		= filter_by_projid("get_data_day","[\"$active_pid\",\"$date\"]");
// $days_data 		= rsort($response["rows"]); 
$response 	= getPhotos("all", []);

// now loop and create thumbnails for them
// $url_path 	= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/";
$url_path 	= "https://ourvoice-projects-dev.med.stanford.edu/";
$photos 	= $response["rows"];
if(!empty($photos)){
	foreach($photos as $photo){
		$ph_id 	= $photo["id"];
		$temp 		= explode("_photo_",$ph_id);

		$filename 		= "photo_" . $temp[count($temp)-1];
		$file_uri  	= "passthru.php?_id=".$ph_id."&_file=$filename" ;
		$thumb_uri 	= $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
		cacheThumb($ph_id,$thumb_uri);
	}
}
exit;

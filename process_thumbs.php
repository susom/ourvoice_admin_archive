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
$filescreated 		= array();
$filescreated[] 	= "following are list of fotos that thumbnails were created for";

$url_path 			= "https://ourvoice-projects.med.stanford.edu/";
// $url_path 			= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/";
if(isset($_POST["ph_ids"])){
	// GET THE SPECIFIC FILES TO MAKE THUMBNAILS FOR
	$photos 		= $_POST["ph_ids"];
	$webhook_from_app = true;
}else{
	// GET ALL PHOTOS (new style from disc_attachment) FOR NOW ONE TIME HIT
	$response 	= getPhotos("all", []);
	$photos 	= $response["rows"];

	// GET OLD PHOTOS in disc_users FOR NOW ONE TIME HIT
	$response 		= filter_by_projid("get_old_photos",[]);
	foreach($response["rows"] as $_ => $walk){
		$photos = $walk["value"];
		for($i = 0; $i < count($photos); $i++){
			$ph_id			= $walk["id"]."_photo_$i.jpg";
			$_id 			= $walk["id"];
			$filename 		= "photo_$i.jpg";
			$file_uri  		= "passthru.php?_id=".$_id."&_file=$filename&_old=1" ;
			$thumb_uri 		= $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
			$filescreated[] = cacheThumb($ph_id,$thumb_uri);
			sleep(.5);
		};
	}
	
	// GET OLD PHOTOS IN disc_attachments PHOTOS FOR NOW ONE TIME HIT
	$couch_url  = cfg::$couch_url . "/disc_attachments/_all_docs?include_docs=true";
	$response   = json_decode(doCurl($couch_url),1);
	foreach($response["rows"] as $walk){
		$doc 	= $walk["doc"];
		$_id 	= $doc["_id"];
		$att 	= !empty($doc["_attachments"]) ? array_keys($doc["_attachments"]) : array();
		foreach($att as $filename){
			if(strpos($filename,"audio") > -1){
				continue;
			}
			$ph_id			= $_id."_".$filename;
			$file_uri  		= "passthru.php?_id=".$_id."&_file=$filename&_old=2" ;
			$thumb_uri 		= $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
			$filescreated[] = cacheThumb($ph_id,$thumb_uri);
			sleep(.5);
		}
	}
}

// now loop and create thumbnails for them
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

$filescreated = array_filter($filescreated);
echo json_encode($filescreated);
exit;

<?php
require_once "common.php";

// Cache Time in Seconds
$day_cache      = 86400;
$week_cache     = 604800;
$month_cache    = 2628000;
$year_cache     = 31536000;

// File would be obtained from url of ajax request, like /download/?id=GNT_4C01067B-5704-4C7E-A30E-A501C13A19E7_1_1482192593554&file=photo_0.jpg
//$id 	= isset($_GET["_id"])	? $_GET["_id"] 		: "GNT_4C01067B-5704-4C7E-A30E-A501C13A19E7_1_1482192593554";
//$file 	= isset($_GET["_file"]) ? $_GET["_file"] 	: "photo_0.jpg";

$id 	= isset($_GET["_id"]) 	? filter_var($_GET["_id"], FILTER_SANITIZE_STRING) 		: NULL ;
$file 	= isset($_GET["_file"]) ? filter_var($_GET["_file"], FILTER_SANITIZE_STRING) 	: NULL ;
$old 	= isset($_GET["_old"])  ? true 	: false ;

if (empty($id) || empty($file)) {
    header('HTTP/1.1 404 Not Found');
	exit;
}

// DO INITIAL QUERY TO GET METADATA FROM COUCH
if($old){
	if($_GET["_old"] == 2){
		$url = cfg::$couch_url . "/disc_attachments/$id";
	}else{
		$url = cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id;
	}
}else{
	//
	$url = cfg::$couch_url . "/". cfg::$couch_attach_db."/" . $id;
}
$result = $ds->doCurl($url);
$result = json_decode($result,true);

if(!empty($result['_attachments'][$file])) {
    // Get metadata
	$meta 			= $result['_attachments'][$file];
	$content_type 	= $meta['content_type'];
	$content_length	= $meta['length'];
	$digest 		= $meta['digest'];
	$revpos 		= $meta['revpos'];
	$cache_age      = $year_cache;

	// YES, THE digest changes for each revision of the image (ie, pixelating images cause new revision cause new digest, use this as ETag)
	$sEtag 			= $digest;

	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $sEtag){
		// Okay, the browser already has the latest version of our file in his cache.
	    // So just tell it that the page was not modified and DON'T send the content
	    header('HTTP/1.1 304 Not Modified', true, 304);
	    header('Cache-Control: no-cache');    //Need this and Etag
       	header('ETag: ' . $sEtag);    // send a ETag again with the response

	    header_remove("Pragma");
	    header_remove("Expires");
	}else{
	    // It is important to specify  Cache-Control max-age, and ETag, for all cacheable resources.
	    // Set download headers MUST BE IN THIS ORDER
	    header('HTTP/1.1 200 OK', true, 200);
	    header('Cache-Control: no-cache');
	    header('ETag: ' . $sEtag);
	    header('Content-Type:  ' . $content_type);

	    header('Content-Disposition: attachment; filename="' . $file . '"');
	    header('Content-Length: ' . $content_length);

	    header_remove("Pragma");
	    header_remove("Expires");
	    // Display file
		$result = $ds->doCurl($url ."/" . $file);
		echo $result;
	}
}else{
	header('HTTP/1.1 404 Not Found');
	exit;
}



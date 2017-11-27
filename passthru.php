<?php
require_once "common.php";


/**
 * A test for passthru
 */

// File would be obtained from url of ajax request, like /download/?id=GNT_4C01067B-5704-4C7E-A30E-A501C13A19E7_1_1482192593554&file=photo_0.jpg
//$id 	= isset($_GET["_id"])	? $_GET["_id"] 		: "GNT_4C01067B-5704-4C7E-A30E-A501C13A19E7_1_1482192593554";
//$file 	= isset($_GET["_file"]) ? $_GET["_file"] 	: "photo_0.jpg";

$id 	= isset($_GET["_id"]) 	? $_GET["_id"] 		: NULL ;
$file 	= isset($_GET["_file"]) ? $_GET["_file"] 	: NULL ;
$old 	= isset($_GET["_old"])  ? true 	: false ;

if (empty($id) || empty($file)) {
    exit ("Invalid id or file");
}

// Do initial query to get metadata from couchdb
$url 	= $old ? cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id : cfg::$couch_url . "/". $couch_attach_db."/" . $id;
$result = doCurl($url);
$result = json_decode($result,true);

if (empty($result['_attachments'][$file])) {
    exit("Invalid ID or File");
}

// Get metadata
$meta = $result['_attachments'][$file];
//print "<pre>" . print_r($meta,true) . "</pre>";

// Display file
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Content-Type: " . $meta['content_type']);
header("Content-Disposition: inline; filename=".$file.";");
header("Content-Length: ". $meta['length']);

$result = doCurl($url ."/" . $file);
print $result;
exit();


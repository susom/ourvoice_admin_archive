<?php
require_once "common.php";

//TODO ADD ZIP to DOCKER FILE or BASE IMAGE

if(empty($_GET["doc_id"]) && !empty($_SERVER["HTTP_REFERER"])){
	header("location:". $_SERVER["HTTP_REFERER"]);
	exit;
}

$doc_id 		= filter_var($_GET["doc_id"], FILTER_SANITIZE_STRING);
$walk_data 		= $ds->getWalkData($doc_id);
$walk_photos 	= !empty($walk_data["attachment_ids"]) ? $walk_data["attachment_ids"] : array();
$walk_photos	= array_filter($walk_photos, function($item){
						return strpos($item,"_photo") > -1 && strpos($item,"jpg") > -1;
					});

$photo_names 	= array();
foreach($walk_photos as $photo){
	$file_name 	= substr($photo, strlen($doc_id) + 1);
	$img_url 	= $ds->getStorageFile($google_bucket, $doc_id , $file_name);
	$photo_names[$file_name] = $img_url;
}

$zip 		= new ZipArchive();
$zip_name 	= $doc["_id"] ."_photos.zip"; // Zip name
if($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE){ 
 	// Opening zip file to load files
	$error .= "* Sorry ZIP creation failed at this time";
}

foreach($photo_names as $filename => $file){
	$fileContent = file_get_contents($file);
	$zip->addFromString($filename, $fileContent);
}
$zip->close();

if(file_exists($zip_name)){
	// push to download the zip
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="'.$zip_name.'"');
	header('Content-Length: ' . filesize($zip_name));
	readfile($zip_name);

	// remove zip file is exists in temp path
	unlink($zip_name);
}
?>


<?php
require_once "common.php";

//TODO ADD ZIP to DOCKER FILE or BASE IMAGE

if(empty($_GET["doc_id"]) && !empty($_SERVER["HTTP_REFERER"])){
	header("location:". $_SERVER["HTTP_REFERER"]);
	exit;
}

$doc_id 		= filter_var($_GET["doc_id"], FILTER_SANITIZE_STRING);
$walk_data 		= $ds->getWalkData($doc_id);
$partial_files 	= !empty($walk_data["partial_files"]) ? $walk_data["partial_files"] : array();
$photo_names 	= !empty($walk_data["photo_names"]) ? $walk_data["photo_names"] : array();
$photos 		= array();
$existing_files = array_diff($photo_names, $partial_files);
foreach($existing_files as $file_name){
	//want full size
	$img_url 	= $ds->getStorageFile($google_bucket, $doc_id , $file_name);
	$photos[$file_name] = $img_url;
}

$zip 		= new ZipArchive();
$zip_name 	= $doc_id ."_photos.zip"; // Zip name
if($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE){ 
 	// Opening zip file to load files
	$error .= "* Sorry ZIP creation failed at this time";
}

foreach($photos as $filename => $file){
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


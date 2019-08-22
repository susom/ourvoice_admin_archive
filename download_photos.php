<?php
require_once "common.php";

if(empty($_GET["doc_id"]) && !empty($_SERVER["HTTP_REFERER"])){
	header("location:". $_SERVER["HTTP_REFERER"]);
	exit;
}

$doc_id 	= $_GET["doc_id"];
$url        = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $doc_id;
$response   = doCurl($url);
$doc 		= json_decode(stripslashes($response),1); 

if(array_key_exists("error",$doc)){
	header("location: https://ourvoice-projects.med.stanford.edu/summary.php");
	exit;
}

$photo_names = array();
$baseurl 	= "http://". $_SERVER['SERVER_NAME']. $_SERVER["REQUEST_URI"];
$baseurl 	= substr($baseurl,0,strrpos($baseurl,"/")+1);
foreach($doc["photos"] as $photo){
	$filename 	= $photo["name"];
	$ph_id 		= $doc["_id"]."_".$filename;

	$img_url 	= $baseurl . "passthru.php?_id=".$ph_id."&_file=$filename";
	$photo_names[$filename] = $img_url;
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


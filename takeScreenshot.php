<?php 
require_once "common.php";
require_once "vendor/tcpdf/tcpdf.php";
if(isset($_GET["_id"]) && isset($_GET["_file"])){
	$_id 		= trim($_GET["_id"]);
	$_file 		= $_GET["_file"];
	$full_path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	print_rr($full_path);
	$result = str_replace('takeScreenshot.php','pdf_conversion.php',$full_path);
	print_rr($result);
	$googlePagespeedData = file_get_contents("https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$result&screenshot=true");
	$googlePagespeedData = json_decode($googlePagespeedData, true);
	print_rr($googlePagespeedData);
	$screenshot = $googlePagespeedData['screenshot']['data'];
	$screenshot = str_replace(array('_','-'),array('/','+'),$screenshot); 
	echo "<img src=\"data:image/jpeg;base64,".$screenshot."\" />";
 


}


?>
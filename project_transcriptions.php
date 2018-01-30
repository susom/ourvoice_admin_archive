<?php
require_once "common.php";

if( empty($_SESSION["DT"]) ){
	// FIRST GET THE PROJECT DATA
	$couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	$response 		= doCurl($couch_url);

	//TURN IT INTO PHP ARRAY
	$_SESSION["DT"] = json_decode(stripslashes($response),1);
}

// NEXT GET SPECIFIC PROJECT DATA
$ap 				= $_SESSION["DT"];
$_id 				= $ap["_id"];
$_rev 				= $ap["_rev"];
$projs 				= $ap["project_list"];
$active_project_id 	= $_GET["active_project_id"];
$active_pid 		= $_GET["pid"];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous"/>
<link href="css/dt_common.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<link href="css/dt_summary.css?v=<?php echo time();?>" rel="stylesheet" type="text/css"/>
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=<?php echo cfg::$gmaps_key; ?>"></script>
<script type="text/javascript" src="js/dt_summary.js?v=<?php echo time();?>"></script>
<style>
h4[data-toggle="collapse"]{
	padding-bottom:5px;
	margin-bottom:20px;
	border-bottom:1px solid #999;
	cursor:pointer;
	font-size:250%;
	font-weight:normal;
}
</style>
</head>
<body id="main">
<a href="summary.php">Back to Summary</a>
<?php
echo "<pre>";
if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("transcriptions","[\"$active_pid\"]");
	$project_meta 	= $ap["project_list"][$active_pid];

	//PRINT TO SCREEN
	echo "<h1>Transcription data for project : $active_project_id</h1>";

	foreach($response["rows"] as $sesh){
		echo "<h4>".$sesh["id"]."</h4>";
		$transx = $sesh["value"]["transcriptions"];

		$photo 	= array(); 
		foreach($transx as $audio_key => $trans){
			$split 		= explode("_",$audio_key);
			$photo_key 	= $split[1];
			if(!array_key_exists($photo_key, $photo)){
				$photo[$photo_key] = array();
			} 
			$photo[$photo_key][$audio_key] = $trans;
		}

		foreach($photo as $photo_key => $transx){
			echo "<h5>Photo_$photo_key</h5>";
			echo "<ul>";
			foreach($transx as $audio_key => $trans){
				echo "<li>$audio_key : $trans</li>";
			}
			echo "</ul>";
		}

		echo "<hr>";
	}
}
?>
<script>

</script>
</body>
</html>





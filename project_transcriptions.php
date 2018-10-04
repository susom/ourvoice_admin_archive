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
h1{
	margin-bottom:30px;
}
h5{
	padding-bottom:5px;
	margin-bottom:10px;
	border-bottom:1px solid #999;
	cursor:pointer;
	font-size:120%;
	font-weight:normal;
}

dl { 
	margin:0 0 30px; 
	overflow:hidden; 
}
dt { 
	float:left;  
	width:148px;
}
dt a { 
	display:block; 
	border:1px solid #ccc;
	text-align:center;
	padding:3px;
}
dd { 
	float:right; 
	text-align:left;
	clear:left; 
	clear:right;
	margin:0 0 5px;
	width:calc(100% - 168px);
}
</style>
</head>
<body id="main">
<a href="summary.php">Back to Summary</a>
<?php
if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("transcriptions","[\"$active_pid\"]");
	$project_meta 	= $ap["project_list"][$active_pid];

	$html_buffer 	= array();
	echo "<h1>Transcription data for project : $active_project_id</h1>";
	
	foreach($response["rows"] as $sesh){
		$temp_buffer = array();

	    unset($sesh["value"]["project_id"]);
	    unset($sesh["value"]["_id"]);
	    unset($sesh["value"]["_rev"]);
	    unset($sesh["value"]["user_id"]);
	    unset($sesh["value"]["geotags"]);
	    unset($sesh["value"]["survey"]);
	    unset($sesh["value"]["currentDistance"]);
	    unset($sesh["value"]["upload_try"]);

	    $lang 			= $sesh["value"]["lang"];
	    $id_a 			= substr($sesh["id"],0,strlen($sesh["id"]) - 4);
	    $walk_id 		= substr($sesh["id"],-4);
		$temp_buffer[] 	=  "<h5>Walk ID: $id_a<b><u>$walk_id</u></b></h5>";
		foreach($sesh["value"]["photos"] as $photo_key => $photo){
			$filename 	= $photo["name"];
			$img_id 	= $ph_id = $sesh["id"] . "_" . $filename;

			$file_uri   = "passthru.php?_id=".$ph_id."&_file=$filename";
	        $thumb_uri  = "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
	        $photo_uri  = getThumb($img_id,$thumb_uri,$file_uri);
	        $detail_url = "photo.php?_id=".$sesh["id"]."&_file=$filename";

			$temp_buffer[] = "<dl>";
			$temp_buffer[] = "<dt><a href='$detail_url'><img src='$photo_uri'/></a><dt>";
			if(isset($photo["audios"])){
				foreach($photo["audios"] as $key => $audio_key){
					$transcription = isset($sesh["value"]["transcriptions"]) && isset($sesh["value"]["transcriptions"][$audio_key]) ? $sesh["value"]["transcriptions"][$audio_key]["text"] : "Not transcribed yet...";
					$temp_buffer[] = "<dd>Audio ".($key+1)." ($lang): " . $transcription ."</dd>";
				}
			}
			$temp_buffer[] = "</dl>";
		}

		$last_ 	= strrpos($sesh["id"],"_");
		$ts 	= substr($sesh["id"], $last_+1);
		$html_buffer[$ts] = implode("\n",$temp_buffer);
	}

	krsort($html_buffer);

	foreach($html_buffer as $walk){
		echo $walk;
	};
}
?>
<script>

</script>
</body>
</html>





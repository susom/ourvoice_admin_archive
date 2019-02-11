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

#summary {
    width:100%;
    border-top:1px solid #000;
    border-left:1px solid #000;
}

#summary td,#summary th {
    border-right:1px solid #000;
    border-bottom:1px solid #000;
    text-align:center;
}
</style>
</head>
<body id="main">
<a href="summary.php">Back to Summary</a>
<?php
if( $active_project_id ){
    markPageLoadTime("BEGIN Project Walk Summary");
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= getProjectSummaryData($active_project_id);

    $html_buffer = [];
    $html_buffer[] = "<table id='summary'>";
    $html_buffer[] = "<thead>";
    $html_buffer[] = "<th>#</th>";
    $html_buffer[] = "<th>Date</th>";
    $html_buffer[] = "<th>Walk Id</th>";
    $html_buffer[] = "<th>Device</th>";
    $html_buffer[] = "<th>Photos #</th>";
    $html_buffer[] = "<th>Audios #</th>";
    $html_buffer[] = "<th>Map Available?</th>";
    $html_buffer[] = "<th>Upload Complete?</th>";
    $html_buffer[] = "</thead>";
    $html_buffer[] = "<tbody>";

    foreach($response["rows"] as $i => $row){
        $walk   = $row["value"];
        $_id    = substr($row["id"] , -4);
        $device = $walk["device"]["platform"] . " (".$walk["device"]["version"].")";

        //check for attachment ids existing
        //IMPORTANT TO FORMAT THIS RIGHT OR ELSE WILL GET INVALID JSON ERROR
        $partial    = '["'.implode('","',$walk["attachment_ids"]).'"]';
        $count_att  = checkAttachmentsExist($partial);
        $uploaded   = count($walk["attachment_ids"]) == count($count_att["rows"]) ? "Y" : "N";

         $html_buffer[] = "<tr>";
         $html_buffer[] = "<td>" . ($i+1) . "</td>";
         $html_buffer[] = "<td>" . $walk["date"] . "</td>";
         $html_buffer[] = "<td>" . $_id . "</td>";
         $html_buffer[] = "<td>" . $device . "</td>";
         $html_buffer[] = "<td>" . $walk["photos"]. "</td>";
         $html_buffer[] = "<td>" . $walk["audios"]. "</td>";
         $html_buffer[] = "<td>" . $walk["maps"]. "</td>";
         $html_buffer[] = "<td>" . $uploaded. "</td>";
         $html_buffer[] = "</tr>";
    }
    $html_buffer[] = "</tbody>";
    $html_buffer[] = "</table>";
    echo implode("\r\n",$html_buffer);

    markPageLoadTime("End Project Walk Summary");
}
?>
</body>
</html>





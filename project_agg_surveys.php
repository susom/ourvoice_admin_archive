<?php
require_once "common.php";

function filter_by_projid($view, $keys_array){
	$qs 		= http_build_query(array( 'key' => $keys_array ));
    $couch_url 	= cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/filter_by_projid/_view/".$view."?" .  $qs;
    $response 	= doCurl($couch_url);
    return json_decode($response,1);
}

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
if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= filter_by_projid("surveys","[\"$active_pid\"]");
	$project_meta 	= $ap["project_list"][$active_pid];

	$survey_data 	= $project_meta["surveys"];
	$lang 			= "en";
	$survey_qa 		= array();

	$headers 		= array();
	foreach($survey_data as $q){
		$headers[] 	= $q["label"][$lang];
		$survey_qa[$q["name"]] = array("label" => $q["label"][$lang], "type" => $q["type"] );
		if(isset($q["options"])){
			$options = array();
			foreach($q["options"] as $option){
				$options[$option["value"]] = $option[$lang];
			}
			$survey_qa[$q["name"]]["options"]	= $options;
			$survey_qa[$q["name"]]["answers"] 	= array();
		}
	}

	//PRINT TO SCREEN
	echo "<h1>Aggregate survey data for project : $active_project_id</h1>";

	$rows = array();
	foreach($response["rows"] as $sesh){
		$row 			= array();
		$prep_values 	= array();
		foreach($sesh["value"] as $qa){
			$prep_values[$qa["name"]] = $qa["value"];
		}

		$row[] = "<td>".substr($sesh["id"],-4)."</td>";
		foreach($survey_qa as $qname => $q){
			if(array_key_exists($qname, $prep_values)){
				$survey_qa[$qname]["answers"][] = $prep_values[$qname];
				$word 	=  array_key_exists("options",$survey_qa[$qname]) ? $survey_qa[$qname]["options"][$prep_values[$qname]] : $prep_values[$qname];
				$row[] 	= "<td>".$word." (".$prep_values[$qname].")</td>";
			}else{
				$row[] 	= "<td>n/a</td>";
			}
		}
		$rows[] = "<tr>". implode("",$row) . "</tr>";
	}

	$summavg = array();
	foreach($survey_qa as $qname => $q){
		$sum = array_sum($survey_qa[$qname]["answers"]);
		$cnt = count($survey_qa[$qname]["answers"]);
		if( is_numeric($survey_qa[$qname]["answers"][0]) ){
			$summavg[] = "<td>". number_format( $sum/$cnt, 2, ".", "") . " ($cnt responses)</td>";
		}else{
			$summavg[] = "<td>n/a</td>";
		}
	}

	echo "<table id='survey_agg'>";
	echo "<thead><tr>";
	echo "<th>Walk Id</th><th>".implode("</th><th>",$headers)."</th>";
	echo "</tr></thead>";
	echo "<tbody>";
	echo implode("\n",$rows);
	echo "</tbody>";
	echo "<tfoot><tr>";
	echo "<td>Average</td>" . implode("",$summavg);
	echo "</tr></tfoot>";
	echo "</table>";
}
?>
<style>
#survey_agg {
	border-top:1px solid #000;
	border-right:1px solid #000;
	width:1200px;
}
#survey_agg td, #survey_agg th{
	min-width:200px;
	border-left:1px solid #000;
	border-bottom:1px solid #000;
	padding:2px 4px;
	vertical-align: top;
}
thead, tfoot{
	background:#ffffcc;
}
tbody {

}
</style>
</body>
</html>





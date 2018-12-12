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

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=surveys_'.$active_project_id.'.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

if( $active_project_id ){
	//FIRST GET JUST THE DATES AVAILABLE IN THIS PROJECT
    $response 		= getAggSurveys("[\"$active_pid\"]");
	$survey_data 	= $ap["survey_text"];
	$lang 			= "en";
	$survey_qa 		= array();

	$headers 		= array();
	foreach($survey_data as $short_or_full){
		foreach($short_or_full as $q){
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
	}

	array_unshift($headers,"walk id");

	// output the column headings
    fputcsv($output, $headers);

    $rows = array();
    foreach($response["rows"] as $sesh){
		$row 			= array();
		$prep_values 	= array();
		foreach($sesh["value"] as $qa){
			$prep_values[$qa["name"]] = $qa["value"];
		}

		$row[] = substr($sesh["id"],-4);
		foreach($survey_qa as $qname => $q){
			if(array_key_exists($qname, $prep_values)){
				$survey_qa[$qname]["answers"][] = $prep_values[$qname];
				$word 	=  array_key_exists("options",$survey_qa[$qname]) ? $survey_qa[$qname]["options"][$prep_values[$qname]] : $prep_values[$qname];
				$row[] 	= $word." (".$prep_values[$qname].")";
			}else{
				$row[] 	= "n/a";
			}
		}
		$rows[] = $row;
	}

	$summavg = array();
	foreach($survey_qa as $qname => $q){
		$sum = !empty($q["answers"]) ? array_sum($q["answers"]) : 0;
		$cnt = !empty($q["answers"]) ? count($q["answers"]) : 0;
		if(!empty($q["answers"]) && is_numeric($q["answers"][0]) ){
			$summavg[] = number_format( $sum/$cnt, 2, ".", "") . " ($cnt responses)";
		}else{
			$summavg[] = "n/a";
		}
	}

	array_unshift($summavg,"Average");
    $rows = array_merge($rows,array($summavg));
	foreach($rows as $surveys){
		fputcsv($output, $surveys);
    }
}
?>





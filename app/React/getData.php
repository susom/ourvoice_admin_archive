<?php
require_once "../common.php";
$ALL_PROJ_DATA = $ds->urlToJson(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db);
$tm = $ds->urlToJson(cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts");
$stor = $listid = array();
$stor = parseTime($tm, $stor, $listid);
$checkWeek = strtotime("-4 Week"); //for recent activities
$counter = $iter = 0;
$retvar = array();

//////////////Get Recent Project Data /////////////
foreach ($stor as $key => $value)
	array_push($listid, $key);

for($i = 0 ; $i < count($stor) ; $i++){
	rsort($stor[$listid[$i]]); //sort corresponding timestamps for each element
	$ful = getFullName($ALL_PROJ_DATA,$listid[$i]);
	$iter = 0;
	$retvar["set"][$i]["abv"] = $listid[$i];
	$retvar["set"][$i]["pid"] = $i;
	$retvar["set"][$i]["full"] = $ful;
	$retvar["set"][$i]["non_rec_times"] = array();
	$retvar["set"][$i]["rec_time"] = array();		
	while(!empty($stor[$listid[$i]][$iter]) && $iter < 1) //display 1
		{

			if(($stor[$listid[$i]][$iter]/1000) > $checkWeek){
				$counter++;
				array_push($retvar["set"][$i]["rec_time"],gmdate("Y-m-d", $stor[$listid[$i]][$iter]/1000));
			}else{
				array_push($retvar["set"][$i]["non_rec_times"],gmdate("Y-m-d", $stor[$listid[$i]][$iter]/1000));
			}
			$iter++;
		}
}
$retvar["list_full"] = array();
$retvar["list_abv"] = array();
//////////////Get ALL Project Data /////////////
foreach($ALL_PROJ_DATA["project_list"] as $key => $value){
	if(isset($value["project_name"])){
		array_push($retvar["list_full"], $value["project_name"]);
		array_push($retvar["list_abv"], $value["project_id"]);
	}
}

// $hello = array();
// array_push($hello, "H");
// echo json_encode($hello);
// exit;
 //echo (json_encode($ALL_PROJ_DATA));
// echo json_encode($list_projects);
 echo json_encode($retvar);
//return (json_encode($listid));
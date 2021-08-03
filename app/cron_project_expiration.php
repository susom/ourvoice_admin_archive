<?php
require_once "common.php";

$couch_url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
$response 		= $ds->doCurl($couch_url);
$project_json 	= json_decode($response,1);

$today 			= date('Y-m-d', time()); 
$expire_today 	= array();
foreach($project_json["project_list"] as $pid => $project){
	if(array_key_exists("expire_date",$project) && !empty($project["expire_date"])  && !strpos($project["project_pass"],"expired")){
		if($project["expire_date"] == $today){
			$project["archived"] 				= 1;	
			$project["project_pass"] 			= "_expired_";
			$project["summ_pass"]				= "_expired_";
			$project_json["project_list"][$pid] = $project;
			$expire_today[] = $project["project_id"];
		}
	}
}

doCurl($couch_url, json_encode($project_json), "PUT");
echo "Expired Projects Today: ";
echo implode(", ", $expire_today);
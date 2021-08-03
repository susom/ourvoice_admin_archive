<?php
require_once "common.php";
//technically callback
$storage = $ds->getAllData();
if(!isset($_SESSION["DT"])){ //store data for use the first time
	$_SESSION["DT"] = $storage;
	
}	

$folders = isset($_POST["folders"]) ? filter_var($_POST["folders"],FILTER_SANITIZE_STRING) : null;
if($folders){ 
	//check if the folder exists within couch already

	if(!isset($storage["folders"]))
		$storage["folders"] = array();
	if(in_array($folders, $storage["folders"])){
		print_r("this folder already exists");
	}else{
		array_push($storage["folders"], $folders);
		$_SESSION["DT"] = $storage;
		print_r("pushing to folders");
	 	$url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
		$response 	= $ds->doCurl($url, json_encode($storage), 'PUT');
        $resp 		= json_decode($response,1);
	}

}

//we want each project to have a drop tag indicating where it's supposed to belong

if(isset($_POST["dropTag"]) && isset($_POST["dragTag"]) && isset($_POST["datakey"])){
  	$drop_tag 	= trim( filter_var($_POST["dropTag"],FILTER_SANITIZE_STRING) );
  	$drag_tag 	= trim( filter_var($_POST["dragTag"],FILTER_SANITIZE_STRING) );
  	$datakey 	= trim( filter_var($_POST["datakey"],FILTER_SANITIZE_STRING) );

	if(!isset($storage["project_list"][$datakey]["dropTag"])){
		$storage["project_list"][$datakey]["dropTag"] = $drop_tag; 	
		print_r("SUCCESS");
		print_r("$drop_tag");
		$_SESSION["DT"] = $storage;
        $url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	    $response 	= $ds->doCurl($url, json_encode($storage), 'PUT');
        $resp 		= json_decode($response,1);
	}else{
		//shouldn't happen
		print_r("element already part of list");
	}
}

// $delete_tag = isset($_POST["deleteTag"]) ? filter_var($_POST["deleteTag"],FILTER_SANITIZE_STRING) : null;
if(isset($_POST["deleteTag"])){	
	$deletion_list = json_decode($_POST["deleteTag"],1);
	$folder_name = $deletion_list["folder"][0];
	//print_r($deletion_list);
	for($i = 0 ; $i < sizeof($deletion_list["keys"]) ; $i++){
		//print_r($deletion_list["keys"]["$i"]);
		$pid = $deletion_list["keys"]["$i"];
		//var_dump(isset($storage["project_list"][$pid]["dropTag"]));

		//print_r($storage["project_list"][$pid]["dropTag"]);
		unset($storage["project_list"][$pid]["dropTag"]);
		//print_r($storage["project_list"][$pid]]);
	}	
	print_r($storage["folders"]);

	if($folder_name != "-1"){ //no folder provided to delete, just project
		foreach($storage["folders"] as $key => $value)
		{
			 if($value == $folder_name)
			 	unset($storage["folders"][$key]);
		}
	}//if 
	$_SESSION["DT"] = $storage;

	$url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	$response 	= $ds->doCurl($url, json_encode($storage), 'PUT');
    $resp 		= json_decode($response,1);
}

exit;


?>
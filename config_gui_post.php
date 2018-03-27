<?php
require_once "common.php";
//technically callback
$storage = getAllData();
if(!isset($_SESSION["DT"])){ //store data for use the first time
	$_SESSION["DT"] = $storage;
	
}	

if(isset($_POST["folders"])){ 
	//check if the folder exists within couch already

	if(!isset($storage["folders"]))
		$storage["folders"] = array();
	if(in_array($_POST["folders"], $storage["folders"])){
		print_r("this folder already exists");
	}else{
		array_push($storage["folders"], $_POST["folders"]);
		$_SESSION["DT"] = $storage;
		print_r("pushing to folders");
	 	$url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
		$response 	= doCurl($url, json_encode($storage), 'PUT');
        $resp 		= json_decode($response,1);
	}

}

//we want each project to have a drop tag indicating where it's supposed to belong

if(isset($_POST["dropTag"]) && isset($_POST["dragTag"]) && isset($_POST["datakey"])){
	print_r($_POST["datakey"]);
  $drop_tag = trim($_POST["dropTag"]);
  $drag_tag = trim($_POST["dragTag"]);
  $datakey = trim($_POST["datakey"]);

		if(!isset($storage["project_list"][$datakey]["dropTag"]))
  		{
  			$storage["project_list"][$datakey]["dropTag"] = $drop_tag; 	
  			print_r("SUCCESS");
  			print_r("$drop_tag");
  			$_SESSION["DT"] = $storage;
  	        $url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
		    $response 	= doCurl($url, json_encode($storage), 'PUT');
            $resp 		= json_decode($response,1);

		}else{
			//shouldn't happen
			print_r("element already part of list");
		}



	}

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

	$url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
	$response 	= doCurl($url, json_encode($storage), 'PUT');
    $resp 		= json_decode($response,1);
}

exit;


?>
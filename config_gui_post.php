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
		//$response 	= doCurl($url, json_encode($storage), 'PUT');
        //$resp 		= json_decode($response,1);
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
  	      //  $url 		= cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
		  //  $response 	= doCurl($url, json_encode($storage), 'PUT');
          //  $resp 		= json_decode($response,1);

		}else{
			//shouldn't happen
			print_r("element already part of list");
		}



	}


exit;


?>
<?php
require_once "common.php";

if(isset($_POST["DragTag"])) {
	$proj_id = $_POST["Project"];
	$drag_tag = $_POST["DragTag"];
	$temp = explode("_", $_POST["DropTag"]);
	$pic_reference = $temp[0] ."_". $temp[1] ."_". $temp[2] ."_". $temp[3]; 
	$pic_number = $temp[5];
	$datakey = $_POST["Key"];
	$storage = getAllData();
	$tag_loc = $storage["project_list"][$datakey]["tags"];
	$present_flag = false;
	//add total tags in all_projects
	 if(isset($tag_loc)){ //if the tags category exists to start
	 	print_r ($storage["project_list"][$datakey]["tags"]);
	 	for($i = 0 ; $i < count($tag_loc) ; $i++){
	 		if($tag_loc[$i] == $drag_tag){
	 			$present_flag = true;
	 			break;
	 		}
	 	}
	 	if(!$present_flag){ //if name isn't already defined within the list of tags
	 		echo "not present in list";
	 		array_push($storage["project_list"][$datakey]["tags"], $drag_tag);
	 		//$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db, $storage);

	 	}
	 }else{ //if the tags category does not exist
	 	 echo "not exist";
	 	 $storage["project_list"][$datakey]["tags"] = array();
	 	 array_push($storage["project_list"][$datakey]["tags"], $drag_tag);
	 	 //$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db, $storage);
	 	 //COMMENT THIS RESP PUSH OUT BECAUSE IT WAS UPDATING DATABASE MULTIPLE TIMES PER DRAG
	 }
	 //add tag to individual photo in disc_users 
	 $url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference;
     $response       = doCurl($url);
	 $storage 		= json_decode($response,1);
	 //print_r($storage);
	
	 $present_flag = false;
	 //print_r($pic_number);
	 //print_r($storage["photos"]);
	 $tag_loc = $storage["photos"][$pic_number]["tags"];
	 if(isset($tag_loc)){
	 	echo "exisdt";
	 	for($i = 0 ; $i < count($tag_loc); $i++){
	 		if($tag_loc[$i] == $drag_tag)
	 		{
	 			echo $tag_loc[$i];
	 			$present_flag = true;
	 			break;
	 		}
	 	}
	 	if(!$present_flag){
	 		array_push($storage["photos"][$pic_number]["tags"],$drag_tag);
	 		//$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference, $storage);
	 	}
	 }else{
	 	$storage["photos"][$pic_number]["tags"] = array();
	 	array_push($storage["photos"][$pic_number]["tags"],$drag_tag);
	 	//$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference, $storage);
	 }

	$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference, $storage);

}	

if(isset($_POST["deleteTag"])){
	$deletetag 	= $_POST["deleteTag"];
	$pcode 		= $_POST["project_code"];
	// RGET PROJECT LIST, FIND PROJECT AND DELETE TAG, RESAVE PROJECT LIST, REASSIGN SESSION VAR
	$payload 	= getAllData();
	foreach($payload["project_list"] as $key => $value){ //loop through projects
		if($value["project_id"] == $pcode){
			$remove_idx = array_search($deletetag, $value["tags"]);
			unset($payload["project_list"][$key]["tags"][$remove_idx]);
			$payload["project_list"][$key]["tags"] = array_values($payload["project_list"][$key]["tags"]);
			break;
		}
	}
	$result 	= push_data(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db, $payload);
	$_SESSION["DT"] = $payload;

	// GET ALL PHOTOS WITH TAG, REMOVE TAG FROM PHOTO, RESAVE
	$photos_w_tag 	= filterProjectByTags($pcode, array($deletetag));
	foreach($photos_w_tag["rows"] as $item){
		$_id 	= $item["id"];
		$photo 	= $item["value"];
		$ph_i 	= $photo[0];
		$ph 	= $photo[2];

		$payload 	= getWalkData($_id);
		$remove_idx = array_search($deletetag, $payload["photos"][$ph_i]["tags"]);
		unset($payload["photos"][$ph_i]["tags"][$remove_idx]);
		$payload["photos"][$ph_i]["tags"] = array_values($payload["photos"][$ph_i]["tags"]);
		$result 	= saveWalkData($_id, $payload);
	};
}

?>
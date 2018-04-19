<?php
require_once "common.php";

if(isset($_POST["DragTag"])) //assign picture a tag
{
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
	$dt = $_POST["deleteTag"];
	$d_pics = $_POST["pictures"];
	$storage = getAllData();
	//remove tag from project
	foreach($storage["project_list"] as $key => $value){ //loop through projects
		if(isset($value["tags"])){
			foreach($value["tags"] as $index => $tag){ //if tag: loop through them
				if($tag == $dt){
				 	unset($storage["project_list"][$key]["tags"][$index]);

				}
			}
			// print_r($storage["project_list"][104]);
		}
	}
	$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db, $storage);
	$_SESSION["DT"] = $storage;
	//remove the tag from each individual photo
	foreach($d_pics as $pid){ //loop through photos on page
		$temp = explode("_", $pid);
		$call_string = $temp[0] ."_". $temp[1] ."_". $temp[2] ."_". $temp[3];
		$pic_number = $temp[5];
		$url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $call_string;
	 	$storage 		= json_decode(doCurl($url),1); //call photo information each time,
	 	foreach ($storage["photos"][$pic_number]["tags"] as $key => $value) {
	 		if($value == $dt)
	 		{
	 			unset($storage["photos"][$pic_number]["tags"][$key]);
	 		}
	 	}
	 	$resp = push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $call_string, $storage);


	}//foreach
}

?>
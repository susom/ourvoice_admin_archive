<?php
require_once "common.php";

$action = filter_var($_POST["action"], FILTER_SANITIZE_STRING);
if(!empty($_POST["action"])){
    // POSSIBLE POST VARS COMING IN
    $_id        = !empty($_POST["doc_id"])  ? filter_var($_POST["doc_id"], FILTER_SANITIZE_STRING) : null;
    $url        = !empty($_POST["url"])     ? filter_var($_POST["url"], FILTER_SANITIZE_ENCODED) :null;
    $lang       = !empty($_POST["lang"])    ? filter_var($_POST["lang"], FILTER_SANITIZE_STRING) :null;
    $photo_i    = isset($_POST["photo_i"]) ? filter_var($_POST["photo_i"], FILTER_SANITIZE_NUMBER_INT) : null;
    $filename   = isset($_POST["_filename"]) ? filter_var($_POST["_filename"], FILTER_SANITIZE_STRING) : null;

    // GET WALK DATA
    if($_id){
        $payload    = $ds->getWalkData($_id, true);
        $read_data  = $payload->snapshot()->data();
        $photos     = $read_data["photos"];
    }

	$ajax_resp  = null;
    $response   = null;

	switch($action){
        case 'rotation':
            $rotate = filter_var($_POST["rotate"], FILTER_SANITIZE_NUMBER_INT);
            if(isset($photos[$photo_i])){
                $photos[$photo_i]["rotate"] = $rotate;
                $response = $payload->update([
                    ['path' => 'photos', 'value' => $photos]
                ]);
            }
        break;

        case 'data_processed':
            $response = $payload->update([
                ['path' => 'data_processed', 'value' => True]
            ]);
            $ajax_resp = true;
        break;

        case 'day_walks':
            $active_pid 	= isset($_POST["active_pid"]) ? filter_var($_POST["active_pid"], FILTER_SANITIZE_STRING) : null;
            $date 			= isset($_POST["date"]) ? filter_var($_POST["date"], FILTER_SANITIZE_STRING) : null;

            //GET THE DATA FROM disc_users
            $response 		= $ds->filter_by_projid($active_pid ,$date);
            $code_block 	= array();

            foreach($response as $i => $row){
                $doc        = $row;
                $code_block = array_merge($code_block, printRow($doc,$i));
            }
            echo implode("",$code_block);
        break;

        case 'delete_photo':
            if(isset($photos[$photo_i])){
                $photos[$photo_i]["_deleted"] = true;
                $response = $payload->update([
                    ['path' => 'photos', 'value' => $photos]
                ]);
            }
        break;

        case 'delete_walk':
            $response = $payload->update([
                ['path' => '_deleted', 'value' => True]
            ]);
            $ajax_resp = true;
        break;

        case 'tag_text':
            //SAVE TAG
            $photo_tag 		= !empty($_POST["tag_text"]) ? filter_var($_POST["tag_text"], FILTER_SANITIZE_STRING) : null;
            $photo_tag 		= str_replace('"',"'",$photo_tag);
            $proj_idx 		= !empty($_POST["proj_idx"]) ? filter_var($_POST["proj_idx"], FILTER_SANITIZE_STRING) : null;

            if($photo_tag){
                $json_response 	= array("new_photo_tag" => false, "new_project_tag" => false);
                if(isset($photos[$photo_i])){
                    if( !array_key_exists("tags", $photos[$photo_i]) ){
                        $photos[$photo_i]["tags"] = array();
                    }

                    if(!in_array($photo_tag,$photos[$photo_i]["tags"])){
                        array_push($photos[$photo_i]["tags"], $photo_tag);
                        $json_response["new_photo_tag"] = true;
                    }

                    $result = $payload->update([
                        ['path' => 'photos', 'value' => $photos]
                    ]);
                }

                if($proj_idx){
                    //POSSIBLE NEW PROJECT TAG, SAVE TO disc_projects
                    $project_fs     = $ds->getProject($proj_idx);
                    $snapshot       = $project_fs->snapshot();
                    $project_data   = $snapshot->data();
                    if(!array_key_exists("tags", $project_data)){
                        $project_data["tags"] = array();
                    }

                    if(!in_array($photo_tag,$project_data["tags"])){
                        array_push($project_data["tags"], $photo_tag);
                        $json_response["new_project_tag"] = true;
                    }

                    $result = $project_fs->update([
                        ['path' => 'tags', 'value' => $project_data["tags"]]
                    ]);
                }
                $response   = json_encode($json_response);
                $ajax_resp  = true;
            }
        break;

        case 'delete_tag_text':
            $photo_tag 		= !empty($_POST["delete_tag_text"]) ? filter_var($_POST["delete_tag_text"], FILTER_SANITIZE_STRING) : null;
            if($photo_tag){
                if(isset($photos[$photo_i])){
                    if( array_key_exists("tags", $photos[$photo_i]) ){
                        if (($key = array_search($photo_tag, $photos[$photo_i]["tags"])) !== false) {
                            unset($photos[$photo_i]["tags"][$key]);
                            $photos[$photo_i]["tags"] = array_values($photos[$photo_i]["tags"]);
                            $result = $payload->update([
                                ['path' => 'photos', 'value' => $photos]
                            ]);
                        }
                    }
                }
            }
        break;

        case 'add_project_tag':
            //POSSIBLE NEW PROJECT TAG, SAVE TO disc_projects
            $json_response 	= array("new_project_tag" => false);
            $proj_idx 		= !empty($_POST["proj_idx"]) ? filter_var($_POST["proj_idx"], FILTER_SANITIZE_STRING) : null;
            $project_tag 	= !empty($_POST["tag_text"]) ? filter_var($_POST["tag_text"], FILTER_SANITIZE_STRING) : null;

            //POSSIBLE NEW PROJECT TAG, SAVE TO disc_projects
            $project_fs     = $ds->getProject($proj_idx);
            $snapshot       = $project_fs->snapshot();
            $project_data   = $snapshot->data();
            if(!array_key_exists("tags", $project_data)){
                $project_data["tags"] = array();
            }

            if(!in_array($project_tag,$project_data["tags"])){
                array_push($project_data["tags"], $project_tag);
                $json_response["new_project_tag"] = true;
            }

            $result = $project_fs->update([
                ['path' => 'tags', 'value' => $project_data["tags"]]
            ]);

            $response   = json_encode($json_response);
            $ajax_resp  = true;
        break;

        case 'delete_project_tag':
            $delete_tag = !empty($_POST["deleteTag"]) ? filter_var($_POST["deleteTag"], FILTER_SANITIZE_STRING) : null;
            $proj_idx   = !empty($_POST["project_code"]) ? filter_var($_POST["project_code"], FILTER_SANITIZE_STRING) : null;

            $project_fs     = $ds->getProject($proj_idx);
            $snapshot       = $project_fs->snapshot();
            $project_data   = $snapshot->data();
            if(array_key_exists("tags", $project_data)){
                if (($key = array_search($delete_tag, $project_data["tags"])) !== false) {
                    //delete tag from project[tags]
                    unset($project_data["tags"][$key]);
                    $project_data["tags"] = array_values($project_data["tags"]);
                    $result = $project_fs->update([
                        ['path' => 'tags', 'value' => $project_data["tags"]]
                    ]);

                    //delete tag from all individual photos that have it
                    $walks_w_tags 	= $ds->filterProjectByTags($proj_idx, array($delete_tag));
                    $walks_fs       = $ds->getWalks($walks_w_tags);
                    foreach($walks_fs as $walk_fs){
                        $data       = $walk_fs->snapshot()->data();
                        $photos     = $data["photos"];
                        $changes    = false;
                        foreach($photos as $photo_i => $photo){
                            if(!array_key_exists("tags",$photo)){
                                continue;
                            }
                            if(($key = array_search($delete_tag, $photo["tags"])) !== false){
                                unset($photos[$photo_i]["tags"][$key]);
                                $photos[$photo_i]["tags"] = array_values($photos[$photo_i]["tags"]);
                                $changes = true;
                            }
                        }

                        if($changes){
                            $result = $walk_fs->update([
                                ['path' => 'photos', 'value' => $photos]
                            ]);
                        }
                    }
                }
            }
        break;

        case 'save_text_comment':
        case 'save_audio_txn':
            $text_comment   = !empty($_POST["text"]) ? filter_var($_POST["text"], FILTER_SANITIZE_STRING) : null;
            $prop           = !empty($_POST["prop"]) ? filter_var($_POST["prop"], FILTER_SANITIZE_STRING) : null;
            if($text_comment){
                $text_comment = str_replace('"','&#39;', $text_comment);
                if(isset($photos[$photo_i])){
                    if($prop == "text_comment"){
                        $photos[$photo_i]["text_comment"] = $text_comment;
                    }
                    if(isset($photos[$photo_i]["audios"][$prop])){
                        $photos[$photo_i]["audios"][$prop] = $text_comment;
                    }

                    $result = $payload->update([
                        ['path' => 'photos', 'value' => $photos]
                    ]);

                    $ajax_resp = "ok";
                }
            }
        break;

        case 'load_thumbs':
            $proj_idx 	        = !empty($_POST["pcode"]) ? filter_var($_POST["pcode"], FILTER_SANITIZE_STRING) : null;
            $pfilters 		    = !empty($_POST["filters"]) ? $_POST["filters"] : array();

            $data 			    = $ds->getFilteredDataGeos($proj_idx, $pfilters);
            $data["code_block"] = printAllDataThumbs($data["code_block"]);

            $response 		    = json_encode($data);
            $ajax_resp          = true;
        break;

        case 'drag_tag':
            $proj_id 	= filter_var($_POST["Project"], FILTER_SANITIZE_NUMBER_INT);
            $drag_tag 	= filter_var($_POST["DragTag"], FILTER_SANITIZE_STRING);
            $temp 		= explode("_", $_POST["DropTag"]);
            $pic_reference = $temp[0] ."_". $temp[1] ."_". $temp[2] ."_". $temp[3];
            $pic_number = $temp[5];
            $datakey 	= filter_var($_POST["Key"], FILTER_SANITIZE_STRING);
            $storage 	= $ds->getAllData();
            $tag_loc 	= $storage["project_list"][$datakey]["tags"];
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
                    //$resp = $ds->push_data(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db, $storage);

                }
            }else{ //if the tags category does not exist
                echo "not exist";
                $storage["project_list"][$datakey]["tags"] = array();
                array_push($storage["project_list"][$datakey]["tags"], $drag_tag);
                //$resp = $ds->push_data(cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db, $storage);
                //COMMENT THIS RESP PUSH OUT BECAUSE IT WAS UPDATING DATABASE MULTIPLE TIMES PER DRAG
            }
            //add tag to individual photo in disc_users
            $url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference;
            $response       = $ds->doCurl($url);
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
                    //$resp = $ds->push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference, $storage);
                }
            }else{
                $storage["photos"][$pic_number]["tags"] = array();
                array_push($storage["photos"][$pic_number]["tags"],$drag_tag);
                //$resp = $ds->push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference, $storage);
            }

            $resp = $ds->push_data(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $pic_reference, $storage);
        break;

        case 'pixelation':
            if(isset($_POST["pic_id"]) && isset($_POST['photo_num'])&& isset($_POST['coordinates'])){
                $face_coord 	= json_decode(filter_var($_POST["coordinates"], FILTER_SANITIZE_STRING),1);
                $_id 			= filter_var($_POST["pic_id"], FILTER_SANITIZE_STRING);
                $photo_num 		= filter_var($_POST["photo_num"], FILTER_SANITIZE_NUMBER_INT);
                $rotationOffset = filter_var($_POST["rotation"], FILTER_SANITIZE_NUMBER_INT);
                $photo_num 		= 'photo_'.$photo_num . '.jpg';
                $id 			= $_id."_".$photo_num;

                //find rev by curling to couch
                $url 			= cfg::$couch_url . "/". cfg::$couch_attach_db . "/" .$id;
                $result 		= $ds->doCurl($url);
                $result 		= json_decode($result,1);
                $rev 			= ($result['_rev']);

                //find the offset so canvas can be specified for each image based on portal rotation
                // $rOffset = findRotationOffset(cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $id);
                // 0 = none, 1 = base, 2 = 90 degree rotation

                $picture 		= $ds->doCurl($url . '/' . $photo_num); //returns the actual image in string format
                $new 			= imagecreatefromstring($picture); //set the actual picture for editing
                $pixel_count 	= (imagesx($new)*imagesy($new)); //scale pixel to % image size
                $altered_image 	= filterFaces($face_coord, $new, $_id, $pixel_count, $rotationOffset);
                if(isset($altered_image) && $altered_image){
                    $filepath = "./temp/$_id.jpg";
                    if(file_exists($filepath)){
                        unlink("./temp/$_id.jpg");
                    }

                    // if(file_exists($filepath))
                    // 	unset($filepath);

                    imagejpeg($altered_image, $filepath); //save it
                    imagedestroy($altered_image);
                    $content_type   = 'image/jpeg';
                    $attach_url 	= cfg::$couch_url . "/" . cfg::$couch_attach_db;
                    $couchurl       = $attach_url."/".$id."/".$photo_num."?rev=".$rev;
                    $content_type   = 'image/jpeg';
                    $response       = $ds->uploadAttach($couchurl, $filepath, $content_type);

                    $storageCLient = new StorageClient([
                        'keyFilePath'   => $keyPath,
                        'projectId'     => $gcp_project_id
                    ]);

                    //UPLOAD TO GOOGLE BUCKET
                    $uploaded   	= $ds->uploadCloudStorage($id ,$_id , $gcp_bucketName, $storageCLient,  $filepath);
                    //refresh page
                }
            }
        break;
    }

    if($ajax_resp) {
        print_r($response);
    }
}else{
    echo json_encode(array("error" => "something went wrong"));
}

<?php
require_once("common.php");

// AJAX HANDLE walk data
if(isset($_POST["doc"]) && isset($_POST["doc_id"])){
	$_id = $_POST["doc_id"];
	$doc = json_decode($_POST["doc"],1);
	// $url = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;

    // IF THE DOC WAS PROPERLY PASSED IN
    if(isset($doc["_id"])){
        $local_folder = "temp/$_id";
        if( !file_exists($local_folder) ){
            mkdir($local_folder, 0777, true);
        }

        // CHECK IF WALK DATA ALREADY EXISTS, NEED TO DELETE IT TO WRITE IT AGAIN, NO OVERWRITE FEATURE?
        $walk_data = $local_folder."/".$_id.".json";
        if( file_exists($walk_data) ){
            unlink($walk_data);
        }

        // CREATE NEW WALK DATA, THEN RETURN EXPECTED LIST OF FILE ATTACHMENTS?
        $fp = fopen($walk_data,'w');
        if(fwrite($fp, json_encode($doc))){
            $nice_return = array();
            foreach($doc["photos"] as $photo){
                array_push($nice_return, array("_id" => $_id . "_" . $photo["name"], "name" => $photo["name"])); 
                foreach($photo["audios"] as $audioname){
                    array_push($nice_return, array("_id" => $_id . "_" . $audioname, "name" => $audioname));
                }
            }
            print_r(json_encode($nice_return));
        }else{
            //what to do if it fails?
            //nothing i guess
        }
        fclose($fp);
    }
    exit;
}

if( isset($_REQUEST["walk_id"]) ){
    $walk_id        = $_REQUEST["walk_id"];
    $local_folder   = "temp/$walk_id";

    if ($_FILES["attachment"]["error"] == UPLOAD_ERR_OK){
        // CHECK IF WALK DATA ALREADY EXISTS, NEED TO DELETE IT TO WRITE IT AGAIN, NO OVERWRITE FEATURE?
        $file       = $_FILES["attachment"]["tmp_name"];
        $name       = "fuckoff.jpg";
        $attachment = $local_folder."/".$name ;
        if( file_exists($attachment) ){
            unlink($attachment);
        }

        // now you have access to the file being uploaded
        //perform the upload operation.
        move_uploaded_file( $file, $attachment );
        print_r(json_encode(array("$attachment saved to disk?!!!")));
    }else{
        print_r(json_encode(array($attachment . " upload failed")));
    }
    exit;
}


exit;

<?php     
require_once "common.php";
require_once "inc/class.mail.php";

require 'vendor/autoload.php';

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

# Imports the Google Cloud client library
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Firestore\FirestoreClient;

// FIRESTORE details
$keyPath            = cfg::$FireStorekeyPath;
$gcp_project_id     = cfg::$gcp_project_id; 
$walks_collection   = cfg::$firestore_collection; 
$firestore_endpoint = cfg::$firestore_endpoint; 
$firestore_scope    = cfg::$firestore_scope; 
$gcp_bucketID       = cfg::$gcp_bucketID; 
$gcp_bucketName     = cfg::$gcp_bucketName; 
$access_token       = getGCPRestToken($keyPath, $firestore_scope);

// GET WALK ID , FROM THE PING
$uploaded_walk_id   = isset($_POST["uploaded_walk_id"]) ? $_POST["uploaded_walk_id"] : null;
// $proccesed_thumb_ids    = isset($_POST["proccesed_thumb_ids"]) ?  $_POST["proccesed_thumb_ids"] : null;                  

if(!empty($uploaded_walk_id)){ 
    $_id                = $uploaded_walk_id;  
    $email              = isset($_POST["project_email"]) ? $_POST["project_email"] : false;   
    $temp               = explode("_",$_id);
    $project_id         = $temp[0];

    $emergency_uploaded = false;
    $check_attach_urls  = array();
    if(isset($_POST["doc"])){
        $doc    = json_decode($_POST["doc"],1);   
        
        // GET PROJECT EMAIL 
        $photos = $doc["photos"];

        // SLEEP GIVE IT TIME TO UPLOAD ATTACHMENTS 
        // 11 seconds (only?) same amount of time as it takes to get to 83% on phone
        sleep(11); 

        // CHECK IF ATTACHMENT IDS ARE ALL EXISTING WITHIN disc_attachments AFTER SLEEP PERIOD
        $couch_url = "http://".cfg::$couch_user.":".cfg::$couch_pw."@couchdb:5984";
        foreach($photos as $i => $photo){
            $ph_id          = $_id . "_" . $photo["name"];
            $photo_attach   = $couch_url . "/".$couch_attach_db."/" . $ph_id ;
            $check_attach_urls[$photo_attach] = get_head($photo_attach);
            if(isset($photo["audios"])){
                foreach($photo["audios"] as $filename){
                    $aud_id         = $_id . "_" . $filename;
                    $audio_attach   = $couch_url . "/".$couch_attach_db."/" . $aud_id ;
                    $check_attach_urls[$audio_attach] = get_head($audio_attach);
                }
            }
        }

        $meta = array("uploaded_walk_id"    => $_id  
                     ,"email"               => $email                                                                       
                     ,"attachments"         => $check_attach_urls
                 );   
        echo json_encode(array("uploaded_walk" => $meta)); 
    }else{
        // EMERGENCY UPLOAD SUCCESS
        $emergency_uploaded = "Notification: [$project_id] Emergency Backup Uploaded";

        // SCAN ./temp FOLDER FOR NEW FOLDERS!
        $backup_folder      = "temp/$_id";
        // AT SOME POINT WILL HAVE TO DECOUPLE THIS FROM COUCHDB 10/17/19
        $backup_files       = scanBackUpFolder($backup_folder);

        // NEED GRPC EXTENSION TO BE INSTALLED FUDGE!, USE REST API INSTEAD 
        // Intanstiate FireStore Client
        // $firestore  = new FirestoreClient([
        //      'projectId'    => $overallProjectId
        //     ,'keyFilePath'  => $keyPath
        // ]);

        // # Instantiates a Storage client
        $storageCLient = new StorageClient([
            'keyFilePath'   => $keyPath,
            'projectId'     => $gcp_bucketID
        ]);

        foreach($backup_files as $file){
            $path = $backup_folder . "/" . $file;
            if(strpos($file,".json") > 0){
                $walks_url  = cfg::$couch_url . "/" . cfg::$couch_users_db ;
                $payload    = file_get_contents($path);
                $response   = doCurl($walks_url, $payload, 'POST');

                // STORE WALK DATA INTO FIRESTORE FORMAT
                $old_walk_id    = str_replace(".json","",$file); 
                $fs_walk_id     = setWalkFireStore($old_walk_id, json_decode($payload,1), $access_token);
            }else{
                $attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db; 
                // TWO STEPS
                
                // first , create the data entry
                $payload    = json_encode(array("_id" => $file));
                $response   = doCurl($attach_url, $payload, 'POST');
                $response   = json_decode($response,1);
                $rev        = $response["rev"];

                // TODO
                // IN CASE WHERE THE data entry is created, but the attachment is empty, 
                // just get the REV somehow and do the PREP attachment as USUAL
                // so only need to do the second step

                // next upload the attach
                $response   = prepareAttachment($file,$rev,$_id,$attach_url); 

                //UPLOAD TO GOOGLE BUCKET
                $uploaded   = uploadCloudStorage($file ,$_id , $gcp_bucketName, $storageCLient);
            }
        }

        // DELETE THE DIRECTORY , NOT YET?
        // deleteDirectory($backup_folder);

        // RECURSIVELY GO THROUGH THOSE AND UPLOAD THOSE MAFUHS
        echo json_encode(array(  "ebackup"      => $backup_files
                                ,"backupfolder" => $backup_folder
                                ,"email"        => $email
                        ));
    }                                                                                

    // EITHER YES OR NO ON PARTIAL UPLOADS
    $failed_uploads = array();
    foreach($check_attach_urls as $attach_id => $head_result){
    	if(array_key_exists("error", $head_result)){
    		 $failed_uploads[] = $attach_id;
    	}
    }

    // Sending emails broke?  5/18/19
    /*
    $from_name      = "Stanford Our Voice";
    $from_email     = "irvins@stanford.edu";
    $email_subject  = count($failed_uploads) ? "Notification: [$project_id] New walk uploaded, possibly missing attachments" : "Notification: [$project_id] New walk uploaded!";
    $email_subject  = !$emergency_uploaded ? $subject : $emergency_uploaded;

    // EMAIL TO PROJECT ADMIN EMAIL, CC ANN,IRVIN, JORDAN 
    $msg_arr        = array();
    $msg_arr[]      = "Hi $project_id  Administrator,<br>";
    $msg_arr[]      = "This email is letting you know that a walk with id $_id has been uploaded to your project.<br>";
    $msg_arr[]      = "Please check the <a href='http://ourvoice-projects.med.stanford.edu/summary.php' target='blank' >Our Voice Data Portal</a> to view the walk data.<br>";
    if(count($failed_uploads)){
        $msg_arr[]  = "There may have been some technical issues uploading the data resulting in the following attachment files not being fully uploaded:";
        $msg_arr[]  = "<ul>";
        $msg_arr[]  = "<li>" . implode("</li>\r\n<li>", $failed_uploads) . "</li>";
        $msg_arr[]  = "</ul>";
        $msg_arr[]  = "If you find that some or all of them are missing from the portal, you can try to reset and upload that walk again from the device.";
        $msg_arr[]  = "Please remember to be on a stable wifi connection and leave the app open through out the upload process.<br>";
    }
    $msg_arr[]      = "Cheers!";
    $msg_arr[]      = "The Our Voice Team";
    $msg_arr[]      = "<i style='font-size:77%;'>Participant rights: contact our IRB at 1-866-680-2906</i>";
    $email_msg      = implode("<br>",$msg_arr);

    //EXTERNAL MODULE IN REDCAP TO ACT AS API TO RELAY EMAILS
    $mail_relay_endpoint    = "https://redcap.stanford.edu/api/?type=module&prefix=email_relay&page=service&pid=13619";
    $mail_api_token         = "XemWorYpUv";
    $to                     = $email;
    $cc                     = "banchoff@stanford.edu";
    $bcc                    = "irvins@stanford.edu, jmschultz@stanford.edu";
	
    sendMailRelay($mail_relay_endpoint, $mail_api_token, $email_subject, $email_msg, $from_name, $from_email, $to, $cc, $bcc);                                                            
    */
}

// if get head = succesful
// {"_id":"IRV_7B6D2189-3F1E-4290-91DB-E7DCBD0E42A0_4_1528047306210_photo_0.jpg","_rev":"15-2b55e7600c989813497f81a114eb23a7
// ","upload_try":15,"_attachments":{"photo_0.jpg":{"content_type":"image/jpeg","revpos":1,"digest":"md5-cK9299gcwakNPwrbYcu
// NfA==","length":1525030,"stub":true}}}

// if get head = fail
// {"error":"not_found","reason":"missing"}

function setWalkFireStore($old_id, $details, $firestore=null){
    // FIRESTORE FORMAT walk_id
    $walk_id        = convertFSwalkId($old_id);

    // IF NO PHOTOS, THEN ITS NOT A COMPLETE WALK 
    if(!array_key_exists("photos", $details)){
        return false;
    }

    // GET COMPONENT PIECES TO START REFORMAT DATA MODEL FOR FIRESTORE

    $pid            = $walk_parts[0];
    $lang           = array_key_exists("lang", $details) ? $details["lang"] : null ;
    
    $device         = array_key_exists("device", $details) ? $details["device"] : array() ; 
    $device["uid"]  = $walk_parts[1]; 

    $survey         = array_key_exists("survey", $details) ? $details["survey"] : array();

    $txn            = array_key_exists("transcriptions", $details) ? $details["transcriptions"] : array();
    $photos         = $details["photos"];

    $new_photos     = formatUpdateWalkPhotos($photos,$txn);

    $geotags        = array_key_exists("geotags", $details) ? $details["geotags"] : array() ; 
    $culled_geos    = array();
    foreach($geotags as $geotag){
        if($geotag["accuracy"]){
            $culled_geos[] = $geotag;//array_intersect_key($geotag,$keep_these);
        }
    }

    // NEED TO FORMAT PROPERLY TO PUSH TO FIRESTORE
    $fs_pid     = ["stringValue" => $pid];
    $fs_lang    = !is_null($lang) ? ["stringValue" => $lang] : ["nullValue" => null];
    $fs_ts      = ["integerValue"   => $walk_parts[3]];

    // map device array
    $temp = array();
    foreach($device as $key => $val){
        $temp[$key] = array("stringValue" => $val);
    }
    $fs_device  = array("mapValue" => array("fields" => $temp));

    // map survey array , the app no longer records surveys
    $fs_survey  = array("arrayValue" => array("values" => array()));
    
    // map photos array
    $fs_photos  = array("arrayValue" => array("values" => $new_photos));

    $firestore_data = [
             'project_id'   => $fs_pid
            ,'lang'         => $fs_lang
            ,'timestamp'    => $fs_ts
            ,'device'       => $fs_device
            ,'survey'       => $fs_survey
            ,'photos'       => $fs_photos
        ];
    $data = ["fields" => (object)$firestore_data];
    $json = json_encode($data);

    $object_unique_id   = $walk_id;
    $firestore_url      = cfg::$firestore_endpoint . "projects/".cfg::$gcp_project_id."/databases/(default)/documents/".cfg::$firestore_collection."/".$object_unique_id;
    $access_token       = $firestore;

    //PUSH THE ORIGINAL WALK DATA DOCUMENT
    $response           = restPushFireStore($firestore_url, $json, $access_token);

    // NOW PUSH A NEW DOCUMENT FOR EACH GEOTAG TO THE SUBCOLLECTION FOR THE WALK 
    $firestore_url_sub  = $firestore_url . "/geotags/";
    foreach($culled_geos as $i => $geotag){
        $geoFields = array();
        foreach($geotag as $key => $val){
            $geoFields[$key] = array("doubleValue" => $val);
        }

        $fs_geo         = array("mapValue" => array("fields" => $geoFields));
        $temp_url       = $firestore_url_sub . $i;

        $firestore_data = [$fs_geo];
        $data           = ["fields" => (object)$firestore_data];
        $json           = json_encode($data);
        $response       = restPushFireStore($temp_url, $json, $access_token);
        set_time_limit(5);
    }

    return $walk_id;

    // THIS IS FOR IF THE GRPC EXTENSION GETS INSTALLED
    try {
        // CREATE THE PARENT DOC
        $docRef = $firestore->collection(cfg::$firestore_collection)->document($walk_id);
        $docRef->set([
             'project_id'   => $pid
            ,'lang'         => $lang
            ,'timestamp'    => $walk_parts[3]
            ,'device'       => $device
            ,'photos'       => $new_photos
            ,'survey'       => $survey
        ]);
        
        // ADD GEOTAGS AS SUBCOLLECTION AND INDIVIDUAL DOCS
        $subCollectionRef = $docRef->collection("geotags");
        foreach($culled_geos as $i => $geotag){
            try{
                $subDocRef = $subCollectionRef->document($i);
                $subDocRef->set($geotag);
            } catch(exception $e){
                echo "bad subcollection : $walk_id <Br>";
            }
            set_time_limit(30);
        }
        echo  "geotags added for : $walk_id <br>";
    } catch (exception $e) {
        echo "bad walk_id : $walk_id <br>";
    }

    return $walk_id;
}

function uploadCloudStorage($attach_id, $walk_id, $bucketName,  $storageCLient){
    # UPLOAD TO CLOUD STORAGE
    $folder_components  = explode("_",$walk_id);

    $project_id         = $folder_components[0];
    $device_id          = $folder_components[1];
    $walk_ts            = $folder_components[3];
    $attachment_prefix  = "$project_id/$device_id/$walk_ts/";
    $file_suffix        = str_replace($walk_id."_","",$attach_id);
    
    $filepath           = 'temp/'.$walk_id.'/'.$attach_id;
    $new_attach_id      = $attachment_prefix . $file_suffix;

    //UPLOAD from TEMP DIR on DISK
    $uploaded           = upload_object($storageCLient, $bucketName, $new_attach_id, $filepath);

    return $uploaded;
}

function sendMailRelay($mail_relay_endpoint, $mail_api_token, $email_subject, $email_msg, $from_name, $from_email, $to, $cc = "", $bcc = ""){
    $data                   = array();
    $data["email_token"]    = $mail_api_token;
    $data["to"]             = $to;
    $data["from_name"]      = $from_name;
    $data["from_email"]     = $from_email;
    $data["cc"]             = $cc;
    $data["bcc"]            = $bcc;
    $data["subject"]        = $email_subject;
    $data["body "]          = $email_msg;
    $method                 = "POST";
    
    $process            = curl_init($mail_relay_endpoint);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);

    if (!empty($data)) curl_setopt($process, CURLOPT_POSTFIELDS, $data);
    if (!empty($method)) curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);
    
    $errors = curl_error($process);
    $result = curl_exec($process);
    curl_close($process);

    return $result;
} 

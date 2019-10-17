<?php     
require_once "common.php";
require_once "inc/class.mail.php";

require 'vendor/autoload.php';

# Imports the Google Cloud client library
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Firestore\FirestoreClient;

$overallProjectId   ='som-rit-ourvoice';

# Firestore collection for walk data
$collection         = "ov_walks";

# Your Google Cloud Storage project ID and The name for the attachment buckets
$projectId          = '696489330177';
$bucketName         = 'ov_walk_files';

# manually copy auth.json file to server
$keyPath            = [PATH_TO_AUTH];

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// GET WALK ID , FROM THE PING
$uploaded_walk_id   = isset($_POST["uploaded_walk_id"]) ? $_POST["uploaded_walk_id"] : null;
// $proccesed_thumb_ids    = isset($_POST["proccesed_thumb_ids"]) ?  $_POST["proccesed_thumb_ids"] : null;                  

// $mail_relay_endpoint    = "https://redcap.stanford.edu/api/?type=module&prefix=email_relay&page=service&pid=13619";
// $mail_api_token         = "XemWorYpUv";
// $to                     = "irvins@stanford.edu";
// $email_subject          = "Test Subject";
// $email_msg              = "Test Body";
// $from_name              = " ME Mario";
// $from_email             = "irvins@stanford.edu";
// $cc                     = "banchoff@stanford.edu";
// $bcc                    = "irvins@stanford.edu, jmschultz@stanford.edu";
// $result = sendMailRelay($mail_relay_endpoint, $mail_api_token, $email_subject, $email_msg, $from_name, $from_email, $to);
// print_rr($result);
// exit;

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
        $backup_files       = scanBackUpFolder($backup_folder);

        // Intanstiate FireStore Client
        $firestore  = new FirestoreClient([
             'projectId'    => $overallProjectId
            ,'keyFilePath'  => $keyPath
        ]);

        # Instantiates a Storage client
        $storageCLient = new StorageClient([
            'keyFilePath'   => $keyPath,
            'projectId'     => $projectId
        ]);
        foreach($backup_files as $file){
            $path = $backup_folder . "/" . $file;
            if(strpos($file,".json") > 0){
                $walks_url  = cfg::$couch_url . "/" . cfg::$couch_users_db ;
                $payload    = file_get_contents($path);
                $response   = doCurl($walks_url, $payload, 'POST');

                // STORE WALK DATA INTO FIRESTORE FORMAT
                $old_walk_id    = str_replace(".json","",$file); 
                $fs_walk_id     = setWalkFireStore($old_walk_id, json_decode($payload,1), $firestore);
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
                $uploaded   = uploadCloudStorage($file ,$_id ,$storageCLient)
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

function setWalkFireStore($old_id, $details,$firestore){
    // CURRENT COUCH FORMAT of the Walk Id
    $walk_parts     = explode("_",$old_id);

    // FIRESTORE FORMAT walk_id
    $walk_id        = $walk_parts[0] ."_" . $walk_parts[1] . "_" . $walk_parts[3];

    // IF NO PHOTOS, THEN ITS NOT A COMPLETE WALK 
    if(!array_key_exists("photos", $details)){
        return false;
    }

    // GET COMPONENT PIECES TO START REFORMAT DATA MODEL FOR FIRESTORE
    $pid            = $walk_parts[0];
    $device         = array_key_exists("device", $details) ? $details["device"] : array() ; 
    $device["uid"]  = $walk_parts[1]; 
    $lang           = array_key_exists("lang", $details) ? $details["lang"] : null ;

    $survey         = array_key_exists("survey", $details) ? $details["survey"] : array();
    $txn            = array_key_exists("transcriptions", $details) ? $details["transcriptions"] : array();
    $photos         = $details["photos"];

    $new_photos     = array();
    foreach($photos as $photo){
        $temp                   = array();
        $temp["goodbad"]        = array_key_exists("goodbad", $photo)       ? $photo["goodbad"]         : null;
        $temp["name"]           = array_key_exists("name", $photo)          ? $photo["name"]            : null;
        $temp["rotate"]         = array_key_exists("rotate", $photo)        ? $photo["rotate"]          : null;
        $temp["text_comment"]   = array_key_exists("text_comment", $photo)  ? $photo["text_comment"]    : null;
        $temp["geotag"]         = array_key_exists("geotag", $photo)        ? $photo["geotag"]          : null;
        $audios                 = array_key_exists("audios", $photo)        ? $photo["audios"]          : array();
        
        $temp["audios"]         = array();
        foreach($audios as $audio_name){
            $temp["audios"][$audio_name] = array_key_exists($audio_name, $txn) ? $txn[$audio_name] : array() ;
        }

        $new_photos[] = $temp;
    }

    $geotags        = array_key_exists("geotags", $details) ? $details["geotags"] : array() ; 
    $culled_geos    = array();
    foreach($geotags as $geotag){
        if($geotag["accuracy"] <= 50){
            $culled_geos[] = array_intersect_key($geotag,$keep_these);
        }
    }

    try {
        $docRef = $firestore->collection($collection)->document($walk_id);
        $docRef->set([
             'project_id'   => $pid
            ,'lang'         => $lang
            ,'timestamp'    => $walk_parts[3]
            ,'device'       => $device
            ,'photos'       => $new_photos
            ,'survey'       => $survey
        ]);
        
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

function uploadCloudStorage($attach_id, $walk_id, $storageCLient){
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

// /**
//  * Upload a file.
//  *
//  * @param string $bucketName the name of your Google Cloud bucket.
//  * @param string $objectName the name of the object.
//  * @param string $source the path to the file to upload.
//  *
//  * @return Psr\Http\Message\StreamInterface
//  */
// function upload_object($storageClient, $bucketName, $objectName, $source) {
//     if($file = file_get_contents($source)){
//         $bucket     = $storageClient->bucket($bucketName);
//         $object     = $bucket->upload($file, [
//             'name' => $objectName
//         ]);

//         return $object;
//     }else{
//         return false;
//     }
// }
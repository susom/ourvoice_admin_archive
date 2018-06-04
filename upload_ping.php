<?php     
require_once "common.php";

function get_head(string $url, array $opts = []){
    // Store previous default context
    $prev = stream_context_get_options(stream_context_get_default());

    // Set new one with head and a small timeout
    stream_context_set_default(['http' => $opts + 
        [
            'method' => 'HEAD',
            'timeout' => 2,
        ]]);

    // Do the head request
    $req = @get_headers($url, true);
    if(!$req){
        return false;
    }

    // Make more sane response
    foreach($req as $h => $v){
        if(is_int($h)){
            $headers[$h]['Status'] = $v;
        }else{
            if(is_string($v)){
                $headers[0][$h] = $v;
            }else{
                foreach($v as $x => $y){
                    $headers[$x][$h] = $y;
                }
            }
        }
    }

    // Restore previous default context and return
    stream_context_set_default($prev);
    return $headers;
}

$uploaded_walk_id       = isset($_POST["uploaded_walk_id"]) ? $_POST["uploaded_walk_id"] : null;                         
// $proccesed_thumb_ids    = isset($_POST["proccesed_thumb_ids"]) ?  $_POST["proccesed_thumb_ids"] : null;                  

// GET PROJECT ID , FROM THE PING
// 
// GET PROJECT EMAIL , NEED TO GET THIS FROM disc_projects OR PASS IT IN
// 
// sleep(11) seconds, 
// 
// CHECK IF ATTACHMENT IDS ARE ALL EXISTING WITHIN disc_attachments AFTER SLEEP PERIOD
// 
// EITHER YES OR NO
// EMAIL TO PROJECT ADMIN EMAIL, CC ANN,IRVIN, JORDAN 
                                                                                                                   
if(!empty($uploaded_walk_id)){                                                                                           
    $post   = json_decode($_POST,1);                                                                                 
    $_id    = $uploaded_walk_id;                                                                                     
    $doc    = json_decode($_POST["doc"],1);    
    $email 	= isset($_POST["project_email"]) ? $_POST["project_email"] : false;                                                       
    $photos = $doc["photos"];

    sleep(1); //11 seconds same amount of time as it takes to get to 83% on phone

    $check_attach_urls 	= array();
    $couch_url = "http://".cfg::$couch_user.":".cfg::$couch_pw."@couchdb:5984";
    foreach($photos as $i => $photo){
		$ph_id 			= $_id . "_" . $photo["name"];
		$photo_attach 	= $couch_url . "/".$couch_attach_db."/" . $ph_id ;
		$check_attach_urls[$photo_attach] = get_head($photo_attach);
		if(isset($photo["audios"])){
			foreach($photo["audios"] as $filename){
				$aud_id			= $_id . "_" . $filename;
				$audio_attach 	= $couch_url . "/".$couch_attach_db."/" . $aud_id ;
				$check_attach_urls[$audio_attach] = get_head($audio_attach);
			}
		}
	}
    $meta = array("uploaded_walk_id" 	=> $_id  
    			 ,"email" 				=> $email                                                                       
                 ,"attachments" 		=> $check_attach_urls
             );   

    echo json_encode(array("uploaded_walk" => $meta)); 

    $failed_uploads = array();
    foreach($check_attach_urls as $attach_id => $head_result){
    	if(array_key_exists("error", $head_result)){
    		 $failed_uploads[] = $attach_id;
    	}
    }

    // the message
	$msg 		= "Hi Admin, there was a new walk uploaded to your project. ";
	$msg  		.= count($failed_uploads) ? "Please check the portal for the following attachments; " . implode(", ", $failed_uploads) . ".  If you find them missing on the portal, you can try to upload again from the app.  Please remember to be on a wifi connection and leave the app open through out the upload process." : "";
	$subject 	= count($failed_uploads) ? "New walk uploaded, possibly missing attachments" : "New walk uploaded!";
	// send email
	mail("irvins@stanford.edu", $subject, $msg);                                                              
} 

// if get head = succesful
// {"_id":"IRV_7B6D2189-3F1E-4290-91DB-E7DCBD0E42A0_4_1528047306210_photo_0.jpg","_rev":"15-2b55e7600c989813497f81a114eb23a7
// ","upload_try":15,"_attachments":{"photo_0.jpg":{"content_type":"image/jpeg","revpos":1,"digest":"md5-cK9299gcwakNPwrbYcu
// NfA==","length":1525030,"stub":true}}}

// if get head = fail
// {"error":"not_found","reason":"missing"}
<?php

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Storage\StorageClient;
use ImageKit\ImageKit;

class Datastore {
    private   $keyPath
            , $gcp_project_id
            , $walks_collection
            , $firestore_endpoint
            , $firestore_scope
            , $collection
            , $firestore;

    public function __construct() {
        // FIRESTORE details
        $this->keyPath 			    = cfg::$FireStorekeyPath;
        $this->gcp_project_id 	    = cfg::$gcp_project_id;

        $this->firestore_projects   = cfg::$firestore_projects;
        $this->firestore_meta       = cfg::$firestore_meta;
        $this->walks_collection 	= cfg::$firestore_collection;
        $this->gcp_bucketName       = cfg::$gcp_bucketName;

        $this->firestore_endpoint	= cfg::$firestore_endpoint;
        $this->firestore_scope 	    = cfg::$firestore_scope;
        $this->gapi_key             = cfg::$gmaps_key;

        $this->masterpw             = cfg::$master_pw;

        #instantiates FireStore client
        $this->firestore            = new FirestoreClient([
            'projectId'         => $this->gcp_project_id,
            'keyFilePath'       => $this->keyPath
        ]);

        $this->imageKit             = new ImageKit(
                                cfg::$imgkit_pub,
                                cfg::$imgkit_priv,
                                cfg::$imgkit_url
                            );
    }

    public function doCurl($url, $data = null, $method = "GET", $username = null, $password = null) {
        $process = curl_init($url);
        if($this->firestore){

        }else{
            if (empty($username)) $username = cfg::$couch_user;
            if (empty($password)) $password = cfg::$couch_pw;
            curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
        }
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
            "Content-type: application/json",
            "Accept: */*"
        ));

        if (!empty($data)) curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        if (!empty($method)) curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);

        $result = curl_exec($process);
        curl_close($process);

        return $result;
    }

    public function urlToJson($url){
        if($url){
            $temp = $this->doCurl($url);
            $temp = json_decode(stripslashes($temp),1);
            return $temp;
        }
    }

    public function push_data($url, $data){
        $response   = $this->doCurl($url, json_encode($data), 'PUT');
        return json_decode($response,1);
    }

    public function prepareAttachment($key,$rev,$parent_dir,$attach_url){
        $file_i         = str_replace($parent_dir."_","",$key);   
        $splitdot       = explode(".",$file_i);
        $c_type         = $splitdot[1];

        $couchurl       = $attach_url."/".$key."/".$file_i."?rev=".$rev;
        $filepath       = 'temp/'.$parent_dir.'/'.$key;
        $content_type   = strpos($key,"photo") ? 'image/jpeg' : $c_type;
        $response       = $this->uploadAttach($couchurl, $filepath, $content_type);
        return $response;
    }

    public function uploadAttach($couchurl, $filepath, $content_type){
        $data       = file_get_contents($filepath);
        $ch         = curl_init();

        $username   = cfg::$couch_user;
        $password   = cfg::$couch_pw;
        $options    = array(
            CURLOPT_URL             => $couchurl,
            CURLOPT_USERPWD         => $username . ":" . $password,
            CURLOPT_SSL_VERIFYPEER  => FALSE,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => 'PUT',
            CURLOPT_HTTPHEADER      => array (
                "Content-Type: ".$content_type,
            ),
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $data
        );
        curl_setopt_array($ch, $options);
        $info       = curl_getinfo($ch);
        // print_rr($info);
        $err        = curl_errno($ch);
        // print_rr($err);
        $response   = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function postData($url, $data){ //MUST INCLUDE Key attached to URL, 
        $data_string = json_encode($data); 
        $ch = curl_init($url);         
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
           'Content-Type: application/json',                                                                                
           'Content-Length: ' . strlen($data_string))                                                                       
        );    
        $resp = curl_exec($ch);
        $c = 0;
        curl_close($ch);
        $resp = json_decode($resp,1);
        return $resp;
    }

    public function updateDoc($url,$keyvalues){
        // TO PROTECT FROM DOC CONFLICTS (LITERALLY THE WORST POSSIBLE THInG) ,
        // WE FIRST GET A FRESH COPY OF THE DOC, ALTER IT, THEN SAVE IT RIGHT AWAY
        $response   = $this->doCurl($url);
        $payload    = json_decode($response,1);
        foreach($keyvalues as $k => $v){
            $payload[$k] = $v;
        }

        $response   = $this->doCurl($url, json_encode($payload), 'PUT');
        return json_decode($response,1);
    }

    public function getAllData(){
        $url            = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
        $response       = $this->doCurl($url);
        return json_decode($response,1);

        //get single
        $docRef = $db->collection('samples/php/cities')->document('SF');
        $snapshot = $docRef->snapshot();

        if ($snapshot->exists()) {
            printf('Document data:' . PHP_EOL);
            print_r($snapshot->data());
        } else {
            printf('Document %s does not exist!' . PHP_EOL, $snapshot->id());
        }

        //get multiple
        $citiesRef = $db->collection('samples/php/cities');
        $query = $citiesRef->where('capital', '=', true);
        $documents = $query->documents();
        foreach ($documents as $document) {
            if ($document->exists()) {
                printf('Document data for document %s:' . PHP_EOL, $document->id());
                print_r($document->data());
                printf(PHP_EOL);
            } else {
                printf('Document %s does not exist!' . PHP_EOL, $document->id());
            }
        }

        //get all
        $citiesRef = $db->collection('samples/php/cities');
        $documents = $citiesRef->documents();
        foreach ($documents as $document) {
            if ($document->exists()) {
                printf('Document data for document %s:' . PHP_EOL, $document->id());
                print_r($document->data());
                printf(PHP_EOL);
            } else {
                printf('Document %s does not exist!' . PHP_EOL, $document->id());
            }
        }
    }

    public function castTypeCleaner($val){
        if(is_array($val)){
            if(isset($val["stringValue"])){
                $val    = $val["stringValue"];
            }elseif(isset($val["integerValue"])){
                $val    = $val["integerValue"];
            }elseif(isset($val["arrayValue"])){
                //regular array
                $val    = $val["arrayValue"]["values"];
                $temp   = array();
                foreach($val as $v){
                    array_push($temp, castTypeCleaner($v));
                }
                $val = $temp;
            }elseif(isset($val["mapValue"])) {
                //object
                $val = $val["mapValue"]["fields"];
                $temp = array();
                foreach ($val as $k => $v) {
                    $temp[$k] = castTypeCleaner($v);
                }

                $val = $temp;
            }
        }

        return $val;
    }

    public function loginProject($project_id, $project_pass){
        $result     = array();
        $ov_meta    = array();

        if($this->firestore && isset($project_id) && isset($project_pass)){
            $docRef     = $this->firestore->collection($this->firestore_projects)->document($project_id);
            $snapshot   = $docRef->snapshot();
            if ($snapshot->exists()) {
                $data = $snapshot->data();
                if(array_key_exists("project_pass",$data) && isset($data["project_pass"])) {
                    $fs_pw = $data["project_pass"];
                    $su_pw = $data["summ_pass"];

                    if ($fs_pw == $project_pass || $su_pw == $project_pass || $project_pass == cfg::$master_pw ||$project_pass == "annban") {
                        foreach($data as $key => $val){
                            $result[$key] = $val;
                        }
                    }

                    //GET ov_meta data
                    $docRef     = $this->firestore->collection($this->firestore_meta)->document("app_data");
                    $snapshot   = $docRef->snapshot();
                    $data       = $snapshot->data();
                    foreach($data as $key => $val){
                        $ov_meta[$key] = $val;
                    }
                }
            }
        }
        return array("active_project" => $result , "ov_meta" => $ov_meta);
    }

    public function getAppVersion(){
        //GET ov_meta data
        $docRef     = $this->firestore->collection($this->firestore_meta)->document("app_data");
        $snapshot   = $docRef->snapshot();
        $data       = $snapshot->data();

        return $data;
    }

    public function getProjectsMeta(){
        $ap = null;

        //if firestore is working use that
        if($this->firestore){
            $all_proj   = $this->firestore->collection($this->firestore_projects);
            $documents  = $all_proj->documents();
            $temp       = array();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    array_push($temp, $document->data());
                }
            }

            if(!empty($temp)){
                $ap = $temp;
            }
        }

        return $ap;
    }

    public function getActiveProjectsMeta(){
        $ap = null;

        //if firestore is working use that
        if($this->firestore){
            $all_proj   = $this->firestore->collection($this->firestore_projects);
            $documents  = $all_proj->documents();
            $temp       = array();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();

                    if(array_key_exists("archived", $data) && $data["archived"]){
                        //continue;
                    }

                    if(!empty($data["expire_date"]) && strtotime($data["expire_date"]) < time()){
                        //continue;
                    }
                    array_push($temp, $document->data());
                }
            }

            if(!empty($temp)){
                $ap = $temp;
            }
        }

        return $ap;
    }

    public function getProject($project_code){
        $result = null;

        if($this->firestore){
            $project    = $this->firestore->collection($this->firestore_projects)->document($project_code);
            $snapshot   = $project->snapshot();
            if ($snapshot->exists()) {
                $result = $project;
            }
        }

        return $result;
    }

    public function getWalkData($doc_id, $raw=false){
        $result = array();

        if($this->firestore){
            $ov_projects    = $this->firestore->collection($this->walks_collection)->document($doc_id);
            $snapshot       = $ov_projects->snapshot();

            if ($snapshot->exists()) {
                if($raw){
                    return $ov_projects;
                }

                $data           = $snapshot->data();
                $walk_tz        = null;
                $lat_for_tz     = null;
                $lng_for_tz     = null;

                $text_count     = 0;
                $photo_count    = 0;
                $audio_count    = 0;

                $complete_upload    = $data["completed_upload"] ?? null;
                $partial_files      = array();

                $has_map        = false;
                $geocoll        = $ov_projects->collection("geotags")->documents();
                if($geocoll){
                    foreach($geocoll as $geotag){
                        $data_array = $geotag->data();
                        if(!empty($data_array)){
                            $has_map    = true;
                            $lat_for_tz = $data_array["lat"] ?? null;
                            $lng_for_tz = $data_array["lng"] ?? null;
                        }
                        break;
                    }
                }

                $photo_names    = array();
                foreach($data["photos"] as $photo){
                    if(array_key_exists("_deleted",$photo)){
                        continue;
                    }

                    if(!$lat_for_tz && !$lng_for_tz && isset($photo["geotag"])) {
                        $lat_for_tz = $photo["geotag"]["lat"] ?? null;
                        $lng_for_tz = $photo["geotag"]["lng"] ?? null;
                    }

                    $photo_count++;
                    array_push($photo_names, $photo["name"]);

                    if(isset($photo["text_comment"])){
                        $text_count++;
                    }

                    if(isset($photo["audios"])){
                        if(count($photo["audios"])){
                            $audio_count += count($photo["audios"]);

                            if(!$complete_upload){
                                foreach($photo["audios"] as $audio_name => $txn){
                                    $filename_mp3   = str_replace(".wav", ".mp3", $audio_name);
                                    $filename_mp3   = str_replace(".amr", ".mp3", $audio_name);
                                    $attach_url 	= $this->getStorageFile(cfg::$gcp_bucketName, $doc_id, $filename_mp3);

                                    $check          = get_head($attach_url);
                                    if(!empty($check) && isset($check[0])){
                                        $current = current($check);
                                        if(strpos($current["Status"], "200 OK") < 0){
                                            array_push($partial_files, $attach_url);
                                        }
                                    }
                                }
                            }
                        }
                    }
                };

                if(!$complete_upload && !count($partial_files)){
                    $complete_upload = true;
                    $ov_projects->update([
                        ['path' => 'completed_upload', 'value' => true]
                    ]);
                }

                if($lat_for_tz && $lng_for_tz){
                    $gkey       = $this->gapi_key;
                    $ts         = round($data["timestamp"]/1000);
                    $url        ="https://maps.googleapis.com/maps/api/timezone/json?location=$lat_for_tz,$lng_for_tz&timestamp=$ts&key=$gkey";
                    $g_result   = $this->doCurl($url);
                    $g_arr      = json_decode($g_result,1);
                    if(isset($g_arr["timeZoneId"])){
                        $walk_tz = $g_arr["timeZoneId"];
                    }
                }
                if(!$walk_tz) {
                    $walk_tz = "America/Los_Angeles";
                }

                $dt = new DateTime("now", new DateTimeZone($walk_tz)); //first argument "must" be a string
                $dt->setTimestamp(round($data["timestamp"]/1000)); //adjust the object to correct timestamp
                $walk_date = $dt->format('Y-m-d');
                
                $result = array(
                     "date"             => $walk_date
                    ,"id"               => $doc_id
                    ,"photos"           => $photo_count
                    ,"photo_names"      => $photo_names
                    ,"maps"             => $has_map ? "Y" : "N"
                    ,"data_processed"   => $data["data_processed"] ?? null
                    ,"device"           => $data["device"]
                    ,"audios"           => $audio_count
                    ,"texts"            => $text_count
                    ,"timezone"         => $walk_tz
                    ,"completed_upload" => $complete_upload
                    ,"partial_files"   => $partial_files
                );
            } else {
                //Walk couldnt be found $snapshot->id();
            }
        }
        return $result;
    }

    public function getProjectSummaryData($project_code){
        $result = array();

        if($this->firestore){
            $ov_projects    = $this->firestore->collection($this->walks_collection);
            $query          = $ov_projects
                ->where('project_id', '=', $project_code)
                ->orderBy("timestamp", "DESC");

            $dt             = new DateTime("now"); //first argument "must" be a string
            $snapshot       = $query->documents();
            foreach ($snapshot as $document) {
                $data           = $document->data();
                if(array_key_exists("_deleted",$data) || !array_key_exists("photos",$data) || !count($data["photos"])){
                    continue;
                }

                $doc_id         = $document->id();
                $walk_tz        = null;
                $lat_for_tz     = null;
                $lng_for_tz     = null;

                $text_count     = 0;
                $photo_count    = 0;
                $audio_count    = 0;

                //Once files are checked one time, flag it so wont have to do the check again
                $complete_upload    = $data["completed_upload"] ?? null;
                $partial_files      = array();


                $has_map        = false;
                $geocoll        = $ov_projects->document($doc_id)->collection("geotags")->documents();
                if($geocoll){
                    foreach($geocoll as $geotag){
                        $data_array = $geotag->data();
                        if(!empty($data_array)){
                            $has_map    = true;

                            $lat_for_tz = $data_array["lat"] ?? null;
                            $lng_for_tz = $data_array["lng"] ?? null;
                        }
                        break;
                    }
                }

                foreach($data["photos"] as $photo){
                    if(array_key_exists("_deleted",$photo)){
                        continue;
                    }

                    if(!$lat_for_tz && !$lng_for_tz && isset($photo["geotag"])) {
                        $lat_for_tz = $photo["geotag"]["lat"] ?? null;
                        $lng_for_tz = $photo["geotag"]["lng"] ?? null;
                    }

                    $photo_count++;

                    if(isset($photo["text_comment"])){
                        $text_count++;
                    }

                    // if(!$complete_upload){
                    //     $photo_url  = $this->getStorageFile(cfg::$gcp_bucketName, $doc_id, $photo["name"]);
                    //     $check      = get_head($photo_url);
                    //     if(!empty($check) && isset($check[0])){
                    //         $current = current($check);
                    //         if(strpos($current["Status"], "200 OK") < 0){
                    //             array_push($partial_files, $photo_url);
                    //         }
                    //     }
                    // }

                    if(isset($photo["audios"])){
                        if(count($photo["audios"])){
                            $audio_count += count($photo["audios"]);

                            if(!$complete_upload){
                                foreach($photo["audios"] as $audio_name => $txn){
                                    $filename_mp3   = str_replace(".wav", ".mp3", $audio_name);
                                    $filename_mp3   = str_replace(".amr", ".mp3", $audio_name);
                                    $attach_url 	= $this->getStorageFile(cfg::$gcp_bucketName, $doc_id, $filename_mp3);

                                    // $check          = get_head($attach_url);
                                    // if(!empty($check) && isset($check[0])){
                                    //     $current = current($check);
                                    //     if(strpos($current["Status"], "200 OK") < 0){
                                    //         array_push($partial_files, $attach_url);
                                    //     }
                                    // }
                                }
                            }
                        }
                    }
                };

                // if(!$complete_upload && !count($partial_files)){
                //     $complete_upload    = true;
                //     $payload            = $ov_projects->document($doc_id);
                //     $payload->update([
                //         ['path' => 'completed_upload', 'value' => true]
                //     ]);
                // }

                if($lat_for_tz && $lng_for_tz){
                    $gkey       = $this->gapi_key;
                    $ts         = round($data["timestamp"]/1000);
                    $url        ="https://maps.googleapis.com/maps/api/timezone/json?location=$lat_for_tz,$lng_for_tz&timestamp=$ts&key=$gkey";
                    $g_result   = $this->doCurl($url);
                    $g_arr      = json_decode($g_result,1);
                    if(isset($g_arr["timeZoneId"])){
                        $walk_tz = $g_arr["timeZoneId"];
                    }
                }
                if(!$walk_tz){
                    $walk_tz = "America/Los_Angeles";
                }

//                , new DateTimeZone($walk_tz) do this on display not before querying
                $dt->setTimestamp(round($data["timestamp"]/1000)); //adjust the object to correct timestamp
                $walk_date = $dt->format('Y-m-d');

                $temp = array(
                     "date"             => $walk_date
                    ,"id"               => $doc_id
                    ,"photos"           => $photo_count
                    // ,"maps"             => $has_map ? "Y" : "N"
                    ,"data_processed"   => $data["data_processed"] ?? null
                    ,"device"           => $data["device"]
                    ,"audios"           => $audio_count
                    ,"texts"            => $text_count
                    ,"timezone"         => $walk_tz
                    // ,"completed_upload" => $complete_upload
                    // ,"partial_files"    => $partial_files
                );
                array_push($result, $temp);
            }
        }
        return $result;
    }

    public function getProjectDateBuckets($project_code){
        $result = array();

        if($this->firestore){
            $ov_projects    = $this->firestore->collection($this->walks_collection);
            $query          = $ov_projects->where('project_id', '=', $project_code);

            $just_ts        = $query->select(["project_id", "timestamp", "device"]);
            $ts_snap        = $just_ts->documents();

            $dt             = new DateTime("now"); //first argument "must" be a string
            $date_buckets   = array();
            foreach($ts_snap as $doc){
                $data       = $doc->data();

                $doc_id     = $doc->id(); // Get the document ID

                $dt->setTimestamp(round($data["timestamp"]/1000)); //adjust the object to correct timestamp
                $walk_date  = $dt->format('Y-m-d');
                if(!array_key_exists($walk_date, $date_buckets)){
                    $date_buckets[$walk_date] = array();
                }
                array_push($date_buckets[$walk_date], $doc_id);
            }

            krsort($date_buckets);
            $result = $date_buckets;
        }

        return $result;
    }

    public function getWalks($ids=array()){
        $result = array();

        if($this->firestore && !empty($ids)){
            foreach($ids as $_id){
                $walk_fs = $this->getWalkData($_id, true);
                if( !empty($walk_fs) ){
                    array_push($result, $walk_fs);
                }
            }
        }

        return $result;
    }

    public function getWalksByIds($ids=array()){
        $result     = array();

        if($this->firestore) {
            $ov_walks = $this->firestore->collection($this->walks_collection);
            foreach($ids as $doc_id){
                $document   = $ov_walks->document($doc_id);
                $snapshot   = $document->snapshot();

                if ($snapshot->exists()) {
                    $walk_data = $snapshot->data();

                    if (array_key_exists("_deleted", $walk_data)) {
                        continue;
                    }

                    $geocoll = $document->collection("geotags")->documents();
                    $geotags = array();
                    if ($geocoll) {
                        foreach ($geocoll as $fakeidx => $geotag) {
                            $data_array = $geotag->data();
                            $geo_data = isset($data_array[0]) ? current($data_array) : $data_array;
                            $geotags[$geotag->id()] = $geo_data;
                        }
                    }
                    ksort($geotags);

                    $walk_data["_id"] = $doc_id;
                    $walk_data["geotags"] = $geotags;
                    array_push($result, $walk_data);
                }
            }
        }

        return $result;
    }

    public function filter_by_projid($project_code, $getdate){ //keys array is the # integer of the PrID
        $result = array();

        if($this->firestore){
            $midnight       = strtotime($getdate) * 1000;
            $midnight_plus  = $midnight + 86400000;
            $ov_walks       = $this->firestore->collection($this->walks_collection);

            //TODO NEED AWAY TO QUERY BOTH INTERGER AND STRING FUCKING TIMESTAMPS
            $query          = $ov_walks->where('project_id', '=', $project_code)
                ->where('timestamp', '>=', $midnight)
                ->where('timestamp', '<', $midnight_plus);
            $snapshot       = $query->documents();

            foreach ($snapshot as $document) {
                $_id        = $document->id();
                $walk_data  = $document->data();

                if(array_key_exists("_deleted",$walk_data)){
                    continue;
                }
                $geocoll    = $ov_walks->document($_id)->collection("geotags")->documents();
                $geotags    = array();
                if($geocoll){
                    foreach($geocoll as $fakeidx => $geotag){
                        $data_array = $geotag->data();
                        $geo_data   = isset($data_array[0]) ? current($data_array) : $data_array;
                        $geotags[$geotag->id()] = $geo_data;
                    }
                }
                ksort($geotags);

                $walk_data["_id"]       = $_id;
                $walk_data["geotags"]   = $geotags;
//                array_push($result, $walk_data);
            }

            //TODO THIS IS FUCKED UP, BOTH BLOCKS EXACTLY THE SAME EXCEPT FOR THE STRVAL()
            //FUCK THIS SHIT FUCK THIS SHIT FUCK IT! SOEM TIMESTAMPS ARE INT SOME ARE STRINGS WHAT THEFUCK MAN
            $midnight       = strval($midnight);
            $midnight_plus  = strval($midnight_plus);
            $query          = $ov_walks->where('project_id', '=', $project_code)
                ->where('timestamp', '>=', $midnight)
                ->where('timestamp', '<', $midnight_plus);
            $snapshot      = $query->documents();

            foreach ($snapshot as $document) {
                $_id        = $document->id();
                $walk_data  = $document->data();

                if(array_key_exists("_deleted",$walk_data)){
                    continue;
                }
                $geocoll    = $ov_walks->document($_id)->collection("geotags")->documents();
                $geotags    = array();
                if($geocoll){
                    foreach($geocoll as $fakeidx => $geotag){
                        $data_array = $geotag->data();
                        $geo_data   = isset($data_array[0]) ? current($data_array) : $data_array;
                        $geotags[$geotag->id()] = $geo_data;
                    }
                }
                ksort($geotags);

                $walk_data["_id"]       = $_id;
                $walk_data["geotags"]   = $geotags;
                array_push($result, $walk_data);
            }
        }

        return $result;
    }

    public function getPhotoData($doc_id, $file_id){
        $temp       = explode("_", $doc_id);
        $pcode      = $temp[0];
        $uuid       = $temp[1];
        $walk_ts    = $temp[2];

        $result = array();
        if($this->firestore){
            $ov_projects    = $this->firestore->collection($this->walks_collection)->document($doc_id);
            $snapshot       = $ov_projects->snapshot();

            if ($snapshot->exists()) {
                $data       = $snapshot->data();
                $walk_tz    = null;
                $photo      = null;

                $lat        = null;
                $lng        = null;

                foreach($data["photos"] as $i => $photo){
                    if($photo["name"] !== $file_id){
                        continue;
                    }

                    //get timezone based on geo data from g api.
                    if(!$walk_tz) {
                        $walk_tz = "America/Los_Angeles";
                        if (isset($photo["geotag"])) {
                            $lat = !empty($photo["geotag"]["lat"]) ? $photo["geotag"]["lat"] : null;
                            $lng = !empty($photo["geotag"]["lng"]) ? $photo["geotag"]["lng"] : null;
                        }

                        if($lat && $lng){
                            $gkey       = $this->gapi_key;
                            $ts         = round($data["timestamp"]/1000);
                            $url        ="https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$lng&timestamp=$ts&key=$gkey";
                            $g_result   = $this->doCurl($url);
                            $g_arr      = json_decode($g_result,1);
                            if(isset($g_arr["timeZoneId"])){
                                $walk_tz = $g_arr["timeZoneId"];
                            }
                        }
                    }
                    $photo["i"] = $i;
                    break;
                };

                if($photo){
                    $photo["prev"] = isset( $data["photos"][$photo["i"]-1] ) ? $photo["i"]-1 : null;
                    $photo["next"] = isset( $data["photos"][$photo["i"]+1] ) ? $photo["i"]+1 : null;
                }

                $geocoll    = $ov_projects->collection("geotags")->documents();
                $geotags    = array();
                if($geocoll){
                    foreach($geocoll as $fakeidx => $geotag){
                        $data_array = $geotag->data();
                        $geo_data   = isset($data_array[0]) ? current($data_array) : $data_array;
                        $geotags[$geotag->id()] = $geo_data;
                    }
                }
                ksort($geotags);

                $dt         = new DateTime("now", new DateTimeZone($walk_tz)); //first argument "must" be a string
                $dt->setTimestamp(round($data["timestamp"]/1000)); //adjust the object to correct timestamp
                $walk_date  = $dt->format('Y-m-d H:i');
                

                $result = array(
                     "date"             => $walk_date
                    ,"id"               => $doc_id
                    ,"walk_ts"          => $walk_ts
                    ,"device"           => $data["device"]
                    ,"lang"             => $data["lang"]
                    ,"project_id"       => $pcode
                    ,"photo"            => $photo
                    ,"bounding_geos"    => $geotags
                );
            } else {
                //Walk couldnt be found $snapshot->id();
            }
        }
        return $result;
    }

    public function saveWalkData($_id, $data){
        $walk_url   = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
        $response   = $this->doCurl($walk_url, json_encode($data), 'PUT');
        return json_decode($response,1);
    }

    public function getWalkIdDataGeos($walk_id){
        //RETURNS JSON ENCODED BLOCK OF FILTERED PHOTO INFO, AND PHOTO GEOs
        // MOOD and TAG FILTERS ARE MIXED TOGETHER, SO SEPERATE THEM OUT
        $response       = $this->getWalkSummaryData($walk_id);
        $photo_geos     = array();
        $code_block     = array();

        //WHAT THE FUCK IS THIS SHIT, NEED TO LOOP THROUGH 3 times? 
        $sort_temp      = array();
        if(!empty($response)){
            $_id    = $walk_id;
            $doc    = $response;
            $device = $doc["device"];
            $photos = $doc["photos"];
            foreach($photos as $photo){
                if(array_key_exists("deleted", $photo)){
                    continue;
                }

                // GATHER EVERY GEO TAG FOR EVERY PHOTO IN THIS WALK, AT LEAST THIS HAS NO ORDER HALLELUJAH
                if(!empty($photo["geotag"])){
                    $filename   = $photo["name"];
                    $rotate     = !empty($photo["rotate"]) ? $photo["rotate"] : 0;
                    $transform  = array("transform" => "print_view", "rotate" => $rotate);



                    $file_uri   = $this->getStorageFile($this->gcp_bucketName, $_id , $filename, $transform);
                    $photo["geotag"]["photo_src"]   = $file_uri;
                    $photo["geotag"]["goodbad"]     = $photo["goodbad"];
                    $photo["geotag"]["photo_id"]    = $_id. "_" . $filename;
                    $photo["geotag"]["platform"]    = $device["platform"];
                    array_push($photo_geos, $photo["geotag"]);
                }

                // Massage a block for each photo in the project
                $code_block = array_merge($code_block, printPhotos($photo,$_id,null));
            }
        }

        // IF ASKING FOR MULTIPLE TAGS COULD HAVE REPEATS FOR MULTI TAGGED PHOTOS
        $code_block = array_unique($code_block,SORT_REGULAR);
        $data       = array("photo_geos" => $photo_geos, "code_block" => $code_block);
        return $data;
    }

    public function getFilteredDataGeos($pcode, $pfilters, $all_walks=false){
        //RETURNS JSON ENCODED BLOCK OF FILTERED PHOTO INFO, AND PHOTO GEOs
        // MOOD and TAG FILTERS ARE MIXED TOGETHER, SO SEPERATE THEM OUT

        $goodbad_filter = array();
        $good           = array_search("good"   ,$pfilters);
        $bad            = array_search("bad"    ,$pfilters);
        $neutral        = array_search("neutral",$pfilters);
        $unrated        = array_search("un-rated", $pfilters);
        $untagged       = array_search("un-tagged", $pfilters);
        if($good || is_int($good)){
            array_push($goodbad_filter,2);
            unset($pfilters[$good]);
        }
        if($bad || is_int($bad)){
            array_push($goodbad_filter,1);
            unset($pfilters[$bad]);
        }
        if($neutral || is_int($neutral)){
            array_push($goodbad_filter,3);
            unset($pfilters[$neutral]);
        }
        if($unrated || is_int($unrated)){
            array_push($goodbad_filter,0);
            unset($pfilters[$unrated]);
        }
        $pfilters       = array_values($pfilters);
        $response       = $this->loadAllProjectThumbs($pcode, $pfilters, $goodbad_filter);

        $photo_geos     = array();
        $code_block     = array();

        //WHAT THE FUCK IS THIS SHIT, NEED TO LOOP THROUGH 3 times?
        $sort_temp      = array();
        foreach($response as $doc){
            $_id        = $doc["id"];
            $photo_i    = $doc["photo_i"];

            //STUFF IT INTO HOLDING ARRAY BY WALK_ID
            if(!array_key_exists($_id, $sort_temp)){
                $sort_temp[$_id] = array();
            }

            $sort_temp[$_id][$photo_i] = $doc["photo"];
        }

        //BETER REVERSE SORT JEEZ
        uksort($sort_temp, function($a, $b) {
            // Extract the timestamps from the keys
            $timestampA = substr($a, strrpos($a, "_") + 1);
            $timestampB = substr($b, strrpos($b, "_") + 1);

            // Compare the timestamps (note the order of $b and $a for descending sort)
            return $timestampB <=> $timestampA;
        });

        //SECOND LOOP!  + BONUS NESTED LOOP BS,   BETTER WAY TO DO THIS???  FUCK IT.
        foreach($sort_temp as $_id => $photos){
            foreach($photos as $photo){
                // GATHER EVERY GEO TAG FOR EVERY PHOTO IN THIS WALK, AT LEAST THIS HAS NO ORDER HALLELUJAH
                $ph_i = null;
                if(!empty($photo["geotag"])){
                    $filename   = $photo["name"];
                    $ph_id      = $_id . "_" .$filename;;
                    $ph_i       = $photo["i"];
                    $rotate     = !empty($photo["rotate"]) ? $photo["rotate"] : 0;
                    $transform  = array("transform" => "print_view", "rotate" => $rotate);
                    if($all_walks){
                        $transform["transform"] = "thumbnail";
                    }
                    $file_uri   = $this->getStorageFile($this->gcp_bucketName, $_id, $filename, $transform);
                    $photo_uri  = $file_uri;
                    $detail_url = "photo.php?_id=".$_id."&_file=$filename";

                    $photo["geotag"]["photo_src"]   = $photo_uri;
                    $photo["geotag"]["goodbad"]     = $photo["goodbad"];
                    $photo["geotag"]["photo_id"]    = $ph_id;
                    $photo["geotag"]["platform"]    = $photo["platform"];
                    array_push($photo_geos, $photo["geotag"]);
                }

                // Massage a block for each photo in the project
                $code_block = array_merge($code_block, printPhotos($photo,$_id,$ph_i));
            }
        }

        // IF ASKING FOR MULTIPLE TAGS COULD HAVE REPEATS FOR MULTI TAGGED PHOTOS
        $code_block = array_unique($code_block,SORT_REGULAR);
        $data       = array("photo_geos" => $photo_geos, "code_block" => $code_block);
        return $data;
    }

    public function filterProjectPhotos($project_code, $tags=array(), $goodbad=array()){
        $result = array();

        if($this->firestore){
            $ov_projects    = $this->firestore->collection($this->walks_collection);
            $query          = $ov_projects->where('project_id', '=', $project_code);
            $snapshot       = $query->documents();
            $walk_tz        = null;

            foreach ($snapshot as $document) {
                $data           = $document->data();
                if(array_key_exists("_deleted",$data)){
                    continue;
                }

                $doc_id         = $document->id();
                foreach($data["photos"] as $photo_i => $photo){
                    if(array_key_exists("_deleted",$photo)){
                        continue;
                    }

                    //FILTER by TAGS and GOOD BAD or UN-Tagged
                    if(in_array("no_tags", $tags) && array_key_exists("tags", $photo) ){
                        continue;
                    }elseif(!empty($tags) && !in_array("no_tags", $tags) && (!isset($photo["tags"]) || empty(array_intersect($tags,$photo["tags"]))) ){
                        continue;
                    }elseif(!empty($goodbad) && (empty($photo["goodbad"]) || !in_array($photo["goodbad"], $goodbad) ) ){
                        continue;
                    }

                    if(!$walk_tz) {
                        if (isset($photo["geotag"])) {
                            $lat = isset($photo["geotag"]["lat"]) ? $photo["geotag"]["lat"] : null;
                            $lng = isset($photo["geotag"]["lng"]) ? $photo["geotag"]["lng"] : null;
                        }else{
                            $walk_tz = "America/Los_Angeles";
                        }

                        if($lat && $lng){
                            $gkey       = $this->gapi_key;
                            $ts         = round($data["timestamp"]/1000);
                            $url        ="https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$lng&timestamp=$ts&key=$gkey";
                            $g_result   = $this->doCurl($url);
                            $g_arr      = json_decode($g_result,1);
                            if(isset($g_arr["timeZoneId"])){
                                $walk_tz = $g_arr["timeZoneId"];
                            }
                        }
                    }

                    $photo["timezone"]  = $walk_tz;
                    $photo["i"]         = $photo_i;
                    $photo["platform"]  = $data["device"]["platform"];

                    $temp = array(
                         "id"               => $doc_id
                        ,"photo_i"          => $photo_i
                        ,"photo"            => $photo
                    );

                    array_push($result, $temp);
                };
            }
        }

        return $result;
    }

    public function loadAllProjectThumbs($project_code, $tags=array(), $goodbad=array()){
        if(empty($tags)){
            if(!empty($goodbad)){
                return $this->filterProjectPhotos($project_code, array() , $goodbad);
            }else{
                // NO FILTERS GET ALL PHOTO DATA FOR A PROJECT
                return $this->filterProjectPhotos($project_code);
            }
        }else{
            if(in_array("un-tagged", $tags)){
                // REGULAR TAGS
                return $this->filterProjectPhotos($project_code, array("no_tags"));
            }else{
                // REGULAR TAGS
                return $this->filterProjectPhotos($project_code, $tags);
            }
        }
    }

    public function getStorageFile($google_bucket, $id_string , $file_name, $image_transform=array()){
        $temp       = explode("_", $id_string);
        $pcode      = $temp[0];
        $uuid       = $temp[1];
        $walk_ts    = $temp[2];

        //once fix pixelation, need to "purge cache"
        /*
         $this->imageKit->purgeCacheApi(array(
                                            "url" => "https://ik.imagekit.io/your_imagekit_id/default-image.jpg"
                                        ));
        */

        if(!empty($image_transform)){
            //for image kit CDN photos only
            $file_uri   = "/$pcode/$uuid/$walk_ts/$file_name";
            $transform  = array();
            $transform["width"] = '1.0';
            $transform["c"]     = 'maintain_ratio';
            //            $transform["ar"]    = '4-3';


            switch($image_transform["transform"]){
                case "thumbnail":
                    $transform["width"]     = '140';
                    $transform["r"]         = '10';
                    break;

                case "print_view":
                    $transform["width"]     = '570';
                    $transform["q"]         = '50';
                break;

                case "photo_detail":
                    $transform["width"]     = '670';
                break;

                case "coverflow":
                    $transform["width"] = '140';
                break;

                case "custom_rasi":
                    $transform["width"] = '365';
                break;
            }

            if(isset($image_transform["rotate"])){
                switch($image_transform["rotate"]){
                    case 1:
                        $transform["rotate"] = '90';
                        break;

                    case 2:
                        $transform["rotate"] = '180';
                        break;

                    case 3:
                        $transform["rotate"] = '270';
                        break;

                    default:
                        $transform["rotate"] = '0';
                        break;
                }
            }

            $imageURL   = $this->imageKit->url(
                [
                    'path' => $file_uri,
                    'transformation' => [
                        $transform
                    ]
                ]
            );
            return $imageURL;
        }else{
            //for audio/mp3/rawphotos
            $file_uri   = "https://storage.googleapis.com/$google_bucket/$pcode/$uuid/$walk_ts/$file_name";
            return $file_uri;
        }
    }

    public function getWalkSummaryData($walk_id,  $view="walk_id", $dd="summary"){
        $result = array();
        if($this->firestore){
            $project       = $this->firestore->collection($this->walks_collection)->document($walk_id);
            $snapshot      = $project->snapshot();
            $result        = $snapshot->data();
        }

        return $result;
    }

    public function checkAttachmentsExist($file_url){
        $check = get_head($file_url);
        if(!empty($check) && isset($check[0])){
            $current = current($check);
            if(strpos($current["Status"], "200 OK") > -1){
                return true;
            }
        }
        return false;
    }

    public function getRecentWalkActivity($days=30){
        $result = array();

        if($this->firestore){
            $nowtime        = time()*1000;
            $nowtime_minus  = $nowtime - ($days*86400000);


            //TODO HERE TOO? FUCK THIS SHIT
            $ov_walks       = $this->firestore->collection($this->walks_collection);
            $query          = $ov_walks
                ->where('timestamp', '>', $nowtime_minus)
                ->where('timestamp', '<=', $nowtime);

            $snapshot       = $query->documents();
            foreach ($snapshot as $document) {
                $_id        = $document->id();
                $walk_data  = $document->data();

                $ts         = $walk_data["timestamp"];
                $proj_id    = $walk_data["project_id"];

                if(array_key_exists("_deleted",$walk_data)){
                    continue;
                }

                if(!array_key_exists($proj_id, $result)){
                    $result[$proj_id]   = $ts;
                }

                if($result[$proj_id] < $ts){
                    $result[$proj_id] = $ts;
                }
            }

            //TODO THIS IS SOME FUCKING BULLSHIT, BUT I DONT HAVE ONE FUCKING DAY OF TIME WITHOUT BEING PESTERED WITH URGENT DEMANDS TO ADDRESS IT SO FUCK IT !
            $nowtime        = strval($nowtime);
            $nowtime_minus  = strval($nowtime_minus);
            $query          = $ov_walks
                ->where('timestamp', '>', $nowtime_minus)
                ->where('timestamp', '<=', $nowtime);


            $snapshot       = $query->documents();
            foreach ($snapshot as $document) {
                $_id        = $document->id();
                $walk_data  = $document->data();

                $ts         = $walk_data["timestamp"];
                $proj_id    = $walk_data["project_id"];

                if(array_key_exists("_deleted",$walk_data)){
                    continue;
                }

                if(!array_key_exists($proj_id, $result)){
                    $result[$proj_id]   = $ts;
                }

                if($result[$proj_id] < $ts){
                    $result[$proj_id] = $ts;
                }
            }

            arsort($result);
        }

        return $result;
    }

    public function getFullProjectName($project_id){
        $result = null;

        if($this->firestore){
            $ov_projects    = $this->firestore->collection($this->firestore_projects);
            $project        = $ov_projects->document($project_id);
            $snap           = $project->snapshot();

            $data           = $snap->data();
            if($data){
                $result     = $data["name"];
            }else{
                $result     = $project_id;
            }
        }

        return $result;
    }

    public function putProject($project, $updateflag=false){
        $result     = null;

        if($this->firestore){
            $temp   = $project;
            if(!array_key_exists("project_id", $project)){
                return $result;
            }

            $code           = $project["project_id"];
            $temp["code"]   = $code;
            if(!array_key_exists("project_name", $project)){
                $temp["name"] = $code;
            }else{
                $temp["name"] = $project["project_name"];
            }


            if(!empty($project["app_lang"])){
                $temp["languages"]  = $project["app_lang"];
            }else{
                $temp["languages"]  = array(array("lang"=>"en", "language"=>"English"));
            }

            if(!array_key_exists("project_email", $project)){
                $temp["project_email"] = "banchoff@stanford.edu";
            }
            if(!array_key_exists("audio_comments", $temp)){
                $temp["audio_comments"] = "0";
            }
            if(!array_key_exists("text_comments", $temp)){
                $temp["text_comments"] = "1";
            }
            if(!array_key_exists("show_project_tags", $temp)){
                $temp["show_project_tags"] = "1";
            }
            if(!array_key_exists("custom_takephoto_text", $temp)){
                $temp["custom_takephoto_text"] = "";
            }
            if(!array_key_exists("thumbs", $temp)){
                $temp["thumbs"] = "0";
            }
            if(!array_key_exists("expire_date", $temp)){
                $temp["expire_date"] = "";
            }

            unset($temp["project_id"]);
            unset($temp["project_name"]);
            unset($temp["app_lang"]);

            try {
                // CREATE THE PARENT DOC
                $ov_projects    = $this->firestore->collection($this->firestore_projects);
                $tempDoc        = $ov_projects->document($code);

                if($updateflag){
                    $update_arr = array();
                    foreach($temp as $path => $value){
                        array_push($update_arr, array("path"=>$path, "value" => $value));
                    }
                    $result = $tempDoc->update($update_arr);
                }else{
                    $result = $tempDoc->set($temp);
                }
            } catch (exception $e) {
                echo "bad opperation";
            }
        }

        return $result;
    }

    public function deleteProject($code){
        $result     = null;
        if($this->firestore){
            try {
                // CREATE THE PARENT DOC
                $ov_projects    = $this->firestore->collection($this->firestore_projects);
                $tempDoc        = $ov_projects->document($code);
                $tempDoc->delete();
            } catch (exception $e) {
                echo "bad opperation";
            }
        }
        return $result;
    }

    // GOOGLE FIRESTORE AND CLOUD STORAGE 
    /**
     * Upload a file.
     *
     * @param string $bucketName the name of your Google Cloud bucket.
     * @param string $objectName the name of the object.
     * @param string $source the path to the file to upload.
     *
     * @return Psr\Http\Message\StreamInterface
     */
    public function upload_object($storageClient, $bucketName, $objectName, $source) {
        if($file = file_get_contents($source)){
            $bucket     = $storageClient->bucket($bucketName);
            $object     = $bucket->upload($file, [
                'name' => $objectName
            ]);

            return $object;
        }else{
            return false;
        }
    }

    public function getGCPRestToken($keyPath, $scope){
        // putenv('GOOGLE_APPLICATION_CREDENTIALS='.$keyPath);
        $client             = new Google_Client();
        $client->setAuthConfigFile($keyPath);
        // $client->useApplicationDefaultCredentials();
        $client->addScope($scope);
        $auth               = $client->fetchAccessTokenWithAssertion();
        $access_token       = $auth["access_token"];
        return $access_token;
    }

    public function restPushFireStore($firestore_url, $json_payload, $access_token){
        $curl               = curl_init($firestore_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json"
                                                    , "Authorization: Bearer $access_token"
                                                    , "Content-Length: " . strlen($json_payload)
                                                    , "X-HTTP-Method-Override: PATCH"
                                                    )
                    );
        curl_setopt($curl, CURLOPT_USERAGENT, "cURL");
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_payload);
        // $getinfo     = curl_getinfo($curl);
        // $error       = curl_error($curl);
        $response   = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function restDeleteFireStore($firestore_url, $access_token){
        $curl               = curl_init($firestore_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json"
                                                    , "Authorization: Bearer $access_token"
                                                    , "X-HTTP-Method-Override: DELETE"
                                                    )
                    );
        curl_setopt($curl, CURLOPT_USERAGENT, "cURL");
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $getinfo     = curl_getinfo($curl);
        $error       = curl_error($curl);
        $response   = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function convertFSwalkId($old_id){
        $walk_parts     = explode("_",$old_id);
        return $walk_parts[0] ."_" . $walk_parts[1] . "_" . $walk_parts[3];
    }

    public function formatUpdateWalkPhotos_rest($photos,$transcriptions){
        $new_photos     = array();
        foreach($photos as $photo){
            $temp                   = array();
            $temp["goodbad"]        = array_key_exists("goodbad", $photo)       ? $photo["goodbad"]         : null;
            $temp["name"]           = array_key_exists("name", $photo)          ? $photo["name"]            : null;
            $temp["rotate"]         = array_key_exists("rotate", $photo)        ? $photo["rotate"]          : null;
            $temp["text_comment"]   = array_key_exists("text_comment", $photo)  ? $photo["text_comment"]    : null;
            $temp["geotag"]         = array_key_exists("geotag", $photo)        ? $photo["geotag"]          : array();
            $temp["tags"]           = array_key_exists("tags", $photo)          ? $photo["tags"]            : array();
            $audios                 = array_key_exists("audios", $photo)        ? $photo["audios"]          : array();

            $temp["audios"]         = array();
            foreach($audios as $audio_name){
                $temp["audios"][$audio_name] = array_key_exists($audio_name, $transcriptions) ? $transcriptions[$audio_name] : array() ;
            }

            $fields                     = array();
            $fields["goodbad"]          = array_key_exists("goodbad", $photo) && !is_null($photo["goodbad"])            ? array("integerValue" => $photo["goodbad"])    : array("nullValue" => null);
            $fields["name"]             = array_key_exists("name", $photo) && !is_null($photo["name"])                  ? array("stringValue" => $photo["name"])        : array("nullValue" => null);
            $fields["rotate"]           = array_key_exists("rotate", $photo) && !is_null($photo["rotate"])              ? array("integerValue" => $photo["rotate"] )    : array("nullValue" => null);
            $fields["text_comment"]     = array_key_exists("text_comment", $photo) && !is_null($photo["text_comment"])  ? array("stringValue" => $photo["text_comment"]): array("nullValue" => null);

            $geoFields = array();
            foreach($temp["geotag"] as $key => $val){
                $geoFields[$key] = array("doubleValue" => $val);
            }
            if(!empty($geoFields)) {
                $fields["geotag"] = array("mapValue" => array("fields" => $geoFields));
            }

            $audioFields = array();
            foreach($temp["audios"] as $key => $val){
                if(empty($val)){
                    $audioFields[$key] = array("arrayValue" => array("values" => $val) );
                }else{
                    $audio_text = isset($val["text"]) ? $val["text"] : "";
                    $audio_confidence = isset($val["confidence"]) ? $val["confidence"] : 0;
                    $audioFields[$key] = array("mapValue" => array("fields" => array("text" => array("stringValue" => $audio_text), "confidence" => array("doubleValue" => $audio_confidence)     ) ));
                }
            }
            $fields["audios"]  = !empty($audioFields) ? array("mapValue" => array("fields" => $audioFields)) : array("arrayValue" => array("values" => array()));

            $tagFields = array();
            foreach($temp["tags"] as $tag){
                $tagFields[]  = array("stringValue" => $tag);
            }
            $fields["tags"] = array("arrayValue" => array("values" => $tagFields));
            $new_photos[]   = array("mapValue" => array("fields" => $fields));
        }

        return $new_photos;
    }

    public function formatUpdateWalkPhotos($photos,$transcriptions= array()){
        $new_photos     = array();
        foreach($photos as $photo){
            $temp                   = array();
            $temp["goodbad"]        = array_key_exists("goodbad", $photo)       ? $photo["goodbad"]         : null;
            $temp["name"]           = array_key_exists("name", $photo)          ? $photo["name"]            : null;
            $temp["rotate"]         = array_key_exists("rotate", $photo)        ? $photo["rotate"]          : null;
            $temp["text_comment"]   = array_key_exists("text_comment", $photo)  ? $photo["text_comment"]    : null;
            $temp["geotag"]         = array_key_exists("geotag", $photo)        ? $photo["geotag"]          : array();
            $temp["tags"]           = array_key_exists("tags", $photo)          ? $photo["tags"]            : array();
            $audios                 = array_key_exists("audios", $photo)        ? $photo["audios"]          : array();

            $temp["audios"]         = array();
            foreach($audios as $audio_name){
                if(!isset($temp["audios"][$audio_name])){
                    $temp["audios"][$audio_name] = null;
                }
//                $temp["audios"][$audio_name]["text"]        = array_key_exists($audio_name, $transcriptions) ? $transcriptions[$audio_name]["text"]         : null;
//                $temp["audios"][$audio_name]["confidence"]  = array_key_exists($audio_name, $transcriptions) ? $transcriptions[$audio_name]["confidence"]   : null;
            }

            $new_photos[] = $temp;
        }

        return $new_photos;
    }

    public function setWalkFireStore($old_id, $details, $firestore=null){
        // FIRESTORE FORMAT walk_id
        $walk_parts     = explode("_",$old_id);

        // FIRESTORE FORMAT walk_id
        $walk_id        = $walk_parts[0] ."_" . $walk_parts[1] . "_" . $walk_parts[3];

        // IF NO PHOTOS, THEN ITS NOT A COMPLETE WALK
        if(empty($details["photos"])){
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

        $new_photos     = $this->formatUpdateWalkPhotos($photos,$txn);

        $geotags        = array_key_exists("geotags", $details) ? $details["geotags"] : array() ;
        $culled_geos    = array();
        foreach($geotags as $geotag){
            if($geotag["accuracy"]){
                $culled_geos[] = $geotag;//array_intersect_key($geotag,$keep_these);
            }
        }
//
//        // NEED TO FORMAT PROPERLY TO PUSH TO FIRESTORE
//        $fs_pid     = ["stringValue" => $pid];
//        $fs_lang    = !is_null($lang) ? ["stringValue" => $lang] : ["nullValue" => null];
//        $fs_ts      = ["integerValue"   => $walk_parts[3]];
//
//        // map device array
//        $temp = array();
//        foreach($device as $key => $val){
//            $temp[$key] = array("stringValue" => $val);
//        }
//        $fs_device  = array("mapValue" => array("fields" => $temp));
//
//        // map survey array , the app no longer records surveys
//        $fs_survey  = array("arrayValue" => array("values" => array()));
//
//        // map photos array
//        $fs_photos  = array("arrayValue" => array("values" => $new_photos));
//
//        $firestore_data = [
//                 'project_id'   => $fs_pid
//                ,'lang'         => $fs_lang
//                ,'timestamp'    => $fs_ts
//                ,'device'       => $fs_device
//                ,'survey'       => $fs_survey
//                ,'photos'       => $fs_photos
//            ];
//        $data = ["fields" => (object)$firestore_data];
//        $json = json_encode($data);
//
//        $object_unique_id   = $walk_id;
//        $firestore_url      = cfg::$firestore_endpoint . "projects/".cfg::$gcp_project_id."/databases/(default)/documents/".cfg::$firestore_collection."/".$object_unique_id;
//        $access_token       = $firestore;
//
//
//        //PUSH THE ORIGINAL WALK DATA DOCUMENT
//        print_rr($json);
//        exit;
//        $response           = $this->restPushFireStore($firestore_url, $json, $access_token);
//        print_rr($response);
//
//        // NOW PUSH A NEW DOCUMENT FOR EACH GEOTAG TO THE SUBCOLLECTION FOR THE WALK
//        $firestore_url_sub  = $firestore_url . "/geotags/";
//        foreach($culled_geos as $i => $geotag){
//            $geoFields = array();
//            foreach($geotag as $key => $val){
//                $geoFields[$key] = array("doubleValue" => $val);
//            }
//
//            $fs_geo         = array("mapValue" => array("fields" => $geoFields));
//            $temp_url       = $firestore_url_sub . $i;
//
//            $firestore_data = [$fs_geo];
//            $data           = ["fields" => (object)$firestore_data];
//            $json           = json_encode($data);
//            $response       = $this->restPushFireStore($temp_url, $json, $access_token);
//            set_time_limit(5);
//        }
//
//        return $walk_id;

        // THIS IS FOR IF THE GRPC EXTENSION GETS INSTALLED

        try {
            // CREATE THE PARENT DOC
            $docRef = $this->firestore->collection($this->walks_collection)->document($walk_id);
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

    public function uploadCloudStorage($attach_id, $walk_id, $bucketName,  $storageCLient, $filepath=false){
        # UPLOAD TO CLOUD STORAGE
        $folder_components  = explode("_",$walk_id);

        $project_id         = $folder_components[0];
        $device_id          = $folder_components[1];
        $walk_ts            = $folder_components[3];
        $attachment_prefix  = "$project_id/$device_id/$walk_ts/";
        $file_suffix        = str_replace($walk_id."_","",$attach_id);

        //wtf
        $file_suffix        = str_replace("jpeg", "jpg", $file_suffix);

        $filepath           = !$filepath ? 'temp/'.$walk_id.'/'.$attach_id : $filepath;
        $new_attach_id      = $attachment_prefix . $file_suffix;

        //UPLOAD from TEMP DIR on DISK
        $uploaded           = $this->upload_object($storageCLient, $bucketName, $new_attach_id, $filepath);

        return $uploaded;
    }
}
<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (!file_exists(__DIR__ . "/_config.php")) {
    exit("You must create a _config.php file from the template before this application can run properly");
}
// Load the configuration
require_once __DIR__ . "/_config.php";

//START TIMER FOR PAGE LOAD
$start_time	= microtime(true);

//TODO REMOVE AFTER UPDATE THE SERVER _config.php
$couch_attach_db    = "disc_attachment";
$masterblaster      = cfg::$master_pw;

date_default_timezone_set('America/Los_Angeles');

ini_set("session.cookie_httponly", 1);
session_start(); 

function doCurl($url, $data = null, $method = null, $username = null, $password = null) {
    if (empty($username)) $username = cfg::$couch_user;
    if (empty($password)) $password = cfg::$couch_pw;

    $process = curl_init($url);

    curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
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

function urlToJson($url){
    if($url){
        $temp = doCurl($url);
        $temp = json_decode(stripslashes($temp),1);
        return $temp;
    }
}

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
        return array();
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

function print_rr($ar){
    echo "<pre>";
    print_r($ar);
    echo "</pre>";
}

function markPageLoadTime($msg=null){
    global $start_time;

    echo "<h6>";
    if($msg){
        echo $msg ."<br>";
    }
    echo microtime(true) - $start_time;
    echo "</h6>";
}

function cmp_date($a, $b){
    $a = str_replace('-', '/', $a); //have to convert to american time because of the strtotime func
    $b = str_replace('-', '/', $b);
    $c = strtotime($a)- strtotime($b);
        return (strtotime($a) < strtotime($b)) ? 1 : -1;
}

function getFullName($data, $abv){
    foreach($data["project_list"] as $in){
        if(isset($in["project_id"]) && $in["project_id"] == $abv){
            return $in["project_name"];
        }
    }
}

function parseTime($data, $storage){
    if($data["rows"] == null)
        return false;
    else
        for($i = 0 ; $i < count($data["rows"]) ; $i++){         
            $temp = explode('_', $data["rows"][$i]["id"]); // index zero is the 4 char PID key, index 3 is the time
            $simp_PID = $temp[0];
            $ts = $temp[3];
            
            if(array_key_exists($simp_PID, $storage)) //if ID is already inside
                array_push($storage[$simp_PID], $ts);
            else
                $storage[$simp_PID] = array($ts);
        }
        ksort($storage);
        return $storage;
}

function fetchKeys($abvList, $ALL_PROJ_DATA){
    $keyList = array();
    if(isset($abvList)){
        foreach($abvList as $entry)
            foreach ($ALL_PROJ_DATA["project_list"] as $key=>$projects)
                if($projects["project_id"] == $entry)
                    array_push($keyList, $key);


    }
    return $keyList;
}

function push_data($url, $data){
    $response   = doCurl($url, json_encode($data), 'PUT');
    return json_decode($response,1);
}

function parseProjectInfo($ALL_PROJ_DATA){
    $return_array = array();
    foreach ($ALL_PROJ_DATA["project_list"] as $project) {
        array_push($return_array,$project);
    }
    return $return_array;
}

function cacheThumb($ph_id,$thumb_uri){
    $localthumb = "img/thumbs/$ph_id";
    
    // IT MIGHT EXIST BUT IT MIGHT BE GARBAGE
    if( (file_exists($localthumb) && filesize($localthumb) < 1200) ){
        unlink($localthumb);
    }

    $haslocal = false;
    // NOW IT DOESNT EXIST SO CREATE IT
    if(!file_exists($localthumb)){
        $ch         = curl_init($thumb_uri);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw        = curl_exec($ch);
        // $errornum   = curl_errno($ch);
        // $info       = curl_getinfo($ch);
        curl_close($ch);
        // print_rr($errornum);
        // print_rr($info);
        $fp         = fopen($localthumb,'x');
        fwrite($fp, $raw);
        fclose($fp);
        $haslocal = true;
    }

    // IT MIGHT HAVE CREATED GARBAGE SHOULD I TEST AGAIN?
    
    return $haslocal ? $ph_id : ""; 
}

function getThumb($ph_id, $thumb_uri, $fileurl){
    $localthumb = "img/thumbs/$ph_id";
    // IF IT EXISTS AND ISNT GARBAGE
    if( file_exists($localthumb) ){
        if( filesize($localthumb) < 1000 ){
            //DELETE IT , ITS GARBAGE
            unlink($localthumb);
        }else{
            //ITS GOOD , USE IT
            $thumb_uri = $localthumb;
        }
    }

    return $thumb_uri;
}

function scanBackUpFolder($backup_dir){
    $backedup   = array();
    $couch_url  = "http://".cfg::$couch_user.":".cfg::$couch_pw."@couchdb:5984";

    if ($folder = opendir($backup_dir)) {
        while (false !== ($file = readdir($folder))) {
            if($file == "." || $file == ".."){
                continue;
            }

            if (!is_dir("$backup_dir/".$file)) {
                if(strpos($file,".json") > 0){
                    $split          = explode(".",$file);
                    $backup         = $split[0];
                    $walk_json      = $couch_url . "/".cfg::$couch_users_db."/" . $backup ;
                    $check_walk_id  = get_head($walk_json);
                    if(array_key_exists("ETag", $check_walk_id[0])){
                         // DOESNT EXIST SO NEED TO UPLOAD TO disc_users
                         continue;
                    }
                }else{
                    $attach_file    = $couch_url . "/".cfg::$couch_attach_db."/" . $file ;
                    $check_attach   = get_head($attach_file);
                    if(array_key_exists("ETag", $check_attach[0])){
                         // DOESNT EXIST SO NEED TO UPLOAD TO disc_users
                         // re upload no matter what. 11/12/19
                         //continue;
                    }
                }
                $backedup[] = $file;
            }
        }
        closedir($folder);
    }

    return $backedup;
}

function prepareAttachment($key,$rev,$parent_dir,$attach_url){
    $file_i         = str_replace($parent_dir."_","",$key);   
    $splitdot       = explode(".",$file_i);
    $c_type         = $splitdot[1];

    $couchurl       = $attach_url."/".$key."/".$file_i."?rev=".$rev;
    $filepath       = 'temp/'.$parent_dir.'/'.$key;
    $content_type   = strpos($key,"photo") ? 'image/jpeg' : $c_type;
    $response       = uploadAttach($couchurl, $filepath, $content_type);
    return $response;
}

function uploadAttach($couchurl, $filepath, $content_type){
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

function postData($url, $data){ //MUST INCLUDE Key attached to URL, 
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

function deleteDirectory($dir) {
    system('rm -rf ' . escapeshellarg($dir), $retval);
    return $retval == 0; // UNIX commands return zero on success
}

function updateDoc($url,$keyvalues){
    // TO PROTECT FROM DOC CONFLICTS (LITERALLY THE WORST POSSIBLE THInG) ,
    // WE FIRST GET A FRESH COPY OF THE DOC, ALTER IT, THEN SAVE IT RIGHT AWAY
    $response 	= doCurl($url);
    $payload    = json_decode($response,1);
    foreach($keyvalues as $k => $v){
        $payload[$k] = $v;
    }

    $response 	= doCurl($url, json_encode($payload), 'PUT');
    return json_decode($response,1);
}

function getAllData(){
    $url            = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
    $response       = doCurl($url);
    return json_decode($response,1);
}
function getWalkData($_id){
    $walk_url   = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($walk_url);
    $doc        = json_decode(stripslashes($response),1);
    return $doc;
}
function saveWalkData($_id, $data){
    $walk_url   = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $_id;
    $response   = doCurl($walk_url, json_encode($data), 'PUT');
    return json_decode($response,1);
}
function getFilteredDataGeos($pcode, $pfilters){
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
    $response       = loadAllProjectThumbs($pcode, $pfilters, $goodbad_filter);

    $photo_geos     = array();
    $code_block     = array();

    //WHAT THE FUCK IS THIS SHIT, NEED TO LOOP THROUGH 3 times? 
    $sort_temp      = array();
    foreach($response["rows"] as $row){
        $doc    = $row["value"];
        $_id    = $row["id"];
        $ph_i   = $doc[0];
        $old    = $doc[1];
        $photo  = $doc[2]; 
        $txns   = $doc[3]; 
        $device = $doc[4]; 

        //STUFF IT INTO HOLDING ARRAY BY WALK_ID
        if(!array_key_exists($_id, $sort_temp)){
            $sort_temp[$_id] = array();
        }

        if(array_key_exists("deleted", $photo)){
            continue;
        }

        // if good bad filters is included in tags
        if(!empty($goodbad_filter)){
            if(!in_array($photo["goodbad"], $goodbad_filter) ){
                continue;
            }
        }

        $sort_temp[$_id][$ph_i] = $doc;        
    }

    //SECOND LOOP!  + BONUS NESTED LOOP BS,   BETTER WAY TO DO THIS???  FUCK IT.
    foreach($sort_temp as $walk_id => $junk){
        ksort($sort_temp[$walk_id]);
        foreach($sort_temp[$walk_id] as $ph_i => $doc){
            $_id    = $walk_id;
            $old    = $doc[1];
            $photo  = $doc[2]; 
            $txns   = $doc[3]; 
            $device = $doc[4];

            // I DID THIS TO MYSELF OH LORD
            $old = is_null($old) ? "" : "&_old=" . $old;

            // GATHER EVERY GEO TAG FOR EVERY PHOTO IN THIS WALK, AT LEAST THIS HAS NO ORDER HALLELUJAH
            if(!empty($photo["geotag"])){
                $filename   = empty($photo["name"]) ? "photo_".$ph_i.".jpg" : $photo["name"];
                $ph_id      = $_id;
                if(array_key_exists("name",$photo)){
                    // new style file pointer
                    $ph_id  .= "_" .$filename;
                }
                $file_uri       = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
                $photo_uri      = "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
                $photo["geotag"]["photo_src"]   = $photo_uri;
                $photo["geotag"]["goodbad"]     = $photo["goodbad"];
                $photo["geotag"]["photo_id"]    = $_id. "_" . "photo_".$ph_i;
                $photo["geotag"]["platform"]    = $device["platform"];
                array_push($photo_geos, $photo["geotag"]);
            }

            // Massage a block for each photo in the project
            $code_block = array_merge($code_block, printPhotos($photo,$_id,$ph_i,$old,$txns));
        }
    }

    // IF ASKING FOR MULTIPLE TAGS COULD HAVE REPEATS FOR MULTI TAGGED PHOTOS
    $code_block = array_unique($code_block,SORT_REGULAR);
    $data       = array("photo_geos" => $photo_geos, "code_block" => $code_block);
    return $data;
}
function getWalkIdDataGeos($walk_id){
    //RETURNS JSON ENCODED BLOCK OF FILTERED PHOTO INFO, AND PHOTO GEOs
    // MOOD and TAG FILTERS ARE MIXED TOGETHER, SO SEPERATE THEM OUT

    $response       = getWalkSummaryData($walk_id);
    // $response       = getProjectSummaryData("IRV", "all_photos");
    $photo_geos     = array();
    $code_block     = array();

    //WHAT THE FUCK IS THIS SHIT, NEED TO LOOP THROUGH 3 times? 
    $sort_temp      = array();
    foreach($response["rows"] as $row){
        $doc    = $row["value"];
        $_id    = $row["id"];
        $ph_i   = $doc[0];
        $old    = $doc[1];
        $photo  = $doc[2]; 
        $txns   = $doc[3]; 
        $device = $doc[4]; 

        //STUFF IT INTO HOLDING ARRAY BY WALK_ID
        if(!array_key_exists($_id, $sort_temp)){
            $sort_temp[$_id] = array();
        }

        if(array_key_exists("deleted", $photo)){
            continue;
        }

        // if good bad filters is included in tags
        if(!empty($goodbad_filter)){
            if(!in_array($photo["goodbad"], $goodbad_filter) ){
                continue;
            }
        }

        $sort_temp[$_id][$ph_i] = $doc;        
    }

    //SECOND LOOP!  + BONUS NESTED LOOP BS,   BETTER WAY TO DO THIS???  FUCK IT.
    foreach($sort_temp as $walk_id => $junk){
        ksort($sort_temp[$walk_id]);
        foreach($sort_temp[$walk_id] as $ph_i => $doc){
            $_id    = $walk_id;
            $old    = $doc[1];
            $photo  = $doc[2]; 
            $txns   = $doc[3]; 
            $device = $doc[4];

            // I DID THIS TO MYSELF OH LORD
            $old = is_null($old) ? "" : "&_old=" . $old;

            // GATHER EVERY GEO TAG FOR EVERY PHOTO IN THIS WALK, AT LEAST THIS HAS NO ORDER HALLELUJAH
            if(!empty($photo["geotag"])){
                $filename   = empty($photo["name"]) ? "photo_".$ph_i.".jpg" : $photo["name"];
                $ph_id      = $_id;
                if(array_key_exists("name",$photo)){
                    // new style file pointer
                    $ph_id  .= "_" .$filename;
                }
                $file_uri       = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
                $photo_uri      = "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
                $photo["geotag"]["photo_src"]   = $photo_uri;
                $photo["geotag"]["goodbad"]     = $photo["goodbad"];
                $photo["geotag"]["photo_id"]    = $_id. "_" . "photo_".$ph_i;
                $photo["geotag"]["platform"]    = $device["platform"];
                array_push($photo_geos, $photo["geotag"]);
            }

            // Massage a block for each photo in the project
            $code_block = array_merge($code_block, printPhotos($photo,$_id,$ph_i,$old,$txns));
        }
    }

    // IF ASKING FOR MULTIPLE TAGS COULD HAVE REPEATS FOR MULTI TAGGED PHOTOS
    $code_block = array_unique($code_block,SORT_REGULAR);
    $data       = array("photo_geos" => $photo_geos, "code_block" => $code_block);
    return $data;
}

// SOMEHTML GENERATION
function printRow($doc, $active_pid){
    global $project_meta, $ap;

    $pcode          = $ap["project_list"][$active_pid]["project_id"];
    $codeblock      = array();
    $i              = $doc["_id"];
    $photos         = $doc["photos"];
    $geotags        = !empty($doc["geotags"]) ? $doc["geotags"] : array();
    $survey         = $doc["survey"];
    $processed      = $doc["data_processed"];

    //TODO THIS IS FOR THE 3 VERSIONS OF ATTACHMENT STORAGE AND RETRIEVAL
    if(!empty($doc["_attachments"])){
        //original attachments stored with walk sessions
        $old = "&_old=1";
    }else{
        if(array_key_exists("name",$doc["photos"][0])){
            //newest and "final" method atomic attachment storage
            $old = "";
        }else{
            //all attachments in seperate data entry
            $old = "&_old=2";
        }
    }

    $forjsongeo     = array();
    $lang           = is_null($doc["lang"]) ? "EN" : $doc["lang"];
    $language_list  = $_SESSION["DT"]["project_list"][$active_pid]["app_lang"];
    $full_language  = array_filter($language_list, function($v) use ($lang){
        if($v["lang"] == $lang){
            return $v;
        }
    });
    if($fl = current($full_language)){
        $lang = $fl["language"];
    }

    // if no walk geo, but has some for indy  photos (indpendent api calls)
    if (empty($geotags)){
        foreach($photos as $photo){
            if(!empty($photo["geotag"])){
                $geotags[] = $photo["geotag"];
                break;
            }
        }
    }

    // filter out low accuracy
    $forjsongeo = array_filter($geotags,function($tag){
        return $tag["accuracy"] <= 50;
    });

    // use the unaccurate if nothing was marked high acc.
    if(empty($forjsongeo)){
        $forjsongeo = $geotags; 
    }

    // get STATIC google map , performance, there a limite to how many markers can be passed to static api (_GET) so figure out how much to spread out the points
    $geopoints      = array();
    $point_count    = count($forjsongeo);
    $n_jump         = $point_count > 500 ? ceil($point_count/500) : 1;  //500 is about the max 
    $n              = 0;
    $path_coords    = array();
    foreach($forjsongeo as $geotag){
        $coord = $geotag["lat"].",".$geotag["lng"];
        if($n%$n_jump == 0){
            $path_coords[] = $coord;
        }
        $geopoints[] = $coord;
        $n++;
    }
    $spread         = implode("|",$path_coords);
    $mapurl         = 'https://maps.googleapis.com/maps/api/staticmap?key='.cfg::$gmaps_key.'&size=420x300&zoom=16&path=color:0x0000FFd7|weight:3|' . $spread;

    $json_geo    = json_encode($forjsongeo);

    $last4       = substr($doc["_id"],-4);
    $firstpart   = substr($doc["_id"],0, strlen($doc["_id"]) - 4);
    $walk_ts_sub = substr($doc["_id"],-13);
    $date_ts     = date("F j, Y", floor($walk_ts_sub/1000)) ;

    $codeblock[] = "<div class='user_entry'>";
    $codeblock[] = "<hgroup>";
    $codeblock[] = "<h4>(". $lang .") : 
    <b>".$date_ts."</b> 
    <i><strong>$last4</strong></i></h4>";
    $codeblock[] = "</hgroup>";

    $codeblock[] = "<div id='google_map_$i' class='gmap'><img src='$mapurl'/><a href='#' class='reload_map' data-mapgeo='$json_geo' data-mapi='$i'>Map look wrong?  Refresh with live map</a></div>";
    $codeblock[] = "<div class = 'location_alert_summary'></div>";

    
    $codeblock[] = "<section class='photo_previews'>";
    $codeblock[] = "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Delete This Walk</a>";

    $codeblock[] = "<a href='download_photos.php?doc_id=".$doc["_id"]."' class='btn btn-info export-photos' target='blank'>Download Photos</a>";
    
    if(!$processed){
        $codeblock[] = "<label class='data_processed' ><input type='checkbox' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'/> Data Processed?</label>";
    }
    $codeblock[] = "<a href='#' class='btn btn-primary export-pdf' data-pcode='".$pcode."' data-active_pid='".$active_pid."' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Print View</a>";


    $codeblock[] = "<h5>Photo Previews (".count($photos).")</h5>";
    $codeblock[] = "<div class='thumbs'>";
    $codeblock[] = "<ul>";
    $host        = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
    $url_path    = $host .dirname($_SERVER['PHP_SELF'])."/";
    // $url_path    = $_SERVER['HTTP_ORIGIN'].dirname($_SERVER['PHP_SELF'])."/";
    $count_empty = 0;

    foreach($photos as $n => $photo){

        if(is_null($photo) || isset($photo["deleted"])){
            continue;
        }

        $hasaudio   = !empty($photo["audio"]) ? "has" : "";

        if(isset($photo["geotag"]["longitude"]) &&  isset($photo["geotag"]["latitude"])){
            $long = $photo["geotag"]["longitude"];
            $lat = $photo["geotag"]["latitude"];
        }else if(isset($photo["geotag"]["lng"]) &&  isset($photo["geotag"]["lat"])){
            $long = $photo["geotag"]["lng"];
            $lat = $photo["geotag"]["lat"];
        }else{
            $long = 0;
            $lat = 0;
        }

        $timestamp  = isset($photo["geotag"]["timestamp"]) ? $photo["geotag"]["timestamp"] : 0;
        $goodbad    = "";
        if($photo["goodbad"] > 1){
            $goodbad  .= "<span class='goodbad good'></span>";
        }

        if($photo["goodbad"] == 1 || $photo["goodbad"] == 3){
            $goodbad  .= "<span class='goodbad bad'></span>";
        }

        $rotate     = isset($photo["rotate"]) ? $photo["rotate"] : 0;
        $photo_name = "photo_".$n.".jpg";

        //TODO FOR MULTIPLE VERSIONS OF ATTACHMENT STORAGE
        if(array_key_exists("name",$photo)){
            $filename   = $photo["name"];
            $ph_id      = $i . "_" .$filename;
        }else{
            $filename   = $photo_name;
            $ph_id      = $doc["_id"];
        }
        $img_id         = $doc["_id"]."_".$photo_name;

        $file_uri   = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
        $thumb_uri  = "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
        $photo_uri  = $file_uri;
        // $photo_uri  = getThumb($img_id,$thumb_uri,$file_uri);
        $detail_url = "photo.php?_id=".$doc["_id"]."&_file=$photo_name";

        $attach_url = "#";
        $audio_attachments = "";
        $text_comment      = "";

        if(!empty($photo["text_comment"])){
            $text_comment  = "<a class='audio keyboard'></a> ";
        }

        if(!empty($photo["audio"])){
            $num_audios = intval($photo["audio"]);
            $num        = $num_audios > 1 ? "<span>x$num_audios</span>" :"";
            $audio_attachments .= "<a class='audio $hasaudio'></a> $num";
        }
        //date_default_timezone_set('America/New_York'); am-us/ny

        if($lat != 0 | $long != 0){
            $time = time();
            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=" . cfg::$gmaps_key;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseJson = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($responseJson);
            date_default_timezone_set($response->timeZoneId); 
        }
        $codeblock[] = "
        <li data-phid='$img_id'>
        <div class = 'load'>
            <div class = 'progress'></div>
        </div>

        <figure>
        <a href='$detail_url' rel='google_map_$i' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate' rev='$rotate'><img src='$photo_uri' /><span></span><b></b></a>
        <figcaption>

            <span class='time'>@".date("g:i a", floor($timestamp/1000))."</span>
            ".$goodbad."
            ".$audio_attachments."
            ".$text_comment."
        </figcaption>
        </figure></li>";
    }
    $codeblock[] = "</ul>";
    $codeblock[] = "</div>";
    $codeblock[] = "</section>";

    
    if(!empty($survey)){
        $codeblock[] = "<section class='survey_response'>";
        $codeblock[] = "<h5>Survey Responses</h5>";
        $codeblock[] = "<div class='survey'>";
    }
    //WHOOO THIS IS NOT GREAT
    if(isset($project_meta["template_type"])) {
        $template_type = $project_meta["template_type"];
    }else {
        $template_type = 1;
    }
    $survey_text    = $ap["survey_text"][$template_type]; //available
    $tempsurvey     = array();
    foreach($survey_text as $s){ //loop through all the survey questions
        $tempoptions = array();
        if(isset($s["options"])){
            foreach($s["options"] as $o){ //loop through each of the answer options
                $tempoptions[$o["value"]] = $o["en"]; //tempoptions["m"] => 'What is your sex'
            }
        }else{
            $tempoptions = null;
        }
        $tempsurvey[$s["name"]] = array(        //"gender" = [0] => "label" => "what is your sex"
                "label" => $s["label"]["en"]    //                  "options" => ""
                ,"options" =>  $tempoptions
            );
       
    }
 //print_rr($survey); //survey corresponds to the answered questions per project
    $unique = array();
    foreach($survey as $q){
        $unique[$q["name"]] = $q["value"];
    }
    $codeblock[] = "<ul>";

    if(array_key_exists("app_rating", $unique ) && count($unique) == 1){ //unique case is hardcoded (short template)
        foreach($unique as $name => $value)
            $v = (!empty($tempsurvey[$name]["options"]))  ?  $tempsurvey[$name]["options"][$value] :$value;
        
        $oldname = $ap["survey_text"][0][0]["label"]["en"];
       // print_rr($oldname);
        $codeblock[] = "<li><i>".$oldname."</i> : <b>$v</b></li>";
        $codeblock[] = "</ul>";
        if(!empty($survey)){
            $codeblock[] = "</div>";
            $codeblock[] = "</section>";
        }
        $codeblock[] = "</div>";
        return $codeblock;
    }else{
        foreach($unique as $name => $value){
         
            $v = (!empty($tempsurvey[$name]["options"]))  ?  $tempsurvey[$name]["options"][$value] :$value;
            $codeblock[] = "<li><i>".$tempsurvey[$name]["label"]."</i> : <b>$v</b></li>";
        }
        $codeblock[] = "</ul>";
        
        if(!empty($survey)){
            $codeblock[] = "</div>";
            $codeblock[] = "</section>";
        }
        $codeblock[] = "</div>";
        return $codeblock;
    }
}
function printPhotos($photo, $_id, $n, $old, $txns=null){
    $codeblock  = array();

    $walk_ts_sub = substr($_id,-13);
    $date_ts     = date("F j, Y", floor($walk_ts_sub/1000)) ;
    $host        = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
    $url_path    = $host .dirname($_SERVER['PHP_SELF']); 
    if($url_path != '/'){
        $url_path .= '/';               
    }

    $photoblock = array();
    $nogeo      = "";
    $lat        = null;
    $long       = null;
    if(empty($photo["geotag"])){
        $nogeo  = "nogeo";
    }else{
        $lat    = array_key_exists("lat",$photo["geotag"]) ? $photo["geotag"]["lat"] : $photo["geotag"]["latitude"];
        $long   = array_key_exists("lng",$photo["geotag"]) ? $photo["geotag"]["lng"] : $photo["geotag"]["longitude"];
    }
    

    $timestamp  = isset($photo["geotag"]["timestamp"])  ? $photo["geotag"]["timestamp"] : null;
    $txt        = array_key_exists("text_comment",$photo) ? $photo["text_comment"] : null;

    $rotate     = isset($photo["rotate"]) ? $photo["rotate"] : 0;
    $filename   = array_key_exists("name",$photo) ? $photo["name"] : "photo_".$n.".jpg";
    $ph_id      = $_id;
    if(array_key_exists("name",$photo)){
        $ph_id .= "_" .$filename;
    }

    $file_uri       = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
    $thumb_uri      = $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
    $photo_uri      = getThumb($ph_id,$thumb_uri,$file_uri);

    $detail_url     = "photo.php?_id=".$_id."&_file=$filename";
    $pic_time       = date("g:i a", floor($timestamp/1000));
    $photo_tags     = isset($photo["tags"]) ? $photo["tags"] : array();
    
    $photoblock["id"]            = $_id."_photo_".$n;
    $photoblock["tags"]          = $photo_tags;
    $photoblock["detail_url"]    = $detail_url;
    $photoblock["pic_time"]      = $pic_time;
    $photoblock["date_ts"]       = $date_ts;
    $photoblock["actual_ts"]     = $timestamp;
    $photoblock["doc_id"]        = $_id;
    $photoblock["n"]             = $n;
    $photoblock["long"]          = $long;
    $photoblock["lat"]           = $lat;
    $photoblock["nogeo"]         = $nogeo;
    $photoblock["photo_uri"]     = $photo_uri;
    $photoblock["rotate"]        = $rotate;
    $photoblock["audios"]        = $photo["audios"];
    $photoblock["goodbad"]       = $photo["goodbad"];
    $photoblock["text_comment"]  = $txt;
    $photoblock["old"]           = $old;
    $photoblock["full_img"]      = $file_uri;
    $photoblock["transcriptions"]  = $txns;

    array_push($codeblock, $photoblock);
    return $codeblock;
}
function populateRecent($ALL_PROJ_DATA, $stor, $listid){ //stor should be 
    $checkWeek = strtotime("-4 Week");
    $abvStorage = array();
    $cache_data = array();
    for($i = 0 ; $i < count($stor) ; $i++){
        $iter = 0;
        rsort($stor[$listid[$i]]); //sort each element's timestamps
                $ful = getFullName($ALL_PROJ_DATA,$listid[$i]);

    
        while(!empty($stor[$listid[$i]][$iter]) && $iter < 1) //display only the most recent update per proj 
        {
            if(($stor[$listid[$i]][$iter]/1000) > $checkWeek){
                echo '<tr>';
                echo '<th style = "font-weight: normal">'. "<strong>(".$listid[$i]. ")</strong> " . 
                '<a class="gotosumm" data-pid="'.$listid[$i].'" href="summary.php?id='.$listid[$i].'"'.'>'.$ful .'</a></th>';
                echo '<th style = "font-weight: normal">'.gmdate("Y-m-d", $stor[$listid[$i]][0]/1000).'</th>';
                echo '</tr>';
            }
            $cache_data[$listid[$i]] = array();
            $cache_data[$listid[$i]]["full"] = $ful;
            $cache_data[$listid[$i]]["rec_date"] = $stor[$listid[$i]][0]/1000;
            $iter++;
        }
        
    }//for
    $_SESSION["rec_times"] = $cache_data;
}
function printAllDataThumbs($photo_block ,$container_w=1200 ,$perchunk=16){
    // $chunk      = ceil(count($photo_block)/$perchunk);
    $chunk          = 1;
    $container_w    = count($photo_block) * 150;
    $perchunk       = count($photo_block);
    $req_width      = $chunk*$container_w;
    $req_width      .= "px";
    $chunks         = array_chunk($photo_block, $perchunk);

    $html = "<style>#tags{ width: $req_width }</style>";
    foreach($chunks as $n=> $blocks){
        $html .= "<div class='preview_chunk' data-perchunk='$perchunk' data-chunk='$n'>";
        foreach($blocks as $block){
            $html .= getAllDataPicLI($block);
        }
        $html .= "</div>";
    }

    return $html;
}
function getAllDataPicLI($photo_o){
    $txns = "";
    if(!empty($photo_o["audios"])){
        $temp = array();
        foreach($photo_o["audios"] as $audio_key){
            $temp[] = $photo_o["transcriptions"][$audio_key]["text"];
        }
        $txns = json_encode($temp);
    }

    $html_li  = "";
    $html_li .= "<li id='".$photo_o["id"]."' class='ui-widget-drop' data-phid='".$photo_o["id"]."'><figure>";
    $html_li .= "<ul>";
    foreach($photo_o["tags"] as $idx => $tag){
        $html_li .= "<li class = '$tag'>$tag<a href='#' class='deletetag' data-deletetag='$tag' data-doc_id='".$photo_o["doc_id"]."' data-photo_i='".$photo_o["n"]."'>x</a></li>";
    }
    $html_li .= "</ul>";
    $html_li .= "<a href='".$photo_o["detail_url"]."' target='_blank' class='preview rotate walk_photo ".$photo_o["nogeo"]."' 
    data-photo_i='".$photo_o["n"]."' 
    data-goodbad=".$photo_o["goodbad"]." 
    data-textcomment='".$photo_o["text_comment"]."'
    data-audiotxns='".$txns."' 
    data-doc_id='".$photo_o["doc_id"]."' 
    data-fullimgsrc='".$photo_o["full_img"]."' 
    data-imgsrc='".$photo_o["photo_uri"]."' 
    data-platform='".$photo_o["platform"]."' 
    rev='".$photo_o["rotate"]."'><img src='".$photo_o["photo_uri"]."' /><span></span><b></b><i></i><em></em></a>";
    
    $html_li .= "</figure></li>";
    return $html_li;
}

//DESIGN DOCUMENT CALLS
function filter_by_projid($view, $keys_array){ //keys array is the # integer of the PrID
    $qs         = http_build_query(array( 'key' => $keys_array ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/filter_by_projid/_view/".$view."?" .  $qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function getProjectSummaryData($project_code, $view="walk", $dd="project"){
    $qs         = http_build_query(array( 'keys' => '["'.$project_code.'"]' ,  'descending' => 'true'));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}
function getWalkSummaryData($walk_id,  $view="walk_id", $dd="summary"){
    $qs         = http_build_query(array( 'keys' => '["'.$walk_id.'"]' ,  'descending' => 'true'));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function filterProjectByTags($project_code, $tags=array(), $view="by_tags", $dd="project"){
    // WILL RETURN PHOTO OBJECT(s) FOR TAG , can be duplicates if passing in multiple tags
    // $project_code   = "AAAA";
    // returns [i,photo]
    
    if(empty($tags) || !is_array($tags)){
        return false;
    }

    $temp = array();
    foreach($tags as $tag){
        $temp[] = '["'.$project_code.'","'.$tag.'"]';
    }
    $keys       = '['.implode(',', $temp).']';
    $qs         = http_build_query(array( 'keys' => $keys ,  'descending' => 'true'));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function filterProjectNoTags($project_code, $view="no_tags", $dd="project"){
    // WILL RETURN PHOTO OBJECT(s) FOR TAG , can be duplicates if passing in multiple tags
    // $project_code   = "AAAA";
    $temp = array();
    $temp[] = '["'.$project_code.'","un_tagged"]';
    $keys       = '['.implode(',', $temp).']';
    $qs         = http_build_query(array( 'keys' => $keys ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function filterProjectByGoodBad($project_code, $goodbad=array(), $view="by_goodbad", $dd="project"){
    // WILL RETURN PHOTO OBJECT(s) FOR 1 bad, 2 good, 3 nuetral
    // $project_code   = "AAAA";
    // returns [i,photo]
    if(empty($goodbad) || !is_array($goodbad)){
        return false;
    }

    $temp = array();
    foreach($goodbad as $mood){
        $temp[] = '["'.$project_code.'",'.$mood.']';
    }
    $keys       = '['.implode(',', $temp).']';
    $qs         = http_build_query(array( 'keys' => $keys ,  'descending' => 'true'));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function loadAllProjectThumbs($project_code, $tags=array(), $goodbad=array()){
    if(empty($tags)){
        if(!empty($goodbad)){
            return filterProjectByGoodBad($project_code, $goodbad);
        }else{
            // NO FILTERS GET ALL PHOTO DATA FOR A PROJECT
            return getProjectSummaryData($project_code, "all_photos");
        }
    }else{
        if(in_array("un-tagged", $tags)){
            // REGULAR TAGS
            return filterProjectNoTags($project_code);
        }elseif(in_array("un-tagged", $tags)){
            // REGULAR TAGS
            return filterProjectNoTags($project_code);
        }else{
            // REGULAR TAGS
            return filterProjectByTags($project_code, $tags);
        }
    }
}

function checkAttachmentsExist($_ids, $view="ids", $dd="checkExisting"){
    $qs         = http_build_query(array( 'keys' => $_ids, 'group' => 'true'));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function getAggMaps($pid, $view="filter", $dd="geo"){
    $qs         = http_build_query(array( 'key' => $pid ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function getAggSurveys($pid, $view="filter", $dd="surveys"){
    $qs         = http_build_query(array( 'key' => $pid ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}

function getAggTranscriptions($pid, $view="filter", $dd="transcriptions"){
    $qs         = http_build_query(array( 'key' => $pid ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/$dd/_view/".$view."?" .$qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}



// PHOTO PAGE FUNCTIONS (AUDIO TRANSCRIPTION, FACE PIXELATION)
function getFullUrl($partialUrl){
    $paths = explode("/",$_SERVER["SCRIPT_NAME"]);
    array_unshift($paths,$_SERVER["HTTP_HOST"]);
    array_pop($paths);

    $fullpath = "";
    foreach($paths as $part){
        if($part == ""){
            continue;
        }
        $fullpath .= $part;
        $fullpath .= "/";
    }
    return $fullpath . $partialUrl;
}

function getConvertedAudio($attach_url, $lang){
    //FIRST DOWNLOAD THE AUDIO FILE

    $fullURL    = getFullUrl($attach_url);
    $ch         = curl_init($fullURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data       = curl_exec($ch);
    $errors     = curl_error($ch);
    curl_close ($ch);
    $newAudioPath = "";
    // echo $errors;
    if(empty($errors)){
        //THEN EXTRACT THE FILE NAME
        $split              = explode("=",$attach_url);
        $filename_or_old    = array_pop($split);

        if($filename_or_old == 1 || $filename_or_old == 2){
            $old_           = explode("&",array_pop($split));
            $filename       = $old_[0];
            $full_proj_code = explode("&",array_pop($split));
        }else{
            $filename       = $filename_or_old;
            $full_proj_code = explode("_audio",array_pop($split));
        }
        // echo $filename; //audio1.amr
        //save to server as audio_x_x.wav/AMR
        //if(file_exists)
        $localfile  = "./temp/$filename";
        $file       = fopen($localfile, "w+");
        fputs($file, $data);
        fclose($file);

        //THEN CONVERT THE AUDIO
        $newAudioPath = convertAudio($filename, $full_proj_code[0], $lang);
    }
    return $newAudioPath;
}

function convertAudio($filename, $full_proj_code , $lang){
    
    $split = explode("." , $filename);
    $noext = $split[0]; //audio_0_1 (ex)
    // echo '--------------' . "./temp/".$full_proj_code . '---------------';;
    // echo "_".$noext.".mp3" . '---------------';
    if (function_exists('curl_file_create')) { // php 5.5+
          $cFile = curl_file_create("./temp/".$filename);
        } else { // 
          $cFile = '@' . realpath("./temp/".$filename);
        }
    if(!file_exists("./temp/".$full_proj_code."_".$noext.".mp3")){ //if the mp3 does not exist on the server already
        // MAKE THE MP3 FROM locally saved .wav or .amr
        // print_rr("DNE");
        $ffmpeg_url = cfg::$ffmpeg_url; 
        $postfields = array(
                 "file"     => $cFile
                ,"format"   => "mp3"
                ,"rate"     => 16000
            );

        // CURL OPTIONS
        // POST IT TO FFMPEG SERVICE
        $ch = curl_init($ffmpeg_url);
        curl_setopt($ch, CURLOPT_POST, 'POST'); //PUT to UPDATE/CREATE IF NOT EXIST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // REPLACE ATTACHMENT
        $newfile    = "./temp/".$full_proj_code."_".$noext.".mp3";
        // echo 'newfile  ' . $newfile;
        $handle     = fopen($newfile, 'w');
        fwrite($handle, $response); 
    }else{
        //if the mp3 already exists just link it 
        $newfile    = "./temp/".$full_proj_code."_".$noext.".mp3";
        // echo '<br> ' . $newfile;
    }

    //check if transcription exists on database
    $url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code;
    $response       = doCurl($url);
    $storage        = json_decode($response,1);
    if($lang == "en" && (!isset($storage["transcriptions"]) || !isset($storage["transcriptions"][$filename]))){
        $trans = transcribeAudio($cFile,$filename);
        if(!empty($trans["transcript"])){
            $storage["transcriptions"][$filename]["text"] = $trans["transcript"];
            $storage["transcriptions"][$filename]["confidence"] = $trans["confidence"];
            $response   = doCurl($url, json_encode($storage), 'PUT');
            $resp       = json_decode($response,1);

            $_id                = $full_proj_code;
            $access_token       = getGCPRestToken(cfg::$FireStorekeyPath, cfg::$firestore_scope);
            $object_unique_id   = convertFSwalkId($_id);
            $firestore_url      = cfg::$firestore_endpoint . "projects/".cfg::$gcp_project_id."/databases/(default)/documents/".cfg::$firestore_collection."/".$object_unique_id."?updateMask.fieldPaths=photos";

            // SEND IT TO FIRESTORE
            $payload            = $storage;
            $photos             = $payload["photos"];
            $txn                = array_key_exists("transcriptions", $payload) ? $payload["transcriptions"] : array();
            $new_photos         = formatUpdateWalkPhotos($photos,$txn);

            $firestore_data     = ["photos" => array("arrayValue" => array("values" => $new_photos))];
            $data               = ["fields" => (object)$firestore_data];
            $json               = json_encode($data);
            $response           = restPushFireStore($firestore_url, $json, $access_token);

            header("Refresh:0");
        }
    }

    //remove extraneous files from server after creation of mp3
    $flac = explode(".",$filename);
    if(file_exists('./temp/'.$filename)){
        unlink('./temp/'.$filename);

    if(file_exists('./temp/'.$flac[0].'.flac'))
        unlink('./temp/'.$flac[0].'.flac');
    }
    return $newfile; //string representation of path to mp3
}

function transcribeAudio($cFile,$filename){
    $split = explode("." , $filename);
    $noext = $split[0];

    $ffmpeg_url = cfg::$ffmpeg_url; 
    $postfields = array(
             "file"     => $cFile
            ,"format"   => "flac"
        );

    // print_rr($postfields);
    // print_rr($ffmpeg_url);
    // CURL OPTIONS
    // POST IT TO FFMPEG SERVICE, Convert to FLAC
    $ch = curl_init($ffmpeg_url);
    curl_setopt($ch, CURLOPT_POST, 'POST'); //PUT to UPDATE/CREATE IF NOT EXIST
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    // print_rr($response);
    curl_close($ch);

    // REPLACE ATTACHMENT
    $newfile    = "./temp/".$noext.".flac";
    $handle     = fopen($newfile, 'w');
    fwrite($handle, $response); //what if no response  ?

    //Convert to base 64 for google's API
    $flac = file_get_contents($newfile);
    $flac = base64_encode($flac);

    // WE NEED TO json_encode the base64 of the flac file
    // Set some options 
    $data = array(
        "config" => array(
            "encoding" => "FLAC",
            "languageCode" => "en-US"
        ),
       "audio" => array(
            "content" => $flac
        )
    );
    $data_string = json_encode($data);                                                              

    //POST to google's service
    $ch = curl_init('https://speech.googleapis.com/v1/speech:recognize?key='.cfg::$gvoice_key);                                                                      
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
       'Content-Type: application/json',                                                                                
       'Content-Length: ' . strlen($data_string))                                                                       
    );                                
    $resp = curl_exec($ch); //NO flac data returned as a result , can we even convert to flac using ffmpeg?
    curl_close($ch);
    $resp = json_decode($resp,1);
    // print_rr($resp); //error here, response doesnt have an audio file to process results in ERROR
    $count = 0;
    $transcript = '';
    $confidence = 0;
    if(!empty($resp["results"])){
        foreach($resp["results"] as $results){
            $transcript = $transcript . $results["alternatives"][0]["transcript"];
            $confidence = $confidence + $results["alternatives"][0]["confidence"];
            $count++;
        }
    }
    if(isset($confidence) && $count != 0){
        $confidence = $confidence / $count;
        $data["transcript"] = $transcript;
        $data["confidence"] = $confidence;
        if($confidence > 0.7)
            return $data;
        
    }
        return "";
}   

function appendConfidence($attach_url){
    $split          = explode("=",$attach_url);
    $filename       = $split[count($split) -1];
    $full_proj_code = explode("_audio",$split[1]);
    
    $url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code[0];
    $response       = doCurl($url);
    $storage        = json_decode($response,1);
    if(isset($storage["transcriptions"][$filename]["confidence"]))
        return $storage["transcriptions"][$filename]["confidence"];
    else
        return "";
}

function detectFaces($id, $old, $photo_name){
    if($old){
        if($old == 2)
            $url = cfg::$couch_url . "/disc_attachments/$id";
        else
            $url = cfg::$couch_url . "/".cfg::$couch_users_db."/" . $id;
    }else{
        $url = cfg::$couch_url . "/". cfg::$couch_attach_db . "/" . $id; 
    }
    $result = doCurl($url);
    $meta = json_decode($result,true);
    
    $picture = doCurl($url . '/' . $photo_name); //returns the actual image
    // $picture = file_get_contents('./AAA.jpg'); //delete when actual. 
    $picture = base64_encode($picture); //encode so we can send it to API 

    $data = array(
        "requests" => array(
            "image" => array(
                "content" => $picture
            ),
            "features" => array(
                "type" => "FACE_DETECTION",
                "maxResults" => 4
            )    
        )
    );

    $vertices = array();
    //$new = imagecreatefromstring(base64_decode($contents)); //create image from raw data
    // //POST to google's service
    $resp = postData('https://vision.googleapis.com/v1/images:annotate?key='.cfg::$gvoice_key,$data);
    // print_rr($resp);

    //parse response into useable format : XY coordinates per face
    if(!empty($resp['responses'][0])){
        foreach($resp['responses'][0]['faceAnnotations'] as $index => $entry){
            $coord = ($entry['boundingPoly']['vertices']);
            $put = array();
            foreach($coord as $vtx){
                array_push($put, $vtx['x']);
                array_push($put, $vtx['y']);
            }
            array_push($vertices,$put);
        }
    // print_rr($vertices);
        $new = imagecreatefromstring(base64_decode($picture));
        filterFaces($vertices, $new, $id);
    }
}

function filterFaces($vertices,$image,$id, $pixel_count, $rotationOffset = 0){
	echo $pixel_count;
	$passed = false;
	if($rotationOffset){ //rotate back
		if($rotationOffset == 1){
			$image = imagerotate($image,-90,0);
		}elseif($rotationOffset ==2){
			$image = imagerotate($image,-180,0);
		}elseif($rotationOffset ==3){
			$image = imagerotate($image,-270,0);
		}
	}
	// imagedestroy($image);
	
	if(count($vertices) == 6){ //from the portal tool
		$scale_factor_x = imagesx($image) / $vertices['width_pic']; //width_pic is the thumbnail size on the portal , imagesx returns FULL res
		$scale_factor_y = imagesy($image) / $vertices['height_pic'];
		// echo $scale_factor_x . " " . $scale_factor_y;
		$scale_pixels = isset($pixel_count)? ($pixel_count*0.000015) : 20;

		$width = isset($vertices['width']) ? $vertices['width'] : -1;
		$height = isset($vertices['height']) ? $vertices['height'] : -1;
		if($width != -1 && $height != -1){
			$crop = imagecrop($image,['x'=>$vertices['x']*$scale_factor_x,'y'=>$vertices['y']*$scale_factor_y,'width'=>$width*$scale_factor_x, 'height'=>$height*$scale_factor_y]);
			// pixelate($crop, $scale_pixels,$scale_pixels);
			pixelate($crop, $scale_pixels, $scale_pixels);
			//put faces back on the original image
			imagecopymerge($image, $crop, $vertices['x']*$scale_factor_x, $vertices['y']*$scale_factor_y, 0, 0, $width*$scale_factor_x, $height*$scale_factor_y, 100);
			$passed = true;
			imagedestroy($crop);

		}
	}else{
		foreach($vertices as $faces){
			$width = isset($faces[0]) && isset($faces[2]) ? $faces[2] - $faces[0] : 0;
			$height = isset($faces[1]) && isset($faces[7]) ? $faces[7] - $faces[1] : 0;
			$scale_pixels = isset($pixel_count)? ($pixel_count*0.000015) : 20;
			if($width != 0 && $height != 0){
				//have to crop out the faces first then apply filter
				$crop = imagecrop($image,['x'=>$faces[0],'y'=>$faces[1],'width'=>$width, 'height'=>$height]);
				// pixelate($crop, $scale_pixels,$scale_pixels);
				pixelate($crop,$scale_pixels,$scale_pixels);
				//put faces back on the original image
				imagecopymerge($image, $crop, $faces[0], $faces[1], 0, 0, $width, $height, 100);
				$passed = true;
				imagedestroy($crop);
			}
			// $gaussian = array(array(1.0, 3.0, 1.0), array(3.0, 4.0, 3.0), array(1.0, 3.0, 1.0));
			// $divisor = array_sum(array_map('array_sum',$gaussian));
			// 	$col = imagecolorallocate($new, 255, 255, 255);
			// 	imagepolygon($new, $faces, 4, $col);
			// 	//imagecrop($new,$faces);
			// for($i = 0 ; $i < $itr ; $i++)
			// 	imageconvolution($crop, $gaussian, $divisor, 0);
		}
	}

	if($rotationOffset){ //rotate back so uploaded image will have the same format
		if($rotationOffset == 1){
			$image = imagerotate($image,90,0);
		}elseif($rotationOffset ==2){
			$image = imagerotate($image,180,0);
		}elseif($rotationOffset ==3){
			$image = imagerotate($image,270,0);
		}
	}
		// imagedestroy($image_r);
	
	//save image locally
	if($passed){
		echo 'yes';
		return $image;

	}else{
		echo 'no';
		return false;
	}
}

function pixelate($image, $pixelate_x = 12, $pixelate_y = 12){
    if(isset($image)){
        $height = imagesy($image);
        $width = imagesx($image);

        // start from the top-left pixel and keep looping until we have the desired effect
        for($y = 0; $y < $height; $y += $pixelate_y+1){
            for($x = 0; $x < $width; $x += $pixelate_x+1){
                // get the color for current pixel, make it legible 
                $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));

                // get the closest color from palette
                $color = imagecolorclosest($image, $rgb['red'], $rgb['green'], $rgb['blue']);
                // fill squares with specified width/height
                imagefilledrectangle($image, $x, $y, $x+$pixelate_x, $y+$pixelate_y, $color);
            }       
        }
    }
}

function scanForBackUpFolders($backup_dir){
    $backedup = array();
    if ($folder = opendir($backup_dir)) {
        while (false !== ($file = readdir($folder))) {
            if($file == "." || $file == ".."){
                continue;
            }

            if (is_dir("$backup_dir/".$file)) {
                $backedup[] = $file;
            }
        }
        closedir($folder);
    }

    return $backedup;
}

function scanForBackUpFiles($backedup, $backup_dir){
    $backedup_attachments = array();
    $parent_check         = array();  //THIS WILL BE USED IN THE POST HANDLER UGH
    foreach($backedup as $backup){
        //CHECK COUCH IF $backup exists in disc_users
        //if not then put it to couch
        $couch_url      = "http://".cfg::$couch_user.":".cfg::$couch_pw."@couchdb:5984";

        $walk_json      = $couch_url . "/".cfg::$couch_users_db."/" . $backup ;
        $check_walk_id  = get_head($walk_json);
        if(array_key_exists("error", $check_walk_id)){
            // DOESNT EXIST SO NEED TO UPLOAD TO disc_users
        }

        // for deleting
        // "<form method='POST'><input type='hidden' name='deleteDir' value='temp/$backup'/><input type='submit' value='Delete Directory'/></form></h3>";

        //check the photo attachments
        //push to couch if not in disc_attachment

        if ($folder = opendir('temp/'.$backup)) {
            while (false !== ($file = readdir($folder))) {
                if($file == "." || $file == ".."){
                    continue;
                }

                if(!strpos($file,".json")){
                    $backedup_attachments[] = $file;
                    $parent_check[$file]    = $backup;
                }
                $html[] =  "<li><a href='temp/$backup/$file' target='blank'>";
                $html[] =  $file;
                $html[] =  "</a></li>";
            }
            closedir($folder);
        }
    }
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
function upload_object($storageClient, $bucketName, $objectName, $source) {
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

function getGCPRestToken($keyPath, $scope){
    // putenv('GOOGLE_APPLICATION_CREDENTIALS='.$keyPath);
    $client             = new Google_Client();
    $client->setAuthConfigFile($keyPath);
    // $client->useApplicationDefaultCredentials();
    $client->addScope($scope);
    $auth               = $client->fetchAccessTokenWithAssertion();
    $access_token       = $auth["access_token"];
    return $access_token;
}

function restPushFireStore($firestore_url, $json_payload, $access_token){
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

function restDeleteFireStore($firestore_url, $access_token){
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

function formatUpdateWalkPhotos($photos,$transcriptions){
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
        $fields["geotag"]           = array("mapValue" => array("fields" => $geoFields));

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
        $fields["audios"]           = array("mapValue" => array("fields" => $audioFields));

        $tagFields = array();
        foreach($temp["tags"] as $tag){
            $tagFields[]  = array("stringValue" => $tag);
        }
        $fields["tags"] = array("arrayValue" => array("values" => $tagFields));
        $new_photos[]   = array("mapValue" => array("fields" => $fields));
    }

    return $new_photos;
}

function convertFSwalkId($old_id){
    $walk_parts     = explode("_",$old_id);
    return $walk_parts[0] ."_" . $walk_parts[1] . "_" . $walk_parts[3];
}

function setWalkFireStore($old_id, $details, $firestore=null){
    // FIRESTORE FORMAT walk_id
    $walk_parts     = explode("_",$old_id);

    // FIRESTORE FORMAT walk_id
    $walk_id        = $walk_parts[0] ."_" . $walk_parts[1] . "_" . $walk_parts[3];

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

function uploadCloudStorage($attach_id, $walk_id, $bucketName,  $storageCLient, $filepath=false){
    # UPLOAD TO CLOUD STORAGE
    $folder_components  = explode("_",$walk_id);

    $project_id         = $folder_components[0];
    $device_id          = $folder_components[1];
    $walk_ts            = $folder_components[3];
    $attachment_prefix  = "$project_id/$device_id/$walk_ts/";
    $file_suffix        = str_replace($walk_id."_","",$attach_id);
    
    $filepath           = !$filepath ? 'temp/'.$walk_id.'/'.$attach_id : $filepath;
    $new_attach_id      = $attachment_prefix . $file_suffix;

    //UPLOAD from TEMP DIR on DISK
    $uploaded           = upload_object($storageCLient, $bucketName, $new_attach_id, $filepath);

    return $uploaded;
}
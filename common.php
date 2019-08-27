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
$couch_attach_db = "disc_attachment";

date_default_timezone_set('America/Los_Angeles');
session_start(); //begins session

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

function printRow($doc, $active_pid){
    global $project_meta, $ap;

    $codeblock      = array();
    $i              = $doc["_id"];
    $photos         = $doc["photos"];
    $geotags        = $doc["geotags"];
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

    // filter out low accuracy
    $forjsongeo = array_filter($geotags,function($tag){
        return $tag["accuracy"] <= 50;
    });

    // use the unaccurate if nothing was marked high acc.
    if(empty($forjsongeo)){
        $forjsongeo = $geotags; 
    }

    // get STATIC google map , performance
    $geopoints = array();
    foreach($forjsongeo as $geotag){
        $geopoints[] = $geotag["lat"].",".$geotag["lng"];
    
    }
    $markers    = implode("|",$geopoints);
    $parameters = urlencode("icon:https://ourvoice-projects.med.stanford.edu/img/icon_small_blue_dot.png"."|".$markers);
    $mapurl     = 'https://maps.googleapis.com/maps/api/staticmap?size=420x300&zoom=16&markers='.$parameters."&key=".cfg::$gvoice_key;
    
    $json_geo    = json_encode($forjsongeo);

    $last4       = substr($doc["_id"],-4);
    $firstpart   = substr($doc["_id"],0, strlen($doc["_id"]) - 4);
    $walk_ts_sub = substr($doc["_id"],-13);
    $date_ts     = date("F j, Y", floor($walk_ts_sub/1000)) ;

    $codeblock[] = "<div class='user_entry'>";
    $codeblock[] = "<hgroup>";
    $codeblock[] = "<h4>(". $lang .") : 
    <b>".$date_ts."</b> 
    <i>$firstpart<strong>$last4</strong></i></h4>";
    $codeblock[] = "</hgroup>";

    $codeblock[] = "<div id='google_map_$i' class='gmap'><img src='$mapurl'/><a href='#' class='reload_map' data-mapgeo='$json_geo' data-mapi='$i'>Map look wrong?  Refresh with live map</a></div>";
    $codeblock[] = "<div class = 'location_alert_summary'></div>";

    
    $codeblock[] = "<section class='photo_previews'>";
    $codeblock[] = "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Delete This Walk</a>";

    $codeblock[] = "<a href='download_photos.php?doc_id=".$doc["_id"]."' class='btn btn-info export-photos' target='blank'>Download Photos</a>";
    
    if(!$processed){
        $codeblock[] = "<label class='data_processed' ><input type='checkbox' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'/> Data Processed?</label>";
    }
    $codeblock[] = "<a href='#' class='btn btn-primary export-pdf' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Walk Data PDF</a>";


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
            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=AIzaSyDCH4l8Q6dVpYgCUyO_LROnCuSE1W9cwak";
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
        <a href='$detail_url' target='_blank' rel='google_map_$i' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate' rev='$rotate'><img src='$photo_uri' /><span></span><b></b></a>
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

function printPhotos($doc){
    global $project_meta, $ap;

    $codeblock  = array();
    $i          = $doc["_id"];
    $photos     = $doc["photos"];

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

    $forjsongeo = array();
    $lang       = is_null($doc["lang"]) ? "EN" : $doc["lang"];

    // filter out low accuracy
    $last4       = substr($doc["_id"],-4);
    $firstpart   = substr($doc["_id"],0, strlen($doc["_id"]) - 4);
    $walk_ts_sub = substr($doc["_id"],-13);
    
    $date_ts     = date("F j, Y", floor($walk_ts_sub/1000)) ;
    $host        = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
    $url_path    = $host .dirname($_SERVER['PHP_SELF']); 
    if($url_path != '/') //if its empty then we dont want to add the additional / 
        $url_path = $host .dirname($_SERVER['PHP_SELF']) . '/';               

    foreach($photos as $n => $photo){
        if(is_null($photo)){
            continue;
        }

        $nogeo      = empty($photo["geotag"]) ? "nogeo" : "";
        $long       = isset($photo["geotag"]["longitude"]) ? $photo["geotag"]["longitude"] : null;
        $lat        = isset($photo["geotag"]["latitude"])  ? $photo["geotag"]["latitude"]  : null;
        $timestamp  = isset($photo["geotag"]["timestamp"]) ? $photo["geotag"]["timestamp"] : null;

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


        if($lat != 0 | $long != 0){
            $time = time();
            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=AIzaSyDCH4l8Q6dVpYgCUyO_LROnCuSE1W9cwak";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $responseJson = curl_exec($ch);
            curl_close($ch);
             
            $response = json_decode($responseJson);
            date_default_timezone_set($response->timeZoneId); 
        }

        $img_id         = $doc["_id"]."_".$photo_name;
        
        $file_uri       = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
        $thumb_uri      = $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
 
        $photo_uri      = getThumb($img_id,$thumb_uri,$file_uri);
        $blur_coord     = "facial_detection.php?uri=".$photo_uri;
        $detail_url     = "photo.php?_id=".$doc["_id"]."&_file=$photo_name";
        $attach_url     = "#";
        $pic_time       = date("g:i a", floor($timestamp/1000));
        
        $photo_tags     = isset($photo["tags"]) ? $photo["tags"] : array();
        $codeblock[]    = "<li id='".$doc["_id"]."_"."photo_".$n."' class = 'ui-widget-drop'><figure>";
        $codeblock[]    = "<ul>";
        foreach($photo_tags as $idx => $tag){
            $codeblock[]    = "<li class = '$tag'>$tag<a href='#' class='deletetag' data-deletetag='$tag' data-doc_id='".$doc["_id"]."' data-photo_i='$n'>x</a></li>";
        }
        $codeblock[]    = "</ul>";
        $codeblock[]    = "<a href='$detail_url' target='_blank'  data-time='".$pic_time."' data-date='".$date_ts."' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate walk_photo $nogeo' data-imgsrc='$photo_uri' rev='$rotate'><img src='$photo_uri' /><span></span><b></b><i></i></a>";
        
        $codeblock[]    = "</figure></li>";
    }
    return $codeblock;
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
                '<a href="summary.php?id='.$listid[$i].'"'.'>'.$ful .'</a></th>';
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

function getAllData(){
    $url            = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;
    $response       = doCurl($url);
    return json_decode($response,1);
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
                         continue;
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
//    print_rr('made to getCon');
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
//		print_rr($scale_pixels);
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
//			print_rr($pixel_count);
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

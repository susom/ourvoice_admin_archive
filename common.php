<?php
if (!file_exists("_config.php")) {
    exit("You must create a _config.php file from the template before this application can run properly");
}
// Load the configuration
require_once "_config.php";

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

function print_rr($ar){
    echo "<pre>";
    print_r($ar);
    echo "</pre>";
}

function cmp_date($a, $b){
    $a = str_replace('-', '/', $a); //have to convert to american time because of the strtotime func
    $b = str_replace('-', '/', $b);
    $c = strtotime($a)- strtotime($b);
        return (strtotime($a) < strtotime($b)) ? 1 : -1;
}

function urlToJson($url){
    if($url){
        $temp = doCurl($url);
        $temp = json_decode(stripslashes($temp),1);
        return $temp;
    }
}

function getFullName($data, $abv){
    foreach($data["project_list"] as $in){
        if(isset($in["project_id"]) && $in["project_id"] == $abv){
            return $in["project_name"];
        }
    }
}

function printRow($doc){
    global $project_meta, $ap;

    $codeblock  = array();
    $i          = $doc["_id"];
    $photos     = $doc["photos"];
    $geotags    = $doc["geotags"];
    $survey     = $doc["survey"];

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
    $forjsongeo = array_filter($geotags,function($tag){
        return $tag["accuracy"] <= 50;
    });

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
    
    $codeblock[] = "<div id='google_map_$i' class='gmap'></div>";
    
    $codeblock[] = "<section class='photo_previews'>";
    $codeblock[] = "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Delete This Walk</a>";
    $codeblock[] = "<h5>Photo Previews</h5>";
    $codeblock[] = "<div class='thumbs'>";
    $codeblock[] = "<ul>";

    $url_path    = $_SERVER['HTTP_ORIGIN'].dirname($_SERVER['PHP_SELF'])."/";

    foreach($photos as $n => $photo){
        if(is_null($photo)){
            continue;
        }

        $hasaudio   = !empty($photo["audio"]) ? "has" : "";
        $long       = isset($photo["geotag"]["longitude"]) ? $photo["geotag"]["longitude"]: 0;
        $lat        = isset($photo["geotag"]["latitude"])  ? $photo["geotag"]["latitude"] : 0;
        $timestamp  = $photo["geotag"]["timestamp"];
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

        $file_uri   = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
        $thumb_uri  = $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";

        // $photo_uri  = $file_uri;
        $photo_uri  = cacheThumb($ph_id,$thumb_uri);
        $detail_url = "photo.php?_id=".$doc["_id"]."&_file=$photo_name";

        $attach_url = "#";
        $audio_attachments = "";
        if(!empty($photo["audio"])){
            $num_audios = intval($photo["audio"]);
            $num        = $num_audios > 1 ? "<span>x$num_audios</span>" :"";
            $audio_attachments .= "<a class='audio $hasaudio'></a> $num";
        }
        $codeblock[] = "<li id='photo_$n'>
        <figure>
        <a href='$detail_url' target='_blank' rel='google_map_$i' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate' rev='$rotate'><img src='$photo_uri' /><span></span><b></b></a>
        <figcaption>
            <span class='time'>@".date("g:i a", floor($timestamp/1000))."</span>
            ".$goodbad."
            ".$audio_attachments."
        </figcaption>
        </figure></li>";
    }
    $codeblock[] = "</ul>";
    $codeblock[] = "</div>";
    $codeblock[] = "</section>";

    $codeblock[] = "<section class='survey_response'>";
    $codeblock[] = "<h5>Survey Responses</h5>";
    $codeblock[] = "<div class='survey'>";
    if(empty($survey)){
        $codeblock[] = "<p><i>No Survey Responses</i></p>";
    }

    //WHOOO THIS IS NOT GREAT
    if(isset($project_meta["template_type"]))
        $template_type = $project_meta["template_type"];
    else
        $template_type = 1;
   // $template_type  = $project_meta["template_type"];
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
        $codeblock[] = "</div>";
        $codeblock[] = "</section>";
        $codeblock[] = "</div>";
        $codeblock[] = "<script>$(document).ready(function(){ drawGMap($json_geo, '$i', 16);\n  });</script>";
        $codeblock[] = "<div class='$i' data-mapgeo='$json_geo'></div>";
        return $codeblock;
    }else{
        foreach($unique as $name => $value){
         
            $v = (!empty($tempsurvey[$name]["options"]))  ?  $tempsurvey[$name]["options"][$value] :$value;
            $codeblock[] = "<li><i>".$tempsurvey[$name]["label"]."</i> : <b>$v</b></li>";
        }
        $codeblock[] = "</ul>";
        $codeblock[] = "</div>";
        $codeblock[] = "</section>";
        $codeblock[] = "</div>";
        $codeblock[] = "<script>$(document).ready(function(){ drawGMap($json_geo, '$i', 16);\n  });</script>";
        $codeblock[] = "<div class='$i' data-mapgeo='$json_geo'></div>";
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
    $url_path    = $_SERVER['HTTP_ORIGIN'].dirname($_SERVER['PHP_SELF'])."/";
    foreach($photos as $n => $photo){
        if(is_null($photo)){
            continue;
        }

        $nogeo      = empty($photo["geotag"]) ? "nogeo" : "";
        $long       = $photo["geotag"]["longitude"];
        $lat        = $photo["geotag"]["latitude"];
        $timestamp  = $photo["geotag"]["timestamp"];

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
        $file_uri       = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
        $thumb_uri      = $url_path. "thumbnail.php?file=".urlencode($file_uri)."&maxw=140&maxh=140";
        // $photo_uri  = $file_uri;
        $photo_uri      = cacheThumb($ph_id,$thumb_uri);

        $detail_url     = "photo.php?_id=".$doc["_id"]."&_file=$photo_name";
        $attach_url     = "#";
        $pic_time       = date("g:i a", floor($timestamp/1000));
        
        $photo_tags     = isset($photo["tags"]) ? $photo["tags"] : array();
        $codeblock[]    = "<li id='".$doc["_id"]."_"."photo_".$n."' class = 'ui-widget-drop'><figure>";
        $codeblock[]    = "<ul>";
        foreach($photo_tags as $idx => $tag){
            $codeblock[]    = "<li>$tag<a href='#' class='deletetag' data-deletetag='$tag' data-doc_id='".$doc["_id"]."' data-photo_i='$n'>x</a></li>";
        }
        $codeblock[]    = "</ul>";
        $codeblock[]    = "<a href='$detail_url' target='_blank'  data-time='".$pic_time."' data-date='".$date_ts."' data-photo_i=$n data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate walk_photo $nogeo' data-imgsrc='$photo_uri' rev='$rotate'><img src='$photo_uri' /><span></span><b></b><i></i></a>";
        
        $codeblock[]    = "</figure></li>";
    }
    return $codeblock;
}

function filter_by_projid($view, $keys_array){ //keys array is the # integer of the PrID
    $qs         = http_build_query(array( 'key' => $keys_array ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/filter_by_projid/_view/".$view."?" .  $qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
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

function populateRecent($ALL_PROJ_DATA, $stor, $listid){
    $checkWeek = strtotime("-4 Week");
    $counter = 0;

    for($i = 0 ; $i < count($stor) ; $i++){
        $iter = 0;
        $ful = getFullName($ALL_PROJ_DATA,$listid[$i]);
        rsort($stor[$listid[$i]]); //sort each element's timestamps
        
    
        while(!empty($stor[$listid[$i]][$iter]) && $iter < 1) //display only the most recent update per proj 
        {
            if(($stor[$listid[$i]][$iter]/1000) > $checkWeek){
                $counter++;
                echo '<tr>';
                echo '<th style = "font-weight: normal">'. "<strong>(".$listid[$i]. ")</strong> " . $ful . '</th>';
                echo '<th style = "font-weight: normal">'.gmdate("Y-m-d", $stor[$listid[$i]][$iter]/1000).'</th>';
                echo '</tr>';
            }

            $iter++;
        }
        
    }//for
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
    if(!file_exists($localthumb)){
        $ch         = curl_init($thumb_uri);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw        = curl_exec($ch);
        // $errornum   = curl_errno($ch);
        // $info       = curl_getinfo($ch);
        curl_close($ch);

        $fp         = fopen($localthumb,'x');
        fwrite($fp, $raw);
        fclose($fp);
    }
    return $thumb_uri;
}
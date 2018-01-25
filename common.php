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
        if($in["project_id"] == $abv)
            return $in["project_name"];
    }
}

function printRow($doc){
    global $project_meta;

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
    $json_geo   = json_encode($forjsongeo);

    $last4       = substr($doc["_id"],-4);
    $firstpart   = substr($doc["_id"],0, strlen($doc["_id"]) - 4);
    $codeblock[] = "<div class='user_entry'>";
    $codeblock[] = "<hgroup>";
    $codeblock[] = "<h4>(". $lang .") : 
    <b>".date("F j, Y", floor($doc["geotags"][0]["timestamp"]/1000))."</b> 
    <i>$firstpart<strong>$last4</strong></i></h4>";
    $codeblock[] = "</hgroup>";
    
    $codeblock[] = "<div id='google_map_$i' class='gmap'></div>";
    
    $codeblock[] = "<section class='photo_previews'>";
    $codeblock[] = "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' data-rev='".$doc["_rev"]."'>Delete This Walk</a>";
    $codeblock[] = "<h5>Photo Previews</h5>";
    $codeblock[] = "<div class='thumbs'>";
    $codeblock[] = "<ul>";

    foreach($photos as $n => $photo){
        if(is_null($photo)){
            continue;
        }

        $hasaudio   = !empty($photo["audio"]) ? "has" : "";
        $long       = $photo["geotag"]["longitude"];
        $lat        = $photo["geotag"]["latitude"];
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
        $photo_uri  = "passthru.php?_id=".$ph_id."&_file=$filename" . $old;
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
    $tempsurvey = array();
    foreach($project_meta["surveys"] as $s){
        $tempoptions = array();
        if(isset($s["options"])){
            foreach($s["options"] as $o){
                $tempoptions[$o["value"]] = $o["en"]; 
            }
        }else{
            $tempoptions = null;
        }
        $tempsurvey[$s["name"]] = array(
                "label" => $s["label"]["en"]
                ,"options" =>  $tempoptions
            );
    }

    $unique = array();
    foreach($survey as $q){
        $unique[$q["name"]] = $q["value"];
    }
    $codeblock[] = "<ul>";
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

function filter_by_projid($view, $keys_array){ //keys array is the # integer of the PrID
    $qs         = http_build_query(array( 'key' => $keys_array ));
    $couch_url  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . "_design/filter_by_projid/_view/".$view."?" .  $qs;
    $response   = doCurl($couch_url);
    return json_decode($response,1);
}




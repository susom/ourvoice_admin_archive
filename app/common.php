<?php
if (!file_exists(__DIR__ . "/_config.php")) {
    exit("You must create a _config.php file from the template before this application can run properly");
}

// Load the configuration
//require_once("../../secrets/_config_prod.php");
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/_config.php";
require_once __DIR__ . "/_datastore.php";

$google_bucket  = "ov_walk_files";
$ds             = new Datastore();
//$ds->hello();

//START TIMER FOR PAGE LOAD
$start_time	= microtime(true);

//TODO REMOVE AFTER UPDATE THE SERVER _config.php
$couch_attach_db    = "disc_attachment";
$masterblaster      = cfg::$master_pw;

date_default_timezone_set('America/Los_Angeles');
ini_set("session.cookie_httponly", 1);
session_start();

if(isset($_GET["clearsession"])){
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(),'',0,'/');
    header("location:/summary.php");
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
    if(!isset($data["rows"]) || $data["rows"] == null)
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
                }
                $backedup[] = $file;
            }
        }
        closedir($folder);
    }

    return $backedup;
}

function deleteDirectory($dir) {
    system('rm -rf ' . escapeshellarg($dir), $retval);
    return $retval == 0; // UNIX commands return zero on success
}

// SOMEHTML GENERATION
function printRow($doc, $i){
    global $project_meta, $ap, $ds;

    $last4          = substr($doc["_id"],-4);
    $pcode          = $doc["project_id"];
    $codeblock      = array();

    $photos         = $doc["photos"];

    $geotags        = !empty($doc["geotags"]) ? $doc["geotags"] : array();
    $survey         = !empty($doc["survey"]) ? $doc["survey"] : array();
    $processed      = !empty($doc["data_processed"]) ? $doc["data_processed"] : null;

    $forjsongeo     = array();
    $lang           = is_null($doc["lang"]) ? "EN" : $doc["lang"];

    // if no walk geo, but has some for indy  photos (indpendent api calls)
    if (empty($geotags)){
        foreach($photos as $photo){
            if(!empty($photo["geotag"])){
                $geotags[] = array("geotag" => $photo["geotag"]);
                break;
            }
        }
    }else{
        $check_structure = current($geotags);
        //NEED A UNIFORM STRUCTURE BETWEEN OLD CORDOVA APP AND NEW PWA APP DATA
        if(!array_key_exists("geotag", $check_structure)){
            $geotags = array_map(function($element) {
                $newElement = ['geotag' => []];
                foreach ($element as $key => $value) {
                    if ($key === 'lat') {
                        $newElement['geotag']['latitude'] = $value;
                    } elseif ($key === 'lng') {
                        $newElement['geotag']['longitude'] = $value;
                    } else {
                        $newElement['geotag'][$key] = $value;
                    }
                }
                return $newElement;
            }, $geotags);
        }
    }

    // filter out low accuracy
    $forjsongeo = array_filter($geotags,function($tag){
        if(isset($tag["geotag"])){
            $tag = $tag["geotag"];
        }

        return isset($tag["accuracy"]) ? $tag["accuracy"] <= 50 : true;
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

    // Reset keys to be zero-based
    $originalArray = array_values($forjsongeo);

    // Re-index to start from 1
    $forjsongeo = [];
    foreach ($originalArray as $index => $value) {
        $forjsongeo[$index + 1] = $value;
    }

    foreach($forjsongeo as $geotag){
        if(isset($geotag["geotag"])){

            $geotag = $geotag["geotag"];
            $coord  = null;

            if (isset($geotag['latitude']) && isset($geotag['longitude'])) {
                $coord = $geotag['latitude'] . "," . $geotag['longitude'];
            } elseif (isset($geotag['lat']) && isset($geotag['lng'])) {
                $coord = $geotag['lat'] . "," . $geotag['lng'];
            }

            if($coord){
                if($n%$n_jump == 0){
                    $path_coords[] = $coord;
                }
                $geopoints[] = $coord;
                $n++;
            }
        }
    }


    $spread         = implode("|",$path_coords);
    $mapurl         = 'https://maps.googleapis.com/maps/api/staticmap?key='.cfg::$gmaps_key.'&size=420x300&zoom=16&path=color:0x0000FFd7|weight:3|' . $spread;
    $json_geo       = json_encode($forjsongeo);

    $firstpart   = substr($doc["_id"],0, strlen($doc["_id"]) - 4);
    $walk_ts_sub = substr($doc["_id"],-13);
    $date_ts     = date("F j, Y", floor($walk_ts_sub/1000)) ;

    $codeblock[] = "<div class='user_entry'>";
    $codeblock[] = "<hgroup>";
    $codeblock[] = "<h4>(". $lang .") : 
    <b>".$date_ts."</b> 
    <i><strong>$last4</strong></i></h4>";
    $codeblock[] = "</hgroup>";

//    $codeblock[] = "<div data-mapid='$i' class = 'gmap summary location_alert'></div>";
    $codeblock[] = "<div id='google_map_{$last4}' class='gmap'><img src='$mapurl'/><a href='#' class='reload_map' data-mapgeo='$json_geo' data-mapi='$i' data-walkid='$last4'>Map look wrong?  Refresh with live map</a></div>";
    $codeblock[] = "<div data-walkid='$last4' class = 'location_alert_summary'></div>";

    
    $codeblock[] = "<section class='photo_previews'>";
    $codeblock[] = "<a href='#' class='btn btn-danger deletewalk' data-id='".$doc["_id"]."' >Delete This Walk</a>";

    $codeblock[] = "<a href='download_photos.php?doc_id=".$doc["_id"]."' class='btn btn-info export-photos' target='blank'>Download Photos</a>";
    
    if(!$processed){
        $codeblock[] = "<label class='data_processed' ><input type='checkbox' data-id='".$doc["_id"]."'/> Data Processed?</label>";
    }
    $codeblock[] = "<a href='#' class='btn btn-primary export-pdf' data-pcode='".$pcode."' data-active_pid='".$pcode."' data-id='".$doc["_id"]."' >Print View</a>";


    $codeblock[] = "<h5>Photo Previews (".count($photos).")</h5>";
    $codeblock[] = "<div class='thumbs'>";
    $codeblock[] = "<ul>";
    $host        = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
    $url_path    = $host .dirname($_SERVER['PHP_SELF'])."/";
    // $url_path    = $_SERVER['HTTP_ORIGIN'].dirname($_SERVER['PHP_SELF'])."/";
    $count_empty = 0;


    foreach($photos as $n => $photo){

        if(is_null($photo) || isset($photo["_deleted"])){
            continue;
        }

        $hasaudio   = !empty($photo["audios"]) ? "has" : "";

        if(isset($photo["geotag"]["lng"]) &&  isset($photo["geotag"]["lat"])){
            $long   = $photo["geotag"]["lng"];
            $lat    = $photo["geotag"]["lat"];
        }else{
            $long   = 0;
            $lat    = 0;
        }
        $timestamp = isset($photo["geotag"]["timestamp"])
            ? $photo["geotag"]["timestamp"]
            : (isset($photo["timestamp"]) ? $photo["timestamp"] : $doc["timestamp"]);

        $goodbad    = "";
        if($photo["goodbad"] > 1){
            $goodbad  .= "<span class='goodbad good'></span>";
        }
        if($photo["goodbad"] == 1 || $photo["goodbad"] == 3){
            $goodbad  .= "<span class='goodbad bad'></span>";
        }

        $rotate     = isset($photo["rotate"]) ? $photo["rotate"] : 0;
        $photo_name = $photo["name"];

        //TODO FOR MULTIPLE VERSIONS OF ATTACHMENT STORAGE
        if(array_key_exists("name",$photo)){
            $filename   = $photo["name"];
            $ph_id      = $i . "_" .$filename;
        }

        $img_id         = $doc["_id"]."_".$photo_name;

        //https://storage.googleapis.com/$google_bucket/$project_id/$uuid/$ts/$photo_name
//        $uuid           = $doc["device"]["uid"];
//        $walk_ts        = $doc["timestamp"];

        $transform  = array("transform" => "thumbnail", "rotate" => $rotate);
        $file_uri   = $ds->getStorageFile(cfg::$gcp_bucketName, $doc["_id"], $photo_name, $transform);
        $photo_uri  = $file_uri;
        $detail_url = "photo.php?_id=".$doc["_id"]."&_file=$photo_name";

        $attach_url         = "#";
        $audio_attachments  = "";
        $text_comment       = "";

        if(!empty($photo["text_comment"])){
            $text_comment  = "<a class='audio keyboard'></a> ";
        }

        if(!empty($photo["audios"])){
            $num_audios = count($photo["audios"]);
            $num        = $num_audios > 1 ? "<span>x$num_audios</span>" :"";
            $audio_attachments .= "<a class='audio $hasaudio'></a> $num";
        }

//        if($lat != 0 | $long != 0){
//            $time = time();
//            $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$time&key=" . cfg::$gmaps_key;
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            $responseJson = curl_exec($ch);
//            curl_close($ch);
//            $response = json_decode($responseJson);
//            date_default_timezone_set($response->timeZoneId);

        $codeblock[] = "
        <li data-phid='$img_id'>
        <div class = 'load'>
            <div class = 'progress'></div>
        </div>

        <figure>
        <a href='$detail_url' rel='google_map_{$last4}' data-photo_i=$n data-filename='$photo_name' data-doc_id='".$doc["_id"]."' data-long='$long' data-lat='$lat' class='preview rotate' rev='$rotate'><img src='$photo_uri' /><span></span><b></b></a>
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

    $codeblock[] = "</div>";
    return $codeblock;
}

function printPhotos($photo, $_id, $n, $old=null, $txns=null){
    global $ds;

    $codeblock  = array();
    if(array_key_exists("_deleted", $photo)){
        return $codeblock;
    }

    $walk_ts_sub = !empty($photo["geotag"]["timestamp"]) ? $photo["geotag"]["timestamp"] : null;
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
        $lat    = isset($photo["geotag"]["latitude"]) ? $photo["geotag"]["latitude"] : $photo["geotag"]["lat"];
        $long   = isset($photo["geotag"]["longitude"]) ? $photo["geotag"]["longitude"] : $photo["geotag"]["lng"];
    }

    $timestamp  = !empty($photo["geotag"]["timestamp"])  ? $photo["geotag"]["timestamp"] : null;
    $txt        = array_key_exists("text_comment",$photo) ? $photo["text_comment"] : null;

    $rotate     = !empty($photo["rotate"]) ? $photo["rotate"] : 0;
    $filename   = $photo["name"];
    $ph_id      = !empty($photo["geotag"]) ? $photo["geotag"]["photo_id"] : null;

    $transform      = array("transform" => "photo_detail", "rotate" => $rotate);
    $file_uri 	    = $ds->getStorageFile(cfg::$gcp_bucketName, $_id, $filename, $transform);
    $thumb_uri      = !empty($photo["geotag"]) ? $photo["geotag"]["photo_src"] : null;;
    $photo_uri      = $thumb_uri;

    $detail_url     = "photo.php?_id=".$_id."&_file=$filename";
    $pic_time       = date("g:i a", floor($timestamp/1000));
    $photo_tags     = isset($photo["tags"]) ? $photo["tags"] : array();
    
    $photoblock["id"]            = $_id."_".$photo["name"];
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
    $photoblock["full_img"]      = $file_uri;
    $photoblock["transcriptions"]  = $txns;


    array_push($codeblock, $photoblock);
    return $codeblock;
}

function populateRecent($walk_times){
    global $ds;
    foreach($walk_times as $proj_id =>  $walk_time){
        $full_name = $ds->getFullProjectName($proj_id);
        echo '<tr>';
        echo '<th style = "font-weight: normal">'. "<strong>(".$proj_id. ")</strong> " .
            '<a class="gotosumm" data-pid="'.$proj_id.'" href="summary.php?id='.$proj_id.'"'.'>'.$full_name .'</a></th>';
        echo '<th style = "font-weight: normal">'.gmdate("Y-m-d", $walk_time/1000).'</th>';
        echo '</tr>';
    }
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
    $audios     = $photo_o["audios"];
    $_id        = $photo_o["id"];
    $photo_tags = $photo_o["tags"];
    $doc_id     = $photo_o["doc_id"];
    $photo_i    = $photo_o["n"];
    $detail_url = $photo_o["detail_url"];
    $nogeo      = $photo_o["nogeo"];
    $goodbad    = $photo_o["goodbad"];
    $text_com   = $photo_o["text_comment"];
    $fullimg    = $photo_o["full_img"];
    $imgsrc     = $photo_o["photo_uri"];
    $platform   = null;//$photo_o["platform"];
    $rotation   = $photo_o["rotate"];

    if(!empty($audios)){
        $temp = array();
        foreach($audios as $audio_key => $txn){
            $temp[] = $txn;
        }
        $txns = json_encode($temp);
    }

    $html_li  = "";
    $html_li .= "<li id='".$_id."' class='ui-widget-drop' data-phid='".$_id."'><figure>";
    $html_li .= "<ul>";
    foreach($photo_tags as $idx => $tag){
        $html_li .= "<li class = '$tag'>$tag<a href='#' class='deletetag' data-deletetag='$tag' data-doc_id='".$doc_id."' data-photo_i='".$photo_i."'>x</a></li>";
    }
    $html_li .= "</ul>";
    $html_li .= "<a href='".$detail_url."' target='_blank' class='preview rotate walk_photo ".$nogeo."' 
    data-photo_i='".$photo_i."' 
    data-goodbad=".$goodbad." 
    data-textcomment='".$text_com."'
    data-audiotxns='".$txns."' 
    data-doc_id='".$doc_id."' 
    data-fullimgsrc='".$fullimg."' 
    data-imgsrc='".$imgsrc."' 
    data-platform='".$platform."' 
    rev='".$rotation."'><img src='".$imgsrc."' /><span></span><b></b><i></i><em></em></a>";
    
    $html_li .= "</figure></li>";
    return $html_li;
}


//SOME PHOTO PAGE FUNCTIONALITY
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

function getConvertedAudio($attach_url){
    //FIRST DOWNLOAD THE AUDIO FILE to TEMP;

    $ch             = curl_init($attach_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data           = curl_exec($ch);
    $errors         = curl_error($ch);
    curl_close ($ch);

    $newAudioPath   = "";
    if(empty($errors)){
        //THEN EXTRACT THE FILE NAME
        $split      = explode("/ov_walk_files/",$attach_url);
        $filename   = str_replace("/","_", $split[1]);

        $localfile  = "./temp/$filename";
        $file       = fopen($localfile, "w+");
        fputs($file, $data);
        fclose($file);

        //THEN CONVERT THE AUDIO
        $newAudioPath = $localfile;
    }
    return $newAudioPath;
}


use Google\Cloud\Speech\SpeechClient;

function transcribeAudio($attach_url, $lang=null){
    $gcp_lang = array(
        "en"    => "en-US",
        "es"    => "es-MX",
        "nl"    => "nl-NL",
        "am"    => "am-ET",
        "ar"    => "ar-AE",
        "tw"    => "zh-TW",
        "ch"    => "zh-CN",
        "pt"    => "pt-PT",
        "iw"    => "iw-IL",
        "fr"    => "fr-FR",
        "sw"    => "sv-SE",
        "ru"    => "ru-RU",
        "th"    => "th-TH"
    );

    $txn_lang   = !empty($lang) && isset($gcp_lang[$lang]) ? $gcp_lang[$lang] : "en-US";
    $localfile  = getConvertedAudio($attach_url);
    $newfile    = $localfile;

    $newfile    = "./temp/sample.mp3";

    $split      = explode("." , $attach_url);
    $ext        = strtoupper(array_pop($split));

    //Convert to base 64 for google's API
    $for_txn    = file_get_contents($newfile);
    $for_txn    = base64_encode($for_txn);

    // WE NEED TO json_encode the base64 of the audio file
    // Set some options
    $data = array(
        "config" => array(
            "encoding" => $ext,
            "languageCode" => $txn_lang
        ),
        "audio" => array(
            "content" => $for_txn
        )
    );
    $data_string = json_encode($data);

    //POST to google's service
    $ch = curl_init('https://speech.googleapis.com/v1p1beta1/speech:recognize?key='.cfg::$gvoice_key);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
    );
    $resp = curl_exec($ch);
    curl_close($ch);

    $resp = json_decode($resp,1);

    print_rr($resp); //error here, response doesnt have an audio file to process results in ERROR

    return;
    $count      = 0;
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
        if($confidence > 0.7) {
            return $data;
        }
    }

    return null;
}

function appendConfidence($attach_url){
    $split          = explode("=",$attach_url);
    $filename       = $split[count($split) -1];
    $full_proj_code = explode("_audio",$split[1]);

    $url            = cfg::$couch_url . "/" . cfg::$couch_users_db . "/" . $full_proj_code[0];
    $response       = $ds->doCurl($url);
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
    $result = $ds->doCurl($url);
    $meta = json_decode($result,true);

    $picture = $ds->doCurl($url . '/' . $photo_name); //returns the actual image
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
    $resp = $ds->postData('https://vision.googleapis.com/v1/images:annotate?key='.cfg::$gvoice_key,$data);
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
        return $image;
    }else{
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

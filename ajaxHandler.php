<?php
require_once "common.php";

if(!empty($_POST["action"])){
    $request    = filter_var($_POST["action"], FILTER_SANITIZE_STRING);
	$url        = isset($_POST["url"])     ? filter_var($_POST["url"], FILTER_SANITIZE_ENCODED);
    $lang       = isset($_POST["lang"])    ? filter_var($_POST["lang"], FILTER_SANITIZE_STRING);
	
    switch($request){
        case 'convertAudio':
            if(!empty($url)){  
                $pathToAudio = getConvertedAudio($url,$lang);
                echo $pathToAudio;
            }
        break;
    }

}else{
    return 'no data provided';
}





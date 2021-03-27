<?php
require_once "common.php";
$request = array_keys($_REQUEST)[0]; //get the request sender
$data = $_REQUEST[$request]; //get the data associated with directive

if(!empty($request)){
    switch($request){
        case 'convertAudio':
            if(!empty($data['url']))  
                $pathToAudio = getConvertedAudio($data['url'],$data["lang"]);
                echo $pathToAudio;
            break;
    }

}else{
    return 'no data provided';
}





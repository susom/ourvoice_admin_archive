<?php
require 'vendor/autoload.php';
require_once "common.php";
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
//exec from transcribeAudio passes in name of audio file as parameter
if(isset($_SERVER['argv'][1]))
    $filename = $_SERVER['argv'][1];

// if (function_exists('curl_file_create')) { // php 5.5+
//     $cFile = curl_file_create('./'.$filename);
// } else { // 
//     $cFile = '@' . realpath('./'.$filename);
// }
if (function_exists('curl_file_create')) { // php 5.5+
      $cFile = curl_file_create("./".$filename);
    } else { // 
      $cFile = '@' . realpath("./".$filename);
    }
    print_r($cFile);
$postfields = array(
    "file"     => $cFile,
    "format"   => "flac"
);
    // CURL OPTIONS
    // POST IT TO FFMPEG SERVICE
//$postfields = base64_encode($postfields);
//$data_string = json_encode($postfields);                                                              
echo "sending off the data string@@@@@@@@";
$ffmpeg_url = cfg::$ffmpeg_url; 
$ch = curl_init($ffmpeg_url);
curl_setopt($ch, CURLOPT_POST, 'POST'); //PUT to UPDATE/CREATE IF NOT EXIST
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
//    'Content-Type: application/json',                                                                                
//    'Content-Length: ' . strlen($data_string))                                                                       
// ); 
$response = curl_exec($ch);
$repsonse = json_decode($response);
// echo $response;

curl_close($ch);
$repsonse = base64_encode($response);
// print_rr($response);

// $ffmpeg = FFMpeg\FFMpeg::create(array(
//     'ffmpeg.binaries' => '/usr/local/bin/ffmpeg',
//     'ffprobe.binaries' => '/usr/local/bin/ffprobe',
//     'timeout' => 3600,
//     'ffmpeg.threads' => 12
// ));


// $audio = $ffmpeg->open($filename);
// $format = new FFMpeg\Format\Audio\Flac();
// $format->on('progress', function ($audio, $format, $percentage) {
//     echo "$percentage % transcoded";
// });

// $format
//     ->setAudioChannels(1)
//     ->setAudioKiloBitrate(256);

// $audio->save($format, 'track.flac');

// $flac = file_get_contents('track.flac');
// $flac = base64_encode($flac);

// Set some options - we are passing in a useragent too here
$data = array(
    "config" => array(
        "encoding" => "FLAC",
        "languageCode" => "en-US"
    ),
   "audio" => array(
        "content" => $response
    )
);
// print_rr($data);
$data_string = json_encode($data);                                                              

$ch = curl_init('https://speech.googleapis.com/v1/speech:recognize?key='.cfg::$gvoice_key);                                                                      
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
print_r($resp);
if(!empty($resp)){
    foreach($resp["results"] as $results){
        $transcript = $transcript . $results["alternatives"][0]["transcript"];
    }
    // $transcript = $resp["results"][0]["alternatives"][0]["transcript"];
    $confidence = $resp["results"][0]["alternatives"][0]["confidence"];

    print_r($transcript);
    // print_r($confidence);
}
unlink($filename);
// unlink('track.flac');


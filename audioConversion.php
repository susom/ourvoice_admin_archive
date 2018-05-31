<?php
require 'vendor/autoload.php';
require_once "common.php";
//exec from transcribeAudio passes in name of audio file as parameter
if(isset($_SERVER['argv'][1]))
    $filename = $_SERVER['argv'][1];

$temp = file_get_contents($filename);
// echo "Hi";
$ffmpeg = FFMpeg\FFMpeg::create(array(
    'ffmpeg.binaries' => '/usr/local/bin/ffmpeg',
    'ffprobe.binaries' => '/usr/local/bin/ffprobe',
    'timeout' => 3600,
    'ffmpeg.threads' => 12
));


$audio = $ffmpeg->open($filename);
$format = new FFMpeg\Format\Audio\Flac();
$format->on('progress', function ($audio, $format, $percentage) {
    echo "$percentage % transcoded";
});

$format
    ->setAudioChannels(1)
    ->setAudioKiloBitrate(256);

$audio->save($format, 'track.flac');

$flac = file_get_contents('track.flac');
$flac = base64_encode($flac);

// Set some options - we are passing in a useragent too here
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
// print_r($resp);
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
unlink('track.flac');


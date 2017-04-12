<?php

if (!file_exists("_config.php")) {
    exit("You must create a _config.php file from the template before this application can run properly");
}
// Load the configuration
require_once "_config.php";


session_start();





function doCurl($url, $data = null, $method = null, $username = null, $password = null) {
    if (empty($username)) $username = cfg::$couch_user;
    if (empty($password)) $password = cfg::$couch_pw;

    $process = curl_init($url);
    curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

    if (!empty($data)) curl_setopt($process, CURLOPT_POSTFIELDS, $data);
    if (!empty($method)) curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);

    $result = curl_exec($process);
    curl_close($process);
    return $result;
}






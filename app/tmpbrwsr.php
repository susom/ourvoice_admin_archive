<?php

//test check in
if(!empty($_GET["irvin"]) && $_GET["irvin"] == "showme") {
    $starting_path  = "./temp";
    $subs_files     = recurseScanDir($starting_path);

    echo "<h1>Data Currently In Temp Store</h1>";
    if(empty($subs_files)){
        echo "<p>no data in temp currently</p>";
    }else{
        foreach($subs_files as $dir => $files_array){
            echo "<details>";
            echo "<summary>$dir</summary>";
            echo "<ul>";
            foreach($files_array as $file){
                echo "<li><a href='$starting_path/$dir/$file'>$file</a></li>";
            }
            echo "</ul>";
            echo "</details>";
        }
    }
}

function recurseScanDir( $path ){
    $file_array = array();

    if(file_exists($path)){
        $scanpath   = scandir($path);

        $files      = array_diff($scanpath, array('.', '..'));
        foreach ($files as $file) {
            if(strpos($file,"DS_Store") > -1){
                continue;
            }

            $filepath = "$path/$file";
            if (is_dir($filepath)) {
                $file_array[$file] = recurseScanDir($filepath);
            }else{
                array_push($file_array, $file);
            }
        }
    }

    return $file_array;
}
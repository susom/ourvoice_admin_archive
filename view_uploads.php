<?php
require_once("common.php");

$backedup = array();
if ($folder = opendir('temp')) {
    while (false !== ($file = readdir($folder))) {
        if($file == "." || $file == ".."){
            continue;
        }

        if (is_dir("temp/".$file)) {
            $backedup[] = $file;
        }
    }
    closedir($folder);
}

echo "<h2>Our Voice Emergency Back Up Folder</h2>";
echo "<ul>";
foreach($backedup as $backup){
    echo "<li><h3>$backup</h3>";
    echo "<ul>";
        if ($folder = opendir('temp/'.$backup)) {
            while (false !== ($file = readdir($folder))) {
                if($file == "." || $file == ".."){
                    continue;
                }

                echo "<li><a href='temp/$backup/$file' target='blank'>";
                echo $file;
                echo "</a></li>";
            }
            closedir($folder);
        }
    echo "</ul>";
    echo "</li>";
}
echo "</ul>";
exit;

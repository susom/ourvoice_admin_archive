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
$backedup_attachments = array();
$parent_check         = array();  //THIS WILL BE USED IN THE POST HANDLER UGH
foreach($backedup as $backup){
    //CHECK COUCH IF $backup exists in disc_users
    //if not then put it to couch
    
    //check the photo attachments 
    //push to couch if not in disc_attachment
    
    echo "<li><h3>$backup <form method='POST'><input type='hidden' name='deleteDir' value='temp/$backup'/><input type='submit' value='Delete Directory'/></form></h3>";
    echo "<ul>";
        if ($folder = opendir('temp/'.$backup)) {
            while (false !== ($file = readdir($folder))) {
                if($file == "." || $file == ".."){
                    continue;
                }
                
                if(!strpos($file,".json")){
                    $backedup_attachments[] = $file;
                    $parent_check[$file]    = $backup;
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

$backup_url         = cfg::$couch_url . "/" . cfg::$couch_users_db . "/_all_docs";
$backup_keys        = json_encode(array("keys" => $backedup));
$backup_attach_url  = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/_all_docs";
$backup_attach_keys = json_encode(array("keys" => $backedup_attachments));
echo "<form method='POST'>";
echo "<input type='hidden' name='syncToCouch' value='1'/>";
echo "<input type='hidden' name='backups' value='$backup_keys'/>";
echo "<input type='hidden' name='backups_attach' value='$backup_attach_keys'/>";
echo "<input type='hidden' name='backups_url' value='$backup_url'/>";
echo "<input type='hidden' name='backups_attach_url' value='$backup_attach_url'/>";
echo "<input type='submit' value='save to couch'/>";
echo "</form>";

function uploadAttach($couchurl, $filepath, $content_type){
    $data       = file_get_contents($filepath);
    $ch         = curl_init();

    $username   = cfg::$couch_user;
    $password   = cfg::$couch_pw;
    $options    = array(
        CURLOPT_URL             => $couchurl,
        CURLOPT_USERPWD         => $username . ":" . $password,
        CURLOPT_SSL_VERIFYPEER  => FALSE,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_CUSTOMREQUEST   => 'PUT',
        CURLOPT_HTTPHEADER      => array (
            "Content-Type: ".$content_type,
        ),
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => $data
    );
    curl_setopt_array($ch, $options);
    $info       = curl_getinfo($ch);
    print_rr($info);
    $err        = curl_errno($ch);
    print_rr($err);
    $response   = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function deleteDirectory($dir) {
    print_rr($dir);
    system('rm -rf ' . escapeshellarg($dir), $retval);
    return $retval == 0; // UNIX commands return zero on success
}

if(isset($_POST["syncToCouch"])){
    $backup_keys            = $_POST["backups"];
    $backup_attach_keys     = $_POST["backups_attach"];
    $backup_url             = $_POST["backups_url"];
    $backup_attach_url      = $_POST["backups_attach_url"];

    $backup_response        = json_decode(doCurl($backup_url, $backup_keys, "POST"),1);
    $backup_attach_response = json_decode(doCurl($backup_attach_url."?include_docs=true", $backup_attach_keys, "POST"),1);

    $walks_url = cfg::$couch_url . "/" . cfg::$couch_users_db ;
    foreach($backup_response["rows"] as $row){
        if(isset($row["error"]) && $row["error"] == "not_found"){
            $payload  = file_get_contents('temp/'.$row["key"].'/'.$row["key"].'.json');
            $response   = doCurl($walks_url, $payload, 'POST');
            print_rr($response);
        }
    }

    $attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db;
    foreach($backup_attach_response["rows"] as $row){
        if(isset($row["error"]) && $row["error"] == "not_found"){
            // 2 step process 
            // first , create the data entry
            $payload    = json_encode(array("_id" => $row["key"]));
            $response   = doCurl($attach_url, $payload, 'POST');
            $response   = json_decode($response,1);
            
            // next upload the attach
            $parent_dir     = $parent_check[$row["key"]];
            $file_i         = str_replace($parent_dir."_","",$row["key"]);           
            $couchurl       = $attach_url."/".$row["key"]."/".$file_i."?rev=".$response["rev"];
            $filepath       = 'temp/'.$parent_dir.'/'.$row["key"];
            $content_type   = strpos($row["key"],"photo") ? 'image/jpeg' : 'audio/wav';
            $response       = uploadAttach($couchurl, $filepath, $content_type);
            print_rr($response);
        }
    }
}elseif(isset($_POST["deleteDir"])){
    $rmdir = $_POST["deleteDir"];
    deleteDirectory($rmdir);
    header("location:view_uploads.php");
}
?>
<style>
h3 form{ display:inline-block; }
</style>
<?php
require_once("common.php");

$backedup = scanForBackUpFolders("temp");

$html                   = array();
$html[]                 =  "<h2>Our Voice Emergency Back Up Folder</h2>";
$html[]                 =  "<ul>";
$backedup_attachments   = array();
$parent_check           = array();  //THIS WILL BE USED IN THE POST HANDLER UGH
foreach($backedup as $backup){
    $backedup_attachments_walk   = array();
    //CHECK COUCH IF $backup exists in disc_users
    //if not then put it to couch
    
    //check the photo attachments 
    //push to couch if not in disc_attachment
    $html[] =  "<li><h3>$backup</h3>";
    $html[] =  "<form method='POST'><input type='hidden' name='deleteDir' value='temp/$backup'/><input type='submit' value='Delete Directory'/></form>";

    $html[] =  "<ul>";
        if ($folder = opendir('temp/'.$backup)) {
            while (false !== ($file = readdir($folder))) {
                if($file == "." || $file == ".." || $file == ".DS_Store" || $file == "thumbnail"){
                    continue;
                }
                
                if(!strpos($file,".json")){
                    $backedup_attachments[]         = $file;
                    $backedup_attachments_walk[]    = $file;
                    $parent_check[$file]            = $backup;
                }
                $html[] =  "<li><a href='temp/$backup/$file' target='blank'>";
                $html[] =  $file;
                $html[] =  "</a></li>";
            }
            closedir($folder);
        }
    $html[] =  "</ul>";
    $html[] =  "</li>";

    $backup_url         = cfg::$couch_url . "/" . cfg::$couch_users_db . "/_all_docs";
    $backup_keys        = json_encode(array("keys" => $backup));
    $backup_attach_url  = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/_all_docs";
    $backup_attach_keys = json_encode(array("keys" => $backedup_attachments_walk));

    $html[] =  "<form method='POST'>";
    $html[] =  "<input type='hidden' name='syncToCouch' value='1'/>";
    $html[] =  "<input type='hidden' name='backups' value='$backup_keys'/>";
    $html[] =  "<input type='hidden' name='backups_attach' value='$backup_attach_keys'/>";
    $html[] =  "<input type='hidden' name='backups_url' value='$backup_url'/>";
    $html[] =  "<input type='hidden' name='backups_attach_url' value='$backup_attach_url'/>";
    $html[] =  "<input type='submit' value='save to couch'/>";
    $html[] =  "</form>";
}
$html[] =  "</ul>";

$backup_url         = cfg::$couch_url . "/" . cfg::$couch_users_db . "/_all_docs";
$backup_keys        = json_encode(array("keys" => $backup));
$backup_attach_url  = cfg::$couch_url . "/" . cfg::$couch_attach_db . "/_all_docs";
$backup_attach_keys = json_encode(array("keys" => $backedup_attachments_walk));

$html[] =  "<form method='POST'>";
$html[] =  "<input type='hidden' name='syncToCouch' value='1'/>";
$html[] =  "<input type='hidden' name='backups' value='$backup_keys'/>";
$html[] =  "<input type='hidden' name='backups_attach' value='$backup_attach_keys'/>";
$html[] =  "<input type='hidden' name='backups_url' value='$backup_url'/>";
$html[] =  "<input type='hidden' name='backups_attach_url' value='$backup_attach_url'/>";
$html[] =  "<input type='submit' value='save to couch'/>";
$html[] =  "</form>";

if(isset($_POST["syncToCouch"])){
    $backup_keys            = $_POST["backups"];
    $backup_attach_keys     = $_POST["backups_attach"];
    $backup_url             = $_POST["backups_url"];
    $backup_attach_url      = $_POST["backups_attach_url"];
    $backup_response        = json_decode($ds->doCurl($backup_url, $backup_keys, "POST"),1);
    $backup_attach_response = json_decode($ds->doCurl($backup_attach_url."?include_docs=true", $backup_attach_keys, "POST"),1);

    $walks_url = cfg::$couch_url . "/" . cfg::$couch_users_db ;
    foreach($backup_response["rows"] as $row){
        if(isset($row["error"]) && $row["error"] == "not_found"){
            $payload  = file_get_contents('temp/'.$row["key"].'/'.$row["key"].'.json');
            $response   = $ds->doCurl($walks_url, $payload, 'POST');
        }
    }

    $attach_url = cfg::$couch_url . "/" . cfg::$couch_attach_db;
    foreach($backup_attach_response["rows"] as $row){

        if(isset($row["error"]) && $row["error"] == "not_found"){
            // 2 step process 
            $parent_dir     = $parent_check[$row["key"]];

            // first , create the data entry
            $payload    = json_encode(array("_id" => $row["key"]));
            $response   = $ds->doCurl($attach_url, $payload, 'POST');
            $response   = json_decode($response,1);
            $rev        = $response["rev"];

            // next upload the attach
            $response   = $ds->prepareAttachment($row["key"],$rev,$parent_dir,$attach_url);
            print_rr($response);
        }elseif(isset($row["doc"]["_rev"]) && !isset($row["doc"]["_attachments"])){
            $parent_dir     = $parent_check[$row["key"]];

            // the stub was created but the attachment was not yet uploaded
            // so only need to do the second step
            $rev        = $row["doc"]["_rev"];
            $response   = $ds->prepareAttachment($row["key"],$rev,$parent_dir,$attach_url);
            print_rr($response);
        }
    }
}elseif(isset($_POST["deleteDir"])){
    $rmdir = $_POST["deleteDir"];
    deleteDirectory($rmdir);
    header("location:view_uploads.php");
}


echo implode("\r\n",$html);
?>
<style>
h3 form{ display:inline-block; }
</style>
<?php
require_once "common.php";




$turl  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts"; 
$pdurl = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;

$ALL_PROJ_DATA = urlToJson($pdurl); //might have to store this in a session variable
//print_rr($ALL_PROJ_DATA);
$tm = urlToJson($turl); //Just for times + project abv
$stor = $listid = array();
$stor = parseTime($tm, $stor, $listid);

foreach ($stor as $key => $value)
  array_push($listid, $key);

//if(!storedSession)
for($i = 0 ; $i < count($listid) ; $i++){
  echo '<div class="ui-widget-drag"><p>'.$listid[$i].'</p></div>';
}


?>


<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>jQuery UI Droppable - Default functionality</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

  <script> 
    $( function() { //shorthand for onReady()
    $( ".ui-widget-drag").draggable();
    $( ".ui-widget-drop" ).droppable({
      drop: function( event, ui ) {
        //$( this ).addClass( "ui-state-highlight" ).find( "p" ).html( "Yay!" );
        var dropBox_name = this.innerText;
        var dragBox_name = ui.draggable[0].innerText;
       // console.log(dropBox_name); //current dropbox instance
       // console.log(dragBox_name); //current draggable instance
        
        $.ajax({
          url:  "config_gui_post.php",
          type:'POST',
          data: "&drop=" + dropBox_name + "&drag=" + dragBox_name, 
          success:function(result){
            console.log(result);
          }        
            //THIS JUST STORES IS 
          },function(err){
          console.log("ERRROR");
          console.log(err);
        });

        ui.draggable.hide(800);
      }
    });

  });

  function CreateFolder(){
    var div = document.createElement('div');
    div.appendChild(document.createTextNode("Hi"));
    document.body.appendChild(div);

  }

  </script>
</head>
<body>
 
<div id="droppable1" class="ui-widget-drop">
  <p>Recent Projects</p>
</div>

<div id="droppable2" class="ui-widget-drop">
  <p>TESTBOX</p>
</div>


<button onclick="CreateFolder">Try Me</button>
 
 
</body>

<style>
  .ui-widget-drop{

    width: 150px; height: 150px; padding: 0.5em; 
    float: left;
    margin: 10px; text-align: center; 
    background-image: url('img/FolderClose.svg');
    border: 1px solid red;
    background-color: transparent;
    background-size: 100%;
    line-height: 600%;
    background-repeat: no-repeat;

  }
  .ui-widget-drag{
    padding: 0.1em; 
    float: left; 
    margin: 5px 5px 10px 0; 
    text-align: center;
    background-image: url('img/FolderClose.svg');
    border: transparent;
    height: 100px;
    width: 100px;
    text-align: center;
    font-size: 14;
    font-weight: bold;
    padding-top:10px;
    background-color: transparent;
    line-height: 316%;
  } 
  .ui-state-highlight{
    background: transparent;
  }
  </style>


</html>

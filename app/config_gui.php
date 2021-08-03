<?php
require_once "common.php";


$turl  = cfg::$couch_url . "/" . cfg::$couch_users_db . "/"  . "_design/filter_by_projid/_view/get_data_ts"; 
$pdurl = cfg::$couch_url . "/" . cfg::$couch_proj_db . "/" . cfg::$couch_config_db;

$ALL_PROJ_DATA = $ds->urlToJson($pdurl); //might have to store this in a session variable
$_SESSION["DT"] = $ALL_PROJ_DATA;
//print_rr($ALL_PROJ_DATA);
$tm = $ds->urlToJson($turl); //Just for times + project abv
$stor = $listid = array();
$stor = parseTime($tm, $stor, $listid);

foreach ($stor as $key => $value)
  array_push($listid, $key);

//print_rr($listid);

//if(!storedSession)


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
      clearData();
      bindProperties();
    
    $(document).on("dblclick",".ui-widget-drop",function(event,ui){
      console.log(this.innerText);
      if($('#'+this.innerText+':visible').length == 0)
        $('#'+this.innerText).css('display','block');
      else{
        $('#'+this.innerText).css('display','none');
        console.log(this.innerText);
      }

    });
    $('.ui-widget-drop').mousedown(function(event,ui) {
      /*if (event.which == 3){        
        console.log(this);
        console.log(this.parentNode);
        if(deleteprompt())
          workingspace.removeChild(this);
      }*/
    });


  });//onReady

/*  function appendProjCount(){
    var folders = document.getElementsByClassName("ui-widget-drop");
      for(i = 0 ; i < folders.length; i++){

          let projCount = $("#"+folders[i].innerText)[0].children.length;
          //var temp = $(folders[i]).find("p");
          let p = document.createElement("pCount");
          p.innerText = projCount;
          console.log(folders[i]);
          
      }
  }
*/
    
  function bindProperties(){
      $( ".ui-widget-drag").draggable({
      cursor: "move",
      drag: function(event,ui){
      //  ui.css("z-index", "-1"); //fix frontal input
      }

    });

    $( ".ui-widget-drop" ).droppable({
      drop: function( event, ui ) {
        //var pdata = <?php echo json_encode($ALL_PROJ_DATA);?>;
        var dropBox_name = $.trim(this.innerText);
        var dragBox_name = $.trim(ui.draggable[0].innerText);
        var key = $(ui.draggable[0]).data("key");
        //if does not exist within folder then render it
        addProject(key,dragBox_name,dropBox_name);
        $.ajax({
          url:  "config_gui_post.php",
          type:'POST',
          data: "&dropTag=" + dropBox_name + "&dragTag=" + dragBox_name + "&datakey=" + key,
          success:function(result){
            console.log(result);
          }        
            //THIS JUST STORES IS 
          },function(err){
          console.log("ERRROR");
          console.log(err);
        });
        ui.draggable.hide(350);
      }//drop

    }); //ui-widget-drop
  }

  function deleteprompt(){
      var value = confirm("Are you sure you want to delete this folder?");
      return value;

  }
  function CreateFolder(name){
    if(name)
    {
      $("<div class ='ui-widget-drop'><p>"+name+"</p></div>").appendTo("#folderspace");
      let hiddennode = $("<div class = 'hidden' id ='"+name+"'></div");
      $("#folderspace").append(hiddennode);
      bindProperties();
      $.ajax({
        url:"config_gui_post.php",
        type: 'POST',
        data: "&folders=" + name,
        success:function(result){
          console.log(result);
        }
        
        },function(err){
          console.log("ERROR");
          console.log(err);
        
      });
    }//if
    else
      alert("Please enter a name for your folder");
  }//CreateFolder

  function clearData(){
    <?php session_unset();?>
  }
  function addProject(key,dragBox_name,dropBox_name){
    let div = document.createElement("div");
    
    let p = document.createElement("p");
    let a = document.createElement("a");
    a.href = "index.php?proj_idx="+key;
    a.textContent = dragBox_name;
    $(p).append(a);
    
    div.className = "foldercontents";
    div.setAttribute("data-key",key);
    $(div).append(p);
    let search = document.getElementById(dropBox_name);
    $(search).append(div);
  }

  </script>
</head>
  <body>
    <input type ="text" id = "foldername">
    <button type ="button" onclick="CreateFolder(document.getElementById('foldername').value)">Create Folder</button>
    <div id = "folderspace">
      <?php 
        foreach ($ALL_PROJ_DATA["folders"] as $key => $value) { //populate folders inside working space
          echo "<div class ='ui-widget-drop'><p>".$value." </p></div>";
          echo "<div class ='hidden' id ='".$value."'>";
            foreach ($ALL_PROJ_DATA["project_list"] as $k => $v) {
              if(isset($v["dropTag"]) && $v["dropTag"] ==$value){
               // echo '<div class="foldercontents" data-key = "'.$k.'" ><p>'.$v["project_id"] .'</p></div>';
                echo '<div class="foldercontents" data-key = "'.$k.'" ><p><a href="index.php?proj_idx='.$k.'"'.'>'.$v["project_id"] .'</a></p></div>';
                  

              }
            }

          echo "</div>";
        }

      ?>    
    </div>
   
     
  </body>
    <div id = workingspace>
      <?php
      foreach ($ALL_PROJ_DATA["project_list"] as $key=>$projects) { //populate projects on base page
          if(isset($ALL_PROJ_DATA["project_list"][$key]["dropTag"]))
          {
            //if droptag is set we want to store things in the individual folders.
          }else
            //echo '<div class="ui-widget-drag" data-key = "'.$key.'" ><p>'.$projects["project_id"] .'</p></div>';
            echo '<div class="ui-widget-drag" data-key = "'.$key.'" ><p><a href="index.php?proj_idx='.$key.'"'.'>'.$projects["project_id"] .'</a></p></div>';
        }
        ?>
    </div>

<style>
  

  .hidden{
    display: none;

  }
  #folderspace{
    height:170px;
    display:block;
  }
  #workingspace{
    height:300px;
  }
  .ui-widget-drop{
    width: 100; height: 100px; padding: 0.5em; 
    float: left;
    margin: 10px; text-align: center; 
    background-image: url('img/FolderClose.svg');
    border: 1px solid red;
    background-color: transparent;
    background-size: 100%;
    line-height: 600%;
    background-repeat: no-repeat;
    font-size: 14;

  }
  .ui-widget-drag{
    padding: 0.1em; 
    float: left; 
    margin: 5px 5px 10px 0; 
    text-align: center;
    background-image: url('img/icons8-star-26.png');
    background-repeat: no-repeat;
    border: transparent;
    height: 30px;
    width: 125px;
    text-align: center;
    font-size: 11;
    font-weight: bold;
    line-height: 45%;
    border:ridge;
    border-color: blue;
    border-width: 2px;
    background-color: azure;
  } 
  .foldercontents{
    padding: 0.1em; 
    float: left; 
    margin: 5px 5px 10px 0; 
    text-align: center;
    background-image: url('img/icons8-star-26.png');
    background-repeat: no-repeat;
    border: transparent;
    height: 30px;
    width: 125px;
    text-align: center;
    font-size: 11;
    font-weight: bold;
    line-height: 45%;
    border:ridge;
    border-color: blue;
    border-width: 2px;
    background-color: lightgreen;
  } 

  .ui-state-highlight{
    background: transparent;
  }
  </style>


</html>

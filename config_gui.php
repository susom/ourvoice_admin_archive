<?php
require_once "common.php";





?>


<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>jQuery UI Droppable - Default functionality</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="/resources/demos/style.css">
  <style>
  #draggable-box1 { width: 75px; height: 50px; padding: 0.1em; float: left; margin: 10px 10px 10px 0; text-align: center;}
  #draggable-box2 { width: 75px; height: 50px; padding: 0.1em; float: left; margin: 10px 10px 10px 0; text-align: center;}
  #droppable { width: 150px; height: 150px; padding: 0.5em; float: left; margin: 10px; text-align: center; }
  </style>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script>
  $( function() {
    $( "#draggable-box1" ).draggable();
    $( "#draggable-box2" ).draggable();
    $( "#droppable" ).droppable({
      drop: function( event, ui ) {
        $( this ).addClass( "ui-state-highlight" ).find( "p" ).html( "Success!" );
      }
    });
  } );
  </script>
</head>
<body>


 
<div id="draggable-box1" class="ui-widget-content">
  <p>IRV</p>
</div>


<div id="droppable" class="ui-widget-header">
  <p>Recent Projects</p>
</div>

<div id="draggable-box2" class="ui-widget-content">
  <p>GNT</p>
</div>

 
 
</body>
</html>
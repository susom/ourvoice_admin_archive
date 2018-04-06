<html> 
<head>
	<script src="https://unpkg.com/react@15/dist/react.js"></script>
 	<script src="https://unpkg.com/react-dom@15/dist/react-dom.js"></script>
 	<script src="https://unpkg.com/babel-standalone/babel.min.js"></script>
 	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
 	<script type = "text/babel" src="React/recent_activity.js"></script>

</head>
<body> 
	<a href="index.php">Back to Project Picker</a>

	<div id="root"></div> 
</body>
</html>

<style>
	li{
		color:black;
	}
	.recent_times{
   	 background-image: linear-gradient(to right, yellow, transparent 50%);
	}
	
	.entry{
		min-width: 365px;
		border:solid;
		border-width: 1px;
		display: inline-block;
		margin:10px;
	}
	ul{
		column-count: 3;
	}
	a{
		float:right;
	}
</style>
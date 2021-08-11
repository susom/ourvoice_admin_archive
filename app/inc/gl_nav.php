<nav>
    <h2 class="pull-left"><a href="summary.php"> : Citizen Science for Health Equity</a></h2>
	<div class="context_buttons pull-right">
		<?php
		if(isset($_SESSION["summ_pw"])){
			if( $page == "summary"){
	            echo '<a class="inproject btn btn-danger" href="project_agg_photos.php?id='.$active_project_id.'">All Walk Photos</a>';
			}else{
				echo '<a class="inproject btn btn-danger" href="summary.php">Back to Project Summary</a>';
			}

			if( $page != "photo_detail"){
				echo '<a target="_blank" class="inproject btn btn-success" href="project_map_csv.php?active_project_id='.$active_project_id.'">Download All Project Data (.csv)</a>';
			}
		}
		?>
	</div>
</nav>

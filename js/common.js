function implementSearch(ALL_PROJ_DATA){
    $("#magnifying_glass").click(function(){
        searchProject(ALL_PROJ_DATA);
    });
    $("#search").keyup(function(event){
        if(event.keyCode === 13)
            searchProject(ALL_PROJ_DATA);
    });


}
function searchProject(ALL_PROJ_DATA){
    var search_tag = ($("#search").val()).toUpperCase().trim();
    var pdata = ALL_PROJ_DATA;
    console.log(pdata);
    $.each(pdata.project_list, function(index){
        if((this).project_id == search_tag){
            console.log(this);
            console.log(index);
            window.location.href = 'summary.php?id='+(this).project_id;
        }   
    });
  }


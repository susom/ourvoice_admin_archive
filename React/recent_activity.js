class Entry extends React.Component{
  render(){    
    return(
      <div className = "entry">              
        <div className = "title">({this.props.abv}) - <b>{this.props.full}</b></div>  
        <div className = "recent_times">{this.props.rec}</div>
        <div className = "non_recent_times">{this.props.non_rec}</div>
        <form action = "summary.php" form id = "route_summary" method = "get">
          <button type = "submit" className = "submitbutton" name = "id" value = {this.props.abv} >GO</button>
        </form>
      </div>
      ); 
  }

}

class Header extends React.Component{
  constructor(props){
    super(props);
    this.state = {
      isLoaded: false,
      data: []
    };
  }
  componentDidMount(){
    var obj = this;
    console.log("in componentDidMount");
    $.ajax({
          url:  "React/getData.php",
          dataType:"JSON",
          success:function(result){
            console.log(result);
            obj.setState({
              isLoaded: true,
              data: result
            });
          }        
            
          },function(err){
          console.log(err);
        });
  }

  consolidate(){
    var rows = [];
    var count = 0;
    var unique_names = this.remove_dup();
    var ph = this.state.data.set;
    this.rec_counter = 0;
    for(var i = 0 ; i < ph.length ; i++){
      if(unique_names[ph[i].abv] != null){
        rows.push(<Entry abv = {ph[i].abv} full = {ph[i].full} rec = {ph[i].rec_time} non_rec = {ph[i].non_rec_times}/>);
        delete unique_names[ph[i].abv];
        
        if(ph[i].rec_time != "")
          this.rec_counter++;
      }
    }
    for(var key in unique_names)
      rows.push(<Entry abv = {unique_names[key]} />);
    return rows;
  }
  
  remove_dup(){
    var unique_names = {};
    $.each(this.state.data.list_abv, function(i, el){
      if($.inArray(el, unique_names) === -1)
        unique_names[el] = el ;
    });
    return unique_names;
  }

  render(props){ //remember this is called async, ensure checking of whether variables exist
    if(this.state.data.set != null){ //necessary on recall
      var rows = this.consolidate();
    
      const listItems = rows.map((entries) => <div key = {entries.props.abv}>{entries}</div>);
      return(
        <div>
          <h1>Recent Activities</h1>
          <p> Projects updated within the last 4 weeks: <b>{this.rec_counter}</b> </p>

          <ul>{listItems}</ul>
        </div>
      );
    }else{ 
      return null; //default state is empty webpage currently
    }//if
  }//render

}
 
//this section renders @ div root on recent_activity.
ReactDOM.render(
  <div>
    <Header />  
  </div>,
  document.getElementById("root")
);
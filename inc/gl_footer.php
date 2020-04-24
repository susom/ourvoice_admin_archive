<footer id="footer">
    <copyright>© Stanford University 2020</copyright><?php if( isset($_SESSION["discpw"])  && $_SESSION["discpw"] == cfg::$master_pw ) { ?> | <a href="index.php">Admin Overview</a><?php } ?> <?php if(!empty($_SESSION["summ_pw"])){ ?> | <a href="summary.php?clearsession=1">Log Out</a><?php } ?>
</footer>	
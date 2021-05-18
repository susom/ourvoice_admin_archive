<?php


// Customize this configuration file for each instance of the tool

class base_cfg {
    public static $couch_url            ='https://foo.ourvoice-cdb.med.stanford.edu';
    public static $couch_proj_db        ='disc_projects';
    public static $couch_config_db      ='all_projects';
    public static $couch_users_db       ='disc_users';
    public static $couch_all_db         ='_all_docs';
    public static $couch_user            ='disc_user_general';
    public static $couch_pw             ="rQaKibbDx7rP";
    public static $gmaps_key            ="AIzaSyCn-w3xVV38nZZcuRtrjrgy4MUAW35iBOo";
}


// CUSTOMIZE THE CONFIG FOR EACH INSTANCE HERE
class cfg extends base_cfg {
    public static $couch_url        ='https://ourvoice-cdb.med.stanford.edu';
}

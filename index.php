<?php

    session_start();

    require(dirname(__FILE__)."/fabrico/fabrico.php");

    // modules + resources
    global $F;
    $F->loadModule(
        "ErrorHandler",
        "View",
        "Router", 
        "DBAdapters/MySQL",
        "SessionManager",
        "Former"
    );
    $F->loadResource(
        "config/*",
        "controllers/*",
        "helpers/*"
    );

    // configuration of the template engine and Former
    View::$root = dirname(__FILE__)."/templates/";
    View::$forEachView = array(
        "siteURL" => DEPLOYER_URL
    );
    Former::templatesPath(dirname(__FILE__)."/templates/former/");

    // database
    $mysql = new MySQLAdapter((object) array(
        "host" => HOST,
        "user" => USER,
        "pass" => PASS,
        "dbname" => DBNAME
    )); 
    $mysql
    ->defineContext("apps", array(
        "name" => "VARCHAR(250)",
        "source" => "VARCHAR(250)",
        "type" => "VARCHAR(50)",
        "user" => "VARCHAR(150)",
        "pass" => "VARCHAR(150)",
        "destination" => "LONGTEXT",
        "afterRelease" => "LONGTEXT"
    ));

    // routing
    $router = new Router();
    $router    
    ->register("/apps/delete/@id", array("RequireLogin", "Applications"))
    ->register("/apps/add", array("RequireLogin", "Applications"))
    ->register("/apps/@id", array("RequireLogin", "Applications"))
    ->register("/logout", "Logout")
    ->register("/cmd", array("RequireLogin", "Command"))
    ->register("", array("RequireLogin", "Home"))
    ->run();

?>
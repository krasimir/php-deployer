<?php

    session_start();

    require(__DIR__."/fabrico/fabrico.php");

    // modules + resources
    global $F;
    $F->loadModule(
        "ErrorHandler",
        "View",
        "Router", 
        "DBAdapters/MySQL",
        "SessionManager"
    );
    $F->loadResource(
        "controllers/*",
        "config/*"
    );

    // configuration of the template engine
    View::$root = __DIR__."/templates/";

    // database
    $mysql = new MySQLAdapter((object) array(
        "host" => HOST,
        "user" => USER,
        "pass" => PASS,
        "dbname" => DBNAME
    )); 
    $mysql->defineContext("apps", array(
        "name" => "VARCHAR(250)",
        "source" => "VARCHAR(250)",
        "type" => "VARCHAR(50)",
        "user" => "VARCHAR(150)",
        "pass" => "VARCHAR(150)"
    ));

    // routing
    $router = new Router();
    $router
    ->register("/apps/add/store", array("RequireLogin", "Applications"))
    ->register("/apps/add", array("RequireLogin", "Applications"))
    ->register("", array("RequireLogin", "Home"))
    ->run();

?>
<?php

    class Applications {
        public function __construct($params) {
            global $mysql;
            $rule = $params["ROUTER_RULE_MATCH"];
            switch($rule->pattern) {
                case "/apps/add":
                    die(view("layout.html", array(
                        "content" => view("application-add.html")
                    )));
                break;
                case "/apps/add/store":
                    $record = (object) array(
                        "name" => $params["name"],
                        "source" => $params["source"],
                        "type" => $params["type"],
                        "user" => $params["user"],
                        "pass" => $params["pass"]
                    );
                    $mysql->apps->save($record);
                    die(view("layout.html", array(
                        "content" => view("application-added.html")
                    )));
                break;
            }
        }
    }
    
?>
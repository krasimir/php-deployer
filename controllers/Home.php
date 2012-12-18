<?php

    class Home {
        public function __construct() {
            global $mysql;
            $apps = $mysql->apps->order("id")->get();
            $appsMarkup = "";
            if($apps == false) {
                $appsMarkup = "You still don't have any applications added.";
            } else {
                foreach($apps as $app) {
                    $appsMarkup .= view("list-link.html", array(
                        "url" => "apps/manage/".$app->id,
                        "label" => $app->name
                    ));
                }
            }
            die(view("layout.html", array(
                "content" => view("home.html", array(
                    "appsMarkup" => $appsMarkup
                ))
            )));
        }
    }

?>
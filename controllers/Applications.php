<?php

    Former::register("app-form", "/")
    ->addTextBox(array(
        "name" => "name", 
        "label" => "Name of the application:",
        "validation" => Former::validation()->NotEmpty()
    ))
    ->addTextBox(array(
        "name" => "source", 
        "label" => "Source/URL:",
        "validation" => Former::validation()->NotEmpty()
    ))
    ->addDropDown(array(
        "name" => "type", 
        "label" => "Type:",
        "options" => array(
            "svn" => "SVN",
            "git" => "GIT"
        )
    ))
    ->addTextBox(array(
        "name" => "user", 
        "label" => "Username:",
        "validation" => Former::validation()->NotEmpty()
    ))
    ->addTextBox(array(
        "name" => "pass", 
        "label" => "Password:",
        "validation" => Former::validation()->NotEmpty()
    ))
    ->addTextBox(array(
        "name" => "destination", 
        "label" => "Destination (where to deploy the code):",
        "validation" => Former::validation()->NotEmpty()
    ));

    class Applications {
        public function __construct($params) {

            global $mysql;
            $rule = $params["ROUTER_RULE_MATCH"];         

            switch($rule->pattern) {
                case "/apps/add":
                    $form = Former::get("app-form")->url("/apps/add");
                    if($form->submitted && $form->success) {
                        $record = $form->data;
                        $mysql->apps->save($record);
                        die(view("layout.html", array(
                            "content" => "The application is added successfully.",
                            "nav" => view("nav.html")
                        )));
                    } else {
                        die(view("layout.html", array(
                            "content" => $form->markup,
                            "nav" => view("nav.html")
                        )));
                    }
                break;
                case "/apps/@id":
                    $id = $params["id"];
                    $record = $mysql->apps->where("id='".$id."'")->get();
                    $record = $record[0];
                    $form = Former::get("app-form", $record)->url("/apps/".$id);
                    if($form->submitted && $form->success) {
                        $record = $form->data;
                        $record->id = $id;
                        $mysql->apps->save($record);
                        die(view("layout.html", array(
                            "content" => view("application.html", array(
                                "form" => "The application is saved successfully.",
                                "revisions" => $this->revisions($record),
                                "releases" => $this->releases($record),
                                "id" => $id
                            )),
                            "nav" => view("nav.html")
                        )));
                    } else {
                        die(view("layout.html", array(
                            "content" => view("application.html", array(
                                "form" => $form->markup,
                                "revisions" => $this->revisions($record),
                                "releases" => $this->releases($record),
                                "id" => $id
                            )),
                            "nav" => view("nav.html")
                        )));
                    }
                break;
                case "/apps/delete/@id":
                    $record = (object) array("id" => $params["id"]);
                    $mysql->apps->trash($record);
                    header("Location: /");
                break;
            }

        }
        private function revisions($app) {

            $destination = __DIR__."/..".$app->destination;            
            
            switch($app->type) {
                case "svn":
                    if(!file_exists($destination.".svn")) {
                        $output = array();
                        // $cmd = "svn checkout --non-interactive --force ".$app->source." ".$destination." --username ".$app->user." --password ".$app->pass;
                        // exec($cmd, $output);
                        // var_dump($output);die();
                    }
                break;
            }

        }
        private function releases($app) {

        }
    }
?>
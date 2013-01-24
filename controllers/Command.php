<?php

    class Command {
        public function __construct($params) {
            if(isset($params["action"]) && $params["action"] == "do it") {
                $shell = new Shell($params["cmd"]);
                die(view("layout.html", array(
                    "content" => view("cmd.result.html", array(
                        "cmd" => $params["cmdPreview"],
                        "result" => $shell->toString()."<br /><br />Result:<br />".$shell->result(),
                        "callback" => $params["callback"]
                    )),
                    "nav" => view("nav.html")
                )));
            } else {
                die(view("layout.html", array(
                    "content" => 
                        view("warning.html", array("message" => "<strong>WARNING!</strong><br />Are you sure that you want to:<br />".$params["cmdPreview"])).
                        view("form.command.html", array(
                            "cmd" => $params["cmd"],
                            "cmdPreview" => $params["cmdPreview"],
                            "callback" => $params["callback"],
                            "label" => "Yes, I'm sure!",
                            "action" => "do it"
                        )),
                    "nav" => view("nav.html")
                )));
            }
        }
    }

?>

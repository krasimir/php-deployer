<?php

    class RequireLogin {

        public function __construct($params) {            
            if(SessionManager::read("php-deployer-user") === false) {
                global $USERS;
                if(isset($params["action"]) && $params["action"] == "login") {
                    foreach ($USERS as $user) {
                        if($user->username === $params["username"] && $user->password === $params["password"]) {
                            SessionManager::write("php-deployer-user", $user->username);
                            return true;
                        }
                    }
                }
                die(view("layout.html", array(
                    "content" => view("login.html")
                )));
            }
        }

    }

?>
<?php

    Former::register("login-form", DEPLOYER_URL)
    ->addTextBox(array(
        "name" => "username", 
        "label" => "Username:",
        "validation" => Former::validation()->NotEmpty()
    ))
    ->addPasswordBox(array(
        "name" => "password", 
        "label" => "Password:",
        "validation" => Former::validation()->NotEmpty()
    ));

    class RequireLogin {

        public function __construct($params) {
            if(SessionManager::read("php-deployer-user") === false) {
                global $USERS;
                $form = Former::get("login-form");
                if($form->data->username && $form->data->password) {
                    foreach ($USERS as $user) {
                        if($user->username === $form->data->password && $user->password === $form->data->username) {
                            SessionManager::write("php-deployer-user", $user->username);
                            return true;
                        }
                    }
                }
                die(view("layout.html", array(
                    "content" => $form->markup,
                    "nav" => "Please login first."
                )));
            }
        }

    }

?>
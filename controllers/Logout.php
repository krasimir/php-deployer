<?php

    class Logout {

        public function __construct($params) {
            SessionManager::destroy();
            header("Location: ".DEPLOYER_URL);
        }

    }

?>
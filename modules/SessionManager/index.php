<?php

    class SessionManager {
        
        public static function write($key, $value) {
            $_SESSION[$key] = $value;
            self::updateActivity();
            if(!isset($_SESSION['EXPIRE_AFTER'])) {
                $_SESSION['EXPIRE_AFTER'] = 60 * 60;
            }
            return $value;
        }
        public static function read($key) {
            if(isset($_SESSION['LAST_ACTIVITY'])) { 
                if(self::timeLeftBeforeExpire() < 0) {
                    self::destroy();
                    return false;
                }
                if(isset($_SESSION[$key])) {
                    self::updateActivity();
                    return $_SESSION[$key];
                } else {
                    return false;
                }                
            } else {
                return false;
            }
        }
        public static function clear($key) {
            if(isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
            return true;
        }
        public static function destroy() {
            $_SESSION = array();
            @session_destroy();
        }
        public static function setTTL($sec) {
            $_SESSION['EXPIRE_AFTER'] = $sec;
            return $sec;
        }
        public static function getTTL() {
            if(isset($_SESSION['EXPIRE_AFTER'])) {
                return $_SESSION['EXPIRE_AFTER'];
            } else {
                return -1;
            }
        }
        public static function timeLeftBeforeExpire() {
            if(isset($_SESSION['LAST_ACTIVITY'])) {
                return self::getTTL() - (time() - $_SESSION['LAST_ACTIVITY']);
            } else {
                return -1;
            }
        }
        private static function updateActivity() {
            $_SESSION['LAST_ACTIVITY'] = time();
        }

    }

?>
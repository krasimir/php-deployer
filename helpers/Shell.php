<?php

    class Shell {

        private $str = "";
        private $arr = array();
        private $return_var;

        public function __construct($cmd) {
            exec($cmd, $this->arr, $this->return_var);
            foreach($this->arr as $line) {
                $this->str .= $line."\n";
            }
        }
        public function toString() {
            return $this->str;
        }
        public function toArray() {
            return $this->arr;
        }
        public function result() {
            return $this->return_var;
        }
    }

?>
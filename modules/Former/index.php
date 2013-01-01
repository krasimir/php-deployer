<?php

    class Former {

        private static $forms;

        public static function templatesPath($path) {
            FormerView::$root = $path;
        }
        public static function register($key, $url, $method = "POST") {
            if(!isset(self::$forms)) self::$forms = (object) array();
            if(FormerView::$root == "") {
                Former::templatesPath(__DIR__."/tpl/");
            }
            return self::$forms->$key = new FormerForm($url, $method, $key);
        }
        public static function get($key, $defaultValues = null) {
            if(isset(self::$forms->$key)) {
                return self::$forms->$key->update($defaultValues);
            } else {
                throw new Exception("There is no form associated with key=".$key);
            }
        }
        public static function validation() {
            return new FormerValidation();
        }

    }

    class FormerValidation {

        public static $MESSAGE_NotEmpty = "Missing value.";
        public static $MESSAGE_LengthMoreThen = "Wrong value length.";
        public static $MESSAGE_LengthLessThen = "Wrong value length.";
        public static $MESSAGE_Match = "Wrong value.";
        public static $MESSAGE_Not = "Wrong value.";
        public static $MESSAGE_ValidEmail = "Invalid email.";
        public static $MESSAGE_MoreThen = "Wrong value.";
        public static $MESSAGE_LessThen = "Wrong value.";
        public static $MESSAGE_Int = "Wrong value.";
        public static $MESSAGE_String = "Wrong value.";
        public static $MESSAGE_Float = "Wrong value.";

        private $filters;

        public function __construct() {
            $this->filters = array();
        }
        public function __call($name, $arguments) {
            $this->filters []= (object) array("type" => $name, "args" => $arguments);
            return $this;
        }
        public function check($values) {
            if(!is_array($values)) {
                $values = array($values);
            }
            foreach($values as $value) {
                foreach($this->filters as $filter) {
                    $failed = false;
                    switch($filter->type) {
                        case "NotEmpty": $failed = $value === "" || $value === null; break;
                        case "LengthMoreThen": $failed = strlen($value) < $filter->args[0]; break;
                        case "LengthLessThen": $failed = strlen($value) >= $filter->args[0]; break;
                        case "ValidEmail": $failed = !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $value); break;
                        case "Match": $failed = !preg_match($filter->args[0], $value); break;
                        case "Not": $failed = $value === $filter->args[0]; break;
                        case "MoreThen": $failed = $value < $filter->args[0]; break;
                        case "LessThen": $failed = $value > $filter->args[0]; break;
                        case "Int": $failed = !is_numeric($value); break;
                        case "Float": $failed = !is_numeric($value); break;
                        case "String": $failed = is_numeric($value); break;
                    }
                    if($failed) {
                        return (object) array("status" => false, "message" => FormerValidation::${"MESSAGE_".$filter->type});
                    }
                }
            }
            return (object) array("status" => true, "message" => "");
        }
    }

    class FormerForm {

        private $url;
        private $method;
        private $key;
        private $defaultValues;

        public $submitted = false;
        public $success = false;
        public $elements = array();
        public $markup;
        public $data;

        public function __construct($url, $method, $key) {
            $this->url = $url;
            $this->method = $method;
            $this->key = $key;
        }
        public function url($url) {
            $this->url = $url;
            $this->update($this->defaultValues);
            return $this;
        }
        public function update($defaultValues = null) {

            $elementsMarkup = "";
            $defaultValues = $this->defaultValues = $defaultValues == null ? (object) array() : $defaultValues;
            $this->submitted = $this->read("form-".$this->key) !== false;
            $this->success = $this->submitted ? true : false;
            $this->data = (object) array();

            foreach($this->elements as $el) {

                $value = $this->read($el->props["name"]);
                $defaultValue = isset($defaultValues->{$el->props["name"]}) ? $defaultValues->{$el->props["name"]} : "";
                $this->data->{$el->props["name"]} = $value;
                $valid = isset($el->props["validation"]) ? $el->props["validation"]->check($value) : (object) array("status" => true, "message" => "");

                $optionsMarkup = '';
                if($el->type == "dropdown") {
                    foreach($el->props["options"] as $optionValue => $optionLabel) {
                        $optionsMarkup .= former_view($el->type."option.html", array(
                            "value" => $optionValue,
                            "label" => $optionLabel,
                            "selected" => $value == false && !$this->submitted ? ($optionValue == $defaultValue ? "selected='selected'" : "") : ($optionValue == $value ? "selected='selected'" : "")
                        ));
                    }
                } else if($el->type == "radio" || $el->type == "check") {
                    foreach($el->props["options"] as $optionValue => $optionLabel) {
                        $checked = "";
                        if($value == false && $optionValue == $defaultValue && !$this->submitted) {
                            $checked = "checked='checked'";
                        } else if($optionValue == $value) {
                            $checked = "checked='checked'";
                        } else if($value != false && is_array($value) && in_array($optionValue, $value)) {
                            $checked = "checked='checked'";
                        }
                        $optionsMarkup .= former_view($el->type."option.html", array(
                            "value" => $optionValue,
                            "label" => $optionLabel,
                            "name" => $el->props["name"],
                            "checked" => $checked
                        ));
                    }
                }

                $elementsMarkup .= former_view($el->type.".html", array(
                    "name" => $el->props["name"],
                    "label" => $el->props["label"],
                    "value" => $value == false ? $defaultValue : (!is_array($value) ? $value : ""),
                    "error" => $this->submitted && $valid->status == false ? former_view("error.html", array(
                        "message" => $valid->message
                    )) : "",
                    "options" => $optionsMarkup
                ));

                if($this->submitted && $valid->status === false) {
                    $this->success = false;
                }

            }
            $elementsMarkup .= former_view("submit.html");
            $this->markup = former_view("form.html", array(
                "url" => $this->url,
                "method" => $this->method,
                "elements" => $elementsMarkup,
                "key" => $this->key
            ));
            return $this;
        }
        public function addTextBox($props) {
            $this->elements []= (object) array("type" => "textbox", "props" => $props);
            return $this;
        }
        public function addTextArea($props) {
            $this->elements []= (object) array("type" => "textarea", "props" => $props);
            return $this;
        }
        public function addPasswordBox($props) {
            $this->elements []= (object) array("type" => "passwordbox", "props" => $props);
            return $this;
        }
        public function addDropDown($props) {
            $this->elements []= (object) array("type" => "dropdown", "props" => $props);
            return $this;
        }
        public function addRadio($props) {
            $this->elements []= (object) array("type" => "radio", "props" => $props);
            return $this;
        }
        public function addCheck($props) {
            $this->elements []= (object) array("type" => "check", "props" => $props);
            return $this;
        }
        public function addFile($props) {
            $this->elements []= (object) array("type" => "file", "props" => $props);
            return $this;
        }
        // request parameters
        private function read($key) {
            $data = null;
            if(isset($_GET[$key])) {
                $data = $_GET[$key];
            } else if(isset($_POST[$key])) {
                $data = $_POST[$key];
            }
            if($data !== null) {
                if(is_array($data)) {
                    return $data;
                } else {
                    return addslashes(stripslashes($data));
                }
            } else {
                return false;
            }
        }
    }

    // view logic
    class FormerView {
    
        public static $root = "";
    
        // caching mechanism
        private static $cache;
        public static function add($file, $content) {
            if(self::$cache == NULL) {
                self::$cache = (object) array();
            }
            self::$cache->$file = $content;
        }
        public static function get($file) {
            if(isset(self::$cache->$file)) {
                return self::$cache->$file;
            } else {
                return false;
            }
        }

        // view logic
        public $tplFileContent = NULL;
        public $vars = array();

        public function __construct($path, $data, $root = "") {
            if($root != "") {
                FormerView::$root = $root;
            }
            $cache = FormerView::get($path);
            if(!$cache) {
                $path = FormerView::$root.$path;
                $fh = @fopen($path, "r");
                if(!$fh) {
                    throw new ErrorException("Missing file '".$path."'.");
                }
                $this->tplFileContent = fread($fh, filesize($path));
                fclose($fh);
                FormerView::add($path, $this->tplFileContent);
            } else {
                $this->tplFileContent = $cache;
            }
            $this->vars = $data;
        }
        public function __toString() {
            // adding assigned variabls
            $output = $this->tplFileContent;
            foreach($this->vars as $key => $value) {
                $output = str_replace("{".$key."}", $value, $output);
            }
            return $output;
        }
    }

    function former_view($path, $data = array(), $root = "") {
        return new FormerView($path, $data, $root);
    }

?>
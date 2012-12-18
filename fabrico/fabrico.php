<?php

    if(!defined("FABRICO_MODULES_DIR")) define("FABRICO_MODULES_DIR", "modules");
    if(!defined("FABRICO_LOADER_CACHE_FILE")) define("FABRICO_LOADER_CACHE_FILE", "fabrico.loader.cache.php");

    /*************************************************************************************/
    /*                                                                                   */
    /* Fabrico Package Manager                                                           */
    /*                                                                                   */
    /*************************************************************************************/

    if(php_sapi_name() === "cli") {

        if(!class_exists("FabricoPackageManager")) {
            class FabricoPackageManager {

                private $gitEndPoint = "https://api.github.com/";
                private $gitEndPointRaw = "https://raw.github.com/";
                private $gitRepos;
                private $installedModules;

                private $packageFile = "";
                private $modulesDir = "";

                private $gitHubUsername = false;
                private $gitHubPassword = false;
                private $gitHubRateLimit = false;

                public function __construct() {
                    $this->modulesDir = FABRICO_MODULES_DIR;
                    $this->validatePackageJSON();
                    $this->log("\nFabrico Package Manager started\n", "GREEN");
                    $this->gitRepos = (object) array();
                    $this->installedModules = (object) array();
                    $this->installModules($this->packageFile);
                    $this->reportResults();
                }
                private function installModules($packageFile, $indent = 0) {
                    $this->log("Operating in: ".dirname($packageFile), "GREEN", $indent);
                    $sets = json_decode(file_get_contents($packageFile));
                    if(!$this->validateSets($sets, $packageFile)) {
                        return;
                    }
                    foreach($sets as $set) {
                        $dir = dirname($packageFile)."/".$this->modulesDir;
                        if(!file_exists($dir)) {
                            mkdir($dir, 0777);
                        }
                        if($this->shouldContain($set, array("owner", "repository", "branch"))) {
                            $this->log("repository: /".$set->owner."/".$set->repository, "BLUE", $indent);
                            $this->formatModules($set);
                            foreach($set->modules as $module) {
                                $this->installModule($module, $set, $dir, $indent);
                            }
                        } else if($this->shouldContain($set, array("path", "name"))) {
                            $this->log("file: ".$set->path, "BLUE", $indent);
                            $this->installFile($set, $dir, $indent);
                        }
                    }
                }
                private function installModule($module, $set, $installInDir, $indent) {

                    // solving github rate limit problem
                    if($this->gitHubRateLimit === false) {
                        $gitHubRateLimit = $this->request($this->gitEndPoint."rate_limit");
                    }
                    if($gitHubRateLimit->rate->remaining <= 5) {
                        $this->log("The GitHub rate limit is reached for you IP. Github credentials are needed:\nusername:");
                        $handle = fopen ("php://stdin","r");
                        $this->gitHubUsername = trim(fgets($handle));
                        $this->log("password:");
                        $handle = fopen ("php://stdin","r");
                        $this->gitHubPassword = trim(fgets($handle));
                    }

                    $tree = $this->readRepository($set);
                    $found = false;

                    // commenting the check for already installed module
                    // if(isset($this->installedModules->{$set->owner."/".$set->repository."/".$module->path}) && $this->installedModules->{$set->owner."/".$set->repository."/".$module->path}->sha === $tree->sha) {
                    //     $this->log("/".$module->name." already installed", "", $indent + 1);
                    //     return;
                    // }

                    // set default value for ignoreIfAvailable
                    if(!isset($module->ignoreIfAvailable)) {
                        $module->ignoreIfAvailable = true;
                    }

                    if($module->ignoreIfAvailable && file_exists($installInDir."/".$module->name)) {
                        $this->log("/".$module->name." already installed", "", $indent + 1);
                        $this->actionsAfter($module, $installInDir."/".$module->name, $indent);
                        return;
                    }

                    if(file_exists($installInDir."/".$module->name)) {
                        $this->rmdir_recursive($installInDir."/".$module->name);
                    }
                    if(mkdir($installInDir."/".$module->name, 0777)) {
                        $this->log("/".$module->name, "", $indent + 1);
                    } else {
                        $this->error("/".$module->name." directory is no created", "", $indent + 1);
                    }
                    if(isset($tree->tree)) {
                        foreach($tree->tree as $item) {
                            if(($module->path == "" || $module->path == "/" || strpos($item->path, $module->path) === 0) && $item->path !== $module->path) {
                                $found = true;
                                if($item->type == "blob") {
                                    $content = $this->request($this->gitEndPointRaw.$set->owner."/".$set->repository."/".$tree->sha."/".$item->path, false);
                                    $path = $module->path != "" ? str_replace($module->path."/", "", $item->path) : $item->path;
                                    $fileToBeSaved = $installInDir."/".$module->name."/".$path;
                                    if(file_put_contents($fileToBeSaved, $content) !== false) {
                                        $this->log("/".$item->path, "", $indent + 2);
                                    } else {
                                        $this->error("/".$item->path." file is not added", "", $indent + 2);
                                    }
                                } else if($item->type == "tree") {
                                    $path = $module->path != "" ? str_replace($module->path."/", "", $item->path) : $item->path;
                                    $directoryToBeCreated = $installInDir."/".$module->name."/".$path;
                                    if(!file_exists($directoryToBeCreated)) {
                                        if(mkdir($directoryToBeCreated, 0777)) {
                                            $this->log("/".$path, "", $indent + 1);
                                        } else {
                                            $this->error("/".$path." directory is no created", "", $indent + 1);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if(!$found) {
                        $this->error("'".$module->path."' was not found in repository '".$set->owner."/".$set->repository."' (branch: '".$set->branch."')", $indent + 1);
                        rmdir($installInDir."/".$module->name);
                    } else {
                        $this->actionsAfter($module, $installInDir."/".$module->name, $indent);
                        if(isset($tree->sha)) {
                            $fileToBeSaved = $installInDir."/".$module->name."/commit.sha";
                            if(file_put_contents($fileToBeSaved, $tree->sha) !== false) {
                                $this->log("/".$module->name."/commit.sha sha=".$tree->sha."", "", $indent + 2);
                            } else {
                                $this->error("/".$module->name."/commit.sha file is node added", "", $indent + 2);
                            }
                            $this->installedModules->{$set->owner."/".$set->repository."/".$module->path} = (object) array("sha" => $tree->sha);
                            // checking for .json files
                            if ($handle = @opendir($installInDir."/".$module->name)) {
                                $entries = array();
                                while (false !== ($entry = readdir($handle))) {
                                    if(is_file($installInDir."/".$module->name."/".$entry) && strpos($entry, ".json") !== FALSE) {
                                        $this->installModules($installInDir."/".$module->name."/".$entry, $indent + 1);
                                    }
                                }
                                closedir($handle);
                            }
                        }
                    }
                }
                private function installFile($set, $installInDir, $indent) {

                    // set default value for ignoreIfAvailable
                    if(!isset($set->ignoreIfAvailable)) {
                        $set->ignoreIfAvailable = true;
                    }

                    if($set->ignoreIfAvailable && file_exists($installInDir."/".$set->name)) {
                        $this->log("/".$set->name." already installed", "", $indent + 1);
                        $this->actionsAfter($set, $installInDir."/".$set->name, $indent);
                        return;
                    }

                    if(file_exists($installInDir."/".$set->name)) {
                        $this->rmdir_recursive($installInDir."/".$set->name);
                    }
                    if(mkdir($installInDir."/".$set->name, 0777)) {
                        $this->log("/".$set->name, "", $indent + 1);
                    } else {
                        $this->error("/".$set->name." directory is no created", "", $indent + 1);
                    }
                    $content = $this->request($set->path, false);
                    $fileToBeSaved = $installInDir."/".$set->name."/".basename($set->path);
                    if(file_put_contents($fileToBeSaved, $content) !== false) {
                        if(strtolower(pathinfo($fileToBeSaved, PATHINFO_EXTENSION)) == "zip") {
                            $zip = new ZipArchive;
                            $res = $zip->open($fileToBeSaved);
                            if($res === TRUE) {
                                $zip->extractTo($installInDir."/".$set->name);
                                $zip->close();
                                @unlink($fileToBeSaved);
                            }
                        }
                        
                        $this->installedModules->{$set->name} = (object) array("path" => $set->path);
                        $this->log("/".$set->path, "", $indent + 2);
                        $this->actionsAfter($set, $installInDir."/".$set->name, $indent);
                    } else {
                        $this->error("/".$set->path." file is not added", "", $indent + 2);
                    }
                }
                private function readRepository(&$set) {
                    $repoPath = $set->owner."/".$set->repository."/branches/".$set->branch;
                    if(isset($this->gitRepos->{$repoPath})) {
                        if(isset($set->commit) && $set->commit == $this->gitRepos->{$repoPath}->sha) {
                            return $this->gitRepos->{$repoPath};
                        }
                    }
                    if(!isset($set->commit)) {
                        $masterBranchURL = $this->gitEndPoint."repos/".$set->owner."/".$set->repository."/branches/".$set->branch;
                        $masterBranch = $this->request($masterBranchURL);
                        $set->commit = $masterBranch->commit->sha;
                    }
                    $treeURL = $this->gitEndPoint."repos/".$set->owner."/".$set->repository."/git/trees/".$set->commit."?recursive=1";
                    $tree = $this->request($treeURL);
                    $this->gitRepos->{$repoPath} = $tree;
                    return $tree;
                }

                // formatting
                private function formatModules(&$set) {
                    if(!isset($set->modules)) {
                        $set->modules = array((object) array());
                    }
                    foreach($set->modules as $module) {
                        if(!isset($module->path)) {
                            $module->path = "";
                        }
                        if(!isset($module->name)) {
                            if($module->path === "") {
                                $module->name = $set->repository;
                            } else {
                                $pathParts = explode("/", $module->path);
                                $module->name = $pathParts[count($pathParts)-1];
                            }
                        }
                        $module->path = substr($module->path, strlen($module->path)-1, 1) == "/" ? substr($module->path, 0, strlen($module->path)-1) : $module->path;
                        $module->path = substr($module->path, 0, 1) == "/" ? substr($module->path, 1, strlen($module->path)) : $module->path;
                    }
                }

                // requesting
                private function request($url, $json = true) {
                    $ch = curl_init();
                    curl_setopt($ch,CURLOPT_URL, $url);
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
                    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,0);
                    if($this->gitHubUsername !== false && $this->gitHubPassword !== false) {
                        curl_setopt($ch, CURLOPT_USERPWD, $this->gitHubUsername.":".$this->gitHubPassword);
                        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    }
                    $content = curl_exec($ch);
                    curl_close($ch);
                    if($json) {
                        return json_decode($content);
                    } else {
                        return $content;
                    }
                }

                // validation
                private function shouldContain($ob, $properties, $displayMessage = false, $indent = 1) {
                    foreach($properties as $prop) {
                        if(!isset($ob->{$prop})) {
                            if($displayMessage) {
                                $this->error(str_replace("{prop}", $prop, "Missing property '{prop}'!"), $indent);
                            }
                            return false;
                        }
                    }
                    return true;
                }
                private function shouldBeNonEmptyArray($arr) {
                    return is_array($arr) && count($arr) > 0;
                }
                private function validatePackageJSON() {
                    global $argv;
                    if(!isset($argv[1])) {
                        $this->error("Please provide path to package.json file. Format: 'php [path to fabrico.php] [path to package.json]'.");
                        die();
                    }
                    $pathToJSON = $argv[1];
                    if(file_exists($pathToJSON)) {
                        $this->packageFile = $pathToJSON;
                    } else {
                        $this->error("Invalid path to package.json file.");
                        die();
                    }
                }
                private function validateSets($sets, $packageFile) {
                    if(gettype($sets) == "array") {
                        if(count($sets) === 0) {
                            $this->error($packageFile." has not defined modules.");
                            return false;
                        }
                    } else {
                        $this->warning($packageFile." has not a valid format.");
                        return false;
                    }
                    return true;
                }

                // output
                private function error($str, $indent = 0) {
                    $this->log("Error: ".$str, "RED", $indent);
                }
                private function warning($str) {
                    $this->log("Warning: ".$str, "YELLOW");
                }
                private function log($str, $color = "", $indent = 0) {
                    $colors = array(
                        "BLACK" => "\033[00;30m",
                        "RED" => "\033[00;31m",
                        "GREEN" => "\033[00;32m",
                        "YELLOW" => "\033[00;33m",
                        "BLUE" => "\033[00;34m",
                        "MAGENTA" => "\033[00;35m",
                        "CYAN" => "\033[00;36m",
                        "WHITE" => "\033[00;37m",
                        "" => ""
                    );
                    $indentStr = "";
                    for($i=0; $i<$indent; $i++) {
                        $indentStr .= "   ";
                    }
                    echo $colors[$color].$indentStr.$str."\033[39m\n";
                }

                // reporting
                private function reportResults() {
                    $this->log("\nInstalled Modules", "GREEN");
                    foreach($this->installedModules as $key => $value) {
                        $this->log($key, "GREEN", 1);
                    }
                    $this->log("\n");
                }

                // removing directory and its content
                private function rmdir_recursive($dir) {
                    $files = scandir($dir);
                    array_shift($files);    // remove '.' from array
                    array_shift($files);    // remove '..' from array
                   
                    foreach ($files as $file) {
                        $file = $dir . '/' . $file;
                        if (is_dir($file)) {
                            $this->rmdir_recursive($file);
                            @rmdir($file);
                        } else {
                            @unlink($file);
                        }
                    }
                    @rmdir($dir);
                }

                // actions
                private function actionsAfter($module, $dir, $indent) {
                    if(isset($module->actionsAfter)) {
                        $this->log("Executing actions for module ".$module->name.":", "MAGENTA", $indent+2); 
                        if(!is_array($module->actionsAfter)) {
                            $module->actionsAfter = array($module->actionsAfter);
                        }
                        foreach($module->actionsAfter as $action) {
                            if(isset($action->type)) {
                                switch ($action->type) {
                                    case "cmd":
                                        if($this->shouldContain($action, array("command"), true, $indent+3)) {
                                            $output = array();
                                            exec("cd ".$dir." && ".$action->command, $output);
                                            $this->log("> ".$action->command, "MAGENTA", $indent+2); 
                                            foreach ($output as $line) {
                                                $this->log("| ".$line, "MAGENTA", $indent+2); 
                                            }
                                        }
                                    break;
                                    case "replace":
                                        if($this->shouldContain($action, array("file", "searchFor", "replaceWith"), true, $indent+3)) {
                                            $fileContent = file_get_contents($dir."/".$action->file);
                                            $fileContent = str_replace($action->searchFor, $action->replaceWith, $fileContent);
                                            file_put_contents($dir."/".$action->file, $fileContent);
                                            $this->log("> file ".$dir."/".$action->file." updated", "MAGENTA", $indent+3);
                                        }
                                    break;
                                    case "copy": 
                                        if($this->shouldContain($action, array("path", "to"), true, $indent+3)) {
                                            if(!file_exists($dir."/".$action->to)) {
                                                mkdir($dir."/".$action->to, 0777);
                                            }
                                            $output = array();
                                            exec("cp -r ".$dir."/".$action->path." ".$dir."/".$action->to);
                                            $this->log("> coping ".$action->path." to ".$action->to, "MAGENTA", $indent+3); 
                                            foreach ($output as $line) {
                                                $this->log("| ".$line, "MAGENTA", $indent+3); 
                                            }
                                        }
                                    break;  
                                    case "delete":
                                        if($this->shouldContain($action, array("path"), true, $indent+3)) {
                                            if(file_exists($dir."/".$action->path)) {
                                                if(is_file($dir."/".$action->path)) {
                                                    if(unlink($dir."/".$action->path)) {
                                                        $this->log("> ".$action->path." deleted", "MAGENTA", $indent+3); 
                                                    }
                                                } else if(is_dir($dir."/".$action->path)) {
                                                    $this->rmdir_recursive($dir."/".$action->path);
                                                    $this->log("> ".$action->path." deleted", "MAGENTA", $indent+3); 
                                                }
                                                
                                            }
                                        }
                                    break;                                
                                    default:
                                        $this->error("Wrong action type '".$action->type."'", $indent+3);
                                    break;
                                }
                            } else {
                                $this->error("Every action should have 'type' property.", $indent+3);
                            }
                        }
                    }
                }

            }
        }
        
        new FabricoPackageManager();

    }

    /*************************************************************************************/
    /*                                                                                   */
    /* Fabrico loader                                                                    */
    /*                                                                                   */
    /*************************************************************************************/

    if(!class_exists("FabricoLoader") && php_sapi_name() != "cli") {

        class FabricoLoader {

            private $rootPath = "";
            private $currentPath = "";
            private $loaded;

            public function __construct() {
                $this->loaded = (object) array();
                $this->rootPath = $this->getPath(2);
                if(!file_exists(__DIR__."/".FABRICO_LOADER_CACHE_FILE)) {
                    $this->updateCache();
                }
                require(__DIR__."/".FABRICO_LOADER_CACHE_FILE);
            }
            public function loaded() {
                return $this->loaded;
            }
            public function loadModule() {
                $this->currentPath = $this->getPath();
                if(func_num_args() === 0) throw new Exception ("Invalid arguments of 'loadModule' method.");
                $modules = func_get_args();
                foreach($modules as $module) {
                    if(!$this->load(str_replace($this->rootPath, "", $this->currentPath).FABRICO_MODULES_DIR."/".$module."/index.php")) {
                        throw new Exception ("Missing module '".$module."'.");
                    }
                }
                return $this;
            }
            public function loadResource() {
                $this->currentPath = $this->getPath();
                if(func_num_args() === 0) throw new Exception ("Invalid arguments of 'loadResource' method.");
                $resources = func_get_args();
                foreach($resources as $resource) {
                    if(!$this->load(str_replace($this->rootPath, "", $this->currentPath).$resource)) {
                        throw new Exception ("Missing resource '".$resource."'.");
                    }
                }
                return $this;
            }
            private function load($resource) {
                $success = true;
                if(!isset($this->loaded->{$this->rootPath.$resource})) {
                    if(!$this->resolvePath($resource)) {
                        $this->updateCache();
                        if(!$this->resolvePath($resource)) {
                            $success = false;
                        }
                    }
                }
                return $success;
            }
            private function getPath($level = 1) {
                $trace = debug_backtrace();
                if(isset($trace[$level])) {
                    return dirname($trace[$level]["file"])."/";
                } else {
                    return dirname($_SERVER["SCRIPT_FILENAME"])."/";
                }
            }
            private function resolvePath($path, $tree = null, $originalPath = "") {
                global $FABRICO_TREE;
                if(is_string($path)) {
                    $path = substr($path, 0, 1) === "/" ? substr($path, 1, strlen($path)) : $path;
                    $path = substr($path, 0, 2) === "./" ? substr($path, 2, strlen($path)) : $path;
                    $originalPath = $path;
                    $path = explode("/", $path);
                }
                if($tree === null) {
                    $tree = $FABRICO_TREE->files;
                }
                $entry = array_shift($path);
                if(isset($tree->$entry)) {
                    if(is_string($tree->$entry) && $tree->$entry === "file") {
                        $this->requireResource($originalPath);
                        return true;
                    } else {
                        return $this->resolvePath($path, $tree->$entry, $originalPath);
                    }
                } else if($entry === "*") {
                    foreach($tree as $key => $value) {
                        if(is_string($value) && $value === "file") {
                            $file = str_replace("/*", "/", $originalPath).$key;
                            $this->requireResource($file);
                        } else {
                            $this->resolvePath(str_replace("/*", "/".$key."/*", $originalPath));
                        }
                    }
                    return true;
                } else {
                    return false;
                }
            }
            private function requireResource($file) {
                $this->loaded->{$this->rootPath.$file} = true;
                require($this->rootPath.$file);
            }
            public function updateCache() {
                global $FABRICO_TREE;
                $files = (object) array();
                $this->readDir($this->rootPath, &$files);
                $FABRICO_TREE = (object) array(
                    "files" => $files
                );
                $content = '<?php ';
                $content .= 'global $FABRICO_TREE; ';
                $content .= '$FABRICO_TREE = json_decode(\''.json_encode($FABRICO_TREE).'\');';
                $content .= ' ?>';
                file_put_contents(__DIR__."/".FABRICO_LOADER_CACHE_FILE, $content);
                chmod(__DIR__."/".FABRICO_LOADER_CACHE_FILE, 0777);
            }
            private function readDir($dir, $obj) {
                if ($handle = @opendir($dir)) {
                    $entries = array();
                    while (false !== ($entry = readdir($handle))) {
                        if ($entry != "." && $entry != "..") {
                            $entries []= $entry;
                        }
                    }
                    sort($entries);
                    foreach ($entries as $entry) {
                        if(is_dir($dir."/".$entry)) {
                            $dirPath = str_replace($this->rootPath, "", $dir."/".$entry);
                            $this->readDir($dir."/".$entry, $obj->$entry = (object) array());
                        } else if(is_file($dir."/".$entry)) {
                            if(strpos($entry, ".php") !== FALSE) {
                                $obj->$entry = "file";
                            }
                        }
                    }
                    closedir($handle);
                }
                return $obj;
            }
        }

        global $F;
        $F = new FabricoLoader();

    }

?>
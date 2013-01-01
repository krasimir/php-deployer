<?php

    class SVN {

        private $app;
        private $destination;

        public function __construct($app) {
            $this->app = $app;
            $this->destination = __DIR__."/..".$app->destination;
        }
        public function revisions() {

            // checking if destination exists
            if(!file_exists($this->destination)) {
                return view("error.html", array(
                    "message" => "The destination: <strong>".$this->destination."</strong> doesn't exist."
                ));
            }

            if(!file_exists($this->destination.".svn")) {
                $cmd = "svn checkout --non-interactive --force ".$this->app->source." ".$this->destination." --username ".$this->app->user." --password ".$this->app->pass;
                $cmdPreview = "svn checkout ".$this->app->source." ...";
                return view("error.html", array(
                    "message" => "The destination, <strong>".$this->destination."</strong> doesn't have <strong>.svn</strong> folder, which probably means that there is no SVN initialized there.<br /><br />".view("form.command.html", array(
                        "cmd" => $cmd,
                        "cmdPreview" => $cmdPreview,
                        "callback" => "/apps/".$this->app->id,
                        "label" => "Perform 'svn checkout'"
                    ))
                ));
            }

            $revisionsMarkup = "";

            // Getting current revesion
            $shell = new Shell("cd ".$this->destination." && svn info --xml --username ".$this->app->user." --password ".$this->app->pass);
            $result = $shell->toString();
            // $result = file_get_contents(__DIR__."/../testdata/svninfo.xml"); // for testing purpose
            $xml = simplexml_load_string($result);
            $currentRevision = 1;
            if(isset($xml->entry)) {
                $attributes = $xml->entry->attributes();
                $currentRevision = $attributes->revision;
                $revisionsMarkup .= view("info.html", array("message" => "Current revision: ".$currentRevision));
            }

            // Getting latest commits
            $startFromRevision = $currentRevision-10 < 1 ? 1 : $currentRevision-10;
            $cmd = "cd ".$this->destination." && svn log -v -r ".$startFromRevision.":HEAD --non-interactive --xml --with-all-revprops --username ".$this->app->user." --password ".$this->app->pass;
            $shell = new Shell($cmd);
            $result = $shell->toString();
            // $result = file_get_contents(__DIR__."/../testdata/svnlog.xml"); // for testing purpose
            $xml = simplexml_load_string($result);
            $revisionsRows = array();
            $revisionsMarkup .= '<table class="table table-bordered">';
            if(isset($xml->logentry)) {
                foreach($xml->logentry as $entry) {
                    $revisionsRows []= $entry;
                }
                $revisionsRows = array_reverse($revisionsRows);
                foreach($revisionsRows as $entry) {
                    $attributes = $entry->attributes();
                    $files = "";
                    if(isset($entry->paths)) {
                        foreach($entry->paths->path as $path) {
                            $pathAttributes = $path->attributes();
                            if($pathAttributes) {
                                $files .= $pathAttributes->action." ".$path."<br />";
                            }                            
                        }
                    }
                    $tpl = "revision.html";
                    if((int)$currentRevision == (int)(isset($attributes->revision) ? $attributes->revision : "")) {
                        $tpl = "revision.current.html";
                    }
                    $revisionsMarkup .= view($tpl, array(
                        "revision" => isset($attributes->revision) ? $attributes->revision : "",
                        "author" => isset($entry->author) ? $entry->author : "",
                        "message" => isset($entry->msg) ? $entry->msg : "",
                        "date" => isset($entry->date) ? $entry->date : "",
                        "files" => $files,
                        "action-release" => view("form.command.html", array(
                            "cmd" => isset($attributes->revision) ? "cd ".$this->destination." && svn update --non-interactive --force -r ".$attributes->revision." --username ".$this->app->user." --password ".$this->app->pass : "",
                            "cmdPreview" => "svn update -r ".$attributes->revision,
                            "callback" => "/apps/".$this->app->id,
                            "label" => "release"
                        ))
                    ));
                }
            }
            $revisionsMarkup .= '</table>';
            return $revisionsMarkup;

        }

    }

// class SVNContext
// {
//     private $appContext;
//     private $releaseConfig;
    
//     private $svnURI;
//     private $svnACC;
//     private $HOME;
    
//     public function __construct($appContext)
//     {
//         $this->appContext = $appContext;
//     }
    
//     public function getSVNVersion()
//     {
//         $cmd = "svn --version";
//         echo system($cmd);
//     }
    
//     public function getLatestRevisionNumber($releaseConfig, $source = NULL)
//     {
//         if(empty($source))
//             $source = $releaseConfig->getSourceURI();
            
//         $svnACC = "--username ".$releaseConfig->username." --password ".$releaseConfig->password;
            
//         $cmd = "svn info --non-interactive {$source} {$svnACC} | grep '^Revision: ' | cut -d' ' -f2";
//         echo "<!-- revisions system call log: ".$cmd;
//         $latest = system($cmd);
//         echo "-->";
//         return $latest;
//     }
    
//     public function export($releaseConfig, $revision = NULL,  $source = NULL, $target = NULL, $dryrun = NULL)
//     {
//         if(empty($target))
//             $target = $this->appContext->getFullPath($releaseConfig->target);

//         if(empty($source))
//             $source = $releaseConfig->getSourceURI();
            
//         if(empty($revision))
//             $revision = $releaseConfig->revisionNumber;
            
//         $svnACC = "--username ".$releaseConfig->username." --password ".$releaseConfig->password;
        
//         if(!empty($revision))
//             $cmd = "svn export --non-interactive --force -r {$revision} {$source} {$target} {$svnACC}";
//         else
//             $cmd = "svn export --non-interactive --force {$source} {$target} {$svnACC}";
            
//         echo "export : $cmd \n";
//         if(empty($dryrun)) {
            
//             ob_implicit_flush();
//             system($cmd, &$return_var);
//             if($return_var!=0)
//                 return false;
//         }
//         return true;
//     }

//     public function checkout($releaseConfig, $revision = NULL,  $source = NULL, $target = NULL, $dryrun = NULL)
//     {
//         if(empty($target))
//             $target = $this->appContext->getFullPath($releaseConfig->target);

//         if(empty($source))
//             $source = $releaseConfig->getSourceURI();
            
//         if(empty($revision))
//             $revision = $releaseConfig->revisionNumber;
            
//         $svnACC = "--username ".$releaseConfig->username." --password ".$releaseConfig->password;
        
//         if(is_dir($target."/.svn")) {
//             if(!empty($revision))
//                 $cmd = "svn checkout --non-interactive --force -r {$revision} {$source} {$target} {$svnACC}";
//             else
//                 $cmd = "svn checkout --non-interactive --force {$source} {$target} {$svnACC}";
//         }
//         else {
//             if(!empty($revision))
//                 $cmd = "svn checkout --non-interactive --force -r {$revision} {$source} {$target} {$svnACC}";
//             else
//                 $cmd = "svn checkout --non-interactive --force {$source} {$target} {$svnACC}";
//         }
            
//         echo "checkout : $cmd \n";
//         if(empty($dryrun)) {
            
//             ob_implicit_flush();
//             system($cmd, &$return_var);
//             if($return_var!=0)
//                 return false;
//         }
//         return true;
//     }
// }
?>

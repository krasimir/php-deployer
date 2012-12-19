<?php
class SVNContext
{
    private $appContext;
    private $releaseConfig;
    
    private $svnURI;
    private $svnACC;
    private $HOME;
    
    public function __construct($appContext)
    {
        $this->appContext = $appContext;
    }
    
    public function getSVNVersion()
    {
        $cmd = "svn --version";
        echo system($cmd);
    }
    
    public function getLatestRevisionNumber($releaseConfig, $source = NULL)
    {
        if(empty($source))
            $source = $releaseConfig->getSourceURI();
            
        $svnACC = "--username ".$releaseConfig->username." --password ".$releaseConfig->password;
            
        $cmd = "svn info --non-interactive {$source} {$svnACC} | grep '^Revision: ' | cut -d' ' -f2";
        echo "<!-- revisions system call log: ".$cmd;
        $latest = system($cmd);
        echo "-->";
        return $latest;
    }
    
    public function export($releaseConfig, $revision = NULL,  $source = NULL, $target = NULL, $dryrun = NULL)
    {
        if(empty($target))
            $target = $this->appContext->getFullPath($releaseConfig->target);

        if(empty($source))
            $source = $releaseConfig->getSourceURI();
            
        if(empty($revision))
            $revision = $releaseConfig->revisionNumber;
            
        $svnACC = "--username ".$releaseConfig->username." --password ".$releaseConfig->password;
        
        if(!empty($revision))
            $cmd = "svn export --non-interactive --force -r {$revision} {$source} {$target} {$svnACC}";
        else
            $cmd = "svn export --non-interactive --force {$source} {$target} {$svnACC}";
            
        echo "export : $cmd \n";
        if(empty($dryrun)) {
            
            ob_implicit_flush();
            system($cmd, &$return_var);
            if($return_var!=0)
                return false;
        }
        return true;
    }

    public function checkout($releaseConfig, $revision = NULL,  $source = NULL, $target = NULL, $dryrun = NULL)
    {
        if(empty($target))
            $target = $this->appContext->getFullPath($releaseConfig->target);

        if(empty($source))
            $source = $releaseConfig->getSourceURI();
            
        if(empty($revision))
            $revision = $releaseConfig->revisionNumber;
            
        $svnACC = "--username ".$releaseConfig->username." --password ".$releaseConfig->password;
        
        if(is_dir($target."/.svn")) {
            if(!empty($revision))
                $cmd = "svn checkout --non-interactive --force -r {$revision} {$source} {$target} {$svnACC}";
            else
                $cmd = "svn checkout --non-interactive --force {$source} {$target} {$svnACC}";
        }
        else {
            if(!empty($revision))
                $cmd = "svn checkout --non-interactive --force -r {$revision} {$source} {$target} {$svnACC}";
            else
                $cmd = "svn checkout --non-interactive --force {$source} {$target} {$svnACC}";
        }
            
        echo "checkout : $cmd \n";
        if(empty($dryrun)) {
            
            ob_implicit_flush();
            system($cmd, &$return_var);
            if($return_var!=0)
                return false;
        }
        return true;
    }
}
?>

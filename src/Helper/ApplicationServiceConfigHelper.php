<?php

namespace Reliv\Deploy\Helper;

use Zend\Config\Config;

class ApplicationServiceConfigHelper
{
    public $appConfig;

    public $nextRelease;

    /**
     * Constructor
     * 
     * @param Config $appConfig Applications Config
     */
    public function __construct(Config $appConfig)
    {
        $this->appConfig = $appConfig;
    }
    
    /**
     * Get the Application Base Directory
     *
     * @return string
     */
    public function getAppBaseDir()
    {
        $baseDir = $this->getDeployConfig()->get('location');
        $baseDir = rtrim($baseDir, '/\\');

        return $baseDir;
    }

    /**
     * Get the Current Release Directory
     *
     * @return string
     */
    public function getSymLinkPath()
    {
        $baseDir = $this->getAppBaseDir();
        $symlink = $this->getSymlinkConfigPath();
        $currentReleaseDir = $baseDir.DIRECTORY_SEPARATOR.$symlink;
        $currentReleaseDir = rtrim($currentReleaseDir, "/\\\t\n\r\0\x0B");

        return $currentReleaseDir;
    }

    /**
     * Get the actual directory of the current release
     *
     * @return null|string
     */
    public function getCurrentReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $symLinkPath = $this->getSymlinkPath();
        $actualRelease = @readlink($symLinkPath);

        if (!$actualRelease) {
            return null;
        }

        $temp = explode(DIRECTORY_SEPARATOR, $actualRelease);
        $currentRelease = array_pop($temp);

        if (!$currentRelease) {
            return null;
        }

        $currentReleaseDir = $baseDir.DIRECTORY_SEPARATOR.$currentRelease;
        $currentReleaseDir = rtrim($currentReleaseDir, "/\\\t\n\r\0\x0B");

        return $currentReleaseDir;
    }

    /**
     * Get the new or next Release Directory
     *
     * @return string
     */
    public function getNewReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $newRevision = $this->getNewRevision();
        $releaseDir = $baseDir.DIRECTORY_SEPARATOR.$newRevision;
        $releaseDir = rtrim($releaseDir, "/\\\t\n\r\0\x0B");

        return $releaseDir;
    }

    /**
     * Get the next revision number
     *
     * @return bool|string
     */
    public function getNewRevision()
    {
        if (!empty($this->nextRelease)) {
            return $this->nextRelease;

        }
        $microTimestamp = microtime(true);
        $timestamp = floor($microTimestamp);
        $milliseconds = round(($microTimestamp - $timestamp) * 1000000);

        $this->nextRelease = date(preg_replace('`(?<!\\\\)u`', $milliseconds, 'Y.m.d.H.i.s.u'), $timestamp);

        return $this->nextRelease;
    }

    /**
     * Get the Pre Deploy hook for the application
     *
     * @return string|null
     */
    public function getPreDeployHook()
    {
        return $this->getDeployHooks()->get('pre_deploy', null);
    }

    /**
     * Get the Post Deploy hook for the application
     *
     * @return string|null
     */
    public function getPostDeployHook()
    {
        return $this->getDeployHooks()->get('post_deploy', null);
    }

    /**
     * Get the Pre Rollback hook for the application
     *
     * @return string|null
     */
    public function getPreRollbackHook()
    {
        return $this->getDeployHooks()->get('pre_rollback', null);
    }

    /**
     * Get the Post Rollback hook for the application
     *
     * @return string|null
     */
    public function getPostRollbackHook()
    {
        return $this->getDeployHooks()->get('post_rollback', null);
    }

    /**
     * Get all the deploy hooks for the application
     *
     * @return Config
     */
    public function getDeployHooks()
    {
        return $this->getDeployConfig()->get('hooks', new Config(array()));
    }

    /**
     * Get the config for deployment
     *
     * @return Config
     */
    public function getDeployConfig()
    {
        return $this->getApplicationConfig()->get('deploy', new Config(array()));
    }

    /**
     * Get the number of revisions to keep
     *
     * @return integer
     */
    public function getNumberOfRevisionsToKeep()
    {
        return $this->getDeployConfig()->get('revisions', 0);
    }

    /**
     * Get the repository configs
     *
     * @return Config
     */
    public function getRepositoryConfig()
    {
        return $this->getApplicationConfig()->get('repositories', new Config(array()));
    }

    /**
     * Get the configured symlink path
     *
     * @return string
     */
    public function getSymlinkConfigPath()
    {
        return $this->getDeployConfig()->get('symlink');
    }

    /**
     * Get the Applications config
     *
     * @return Config
     */
    public function getApplicationConfig()
    {
        return $this->appConfig;
    }
}

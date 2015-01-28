<?php

namespace Reliv\Deploy\Service;


use Psr\Log\LoggerInterface;
use Reliv\Deploy\Exception\InvalidApplicationConfigException;
use Reliv\Deploy\Exception\InvalidSystemConfigException;
use Reliv\Git\Service\Git;
use Zend\Config\Config;

class Application
{
    protected $appName;

    /** @var \Zend\Config\Config  */
    protected $appConfig;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;


    /**
     * @var array
     */
    protected $repositories = array();

    /*
     * Place Holders
     */
    /**
     * @var string Place holder for Next Release
     */
    protected $nextRelease;

    /**
     * @var array Place Holder for Deployed Versions
     */
    protected $deployedVersions = array();

    public function __construct(
        $appName,
        Config $appConfig,
        LoggerInterface $logger
    ) {
        if (empty($appName)) {
            throw new \RuntimeException(
                'App name must be provided.'
            );
        }

        $this->appName = $appName;
        $this->logger = $logger;
        $this->appConfig = $appConfig;
        $this->nextRelease = $this->getNewRevision();
        $this->setupRepoHelpers();
    }

    public function deploy()
    {
        $this->logger->info('Checking Application '.$this->appName);

        if (!$this->needsUpdate()) {
            $this->logger->notice('Application '.$this->appName.' is current.  Nothing to update.');
            return;
        }

        $this->logger->notice('Application "'.$this->appName.'" is out of date.  Updating');

        $this->nextRelease = $this->getNewRevision();

        $baseDir = $this->getAppBaseDir();
        $releaseToDir = $this->getNewReleaseDir();
        $currentReleaseSymlink = $this->getCurrentReleaseDir();

        $this->createDirectory($baseDir);
        $this->createDirectory($releaseToDir);

        foreach ($this->appConfig['repositories'] as $repoKey => $repoConfig) {
            $repository = $this->getRepoHelper($repoKey);
            $repository->update();
        }

        @unlink($currentReleaseSymlink);
        symlink($releaseToDir, $currentReleaseSymlink);
    }

    protected function needsUpdate()
    {
        $this->logger->debug('Checking to see if app needs updates');

        $baseDir = $this->getAppBaseDir();
        $currentReleaseDir = $this->getCurrentReleaseDir();

        $this->logger->debug('Checking to see if app directory exists for '.$this->appName.' at: '.$baseDir);
        if (!is_dir($baseDir)) {
            $this->logger->debug('App Directory does not exist.  Needs updates.');
            return true;
        }

        $this->logger->debug('Checking to see if symlink directory exists for '.$this->appName.' at: '.$currentReleaseDir);
        if (!is_dir($currentReleaseDir)) {
            $this->logger->debug('Symlink does not exist.  Needs updates.');
            return true;
        }

        $this->logger->debug('Symlink does exist.  Checking Repositories for updates.');

        foreach ($this->appConfig['repositories'] as $repoKey => $repoConfig) {
            $this->logger->info('Checking Repository '.$repoKey);
            $this->logger->debug('Checking to see if symlink directory exists for '.$this->appName.' at: '.$currentReleaseDir);
            if (!is_dir($currentReleaseDir)) {
                $this->logger->debug('Symlink does not exist.  Needs updates.');
                return true;
            }

            $repoHelper = $this->getRepoHelper($repoKey);

            if ($repoHelper->needsUpdate()) {
                $this->logger->debug('Repository "'.$repoKey.'" needs updates.');
                return true;
            }
        }

        $this->logger->debug('No updates needed at this time');
        return false;
    }


    protected function getNewRevision()
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

    protected function getAppBaseDir()
    {
        $baseDir = $this->appConfig['deploy']['location'];
        $baseDir = rtrim($baseDir,'/\\');

        return $baseDir;
    }

    protected function getCurrentReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $currentReleaseDir = $baseDir.DIRECTORY_SEPARATOR.$this->appConfig['deploy']['symlink'];
        $currentReleaseDir = rtrim($currentReleaseDir,"/\\\t\n\r\0\x0B");

        return $currentReleaseDir;
    }

    protected function getNewReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $releaseDir = $baseDir.DIRECTORY_SEPARATOR.$this->nextRelease;
        $releaseDir = rtrim($releaseDir,"/\\\t\n\r\0\x0B");

        return $releaseDir;
    }

    protected function createDirectory($dir)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException(
                    "Unable to create directory: ".$dir
                );
            }
            $this->logger->debug('Created Directory: '.$dir);
        } else {
            $this->logger->debug('Directory already exists: '.$dir);
        }
    }

    /**
     * @param $name
     * @return \Reliv\Deploy\Vcs\VcsInterface
     */
    protected function getRepoHelper($name)
    {
        if (!$this->repositories[$name]) {
            throw new \RuntimeException(
                "Unable to retreive repo helper for: ".$name
            );
        }

        return $this->repositories[$name];
    }

    protected function setupRepoHelpers()
    {
        foreach ($this->appConfig['repositories'] as $repoName => $repoConfig)
        {
            $repoClass = $this->vcsMapper($repoConfig['type']);

            if (!class_exists($repoClass) || !in_array('Reliv\Deploy\Vcs\VcsInterface', class_implements($repoClass))) {
                throw new \RuntimeException(
                    "Invalid Repository Type: ".$repoConfig['type']
                );
            }

            /** @var \Reliv\Deploy\Vcs\VcsInterface $repoHelper */
            $repoHelper = new $repoClass;
            $repoHelper->setConfig($repoConfig);
            $repoHelper->setCurrentReleaseAppDir($this->getCurrentReleaseDir());
            $repoHelper->setNextReleaseAppDir($this->getNewReleaseDir());
            $repoHelper->setLogger($this->logger);

            $this->repositories[$repoName] = $repoHelper;
        }
    }

    protected function vcsMapper($type)
    {
        switch ($type) {
            case 'git':
            case 'Git':
            case 'GIT':
            case 'Reliv\Deploy\Vcs\GitRepo':
                return 'Reliv\Deploy\Vcs\GitRepo';
            default:
                return $type;
        }
    }

}
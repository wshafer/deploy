<?php
/**
 * Application Service Helper
 *
 * Application Service Helper
 *
 * PHP version 5.4
 *
 * LICENSE: License.txt New BSD License
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */
namespace Reliv\Deploy\Service;

use Psr\Log\LoggerInterface;
use Reliv\Deploy\Service\LoggerService;
use Reliv\Deploy\Vcs\Status;
use Zend\Config\Config;

/**
 * Application Service Helper
 *
 * Application Service Helper.  This class provides the needed methods to handle a deployment for an application.
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class Application
{
    /** @var string Application Name */
    protected $appName;

    /** @var \Zend\Config\Config Application Config */
    protected $appConfig;

    /** @var LoggerService Logger */
    protected $loggerService;

    /*
     * Place Holders
     */

    /** @var array Place Holder for repository objects */
    protected $repositories = array();

    /** @var string Place holder for Next Release  */
    protected $nextRelease;

    /** @var array Place Holder for Deployed Versions */
    protected $deployedVersions = array();

    protected $logger;

    /**
     * Constructor
     *
     * @param string        $appName       Name of the application
     * @param Config        $appConfig     Config for the Application deployment
     * @param LoggerService $loggerService Reliv Deploy Logger Factory
     */
    public function __construct(
        $appName,
        Config $appConfig,
        LoggerService $loggerService
    ) {
        if (empty($appName)) {
            throw new \RuntimeException(
                'App name must be provided.'
            );
        }

        $this->appName = $appName;
        $this->loggerService = $loggerService;
        $this->appConfig = $appConfig;
        $this->nextRelease = $this->getNewRevision();
    }

    /**
     * Get a status message for the application
     *
     * @return array
     */
    public function getStatusMessage()
    {
        $message = array();

        $currentReleaseDir = $this->getCurrentReleaseDir();

        if (!is_dir($currentReleaseDir)) {
            $message[] = $this->appName. ' is not currently deployed.';
            return $message;
        }

        $repositories = $this->getRepoHelpers();

        $message[] = 'Status of '.$this->appName.':';
        $message[] = '';

        /**
         * @var string                         $repoKey    Name of repo
         * @var \Reliv\Deploy\Vcs\VcsInterface $repository Repository Helper
         */
        foreach ($repositories as $repoName => $repository) {
            $status = $repository->getStatus();

            if (!$status instanceof Status) {
                throw new \RuntimeException(
                    'Status returned from '.$repoName.' is not valid.'
                );
            }

            $message[] = $repoName;
            $message[] = 'Last deployed         : '.$status->getDeployedDate();
            $message[] = 'Deployed version      : '.$status->getDeployedVersion();
            $message[] = 'Current Remote Version: '.$status->getVcsVersion();
            $message[] = '';
        }

        return $message;
    }

    /**
     * Deploy method.  This method will first check to see if the application needs to update, if it does it
     * will deploy the application to the passed in target directory
     *
     * @return void
     */
    public function deploy()
    {
        $logger = $this->getLogger();

        $logger->info('Checking Application '.$this->appName);

        if (!$this->needsUpdate()) {
            $logger->info('Application '.$this->appName.' is current.  Nothing to update.');
            return;
        }

        $logger->notice('Application "'.$this->appName.'" is out of date.  Updating');

        $this->nextRelease = $this->getNewRevision();

        $baseDir = $this->getAppBaseDir();
        $releaseToDir = $this->getNewReleaseDir();
        $symlink = $this->getCurrentReleaseDir();

        $this->createDirectory($baseDir);
        $this->createDirectory($releaseToDir);

        $repositories = $this->getRepoHelpers();

        /**
         * @var string                         $repoKey    Name of repo
         * @var \Reliv\Deploy\Vcs\VcsInterface $repository Repository Helper
         */
        foreach ($repositories as $repository) {
            $repository->update();
        }

        @unlink($symlink);
        symlink($releaseToDir, $symlink);
        $logger->notice('Application "'.$this->appName.'" deployed.');
    }

    /**
     * Does the application need to update?
     *
     * @return bool
     */
    protected function needsUpdate()
    {
        $logger = $this->getLogger();

        $logger->debug('Checking to see if app needs updates');

        $baseDir = $this->getAppBaseDir();
        $currentReleaseDir = $this->getCurrentReleaseDir();

        $logger->debug('Checking to see if app directory exists for '.$this->appName.' at: '.$baseDir);
        if (!is_dir($baseDir)) {
            $logger->debug('App Directory does not exist.  Needs updates.');
            return true;
        }

        $logger->debug(
            'Checking to see if symlink directory exists for '.$this->appName.' at: '.$currentReleaseDir
        );

        if (!is_dir($currentReleaseDir)) {
            $logger->debug('Symlink does not exist.  Needs updates.');
            return true;
        }

        $logger->debug('Symlink does exist.  Checking Repositories for updates.');

        $repositories = $this->getRepoHelpers();

        /**
         * @var string                         $repoKey    Name of repo
         * @var \Reliv\Deploy\Vcs\VcsInterface $repository Repository Helper
         */
        foreach ($repositories as $repoKey => $repository) {
            $logger->info('Checking Repository '.$repoKey);
            $logger->debug(
                'Checking to see if symlink directory exists for '.$this->appName.' at: '.$currentReleaseDir
            );

            if (!is_dir($currentReleaseDir)) {
                $logger->debug('Symlink does not exist.  Needs updates.');
                return true;
            }

            if ($repository->needsUpdate()) {
                $logger->debug('Repository "'.$repoKey.'" needs updates.');
                return true;
            }
        }

        $logger->debug('No updates needed at this time');
        return false;
    }

    /**
     * Get the next revision number
     *
     * @return bool|string
     */
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

    /**
     * Get the Application Base Directory
     *
     * @return string
     */
    protected function getAppBaseDir()
    {
        $baseDir = $this->appConfig['deploy']['location'];
        $baseDir = rtrim($baseDir, '/\\');

        return $baseDir;
    }

    /**
     * Get the Current Release Directory
     *
     * @return string
     */
    protected function getCurrentReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $currentReleaseDir = $baseDir.DIRECTORY_SEPARATOR.$this->appConfig['deploy']['symlink'];
        $currentReleaseDir = rtrim($currentReleaseDir, "/\\\t\n\r\0\x0B");

        return $currentReleaseDir;
    }

    /**
     * Get the new or next Release Directory
     *
     * @return string
     */
    protected function getNewReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $releaseDir = $baseDir.DIRECTORY_SEPARATOR.$this->nextRelease;
        $releaseDir = rtrim($releaseDir, "/\\\t\n\r\0\x0B");

        return $releaseDir;
    }

    /**
     * Create a Directory
     *
     * @param string $dir Directory Path to create
     *
     * @return void
     */
    protected function createDirectory($dir)
    {
        $logger = $this->getLogger();

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException(
                    "Unable to create directory: ".$dir
                );
            }
            $logger->debug('Created Directory: '.$dir);
        } else {
            $logger->debug('Directory already exists: '.$dir);
        }
    }

    /**
     * Get a single repository helper
     *
     * @param string $name Name or config key for Repo
     *
     * @return \Reliv\Deploy\Vcs\VcsInterface
     */
    protected function getRepoHelper($name)
    {
        $repositories = $this->getRepoHelpers();

        if (!$repositories[$name]) {
            return null;
        }

        return $repositories[$name];
    }

    /**
     * Get all the Repo Helpers for the application.  If none are found use the application config to build them
     *
     * @return array
     */
    protected function getRepoHelpers()
    {
        if ($this->repositories) {
            return $this->repositories;
        }

        foreach ($this->appConfig['repositories'] as $repoName => $repoConfig) {
            $repoClass = $this->vcsMapper($repoConfig['type']);

            if (!class_exists($repoClass) || !in_array('Reliv\Deploy\Vcs\VcsInterface', class_implements($repoClass))) {
                throw new \RuntimeException(
                    "Invalid Repository Type: ".$repoConfig['type']
                );
            }

            /** @var \Reliv\Deploy\Vcs\VcsInterface $repoHelper */
            $repoHelper = new $repoClass;
            $repoHelper->setName($repoName);
            $repoHelper->setConfig($repoConfig);
            $repoHelper->setCurrentReleaseAppDir($this->getCurrentReleaseDir());
            $repoHelper->setNextReleaseAppDir($this->getNewReleaseDir());
            $repoHelper->setLoggerService($this->loggerService);

            $this->repositories[$repoName] = $repoHelper;
        }

        return $this->repositories;
    }

    /**
     * Vcs mapper.  Map a type to the correct helper class
     *
     * @param string $type Type of VCS to mapp
     *
     * @return string
     */
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

    /**
     * Get PSR3 logger from the logger factory
     *
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = $this->loggerService->getLogger($this->appName);
        }

        return $this->logger;
    }
}

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
use Reliv\Deploy\Vcs\StatusMessageInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Zend\Config\Config;
use Symfony\Component\Process\Process;

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

            if (!$status instanceof StatusMessageInterface) {
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
        $symlink = $this->getSymLinkPath();
        $currentReleaseDir = $this->getCurrentReleaseDir();

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

        /*
         * Trigger the Pre Deploy Hook
         */
        $preDeployHook = $this->getPreDeployHook();

        if ($preDeployHook) {
            try {
                $logger->debug("Running pre deploy hook: ".$preDeployHook);
                $this->runHook('pre_deploy', $preDeployHook, $releaseToDir);
            } catch (ProcessFailedException $e) {
                $logger->error('Failed to run command: '.$preDeployHook);
                $this->delTree($releaseToDir);
                $logger->error('Deployment Failed');
                return;
            }
        }

        @unlink($symlink);
        symlink($releaseToDir, $symlink);

        /*
         * Trigger the Pre Deploy Hook
         */
        $postDeployHook = $this->getPostDeployHook();

        if ($postDeployHook) {
            try {
                $logger->debug("Running post deploy hook: ".$postDeployHook);
                $this->runHook('post_deploy', $postDeployHook, $releaseToDir);
            } catch (ProcessFailedException $e) {
                $logger->error('Failed to run command: '.$postDeployHook);

                if ($currentReleaseDir) {
                    $logger->error('Deployment Failed.  Preforming a rollback.');
                    $this->rollback($releaseToDir, $currentReleaseDir);
                } else {
                    $logger->error('Deployment Failed.');
                }

                return;
            }
        }

        $logger->debug("Application deployed.  Running post_deploy hook");

        $logger->notice('Application "'.$this->appName.'" deployed.');
    }

    /**
     * Preform a rollback
     *
     * @param string $currentRelease  Current Release Dir (Used by deploy)
     * @param string $previousRelease Previous Release Dir (Used by deploy)
     *
     * @return void
     */
    public function rollback($currentRelease = null, $previousRelease = null)
    {
        $logger = $this->getLogger();
        $logger->info('Beginning Rollback for: '.$this->appName);

        $appDir = $this->getAppBaseDir();
        $symlink = $this->getSymLinkPath();

        if (!is_dir($appDir)) {
            $logger->error("No deployments found for:" .$this->appName);
            return;
        }

        if (!$currentRelease) {
            $currentRelease = $this->getCurrentReleaseDir();
        }

        if (!$previousRelease) {
            $previousRelease = $this->getPreviousReleaseDir();
        }

        if (!is_dir($previousRelease)) {
            $logger->error("No previous deployments found for: ".$this->appName);
            return;
        }

        /*
         * Trigger the pre_rollback hook
         */
        $preRollbackHook = $this->getPreRollbackHook();

        if ($preRollbackHook) {
            try {
                $logger->debug("Running pre deploy hook: ".$preRollbackHook);
                $this->runHook('pre_rollback', $preRollbackHook, $previousRelease);
            } catch (ProcessFailedException $e) {
                $logger->error('Failed to run command: '.$preRollbackHook);
                $logger->error('Rollback failed.  No changes made to the system.');
                return;
            }
        }

        $logger->debug("Switching symlinks.");
        @unlink($symlink);
        symlink($previousRelease, $symlink);

        $logger->debug("Deleting broken version");
        $this->delTree($currentRelease);

        /*
         * Trigger the pre_rollback hook
         */
        $postRollbackHook = $this->getPostRollbackHook();

        if ($postRollbackHook) {
            try {
                $logger->debug("Running post deploy hook: ".$postRollbackHook);
                $this->runHook('post_rollback', $postRollbackHook, $previousRelease);
            } catch (ProcessFailedException $e) {
                $logger->error('Failed to run command: '.$postRollbackHook);
                $logger->error('Post rollback failed.  System in unclean state.  Manual intervention is required');
                return;
            }
        }

        $logger->notice($this->appName.": Rollback complete.");
    }

    /**
     * Run a script hook
     *
     * @param string $type       Type of hook
     * @param string $hook       Command to run
     * @param string $workingDir Run command from this base directory
     *
     * @return void
     */
    protected function runHook($type, $hook, $workingDir)
    {
        $processLogger = $this->getProcessLogger($this->appName.'.'.$type);

        $process = new Process($hook);
        $process->setWorkingDirectory($workingDir);

        try {
            $process->mustRun();
            $processOutput = $process->getOutput();

            if ($processOutput) {
                $processLogger->notice($processOutput);
            }
        } catch (ProcessFailedException $e) {
            $processLogger->error($process->getErrorOutput());
            throw $e;
        }
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
        $baseDir = $this->getDeployConfig()->get('location');
        $baseDir = rtrim($baseDir, '/\\');

        return $baseDir;
    }

    /**
     * Get the Current Release Directory
     *
     * @return string
     */
    protected function getSymLinkPath()
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
    protected function getCurrentReleaseDir()
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
    protected function getNewReleaseDir()
    {
        $baseDir = $this->getAppBaseDir();
        $releaseDir = $baseDir.DIRECTORY_SEPARATOR.$this->nextRelease;
        $releaseDir = rtrim($releaseDir, "/\\\t\n\r\0\x0B");

        return $releaseDir;
    }

    /**
     * Get the previous release directory
     *
     * @return null|string
     */
    protected function getPreviousReleaseDir()
    {
        $appDir = $this->getAppBaseDir();
        $symLink = $this->getSymlinkConfigPath();
        $currentRelease = $this->getCurrentReleaseDir();


        if (!is_dir($appDir) || !$currentRelease) {
            return null;
        }

        $lastVersion = null;

        $dirListing = @scandir($appDir);

        if ($dirListing && is_array($dirListing)) {
            natsort($dirListing);

            while (!$lastVersion && count($dirListing) > 0) {
                $lastVersion = array_pop($dirListing);

                if ($lastVersion == $symLink
                    || strpos($lastVersion, '.') === 0
                    || $lastVersion == $currentRelease
                ) {
                    $lastVersion = null;
                }
            }
        }

        if (empty($lastVersion)) {
            return null;
        }

        $releaseDir = $this->getAppBaseDir().DIRECTORY_SEPARATOR.$lastVersion;
        $releaseDir = rtrim($releaseDir, "/\\\t\n\r\0\x0B");

        return $releaseDir;
    }

    /**
     * Get the Pre Deploy hook for the application
     *
     * @return string|null
     */
    protected function getPreDeployHook()
    {
        return $this->getDeployHooks()->get('pre_deploy', null);
    }

    /**
     * Get the Post Deploy hook for the application
     *
     * @return string|null
     */
    protected function getPostDeployHook()
    {
        return $this->getDeployHooks()->get('post_deploy', null);
    }

    /**
     * Get the Pre Rollback hook for the application
     *
     * @return string|null
     */
    protected function getPreRollbackHook()
    {
        return $this->getDeployHooks()->get('pre_rollback', null);
    }

    /**
     * Get the Post Rollback hook for the application
     *
     * @return string|null
     */
    protected function getPostRollbackHook()
    {
        return $this->getDeployHooks()->get('post_rollback', null);
    }

    /**
     * Get all the deploy hooks for the application
     *
     * @return Config
     */
    protected function getDeployHooks()
    {
        return $this->getDeployConfig()->get('hooks', new Config(array()));
    }

    /**
     * Get the config for deployment
     *
     * @return Config
     */
    protected function getDeployConfig()
    {
        return $this->getApplicationConfig()->get('deploy', new Config(array()));
    }

    /**
     * Get the repository configs
     *
     * @return Config
     */
    protected function getRepositoryConfig()
    {
        return $this->getApplicationConfig()->get('repositories', new Config(array()));
    }

    /**
     * Get the configured symlink path
     *
     * @return string
     */
    protected function getSymlinkConfigPath()
    {
        return $this->getDeployConfig()->get('symlink');
    }

    /**
     * Get the Applications config
     *
     * @return Config
     */
    protected function getApplicationConfig()
    {
        return $this->appConfig;
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
     * Recursive remove directory.  Equivalent to `rm -Rf`
     *
     * @param string $dir Directory to remove
     *
     * @return bool
     */
    protected function delTree($dir)
    {
        if (!is_dir($dir) && !is_file($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
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

        $repositories = $this->getRepositoryConfig();

        foreach ($repositories as $repoName => $repoConfig) {
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
            $this->logger = $this->getLoggerService()->getLogger($this->appName);
        }

        return $this->logger;
    }

    /**
     * Get a logger for the running process
     *
     * @param string $type Type of process
     *
     * @return \Monolog\Logger
     */
    protected function getProcessLogger($type)
    {
        return $this->getLoggerService()->getLogger($type);
    }

    /**
     * Get the logger service
     *
     * @return LoggerService
     */
    protected function getLoggerService()
    {
        return $this->loggerService;
    }
}

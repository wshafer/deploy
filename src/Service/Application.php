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
use Reliv\Deploy\Helper\ApplicationServiceConfigHelper;
use Reliv\Deploy\Helper\ApplicationServiceVcsHelper;
use Reliv\Deploy\Helper\FileHelper;
use Reliv\Deploy\Vcs\StatusMessageInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
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

    /** @var LoggerService Logger */
    protected $loggerService;

    /** @var ApplicationServiceConfigHelper  */
    protected $appConfig;

    /** @var FileHelper  */
    protected $fileHelper;

    /** @var ApplicationServiceVcsHelper */
    protected $vcsHelper;

    /*
     * Place Holders
     */

    /** @var array Place Holder for repository objects */
    protected $repositories = array();

    /** @var array Place Holder for Deployed Versions */
    protected $deployedVersions = array();

    protected $logger;

    /**
     * Constructor
     *
     * @param string                         $appName       Name of the application
     * @param ApplicationServiceConfigHelper $appConfig     Config for the Application deployment
     * @param LoggerService                  $loggerService Reliv Deploy Logger Factory
     * @param FileHelper                     $fileHelper    File System Helper
     * @param ApplicationServiceVcsHelper    $vcsHelper     Application VCS Helper
     */
    public function __construct(
        $appName,
        ApplicationServiceConfigHelper $appConfig,
        LoggerService $loggerService,
        FileHelper $fileHelper,
        ApplicationServiceVcsHelper $vcsHelper
    ) {
        if (empty($appName)) {
            throw new \RuntimeException(
                'App name must be provided.'
            );
        }

        $this->appName = $appName;
        $this->loggerService = $loggerService;
        $this->fileHelper = $fileHelper;
        $this->vcsHelper = $vcsHelper;
        $this->appConfig = $appConfig;
    }

    /**
     * Get a status message for the application
     *
     * @return array
     */
    public function getStatusMessage()
    {
        $message = array();

        $currentReleaseDir = $this->appConfig->getCurrentReleaseDir();

        if (!is_dir($currentReleaseDir)) {
            $message[] = $this->appName. ' is not currently deployed.';
            return $message;
        }

        $repositories = $this->vcsHelper->getRepoHelpers();

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

        $baseDir = $this->appConfig->getAppBaseDir();
        $releaseToDir = $this->appConfig->getNewReleaseDir();
        $symlink = $this->appConfig->getSymLinkPath();
        $currentReleaseDir = $this->appConfig->getCurrentReleaseDir();

        $this->fileHelper->createDirectory($baseDir);
        $this->fileHelper->createDirectory($releaseToDir);

        $repositories = $this->vcsHelper->getRepoHelpers();

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
        $preDeployHook = $this->appConfig->getPreDeployHook();

        if ($preDeployHook) {
            try {
                $logger->debug("Running pre deploy hook: ".$preDeployHook);
                $this->runHook('pre_deploy', $preDeployHook, $releaseToDir);
            } catch (ProcessFailedException $e) {
                $logger->error('Failed to run command: '.$preDeployHook);
                $this->fileHelper->delTree($releaseToDir);
                $logger->error('Deployment Failed');
                return;
            }
        }

        @unlink($symlink);
        symlink($releaseToDir, $symlink);

        $logger->debug("Application deployed.  Running post_deploy hook");

        /*
         * Trigger the Pre Deploy Hook
         */
        $postDeployHook = $this->appConfig->getPostDeployHook();

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

        $logger->debug("Cleaning application directory");
        $this->fileHelper->cleanAppDir($baseDir, $releaseToDir, $this->appConfig->getNumberOfRevisionsToKeep());

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

        $appDir = $this->appConfig->getAppBaseDir();
        $symlink = $this->appConfig->getSymLinkPath();

        if (!is_dir($appDir)) {
            $logger->error("No deployments found for:" .$this->appName);
            return;
        }

        if (!$currentRelease) {
            $currentRelease = $this->appConfig->getCurrentReleaseDir();
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
        $preRollbackHook = $this->appConfig->getPreRollbackHook();

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
        $this->fileHelper->delTree($currentRelease);

        /*
         * Trigger the pre_rollback hook
         */
        $postRollbackHook = $this->appConfig->getPostRollbackHook();

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

        $logger->debug("Cleaning application directory");
        $this->fileHelper->cleanAppDir($appDir, $previousRelease, $this->appConfig->getNumberOfRevisionsToKeep());

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

        $baseDir = $this->appConfig->getAppBaseDir();
        $currentReleaseDir = $this->appConfig->getCurrentReleaseDir();

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

        $repositories = $this->vcsHelper->getRepoHelpers();

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

    /**
     * Get the previous release directory
     *
     * @return null|string
     * @todo Figure out where this should go
     */
    protected function getPreviousReleaseDir()
    {
        $releases = $this->getReleases();
        $currentRelease = $this->appConfig->getCurrentReleaseDir();

        if (!$currentRelease) {
            return null;
        }

        $previousRelease = null;

        while (!$previousRelease && count($releases) > 0) {
            $release = array_pop($releases);

            if ($release == $currentRelease) {
                $previousRelease = array_pop($releases);
            }
        }

        return $previousRelease;
    }

    /**
     * Get a listing of all releases in the app directory
     *
     * @return array
     */
    protected function getReleases()
    {
        $appBaseDir = $this->appConfig->getAppBaseDir();
        return $this->fileHelper->getReleases($appBaseDir);
    }
}

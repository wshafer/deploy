<?php
/**
 * Git Repo VCS driver for Reliv Deploy
 *
 * Git Repo VCS driver for Reliv Deploy
 *
 * PHP version 5.4
 *
 * LICENSE: BSD
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */
namespace Reliv\Deploy\Vcs;

use Psr\Log\LoggerInterface;
use Reliv\Deploy\Exception\InvalidApplicationConfigException;
use Reliv\Deploy\Exception\InvalidSystemConfigException;
use Reliv\Deploy\Service\LoggerService;
use Reliv\Git\Service\Git as GitService;
use Reliv\Git\Service\Repository;
use Zend\Config\Config;

/**
 * Git Repo VCS driver for Reliv Deploy
 *
 * Git Repo VCS driver for Reliv Deploy.  This driver will preform all git related operations for repos that need
 * to use Git for deployment.
 *
 * PHP version 5.4
 *
 * LICENSE: BSD
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      https://github.com/reliv
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class GitRepo implements VcsInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $currentReleaseAppDir;

    /**
     * @var String
     */
    protected $nextReleaseAppDir;

    /**
     * @var LoggerService
     */
    protected $loggerService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /* Place Holders */

    /**
     * @var GitService
     */
    protected $gitService;

    /**
     * @var \Reliv\Git\Service\Repository
     */
    protected $repoService;

    protected $repoReleaseNumber;

    protected $deployBranchOrTag;

    protected $deployedInfo;


    /**
     * Constructor
     */
    public function __construct()
    {
        if (!class_exists('\Reliv\Git\Service\Git')
            || !class_exists('\Reliv\Git\Service\Repository')
        ) {
            throw new \RuntimeException(
                "Please make sure to install the Git Service Wrapper.  `composer require reliv/git`"
            );
        }
    }

    /**
     * Preform the update.
     *
     * @return void
     */
    public function update()
    {
        $logger = $this->getLogger();
        $repository = $this->getRepoService();
        $targetDir = $this->getNextReleaseDirForRepo();
        $branchOrTag = $this->getDeploymentBranchOrTag();

        $this->createDirectory($targetDir);
        $logger->info('Coping '.$branchOrTag);
        $repository->copyBranchTo($targetDir, $branchOrTag);

        $commitFile = $targetDir.DIRECTORY_SEPARATOR.$this->getCommitFile();

        $contents = array(
            'commit' => $this->getLatestRepoVersionNumber(),
            'date' => new \DateTime()
        );

        file_put_contents($commitFile, serialize($contents));
    }

    /**
     * Get the repos current deployment status
     *
     * @return Status
     */
    public function getStatus()
    {
        return new Status(
            $this->getLatestRepoVersionNumber(),
            $this->getDeployedVersionNumber(),
            $this->getDeployedDate(),
            $this->doCheckForUpdate()
        );
    }
    /**
     * Does the application need an update from the repository?
     *
     * @return bool
     */
    public function needsUpdate()
    {
        $logger = $this->getLogger();

        $logger->info('Deployed Date     : '.$this->getDeployedDate());
        $logger->info('Deployed Commit   : '.$this->getDeployedVersionNumber());
        $logger->info('Repository Commit : '.$this->getLatestRepoVersionNumber());

        return $this->doCheckForUpdate();
    }

    /**
     * Do the check to see if the deployment needs an update.
     *
     * @return bool
     */
    protected function doCheckForUpdate()
    {
        $logger = $this->getLogger();
        $repoCurrentDir = $this->getCurrentReleaseDirForRepo();

        $logger->debug(
            'Checking to see if repo directory exists at: '.$repoCurrentDir
        );

        if (!is_dir($repoCurrentDir)) {
            $logger->debug('Repo Directory does not exist.  Needs updates.');
            return true;
        }

        $logger->debug('Repo Directory exists.  Checking Revisions.');

        $deployedCommit = $this->getDeployedVersionNumber();
        $remoteCommitHash = $this->getLatestRepoVersionNumber();

        if ($remoteCommitHash != $deployedCommit) {
            return true;
        }
    }

    /**
     * Get the Deployed version.  This is stored in a text file in the root folder of the application directory.
     * The filename can be configured in the main configuration file.
     *
     * @return null|string
     */
    protected function getDeployedVersionNumber()
    {
        $deployedInfo = $this->getDeployedInfo();

        if (empty($deployedInfo['commit'])) {
            return null;
        }

        return $deployedInfo['commit'];
    }

    protected function getDeployedDate()
    {
        $deployedInfo = $this->getDeployedInfo();

        if (empty($deployedInfo['date']) || !$deployedInfo['date'] instanceof \DateTime) {
            return null;
        }

        /** @var \DateTime $date */
        $date = $deployedInfo['date'];

        $dateFormat = $this->getConfig()->get('dateFormat', 'c');

        return $date->format($dateFormat);
    }

    protected function getDeployedInfo()
    {
        if ($this->deployedInfo) {
            return $this->deployedInfo;
        }

        $repoCurrentDir = $this->getCurrentReleaseDirForRepo();
        $commitFile = $repoCurrentDir.DIRECTORY_SEPARATOR.$this->getCommitFile();

        if (!is_dir($repoCurrentDir) || !is_file($commitFile)) {
            return array();
        }

        $this->deployedInfo = unserialize(trim(file_get_contents($commitFile)));

        return $this->deployedInfo;
    }

    /**
     * Get the latest commit version for the configured branch or latest tag number.  The branch or tag can be
     * configured in the main configuration file for the repo.
     *
     * @return mixed
     */
    protected function getLatestRepoVersionNumber()
    {
        if (!empty($this->repoReleaseNumber)) {
            return $this->repoReleaseNumber;
        }

        $branchType = $this->getReleaseBranchType();
        $branchOrTag = $this->getDeploymentBranchOrTag();
        $repoService = $this->getRepoService();

        if ($branchType == 'branch') {
            $ref = 'refs/heads/'.$branchOrTag;
        } else {
            $ref = 'refs/tags/'.$branchOrTag;
        }

        $ref = $repoService->getRef($ref);
        $this->repoReleaseNumber = array_pop($ref);

        return $this->repoReleaseNumber;
    }

    /**
     * Set config.  This should be called when constructing the class.
     *
     * @param Config $config Repository Config
     *
     * @return void
     */
    public function setConfig(Config $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
    }

    /**
     * Get the repository config.
     *
     * @return Config
     */
    public function getConfig()
    {
        if (!$this->config) {
            throw new \RuntimeException(
                "No configuration supplied for Git Helper"
            );
        }

        return $this->config;
    }

    /**
     * Set the Applications Current Release Directory.  This should be set when constructing the class.
     *
     * @param string $dir Path to the Applications Current Release Directory.
     *
     * @return void
     */
    public function setCurrentReleaseAppDir($dir)
    {
        $this->currentReleaseAppDir = $dir;
    }

    /**
     * Get the applications current release directory.
     *
     * @return string Path to the applications current release directory.
     */
    public function getCurrentReleaseAppDir()
    {
        if (!$this->currentReleaseAppDir) {
            throw new \RuntimeException(
                "No Current Release Directory supplied for Git Helper"
            );
        }

        return $this->currentReleaseAppDir;
    }

    /**
     * Set the target directory for the next release.  This should be set when constructing the class.
     *
     * @param string $dir Target directory for the next release
     *
     * @return void
     */
    public function setNextReleaseAppDir($dir)
    {
        $this->nextReleaseAppDir = $dir;
    }

    /**
     * Get the target directory for the next release.
     *
     * @return String Target Directory
     */
    public function getNextReleaseDir()
    {
        if (!$this->nextReleaseAppDir) {
            throw new \RuntimeException(
                "No Next Target Release Directory supplied for Git Helper"
            );
        }

        return $this->nextReleaseAppDir;
    }

    /**
     * Set a PSR3 logger to use during deployment.  This should be set when constructing the class.
     *
     * @param LoggerService $loggerService Reliv Deploy Logger Factory
     *
     * @return void
     */
    public function setLoggerService(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    /**
     * Get the current PSR3 compatible logger.
     *
     * @return LoggerService
     */
    public function getLoggerService()
    {
        if (!$this->loggerService) {
            throw new \RuntimeException(
                "No logger factory into git helper"
            );
        }

        return $this->loggerService;
    }

    /**
     * Get the current PSR3 compatible logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = $this->getLoggerService()->getLogger($this->getName());
        }

        return $this->logger;
    }

    /**
     * Validate the passed in config.
     *
     * @param Config $config Config object to validate
     *
     * @return void
     * @throws InvalidApplicationConfigException
     */
    protected function validateConfig(Config $config)
    {
        if (empty($config['type'])) {
            throw new InvalidApplicationConfigException(
                'No Repository Type Defined'
            );
        }

        if (empty($config['origin'])) {
            throw new InvalidApplicationConfigException(
                'No Origin Defined'
            );
        }

        if (empty($config['branch']) && empty($config['tags'])) {
            throw new InvalidApplicationConfigException(
                'No branch or tags Defined'
            );
        }

        if (empty($config['directory'])) {
            throw new InvalidApplicationConfigException(
                'No Directory Defined'
            );
        }

        if (empty($config['commitFile'])) {
            throw new InvalidApplicationConfigException(
                'No CommitFile Defined'
            );
        }

        if (empty($config['executable'])) {
            throw new InvalidSystemConfigException(
                'Unable to find Git Executable in config'
            );
        }
    }

    /**
     * Get the current release directory for the repo.
     *
     * @return string
     */
    protected function getCurrentReleaseDirForRepo()
    {
        $currentReleaseAppDir = $this->getCurrentReleaseAppDir();
        $repoDirectory = $this->getRepoDir();

        if (!empty($repoDirectory)) {
            $repoCurrentDir = $currentReleaseAppDir.DIRECTORY_SEPARATOR.$repoDirectory;
            return $repoCurrentDir;
        }

        return $currentReleaseAppDir;
    }

    /**
     * Get the target directory for next release for this repo.
     *
     * @return string
     */
    protected function getNextReleaseDirForRepo()
    {
        $nextReleaseAppDir = $this->getNextReleaseDir();
        $repoDirectory = $this->getRepoDir();
        $nextRepoDir = $nextReleaseAppDir.DIRECTORY_SEPARATOR.$repoDirectory;

        return $nextRepoDir;
    }

    /**
     * Get the repo directory.  This can be set in the repo config, and should point to a relative directory
     * from the application directory.
     *
     * @return string
     */
    protected function getRepoDir()
    {
        $config = $this->getConfig();
        return trim($config['directory'], "\t\n\r\0\x0B/\\");
    }

    /**
     * Get the configured deployment branch or tag to use for deployment
     *
     * @return mixed|null
     */
    protected function getDeploymentBranchOrTag()
    {
        if ($this->deployBranchOrTag) {
            return $this->deployBranchOrTag;
        }

        $branchType = $this->getReleaseBranchType();
        $config = $this->getConfig();

        if ($branchType == 'branch') {
            $this->deployBranchOrTag = $config['branch'];
        } else {
            $this->deployBranchOrTag = $this->getLatestDeployTag();
        }

        return $this->deployBranchOrTag;
    }

    /**
     * Get the latest tag based on the tag pattern to look for defined by the config file.
     *
     * @return mixed|null
     */
    protected function getLatestDeployTag()
    {
        $config = $this->getConfig();

        if (empty($config['tags'])) {
            return null;
        }

        $repoService = $this->getRepoService();
        $tags = $repoService->getLocalTags();
        $keys = array_keys($tags);
        $matches = preg_grep($config['tags'], $keys);
        natsort($matches);

        return array_pop($matches);
    }

    /**
     * Get the type of branch to use for deployment.  Are we using a Branch or Tag?
     *
     * @return string
     * @throws InvalidApplicationConfigException
     */
    protected function getReleaseBranchType()
    {
        $config = $this->getConfig();

        if (!empty($config['branch'])) {
            return 'branch';
        } elseif (!empty($config['tags'])) {
            return 'tags';
        }

        throw new InvalidApplicationConfigException(
            "No branch or tags provided for deployment"
        );
    }

    /**
     * Get the repository service helper
     *
     * @return \Reliv\Git\Service\Repository
     */
    protected function getRepoService()
    {
        if ($this->repoService instanceof Repository) {
            return $this->repoService;
        }


        $gitService = $this->getGitService();
        $this->repoService = $gitService->getRepository($this->getRepoOrigin());

        return $this->repoService;
    }

    /**
     * Get the origin path
     *
     * @return mixed
     */
    protected function getRepoOrigin()
    {
        $config = $this->getConfig();
        return $config['origin'];
    }

    /**
     * Get the commit filename from the config.
     *
     * @return string
     */
    protected function getCommitFile()
    {
        $config = $this->getConfig();
        return trim($config['commitFile'], "\t\n\r\0\x0B/\\");
    }

    /**
     * Get the Git Wrapper service.
     *
     * @return GitService
     */
    protected function getGitService()
    {

        if ($this->gitService instanceof GitService) {
            return $this->gitService;
        }

        $config = $this->getConfig();

        $this->gitService = new GitService($config['executable']);

        return $this->gitService;
    }

    /**
     * Create a new directory.
     *
     * @param string $dir Directory path to create
     *
     * @return void
     * @throws \RuntimeException
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
     * Set the name of the Repo.  This will be called when constructing the class.
     *
     * @param string $name Name of repository
     *
     * @return mixed
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the repository's name or key
     *
     * @return string
     */
    public function getName()
    {
        if (empty($this->name)) {
            $this->name = $this->getRepoOrigin();
        }

        return $this->name;
    }
}

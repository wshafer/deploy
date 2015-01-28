<?php

namespace Reliv\Deploy\Vcs;

use Psr\Log\LoggerInterface;
use Reliv\Deploy\Exception\InvalidApplicationConfigException;
use Reliv\Deploy\Exception\InvalidSystemConfigException;
use Reliv\Git\Service\Git as GitService;
use Reliv\Git\Service\Repository;

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

    protected $deployedRevisionNumber;

    protected $deployBranchOrTag;


    public function __construct()
    {
        if (!class_exists('\Reliv\Git\Service\Git') || !class_exists('\Reliv\Git\Service\Repository'))
        {
            throw new \RuntimeException(
                "Please make sure to install the Git Service Wrapper.  `composer require reliv/git`"
            );
        }
    }

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

        file_put_contents($commitFile, $this->getLatestRepoVersionNumber());
    }

    public function needsUpdate()
    {
        $logger = $this->getLogger();

        $repoCurrentDir = $this->getCurrentReleaseDirForRepo();

        $logger->debug('Checking to see if repo directory exists for '.$this->name.' at: '.$repoCurrentDir);
        if (!is_dir($repoCurrentDir)) {
            $logger->debug('Repo Directory does not exist.  Needs updates.');
            return true;
        }

        $logger->debug('Repo Directory exists.  Checking Revisions.');

        $remoteCommitHash = $this->getLatestRepoVersionNumber();
        $logger->info('Repository Commit : '.$remoteCommitHash);

        $deployedCommit = $this->getDeployedVersionNumber();
        $logger->info('Deployed Commit   : '.$deployedCommit);

        if ($remoteCommitHash != $deployedCommit) {
            return true;
        }

        return false;
    }

    protected function getDeployedVersionNumber()
    {
        if ($this->deployedRevisionNumber) {
            return $this->deployedRevisionNumber;
        };

        $repoCurrentDir = $this->getCurrentReleaseDirForRepo();
        $commitFile = $repoCurrentDir.DIRECTORY_SEPARATOR.$this->getCommitFile();

        if (!is_dir($repoCurrentDir) || !is_file($commitFile)) {
            return null;
        }

        $this->deployedRevisionNumber = trim(file_get_contents($commitFile));

        return $this->deployedRevisionNumber;
    }

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

    public function setConfig($config)
    {
        $this->validateConfig($config);
        $this->config = $config;
    }

    public function getConfig()
    {
        if (!$this->config) {
            throw new \RuntimeException(
                "No configuration supplied for Git Helper"
            );
        }

        return $this->config;
    }

    public function setCurrentReleaseAppDir($dir)
    {
        $this->currentReleaseAppDir = $dir;
    }

    public function getCurrentReleaseAppDir()
    {
        if (!$this->currentReleaseAppDir) {
            throw new \RuntimeException(
                "No Current Release Directory supplied for Git Helper"
            );
        }

        return $this->currentReleaseAppDir;
    }

    public function setNextReleaseAppDir($dir)
    {
        $this->nextReleaseAppDir = $dir;
    }

    public function getNextReleaseDir()
    {
        if (!$this->nextReleaseAppDir) {
            throw new \RuntimeException(
                "No Next Target Release Directory supplied for Git Helper"
            );
        }

        return $this->nextReleaseAppDir;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        if (!$this->logger) {
            throw new \RuntimeException(
                "No logger passed into git helper"
            );
        }

        return $this->logger;
    }

    protected function validateConfig($config)
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

    protected function getNextReleaseDirForRepo()
    {
        $nextReleaseAppDir = $this->getNextReleaseDir();
        $repoDirectory = $this->getRepoDir();
        $nextRepoDir = $nextReleaseAppDir.DIRECTORY_SEPARATOR.$repoDirectory;

        return $nextRepoDir;
    }

    protected function getRepoDir()
    {
        $config = $this->getConfig();
        return trim($config['directory'],"\t\n\r\0\x0B/\\");
    }

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

        $config = $this->getConfig();
        $gitService = $this->getGitService();
        $this->repoService = $gitService->getRepository($config['origin']);

        return $this->repoService;
    }

    protected function getCommitFile()
    {
        $config = $this->getConfig();
        return trim($config['commitFile'],"\t\n\r\0\x0B/\\");
    }

    protected function getGitService()
    {

        if ($this->gitService instanceof GitService) {
            return $this->gitService;
        }

        $config = $this->getConfig();

        $this->gitService = new GitService($config['executable']);

        return $this->gitService;
    }

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
}
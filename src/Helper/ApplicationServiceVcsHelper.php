<?php

namespace Reliv\Deploy\Helper;

use Reliv\Deploy\Service\LoggerService;

class ApplicationServiceVcsHelper
{

    public $repositories;

    /**
     * @var ApplicationServiceConfigHelper
     */
    public $appConfig;


    /**
     * @var LoggerService
     */
    public $loggerService;

    /**
     * Constructor
     *
     * @param ApplicationServiceConfigHelper $appConfig     Application config
     * @param LoggerService                  $loggerService Logger Service
     */
    public function __construct(
        ApplicationServiceConfigHelper $appConfig,
        LoggerService                  $loggerService
    ) {
        $this->appConfig = $appConfig;
        $this->loggerService = $loggerService;
    }

    /**
     * Get a single repository helper
     *
     * @param string $name Name or config key for Repo
     *
     * @return \Reliv\Deploy\Vcs\VcsInterface
     */
    public function getRepoHelper($name)
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
    public function getRepoHelpers()
    {
        if ($this->repositories) {
            return $this->repositories;
        }

        $repositories = $this->appConfig->getRepositoryConfig();

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
            $repoHelper->setCurrentReleaseAppDir($this->appConfig->getCurrentReleaseDir());
            $repoHelper->setNextReleaseAppDir($this->appConfig->getNewReleaseDir());
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
    public function vcsMapper($type)
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
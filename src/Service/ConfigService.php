<?php

namespace Reliv\Deploy\Service;


use Reliv\Deploy\Exception\InvalidApplicationConfigException;
use Reliv\Deploy\Exception\InvalidSystemConfigException;
use Zend\Config\Config;

class ConfigService
{
    protected $rawConfig;
    protected $mergedConfig;
    protected $configPaths = array();

    public function __construct($configPaths)
    {
        $this->configPaths = $configPaths;
        $this->rawConfig = $this->loadConfigs();
    }

    /**
     * @return Config
     */
    public function getRawConfig()
    {
        return $this->rawConfig;
    }

    /**
     * @return Config
     */
    public function getMainConfig()
    {
        return $this->getMergeConfigs();
    }

    /**
     * @return Config
     */
    public function getDefaultConfig()
    {
        $rawConfig = $this->getRawConfig();
        $defaultConfig = $rawConfig->get('default', new Config(array()));
        return $defaultConfig;
    }

    /**
     * @return Config
     */
    public function getAppsConfig()
    {
        return $this->getMergeConfigs()->get('apps', new Config(array()));
    }

    /**
     * @param $name
     * @return Config
     */
    public function getAppConfig($name)
    {
        return $this->getAppsConfig()->get($name, new Config(array()));
    }

    public function getRepositoryConfigs($repoName, $applicationName)
    {
        return $this->getAppConfig($applicationName)->get($repoName, new Config(array()));
    }

    public function getCommands()
    {
        return $this->getMergeConfigs()->get('commands', new Config(array()));
    }

    protected function loadConfigs()
    {
        $mainConfig = new Config(array());

        foreach ($this->configPaths as $path) {
            if (file_exists($path)) {
                $config = new Config(include $path);
                $mainConfig->merge($config);
                unset($config);
            }
        }

        return $mainConfig;
    }

    /**
     * @return Config
     */
    protected function getMergeConfigs()
    {
        if ($this->mergedConfig) {
            return $this->mergedConfig;
        }

        $this->mergedConfig = new Config($this->rawConfig->toArray());

        $appsConfig = $this->mergedConfig->get('apps', new Config(array()));

        /**
         * @var string $appName
         * @var Config $appConfig
         */
        foreach ($appsConfig as $appName => $appConfig)
        {
            $defaultConfig = $this->getDefaultConfig();
            $defaultClone = new Config($defaultConfig->toArray());
            $defaultClone->merge($appConfig);
            $appConfig->merge($defaultClone);
            $this->validateAppConfig($appName, $appConfig);

            $reposConfig = $appConfig->get('repositories', new Config(array()));

            /**
             * @var string $repoName
             * @var Config $repoConfig
             */
            foreach ($reposConfig as $repoName => $repoConfig) {
                $repoDefaults = $defaultConfig->get($repoConfig['type'], new Config(array()));
                $repoDefaultsClone = new Config($repoDefaults->toArray());
                $repoDefaultsClone->merge($repoConfig);
                $repoConfig->merge($repoDefaultsClone);

                $this->validateRepoConifg($appName, $repoName, $repoConfig);
            }
        }

        return $this->mergedConfig;
    }

    protected function validateAppConfig($name, $config)
    {
        if (empty($config['deploy'])) {
            throw new InvalidSystemConfigException(
                'No Deploy config found in configuration.'
            );
        }

        if (empty($config['deploy']['user'])) {
            throw new InvalidSystemConfigException(
                'No System User defined for '.$name
            );
        }

        if (empty($config['deploy']['group'])) {
            throw new InvalidSystemConfigException(
                'No System Group defined for '.$name
            );
        }

        if (empty($config['deploy']['location'])) {
            throw new InvalidSystemConfigException(
                'No Deploy location defined for '.$name
            );
        }

        if (empty($config['deploy']['symlink'])) {
            throw new InvalidApplicationConfigException(
                'No deploy link defined in application config for '.$name
            );
        }

        if (empty($config['repositories'])) {
            throw new InvalidApplicationConfigException(
                'No Repositories defined in application config for '.$name
            );
        }
    }

    protected function validateRepoConifg($appName, $repoName, $config) {
        if (empty($config['type'])) {
            throw new InvalidApplicationConfigException(
                'No Repositories Type Defined in application config for '.$appName
            );
        }

        if (empty($config['directory'])) {
            throw new InvalidApplicationConfigException(
                'No deploy directory defined in application config for '.$appName
            );
        }
    }
}
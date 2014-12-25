<?php

namespace Reliv\Deploy\Service;


use Zend\Config\Config;

class ConfigService
{
    protected $mainConfig;
    protected $defaultConfig;
    protected $appsConfig;
    protected $mergedAppConfigs = array();
    protected $configPaths = array();

    public function __construct($configPaths)
    {
        $this->configPaths = $configPaths;
    }

    public function getMainConfig()
    {
        if (!$this->mainConfig) {
            $this->mainConfig = $this->loadConfigs();
        }

        return $this->mainConfig;
    }

    public function getDefaultConfig()
    {
        $mainConfig = $this->getMainConfig();

        if (!$this->defaultConfig) {
            $defaultConfig = new Config($mainConfig->get('Default', array())->toArray());
            $this->defaultConfig = $defaultConfig;
        }

        return $this->defaultConfig;
    }

    /**
     * @return Config
     */
    public function getAppsConfig()
    {
        $mainConfig = $this->getMainConfig();

        if (!$this->appsConfig) {
            $this->appsConfig = $mainConfig->get('Apps', new Config(array()));

            /**
             * @var string $appName
             * @var Config $appConfig
             */
            foreach ($this->appsConfig as $appName => $appConfig)
            {
                $appConfig->merge($this->getAppConfig($appName));
            }
        }

        return $this->appsConfig;
    }

    /**
     * @param $name
     * @return Config
     */
    public function getAppConfig($name)
    {
        if (!isset($this->mergedAppConfigs[$name])) {
            $appsConfig = $this->getAppsConfig();
            $appConfigOverrides = $appsConfig->get($name, new Config(array()));
            $appConfig = new Config($this->getDefaultConfig()->toArray());
            $appConfig->merge($appConfigOverrides);
            $this->mergedAppConfigs[$name] = $appConfig;
        }

        return $this->mergedAppConfigs[$name];
    }

    public function getCommands()
    {
        $mainConfig = $this->getMainConfig();
        return $mainConfig->get('Commands', new Config(array()));
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
}
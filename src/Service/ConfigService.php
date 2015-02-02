<?php
/**
 * Config Service Provider
 *
 * Config Service Provider
 *
 * PHP version 5.4
 *
 * LICENSE: No License yet
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


use Reliv\Deploy\Exception\InvalidApplicationConfigException;
use Reliv\Deploy\Exception\InvalidSystemConfigException;
use Zend\Config\Config;

/**
 * Config Service Provider
 *
 * Config Service Provider.  Used to get and parse config files.
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class ConfigService
{
    protected $rawConfig;
    protected $mergedConfig;
    protected $configPaths = array();

    /**
     * Constructor
     *
     * @param array $configPaths Array of file paths to config files.
     */
    public function __construct($configPaths)
    {
        $this->configPaths = $configPaths;
    }

    /**
     * Get Raw Config.  Return the config file without the defaults merged into apps and repository configs.  Used
     * for saving the config to a file.
     *
     * @return Config
     */
    public function getRawConfig()
    {
        if (!$this->rawConfig) {
            $this->rawConfig = $this->loadConfigs();
        }

        return $this->rawConfig;
    }

    /**
     * Get the merged config file.  This will return all the configs merged and all defaults into the correct places.
     *
     * @return Config
     */
    public function getMainConfig()
    {
        return $this->getMergeConfigs();
    }

    /**
     * Get the default config array.  This will return the default values define in the configs.
     *
     * @return Config
     */
    public function getDefaultConfig()
    {
        $rawConfig = $this->getRawConfig();
        $defaultConfig = $rawConfig->get('default', new Config(array()));
        return $defaultConfig;
    }

    /**
     * Get all the config for all applications.
     *
     * @return Config
     */
    public function getAppsConfig()
    {
        return $this->getMergeConfigs()->get('applications', new Config(array()));
    }

    /**
     * Get the application config for a single application.
     *
     * @param string $name Name or Key of Application
     *
     * @return Config
     */
    public function getAppConfig($name)
    {
        return $this->getAppsConfig()->get($name, new Config(array()));
    }

    /**
     * Get the config for a single repo.
     *
     * @param string $repoName        Name or key of the repo to get.
     * @param string $applicationName Name or Key of Application for the repo.
     *
     * @return mixed
     */
    public function getRepositoryConfigs($repoName, $applicationName)
    {
        return $this->getAppConfig($applicationName)->get($repoName, new Config(array()));
    }

    /**
     * Get the list of configured commands.
     *
     * @return mixed
     */
    public function getCommands()
    {
        return $this->getMergeConfigs()->get('commands', new Config(array()));
    }

    /**
     * Get the configured date format
     *
     * @return mixed
     */
    public function getDateFormat()
    {
        $defaultConfig = $this->getDefaultConfig();
        return $defaultConfig->get('dateFormat', 'c');
    }

    /**
     * Load the config files.
     *
     * @return Config
     */
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

        $systemConfigFile = $mainConfig->get('system', new Config(array()))
            ->get('config', null);

        if ($systemConfigFile && file_exists($systemConfigFile)) {
            $systemConfigJson = file_get_contents($systemConfigFile);
            $systemConfig = json_decode($systemConfigJson, true);
            $mainConfig->merge(new Config($systemConfig));
        }

        return $mainConfig;
    }

    /**
     * Get the Merged config files.  See getMainConfig above for more info.
     *
     * @return Config
     */
    protected function getMergeConfigs()
    {
        if ($this->mergedConfig) {
            return $this->mergedConfig;
        }

        $this->mergedConfig = new Config($this->getRawConfig()->toArray());

        $appsConfig = $this->mergedConfig->get('applications', new Config(array()));

        /**
         * @var string $appName
         * @var Config $appConfig
         */
        foreach ($appsConfig as $appName => $appConfig) {
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
            foreach ($reposConfig as $repoConfig) {
                $repoDefaults = $defaultConfig->get($repoConfig['type'], new Config(array()));
                $repoDefaults->merge(new Config(array('dateFormat' => $this->getDateFormat())));
                $repoDefaultsClone = new Config($repoDefaults->toArray());
                $repoDefaultsClone->merge($repoConfig);
                $repoConfig->merge($repoDefaultsClone);

                $this->validateRepoConifg($appName, $repoConfig);
            }
        }

        return $this->mergedConfig;
    }

    /**
     * Validate the application config
     *
     * @param string $name   Name or key of application
     * @param Config $config Application config to check
     *
     * @return void
     */
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

    /**
     * Validate a repository config.  This does not check the entire repo config, only global properties.  The
     * repository helpers should be validating any additional configs on their own.
     *
     * @param string $appName Application Name
     * @param Config $config  Repository config to check
     *
     * @return void
     */
    protected function validateRepoConifg($appName, $config)
    {
        if (empty($config['type'])) {
            throw new InvalidApplicationConfigException(
                'Missing Repositories Type in application config for '.$appName
            );
        }

        if (empty($config['directory'])) {
            throw new InvalidApplicationConfigException(
                'Missing deploy directory in application config for '.$appName
            );
        }
    }


}

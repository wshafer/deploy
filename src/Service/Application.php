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

    /** @var  \Reliv\Git\Service\Git */
    protected $gitService;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;


    /*
     * Place Holders
     */
    protected $updated = false;

    public function __construct(
        $appName,
        Config $appConfig,
        Git $gitService,
        LoggerInterface $logger
    ) {

        if (empty($appName)) {
            throw new \RuntimeException(
                'App name must be provided.'
            );
        }

        $this->appName = $appName;
        $this->logger = $logger;
        $this->gitService = $gitService;

        $this->checkConfig($appConfig);
        $this->appConfig = $appConfig;


    }

    public function deploy()
    {
        $this->logger->info('Checking Application '.$this->appName);

        $this->gitService->

        if (!$this->updated) {
            $this->logger->notice('Application '.$this->appName.' is current.  Nothing to update.');
        }
    }

    protected function checkConfig($config)
    {
        if (empty($config['Repo'])) {
            throw new InvalidApplicationConfigException(
                'No origin remote repo defined in application config for '.$this->appName
            );
        }

        if (empty($config['Repo']['branch'])) {
            throw new InvalidApplicationConfigException(
                'No Repo Branch defined in application config for '.$this->appName
            );
        }

        if (empty($config['Deploy'])) {
            throw new InvalidSystemConfigException(
                'No Deploy config found in configuration.'
            );
        }

        if (empty($config['Deploy']['user'])) {
            throw new InvalidSystemConfigException(
                'No System User defined for '.$this->appName
            );
        }

        if (empty($config['Deploy']['group'])) {
            throw new InvalidSystemConfigException(
                'No System Group defined for '.$this->appName
            );
        }

        if (empty($config['Deploy']['symlink'])) {
            throw new InvalidApplicationConfigException(
                'No deploy link defined in application config for '.$this->appName
            );
        }

        if (empty($config['Deploy']['directory'])) {
            throw new InvalidApplicationConfigException(
                'Application deploy directory not defined for '.$this->appName
            );
        }

        if (empty($config['Deploy']['type'])) {
            throw new InvalidApplicationConfigException(
                'Application Repository Type not defined for '.$this->appName
            );
        }

        $this->logger->info('Config for '.$this->appName.' - OK');
    }

    protected function getNewRevision()
    {
        $microTimestamp = microtime(true);
        $timestamp = floor($microTimestamp);
        $milliseconds = round(($microTimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, 'Y.m.d.H.i.s.u'), $timestamp);
    }
}
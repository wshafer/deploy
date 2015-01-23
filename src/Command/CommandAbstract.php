<?php

namespace Reliv\Deploy\Command;


use Psr\Log\LoggerInterface;
use Reliv\Deploy\Exception\InvalidSystemConfigException;
use Reliv\Deploy\Factory\LoggerFactory;
use Reliv\Deploy\Service\Application;
use Reliv\Deploy\Service\ConfigService;
use Reliv\Git\Service\Git;
use Symfony\Component\Console\Command\Command;

abstract class CommandAbstract extends Command implements CommandInterface
{
    /** @var ConfigService  */
    protected $configService;
    protected $loggerService;
    protected $logger;
    protected $gitService;

    public function __construct(
        ConfigService $configService,
        LoggerFactory $loggerService,
        $name = null
    ) {
        parent::__construct($name);
        $this->configService = $configService;
        $this->loggerService = $loggerService;
    }

    protected function getCommandLogger()
    {
        if ($this->logger instanceof LoggerInterface) {
            return $this->logger;
        }

        return $this->loggerService->getLogger(
            'Command.'.$this->getName(),
            $this->configService->getDefaultConfig()
        );
    }

    protected function getApplicationHelper($name, $config)
    {
        $applicationLogger = $this->loggerService->getLogger(
            $name,
            $config
        );

        $application = new Application($name,
            $config,
            $this->getGitService(),
            $applicationLogger
        );

        return $application;
    }

    protected function getGitService()
    {

        if ($this->gitService instanceof Git) {
            return $this->gitService;
        }

        $config = $this->configService->getDefaultConfig();

        if (empty($config['Git']['executable'])) {
            throw new InvalidSystemConfigException(
                'Unable to find Git Executable in config'
            );
        }

        $this->gitService = new Git($config['Git']['executable']);

        return $this->gitService;
    }
}
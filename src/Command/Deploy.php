<?php

namespace Reliv\Deploy\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Deploy extends CommandAbstract
{
    protected function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy an app');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getCommandLogger();
        $logger->info('starting command');

        $apps = $this->configService->getAppsConfig();

        $logger->debug('Apps Config: '.print_r($apps->toArray(), true));

        foreach ($apps as $appName => $appConfig) {
            $logger->debug('Calling the Application service for '.$appName);
            $application = $this->getApplicationHelper($appName, $appConfig);
            $application->deploy();
            $logger->debug('Control returned from application service of '.$appName);
        }
    }
}
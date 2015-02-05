<?php
/**
 * Deploy Command
 *
 * Deploy Command
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

namespace Reliv\Deploy\Command;

use Reliv\Deploy\Helper\ApplicationServiceConfigHelper;
use Reliv\Deploy\Helper\ApplicationServiceVcsHelper;
use Reliv\Deploy\Helper\FileHelper;
use Reliv\Deploy\Service\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Config\Config;

/**
 * Deploy Command
 *
 * Deploy a configured list of applications.
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class Deploy extends CommandAbstract
{
    /**
     * Symphony's console config method.  This is used by Symphony to set the command descriptors.
     *
     * @return void;
     */
    protected function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy an app')
            ->addArgument(
                'application',
                InputArgument::OPTIONAL,
                'Application to deploy'
            );
    }

    /**
     * Execute the command
     *
     * @param InputInterface  $input  Input supplied by Symphony Console
     * @param OutputInterface $output Output supplied by Symohony console
     *
     * @return void
     * @SuppressWarnings("unused")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getCommandLogger();
        $logger->info('starting command');

        $apps = $this->configService->getAppsConfig();

        $logger->debug('Apps Config: '.print_r($apps->toArray(), true));

        $appToDeploy = $name = $input->getArgument('application');

        if (strtolower($appToDeploy) == 'all') {
            $appToDeploy = null;
        }

        if ($appToDeploy) {
            if (empty($apps[$appToDeploy])) {
                $logger->error($appToDeploy.' not found');
                return;
            }

            $this->runAppDeploy($appToDeploy, $apps[$appToDeploy]);
            return;
        }

        foreach ($apps as $appName => $appConfig) {
            $this->runAppDeploy($appName, $appConfig);
        }
    }

    /**
     * Run the application helper deploy script for a single application
     *
     * @param string $appName   Application name or key
     * @param Config $appConfig Application config
     *
     * @return void
     */
    protected function runAppDeploy($appName, Config $appConfig)
    {
        $logger = $this->getCommandLogger();
        $logger->debug('Calling the Application service for '.$appName);
        $application = $this->getApplicationHelper($appName, $appConfig);
        $application->deploy();
        $logger->debug('Control returned from application service of '.$appName);
    }

    /**
     * Get the Application Helper
     *
     * @param string $name   Application name
     * @param Config $config Config Object
     *
     * @return Application
     */
    protected function getApplicationHelper($name, Config $config)
    {
        $appConfigHelper = new ApplicationServiceConfigHelper($config);
        $fileHelper = new FileHelper();
        $vcsHelper = new ApplicationServiceVcsHelper($appConfigHelper, $this->getLoggerService());

        $application = new Application(
            $name,
            $appConfigHelper,
            $this->getLoggerService(),
            $fileHelper,
            $vcsHelper
        );

        return $application;
    }
}

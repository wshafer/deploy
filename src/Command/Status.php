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

use Reliv\Deploy\Service\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
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
class Status extends CommandAbstract
{
    /**
     * Symphony's console config method.  This is used by Symphony to set the command descriptors.
     *
     * @return void;
     */
    protected function configure()
    {
        $this->setName('status')
            ->setDescription('Get Deployment Status');
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

        foreach ($apps as $appName => $appConfig) {
            $logger->info('Status of RWriter');
            $logger->debug('Calling the Application service for '.$appName);
            $application = $this->getApplicationHelper($appName, $appConfig);
            $statusMessage = $application->getStatusMessage();
            $logger->debug('Control returned from application service of '.$appName);

            $statusMessage[] = "";

            $output->write($statusMessage, true);
        }
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
        $application = new Application(
            $name,
            $config,
            $this->getLoggerService()
        );

        return $application;
    }
}

<?php
/**
 * Auto Command
 *
 * Auto Command
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

use Reliv\Deploy\Exception\InvalidApplicationConfigException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Config\Config;

/**
 * Auto Command
 *
 * Auto Command.  Used to run the deploy service as a long running daemon
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class Auto extends CommandAbstract
{
    /**
     * Symphony's console config method.  This is used by Symphony to set the command descriptors.
     *
     * @return void;
     */
    protected function configure()
    {
        $this->setName('auto')
            ->setDescription('Run deploy as long running daemon');
    }

    /**
     * Execute the command
     *
     * @param InputInterface  $input  Input supplied by Symfony Console
     * @param OutputInterface $output Output supplied by Symfony console
     *
     * @return void
     * @SuppressWarnings("unused")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $daemon = $this->getConfigService()
            ->getMainConfig()
            ->get('auto', new Config(array()))
            ->get('daemon', null);

        if (!$daemon) {
            throw new InvalidApplicationConfigException(
                "No Daemon class supplied to run, nothing for auto to do"
            );
        }

        if (!class_exists($daemon)
            || in_array('DaemonInterface', class_implements('Reliv\\Deploy\\Daemon\\DaemonInterface'))
        ) {
            throw new \RuntimeException(
                "Invalid Daemon class :".$daemon
            );
        }

        /**
         * @var \Reliv\Deploy\Daemon\DaemonInterface $daemonService
         */
        $daemonService = new $daemon($this);

        $daemonService->run();
    }
}

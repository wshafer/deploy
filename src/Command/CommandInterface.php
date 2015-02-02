<?php
/**
 * Command Interface
 *
 * Command Interface for all deploy commands
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

use Reliv\Deploy\Service\LoggerService;
use Reliv\Deploy\Service\ConfigService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Command Interface
 *
 * Command Interface for all deploy commands
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
interface CommandInterface
{
    /**
     * Constructor for commands
     *
     * @param ConfigService   $configService   Config Service
     * @param LoggerService   $loggerService   Logger Service
     * @param EventDispatcher $eventDispatcher Event Dispatcher
     * @param string          $name            Name of command
     */
    public function __construct(
        ConfigService   $configService,
        LoggerService   $loggerService,
        EventDispatcher $eventDispatcher,
        $name = null
    );

    /**
     * Get the logger for the command
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getCommandLogger();

    /**
     * Get the config service
     *
     * @return ConfigService
     */
    public function getConfigService();

    /**
     * Get the logger service
     *
     * @return LoggerService
     */
    public function getLoggerService();

    /**
     * Get the console event dispatcher
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher();

    /**
     * Run an additional command
     *
     * @param InputInterface  $input  Input args
     * @param OutputInterface $output Output Handler
     *
     * @return void
     */
    public function runAdditionalCommand(InputInterface $input, OutputInterface $output);
}

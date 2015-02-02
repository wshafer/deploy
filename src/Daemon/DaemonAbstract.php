<?php

namespace Reliv\Deploy\Daemon;

use Reliv\Deploy\Command\Auto;
use Reliv\Deploy\Service\LoggerService;
use Reliv\Deploy\Service\ConfigService;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class DaemonAbstract implements DaemonInterface
{
    /**
     * @var Auto
     */
    protected $command;

    /**
     * Constructor for daemons
     *
     * @param Auto $command Reliv Auto Command
     */
    public function __construct(Auto $command)
    {
        $this->command = $command;
    }

    /**
     * Classes must impliment the run method to satisfy the interface
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * Get the Running Command
     *
     * @return Auto
     */
    protected function getCommand()
    {
        return $this->command;
    }

    /**
     * Get the config service
     *
     * @return ConfigService
     */
    protected function getConfigService()
    {
        return $this->command->getConfigService();
    }

    /**
     * Get the logger factory
     *
     * @return LoggerService
     */
    protected function getLoggerService()
    {
        return $this->command->getLoggerService();
    }

    /**
     * Get the Event Dispatcher
     *
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->command->getEventDispatcher();
    }
}

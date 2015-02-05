<?php

namespace Reliv\Deploy\Xmpp\Event;

use Reliv\Deploy\Command\CommandInterface;
use Reliv\Deploy\Service\LoggerService;
use Reliv\Deploy\Service\ConfigService;
use Reliv\Deploy\Xmpp\Helper\CronHelper;
use Symfony\Component\EventDispatcher\Event;

abstract class XmppEventAbstract extends Event implements XmppEventInterface
{
    /**
     * @var \JAXL
     */
    protected $client;

    /**
     * @var CronHelper
     */
    protected $cronHelper;

    /**
     * @var CommandInterface
     */
    protected $command;

    /**
     * Event Constructor
     *
     * @param \JAXL            $client     Jaxl Client
     * @param CommandInterface $command    Symfony Console Command object
     * @param CronHelper       $cronHelper Cron Helper
     */
    public function __construct(\JAXL $client, CommandInterface $command, CronHelper $cronHelper)
    {
        $this->client        = $client;
        $this->command       = $command;
        $this->cronHelper    = $cronHelper;
    }

    /**
     * Get the JAXL Client
     *
     * @return \JAXL
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get the running command
     *
     * @return CommandInterface
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Get the Reliv Deploy Config Service
     *
     * @return ConfigService
     */
    public function getConfigService()
    {
        return $this->getCommand()->getConfigService();
    }

    /**
     * Get the Reliv Logger Factory
     *
     * @return LoggerService
     */
    public function getLoggerService()
    {
        return $this->getCommand()->getLoggerService();
    }

    /**
     * Get the current Cron helper
     *
     * @return CronHelper
     */
    public function getCronHelper()
    {
        return $this->cronHelper;
    }
}

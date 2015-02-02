<?php

namespace Reliv\Deploy\Xmpp\Event;

use Reliv\Deploy\Command\CommandInterface;

interface XmppEventInterface
{
    /**
     * Get the JAXL Client
     *
     * @return \JAXL
     */
    public function getClient();

    /**
     * Get the running command
     *
     * @return CommandInterface
     */
    public function getCommand();

    /**
     * Get the Reliv Deploy Config Service
     *
     * @return \Reliv\Deploy\Service\ConfigService
     */
    public function getConfigService();

    /**
     * Get the Reliv Logger Factory
     *
     * @return \Reliv\Deploy\Service\LoggerService
     */
    public function getLoggerService();
}

<?php

namespace Reliv\Deploy\Xmpp\Event;

use Reliv\Deploy\Command\CommandInterface;
use Reliv\Deploy\Xmpp\Helper\CronHelper;

class ConnectEvent extends XmppEventAbstract
{
    protected $connected     = false;
    protected $errorNumber   = 0;
    protected $errorMessage  = '';

    /**
     * Event Constructor
     *
     * @param \JAXL            $client       Jaxl Client
     * @param CommandInterface $command      Running Command
     * @param CronHelper       $cronHelper   Cron Helper
     * @param boolean          $connected    Was the connection successful
     * @param integer          $errorNumber  Connection Error Number
     * @param string           $errorMessage Connection Error Message
     */
    public function __construct(
        \JAXL $client,
        CommandInterface  $command,
        CronHelper $cronHelper,
        $connected,
        $errorNumber = null,
        $errorMessage = ''
    ) {
        $this->connected = $connected;
        $this->errorNumber = $errorNumber;
        $this->errorMessage = $errorMessage;

        parent::__construct($client, $command, $cronHelper);
    }

    /**
     * Is the client connected
     *
     * @return mixed
     */
    public function isConnected()
    {
        return $this->connected;
    }
    /**
     * Get the connect error number
     *
     * @return string
     */
    public function getErrorNumber()
    {
        return $this->errorNumber;
    }

    /**
     * Get the connect error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}

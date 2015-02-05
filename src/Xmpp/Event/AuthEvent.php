<?php

namespace Reliv\Deploy\Xmpp\Event;

use Reliv\Deploy\Command\CommandInterface;
use Reliv\Deploy\Xmpp\Helper\CronHelper;

class AuthEvent extends XmppEventAbstract
{
    protected $authenticated = false;
    protected $errorMessage  = '';

    /**
     * Event Constructor
     *
     * @param \JAXL            $client        Jaxl Client
     * @param CommandInterface $command       Running Command
     * @param CronHelper       $cronHelper    Cron Helper
     * @param boolean          $authenticated Authentication result
     * @param string           $errorMessage  Authentication Error Message
     */
    public function __construct(
        \JAXL $client,
        CommandInterface  $command,
        CronHelper $cronHelper,
        $authenticated,
        $errorMessage = ''
    ) {
        $this->authenticated = $authenticated;
        $this->errorMessage = $errorMessage;

        parent::__construct($client, $command, $cronHelper);
    }

    /**
     * Is the user Authenticated
     *
     * @return mixed
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
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

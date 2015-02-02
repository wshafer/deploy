<?php

namespace Reliv\Deploy\Daemon;

use Reliv\Deploy\Command\Auto;

interface DaemonInterface
{
    /**
     * Constructor for daemons
     *
     * @param Auto $command Reliv Auto Command
     */
    public function __construct(Auto $command);

    /**
     * Start the Daemon
     *
     * @return void
     */
    public function run();
}

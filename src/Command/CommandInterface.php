<?php

namespace Reliv\Deploy\Command;

use Reliv\Deploy\Factory\LoggerFactory;
use Reliv\Deploy\Service\ConfigService;

interface CommandInterface
{
    public function __construct(ConfigService $configService, LoggerFactory $loggerService, $name = null);
}